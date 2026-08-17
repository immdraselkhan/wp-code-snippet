<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Ajax {

	public function init() {
		add_action( 'wp_ajax_wcs_toggle_status', array( $this, 'toggle_status' ) );
		add_action( 'wp_ajax_wcs_delete_snippet', array( $this, 'delete_snippet' ) );
		add_action( 'wp_ajax_wcs_bulk_action', array( $this, 'bulk_action' ) );
		add_action( 'wp_ajax_wcs_validate_php', array( $this, 'validate_php' ) );
		add_action( 'wp_ajax_wcs_render_view', array( $this, 'render_view' ) );
		add_action( 'wp_ajax_wcs_save_snippet', array( $this, 'save_snippet' ) );
		add_action( 'wp_ajax_wcs_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_wcs_get_data', array( $this, 'get_data' ) );
		add_action( 'wp_ajax_wcs_probe_ping', array( $this, 'probe_ping' ) );
		add_action( 'wp_ajax_nopriv_wcs_probe_ping', array( $this, 'probe_ping' ) );
	}



	 
	public function probe_ping() {
		wp_send_json_success( array( 'ok' => true ) );
	}


	 
	public function get_data() {
		$this->check_permissions();

		$view = isset( $_POST['view'] ) ? sanitize_key( $_POST['view'] ) : 'list';
		if ( 'list' === $view ) {
			$status   = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
			$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
			$paged    = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
			$per_page = 20;
			$total    = WCS_DB::count_filtered( array( 'status' => $status, 'search' => $search ) );
			$pages    = max( 1, (int) ceil( $total / $per_page ) );
			$paged    = min( $paged, $pages );
			$items    = WCS_DB::get_all(
				array(
					'status' => $status,
					'search' => $search,
					'number' => $per_page,
					'offset' => ( $paged - 1 ) * $per_page,
				)
			);
			$this->send_success(
				array(
					'view'       => 'list',
					'items'      => $items,
					'paged'      => $paged,
					'totalPages' => $pages,
					'total'      => WCS_DB::count(),
					'active'     => WCS_DB::count( 'active' ),
					'inactive'   => WCS_DB::count( 'inactive' ),
					'errors'     => WCS_DB::count( 'auto-deactivated' ),
				)
			);
		}

		if ( in_array( $view, array( 'add', 'edit' ), true ) ) {
			$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
			$snippet = $id ? WCS_DB::get( $id ) : null;
			if ( $id && ! $snippet ) {
				$this->send_error( array( 'message' => __( 'Snippet not found.', 'wp-code-snippet' ) ), 404 );
			}
			$defaults = array(
				'id'            => 0,
				'title'         => '',
				'description'   => '',
				'code'          => "// Your code here.\n",
				'type'          => 'php',
				'location'      => 'run_everywhere',
				'conditions'    => array( 'version' => 2, 'scope' => 'include', 'logic' => 'and', 'rules' => array() ),
				'priority'      => 10,
				'status'        => 'inactive',
				'shortcode_tag' => '',
				'error_message' => '',
			);
			$this->send_success(
				array(
					'view'    => $id ? 'edit' : 'add',
					'snippet' => wp_parse_args( $snippet ? $snippet : array(), $defaults ),
				)
			);
		}

		if ( 'settings' === $view ) {
			$settings = wp_parse_args(
				get_option( 'wcs_settings', array() ),
				array(
					'safe_mode'          => 1,
					'disable_editor_php' => 0,
					'load_frontend_css'  => 1,
				)
			);
			$this->send_success( array( 'view' => 'settings', 'settings' => $settings ) );
		}

		$this->send_error( array( 'message' => __( 'Unknown view.', 'wp-code-snippet' ) ), 400 );
	}

	 
	public function render_view() {
		$this->check_permissions();

		$view   = isset( $_POST['view'] ) ? sanitize_key( $_POST['view'] ) : 'list';
		$params = array();

		if ( 'list' === $view ) {
			$params['status'] = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
			$params['search'] = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
			$params['paged']  = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
		} elseif ( in_array( $view, array( 'add', 'edit' ), true ) ) {
			$params['id'] = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
			if ( $params['id'] ) {
				$view = 'edit';
			}
		}

		$this->send_success(
			array(
				'html' => WCS_Admin::render_view( $view, $params ),
				'view' => $view,
			)
		);
	}

	 
	public function save_snippet() {
		$this->check_permissions();

		try {
			$result = WCS_Admin::save_snippet_from_array( $_POST );  

			if ( is_wp_error( $result ) ) {
				$this->send_error( array( 'message' => $result->get_error_message() ), 500 );
			}

			if ( empty( $result['id'] ) || is_wp_error( $result['id'] ) ) {
				$message = is_wp_error( $result['id'] )
					? $result['id']->get_error_message()
					: __( 'The snippet could not be saved.', 'wp-code-snippet' );
				$this->send_error( array( 'message' => $message ), 500 );
			}

			 
			 
			 
			$this->send_success(
				array(
					'id'         => (int) $result['id'],
					'status'     => $result['status'],
					'noticeType' => $result['notice_type'],
					'noticeText' => $result['notice_text'],
				)
			);
		} catch ( Throwable $e ) {
			$this->send_error(
				array(
					'message' => defined( 'WP_DEBUG' ) && WP_DEBUG
						? sprintf( __( 'Save failed: %s', 'wp-code-snippet' ), $e->getMessage() )
						: __( 'The snippet could not be saved. Please try again.', 'wp-code-snippet' ),
				),
				500
			);
		}
	}

	 
	public function save_settings() {
		$this->check_permissions();

		try {
			update_option( 'wcs_settings', WCS_Admin::sanitize_settings( $_POST ) );  
			$this->send_success(
				array(
					'noticeType' => 'success',
					'noticeText' => __( 'Settings saved.', 'wp-code-snippet' ),
				)
			);
		} catch ( Throwable $e ) {
			$this->send_error( array( 'message' => __( 'Settings could not be saved.', 'wp-code-snippet' ) ), 500 );
		}
	}

	 
	protected function clean_ajax_output() {
		if ( defined( 'WCS_AJAX_BUFFER_LEVEL' ) ) {
			while ( ob_get_level() >= WCS_AJAX_BUFFER_LEVEL ) {
				ob_end_clean();
			}
		}
	}

	protected function send_success( $data = null, $status_code = null ) {
		$this->clean_ajax_output();
		wp_send_json_success( $data, $status_code );
	}

	protected function send_error( $data = null, $status_code = null ) {
		$this->clean_ajax_output();
		wp_send_json_error( $data, $status_code );
	}

	protected function check_permissions() {
		if ( ! current_user_can( WCS_Admin::CAP ) ) {
			$this->send_error( array( 'message' => __( 'Permission denied.', 'wp-code-snippet' ) ), 403 );
		}
		if ( false === check_ajax_referer( 'wcs_admin_nonce', 'nonce', false ) ) {
			$this->send_error( array( 'message' => __( 'Your session expired. Refresh the page and try again.', 'wp-code-snippet' ) ), 403 );
		}
	}

	public function toggle_status() {
		$this->check_permissions();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id ) {
			$this->send_error( array( 'message' => __( 'Invalid snippet.', 'wp-code-snippet' ) ) );
		}

		$snippet = WCS_DB::get( $id );
		if ( ! $snippet ) {
			$this->send_error( array( 'message' => __( 'Snippet not found.', 'wp-code-snippet' ) ) );
		}

		$new_status = 'active' === $snippet['status'] ? 'inactive' : 'active';

		if ( 'active' === $new_status && 'php' === $snippet['type'] ) {
			$check = WCS_Safety::verify_php_snippet( $snippet );
			if ( is_wp_error( $check ) ) {
				WCS_DB::update_status( $id, 'inactive', $check->get_error_message() );
				$this->send_error( array( 'message' => sprintf(
					 
					__( 'Cannot activate — safety check failed: %s', 'wp-code-snippet' ),
					$check->get_error_message()
				) ) );
			}
			WCS_DB::update_status( $id, 'active', '' );
		} else {
			WCS_DB::update_status( $id, $new_status, '' );
		}

		$this->send_success( array( 'status' => $new_status ) );
	}

	public function delete_snippet() {
		$this->check_permissions();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id || ! WCS_DB::delete( $id ) ) {
			$this->send_error( array( 'message' => __( 'Could not delete snippet.', 'wp-code-snippet' ) ) );
		}

		$this->send_success();
	}

	public function bulk_action() {
		$this->check_permissions();

		$action = isset( $_POST['bulk'] ) ? sanitize_key( $_POST['bulk'] ) : '';
		$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();

		if ( empty( $ids ) || ! in_array( $action, array( 'activate', 'deactivate', 'delete' ), true ) ) {
			$this->send_error( array( 'message' => __( 'Invalid bulk request.', 'wp-code-snippet' ) ) );
		}

		$skipped = array();

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				WCS_DB::delete( $id );
				continue;
			}

			$status  = 'activate' === $action ? 'active' : 'inactive';
			$snippet = WCS_DB::get( $id );

			if ( $snippet && 'active' === $status && 'php' === $snippet['type'] ) {
				$check = WCS_Safety::verify_php_snippet( $snippet );
				if ( is_wp_error( $check ) ) {
					$skipped[] = $snippet['title'];
					WCS_DB::update_status( $id, 'inactive', $check->get_error_message() );
					continue;
				}
			}

			WCS_DB::update_status( $id, $status, '' );
		}

		$this->send_success( array( 'skipped' => $skipped ) );
	}

	 
	public function validate_php() {
		$this->check_permissions();

		$code   = isset( $_POST['code'] ) ? wp_unslash( $_POST['code'] ) : '';  
		$result = WCS_Validator::check_php_syntax( $code );

		if ( is_wp_error( $result ) ) {
			$this->send_success( array(
				'valid'   => false,
				'message' => $result->get_error_message(),
			) );
		}

		$this->send_success( array( 'valid' => true ) );
	}
}
