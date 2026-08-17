<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Admin {

	const CAP = 'manage_options';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submit' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_submit' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_filter( 'plugin_action_links_' . WCS_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	public function register_menu() {
		add_options_page(
			__( 'WP Code Snippet', 'wp-code-snippet' ),
			__( 'WP Code Snippet', 'wp-code-snippet' ),
			self::CAP,
			'wp-code-snippet',
			array( $this, 'render_list_page' )
		);

		add_submenu_page(
			'options-general.php',
			__( 'Add New Snippet', 'wp-code-snippet' ),
			__( 'Add New Snippet', 'wp-code-snippet' ),
			self::CAP,
			'wp-code-snippet-add',
			array( $this, 'render_edit_page' )
		);

		add_submenu_page(
			'options-general.php',
			__( 'Code Snippet Settings', 'wp-code-snippet' ),
			__( 'Code Snippet Settings', 'wp-code-snippet' ),
			self::CAP,
			'wp-code-snippet-settings',
			array( $this, 'render_settings_page' )
		);

		remove_submenu_page( 'options-general.php', 'wp-code-snippet-add' );
		remove_submenu_page( 'options-general.php', 'wp-code-snippet-settings' );
	}

	public function plugin_action_links( $links ) {
		$snippets = '<a href="' . esc_url( admin_url( 'options-general.php?page=wp-code-snippet' ) ) . '">' . esc_html__( 'Snippets', 'wp-code-snippet' ) . '</a>';
		$settings = '<a href="' . esc_url( admin_url( 'options-general.php?page=wp-code-snippet-settings' ) ) . '">' . esc_html__( 'Settings', 'wp-code-snippet' ) . '</a>';

		return array_merge( array( $snippets, $settings ), $links );
	}

	public function plugin_row_meta( $links, $file ) {
		if ( WCS_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$links[] = '<a href="' . esc_url( 'https://raselkhan.dev' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Visit Website', 'wp-code-snippet' ) . '</a>';
		return $links;
	}

	 
	public function enqueue_assets( $hook ) {
		if ( ! isset( $_GET['page'] ) || strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'wp-code-snippet' ) !== 0 ) {  
			return;
		}

		 
		 
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'wcs-admin', WCS_PLUGIN_URL . 'admin/css/wcs-admin.css', array( 'wp-components' ), WCS_VERSION );

		wp_enqueue_script(
			'wcs-admin',
			WCS_PLUGIN_URL . 'admin/js/wcs-admin.js',
			array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-dom-ready', 'wp-util' ),
			WCS_VERSION,
			true
		);

		$cm_settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
		wp_localize_script( 'wcs-admin', 'wcsCodeEditor', false !== $cm_settings ? $cm_settings : array() );
		wp_enqueue_script( 'htmlhint' );
		wp_enqueue_script( 'csslint' );
		wp_enqueue_script( 'jshint' );

		wp_localize_script(
			'wcs-admin',
			'wcsAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wcs_admin_nonce' ),
				'baseUrl'  => admin_url( 'options-general.php' ),
				'strings'  => array(
					'confirmDelete'  => __( 'Delete this snippet? This cannot be undone.', 'wp-code-snippet' ),
					'confirmBulkDel' => __( 'Delete the selected snippets? This cannot be undone.', 'wp-code-snippet' ),
					'saved'          => __( 'Snippet saved.', 'wp-code-snippet' ),
					'settingsSaved'  => __( 'Settings saved.', 'wp-code-snippet' ),
					'error'          => __( 'Something went wrong. Please try again.', 'wp-code-snippet' ),
				),
			)
		);
	}

	 
	public static function render_view( $view, $params = array() ) {
		ob_start();
		self::render_app_shell( $view, $params );
		return ob_get_clean();
	}

	 
	public static function render_app_shell( $view = 'list', $params = array() ) {
		$id = isset( $params['id'] ) ? absint( $params['id'] ) : 0;
		?>
		<div class="wrap wcs-block-wrap">
			<div id="wcs-block-app" data-view="<?php echo esc_attr( $view ); ?>" data-id="<?php echo esc_attr( $id ); ?>"></div>
		</div>
		<?php
	}

	public function render_list_page() {
		self::render_app_shell( 'list' );
	}

	public function render_edit_page() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;  
		self::render_app_shell( $id ? 'edit' : 'add', array( 'id' => $id ) );
	}

	public function render_settings_page() {
		self::render_app_shell( 'settings' );
	}

	 
	public static function save_snippet_from_array( $src ) {
		$id   = isset( $src['snippet_id'] ) ? absint( $src['snippet_id'] ) : 0;
		$data = array(
			'title'         => isset( $src['title'] ) ? sanitize_text_field( wp_unslash( $src['title'] ) ) : '',
			'description'   => isset( $src['description'] ) ? sanitize_textarea_field( wp_unslash( $src['description'] ) ) : '',
			'code'          => isset( $src['code'] ) ? wp_unslash( $src['code'] ) : '',
			'type'          => isset( $src['type'] ) ? sanitize_key( $src['type'] ) : 'php',
			'location'      => isset( $src['location'] ) ? sanitize_key( $src['location'] ) : 'run_everywhere',
			'conditions'    => isset( $src['conditions'] ) ? wp_unslash( $src['conditions'] ) : '{"rule":"everywhere"}',
			'priority'      => isset( $src['priority'] ) ? absint( $src['priority'] ) : 10,
			'shortcode_tag' => isset( $src['shortcode_tag'] ) ? sanitize_key( $src['shortcode_tag'] ) : '',
			'status'        => isset( $src['status'] ) && 'active' === $src['status'] ? 'active' : 'inactive',
		);

		if ( '' === $data['title'] ) {
			$data['title'] = __( 'Untitled snippet', 'wp-code-snippet' );
		}

		$notice_type = 'success';
		$notice_text = __( 'Snippet saved successfully.', 'wp-code-snippet' );

		if ( 'active' === $data['status'] && 'php' === $data['type'] ) {
			 
			 
			$check = WCS_Safety::verify_php_snippet( $data );
			if ( is_wp_error( $check ) ) {
				return new WP_Error(
					'wcs_activation_safety_failed',
					sprintf(
						 
						__( 'Snippet was not saved because the safety check failed: %s', 'wp-code-snippet' ),
						$check->get_error_message()
					)
				);
			}
			$data['error_message'] = '';
		} elseif ( 'active' === $data['status'] ) {
			$data['error_message'] = '';
		}

		if ( $id > 0 ) {
			if ( ! WCS_DB::get( $id ) ) {
				return new WP_Error( 'wcs_snippet_not_found', __( 'Snippet not found.', 'wp-code-snippet' ) );
			}
			if ( ! WCS_DB::update( $id, $data ) ) {
				return new WP_Error( 'wcs_db_update_failed', __( 'Could not update the snippet in the database.', 'wp-code-snippet' ) );
			}
		} else {
			$id = WCS_DB::insert( $data );
			if ( is_wp_error( $id ) ) {
				return $id;
			}
		}

		return array(
			'id'           => $id,
			'status'       => $data['status'],
			'notice_type'  => $notice_type,
			'notice_text'  => $notice_text,
		);
	}

	 
	public function handle_form_submit() {
		 
		 
		 
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['wcs_action'] ) || 'save_snippet' !== $_POST['wcs_action'] ) {
			return;
		}
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wp-code-snippet' ) );
		}
		check_admin_referer( 'wcs_save_snippet', 'wcs_nonce' );

		$result = self::save_snippet_from_array( $_POST );  

		set_transient(
			'wcs_admin_notice_' . get_current_user_id(),
			array(
				'type' => $result['notice_type'],
				'text' => $result['notice_text'],
			),
			45
		);

		wp_safe_redirect( admin_url( 'options-general.php?page=wp-code-snippet-add&id=' . $result['id'] ) );
		exit;
	}

	 
	public function handle_settings_submit() {
		 
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['wcs_settings_nonce'] ) ) {
			return;
		}
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wp-code-snippet' ) );
		}
		check_admin_referer( 'wcs_save_settings', 'wcs_settings_nonce' );

		update_option( 'wcs_settings', self::sanitize_settings( $_POST ) );  

		set_transient(
			'wcs_admin_notice_' . get_current_user_id(),
			array(
				'type' => 'success',
				'text' => __( 'Settings saved.', 'wp-code-snippet' ),
			),
			45
		);

		wp_safe_redirect( admin_url( 'options-general.php?page=wp-code-snippet-settings' ) );
		exit;
	}

	public static function sanitize_settings( $src ) {
		return array(
			'safe_mode'          => isset( $src['safe_mode'] ) ? 1 : 0,
			'disable_editor_php' => isset( $src['disable_editor_php'] ) ? 1 : 0,
			'load_frontend_css'  => isset( $src['load_frontend_css'] ) ? 1 : 0,
		);
	}

	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'wp-code-snippet' ) === false ) {
			return;
		}
		$notice = get_transient( 'wcs_admin_notice_' . get_current_user_id() );
		if ( ! $notice ) {
			return;
		}
		delete_transient( 'wcs_admin_notice_' . get_current_user_id() );
		$class = 'success' === $notice['type'] ? 'wcs-notice-success' : 'wcs-notice-error';
		echo '<div class="wcs-toast ' . esc_attr( $class ) . '"><span>' . esc_html( $notice['text'] ) . '</span></div>';
	}
}
