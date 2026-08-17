<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Engine {

	 
	protected $running_id = null;

	 
	protected $probe_token = '';

	 
	protected $probe_started = false;

	 
	protected $probe_bootstrap_complete = false;

	public function init() {
		 
		 
		 
		 
		if ( isset( $_GET['wcs_probe_token'] ) ) {  
			$this->probe_token = sanitize_text_field( wp_unslash( $_GET['wcs_probe_token'] ) );  
			if ( $this->probe_token ) {
				register_shutdown_function( array( $this, 'handle_probe_shutdown' ) );
			}
		}

		 
		if ( $this->safe_mode_enabled() ) {
			register_shutdown_function( array( $this, 'handle_shutdown' ) );
		}

		 
		 
		 
		$this->run_probe_candidate();
		$this->run_php_everywhere();

		 
		add_action( 'wp_loaded', array( $this, 'mark_probe_bootstrap_complete' ), PHP_INT_MAX );
		add_action( 'wp', array( $this, 'run_php_late_frontend' ), 1 );
		add_action( 'current_screen', array( $this, 'run_php_late_admin' ), 1 );
		add_action( 'wp_head', array( $this, 'run_header' ) );
		add_action( 'wp_footer', array( $this, 'run_footer' ) );
		add_action( 'admin_head', array( $this, 'run_admin_header' ) );
		add_action( 'admin_footer', array( $this, 'run_admin_footer' ) );
		add_action( 'login_head', array( $this, 'run_login_header' ) );

		 
		add_action( 'init', array( $this, 'register_shortcodes' ), 5 );

	}


	 
	protected function is_wcs_ajax_request() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';  
		return 0 === strpos( $action, 'wcs_' );
	}

	 
	public function run_probe_candidate() {
		if ( ! $this->probe_token ) {
			return;
		}

		$payload = get_transient( 'wcs_probe_' . $this->probe_token );
		if ( ! is_array( $payload ) || empty( $payload['snippet'] ) ) {
			return;
		}

		$context = $payload['context'] ?? 'frontend';
		if ( 'admin' === $context && ! is_admin() ) {
			return;
		}
		if ( 'frontend' === $context && is_admin() ) {
			return;
		}

		$snippet = $payload['snippet'];
		if ( 'php' !== ( $snippet['type'] ?? '' ) ) {
			return;
		}

		$this->probe_started = true;

		try {
			ob_start();
			$code = WCS_Validator::normalize_php_code( $snippet['code'] ?? '' );
			eval( $code );  
			ob_end_clean();
		} catch ( Throwable $e ) {
			if ( ob_get_level() ) {
				ob_end_clean();
			}
			set_transient(
				'wcs_probe_result_' . $this->probe_token,
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				60
			);
		}
	}

	public function mark_probe_bootstrap_complete() {
		if ( $this->probe_started ) {
			$this->probe_bootstrap_complete = true;
		}
	}

	public function handle_probe_shutdown() {
		if ( ! $this->probe_token || ! $this->probe_started ) {
			return;
		}

		$existing = get_transient( 'wcs_probe_result_' . $this->probe_token );
		if ( is_array( $existing ) && isset( $existing['success'] ) && false === $existing['success'] ) {
			return;
		}

		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			set_transient(
				'wcs_probe_result_' . $this->probe_token,
				array(
					'success' => false,
					'message' => $error['message'] . ' on line ' . $error['line'],
				),
				60
			);
			return;
		}

		if ( ! $this->probe_bootstrap_complete ) {
			set_transient(
				'wcs_probe_result_' . $this->probe_token,
				array(
					'success' => false,
					'message' => __( 'The snippet stopped WordPress before bootstrap completed.', 'wp-code-snippet' ),
				),
				60
			);
			return;
		}

		set_transient(
			'wcs_probe_result_' . $this->probe_token,
			array( 'success' => true ),
			60
		);
	}

	protected function safe_mode_enabled() {
		$settings = get_option( 'wcs_settings', array() );
		return empty( $settings['safe_mode'] ) ? false : (bool) $settings['safe_mode'];
	}

	protected function frontend_css_enabled() {
		$settings = get_option( 'wcs_settings', array() );
		return ! isset( $settings['load_frontend_css'] ) || (bool) $settings['load_frontend_css'];
	}

	 
	public function run_php_everywhere() {
		 
		 
		 
		 
		if ( $this->is_wcs_ajax_request() ) {
			return;
		}

		foreach ( WCS_DB::get_active() as $snippet ) {
			if ( 'php' !== $snippet['type'] ) {
				continue;
			}
			if ( ! in_array( $snippet['location'], array( 'run_everywhere', 'frontend_only', 'admin_only' ), true ) ) {
				continue;
			}
			if ( 'frontend_only' === $snippet['location'] && is_admin() ) {
				continue;
			}
			if ( 'admin_only' === $snippet['location'] && ! is_admin() ) {
				continue;
			}
			if ( $this->conditions_need_late_context( $snippet['conditions'] ) ) {
				continue;
			}
			if ( ! WCS_Conditions::matches( $snippet['conditions'] ) ) {
				continue;
			}
			$this->execute_php( $snippet );
		}
	}


	protected function conditions_need_late_context( $conditions ) {
		if ( empty( $conditions ) || ! is_array( $conditions ) || empty( $conditions['rules'] ) || ! is_array( $conditions['rules'] ) ) {
			return false;
		}
		$late_fields = array( 'page_type', 'post_type', 'post_id', 'post_status', 'post_author', 'taxonomy_term', 'template', 'woocommerce', 'woo_product_type', 'woo_category', 'woo_cart_product', 'woo_cart_category', 'login_state', 'user_role', 'user_id', 'capability', 'locale' );
		foreach ( $conditions['rules'] as $rule ) {
			if ( is_array( $rule ) && in_array( sanitize_key( $rule['field'] ?? '' ), $late_fields, true ) ) {
				return true;
			}
		}
		return false;
	}

	protected function run_php_late( $admin ) {
		if ( $this->is_wcs_ajax_request() ) {
			return;
		}
		foreach ( WCS_DB::get_active() as $snippet ) {
			if ( 'php' !== $snippet['type'] || ! in_array( $snippet['location'], array( 'run_everywhere', 'frontend_only', 'admin_only' ), true ) ) {
				continue;
			}
			if ( ! $this->conditions_need_late_context( $snippet['conditions'] ) ) {
				continue;
			}
			if ( $admin && 'frontend_only' === $snippet['location'] ) {
				continue;
			}
			if ( ! $admin && 'admin_only' === $snippet['location'] ) {
				continue;
			}
			if ( ! WCS_Conditions::matches( $snippet['conditions'] ) ) {
				continue;
			}
			$this->execute_php( $snippet );
		}
	}

	public function run_php_late_frontend() {
		$this->run_php_late( false );
	}

	public function run_php_late_admin() {
		$this->run_php_late( true );
	}

	public function run_header() {
		$this->run_located( 'wp_head', array( 'css', 'js', 'html' ) );
	}

	public function run_footer() {
		$this->run_located( 'wp_footer', array( 'css', 'js', 'html' ) );
	}

	public function run_admin_header() {
		$this->run_located( 'admin_head', array( 'css', 'js', 'html' ) );
	}

	public function run_admin_footer() {
		$this->run_located( 'admin_footer', array( 'css', 'js', 'html' ) );
	}

	public function run_login_header() {
		$this->run_located( 'login_head', array( 'css', 'js', 'html' ) );
	}

	protected function run_located( $location, $types ) {
		if ( ! is_admin() && in_array( $location, array( 'wp_head', 'wp_footer' ), true ) && ! $this->frontend_css_enabled() ) {
			return;
		}

		foreach ( WCS_DB::get_active() as $snippet ) {
			if ( $snippet['location'] !== $location || ! in_array( $snippet['type'], $types, true ) ) {
				continue;
			}
			if ( ! WCS_Conditions::matches( $snippet['conditions'] ) ) {
				continue;
			}
			$this->output_snippet( $snippet );
		}
	}

	 
	public function register_shortcodes() {
		foreach ( WCS_DB::get_active() as $snippet ) {
			if ( 'shortcode' !== $snippet['location'] || empty( $snippet['shortcode_tag'] ) ) {
				continue;
			}
			add_shortcode(
				$snippet['shortcode_tag'],
				function () use ( $snippet ) {
					ob_start();
					$this->output_snippet( $snippet, false );
					return ob_get_clean();
				}
			);
		}
	}

	 
	protected function output_snippet( $snippet, $echo = true ) {
		if ( ! WCS_Conditions::matches( $snippet['conditions'] ) ) {
			return;
		}

		$this->running_id = $snippet['id'];
		$code             = $snippet['code'];

		switch ( $snippet['type'] ) {
			case 'css':
				$out = "\n<style id=\"wcs-snippet-{$snippet['id']}\">\n{$code}\n</style>\n";
				break;
			case 'js':
				$out = "\n<script id=\"wcs-snippet-{$snippet['id']}\">\n{$code}\n</script>\n";
				break;
			default:  
				$out = "\n{$code}\n";
		}

		if ( $echo ) {
			echo $out;  
		}

		WCS_DB::increment_run_count( $snippet['id'] );
		$this->running_id = null;

		if ( ! $echo ) {
			echo $out;  
		}
	}

	 
	protected function execute_php( $snippet ) {
		$this->running_id = $snippet['id'];

		 
		 
		update_option( 'wcs_currently_running', $snippet['id'], false );

		try {
			ob_start();
			 
			$code = WCS_Validator::normalize_php_code( $snippet['code'] );
			eval( $code );  
			ob_end_flush();

			WCS_DB::increment_run_count( $snippet['id'] );
		} catch ( Throwable $e ) {
			ob_end_clean();
			$this->deactivate_with_error( $snippet['id'], $e->getMessage() );
		}

		delete_option( 'wcs_currently_running' );
		$this->running_id = null;
	}

	 
	public function handle_shutdown() {
		$error = error_get_last();
		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			return;
		}

		$running_id = get_option( 'wcs_currently_running' );
		if ( ! $running_id ) {
			return;
		}

		delete_option( 'wcs_currently_running' );
		$this->deactivate_with_error( $running_id, $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'] );
	}

	protected function deactivate_with_error( $id, $message ) {
		WCS_DB::update_status( $id, 'auto-deactivated', $message );
	}
}
