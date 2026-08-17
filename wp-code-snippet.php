<?php
/**
 * Plugin Name:       WP Code Snippet
 * Plugin URI:        https://github.com/immdraselkhan/wp-code-snippet
 * Description:       Add and manage PHP, HTML, CSS and JS snippets with conditional logic - a safe, fast alternative to editing functions.php directly.
 * Version:           1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Md Rasel Khan
 * Author URI:        https://raselkhan.dev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-code-snippet
 * Domain Path:       /languages
 *
 * @package WP_Code_Snippet
 */

 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

 
if ( defined( 'WCS_PLUGIN_FILE' ) ) {
	return;
}

 
define( 'WCS_VERSION', '1.0' );
define( 'WCS_DB_VERSION', '1.0.0' );
define( 'WCS_PLUGIN_FILE', __FILE__ );
define( 'WCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WCS_TABLE_NAME', 'wcs_snippets' );

 
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	$wcs_early_action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';  
	if ( 0 === strpos( $wcs_early_action, 'wcs_' ) ) {
		ob_start();
		define( 'WCS_AJAX_BUFFER_LEVEL', ob_get_level() );
	}
}

require_once WCS_PLUGIN_DIR . 'includes/functions.php';

 
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'WCS_' ) !== 0 ) {
			return;
		}
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$path      = WCS_PLUGIN_DIR . 'includes/' . $file_name;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

 
function wcs_activate_plugin() {
	require_once WCS_PLUGIN_DIR . 'includes/class-wcs-install.php';
	WCS_Install::install();
}
register_activation_hook( __FILE__, 'wcs_activate_plugin' );

 
function wcs_deactivate_plugin() {
	wp_cache_flush();
}
register_deactivation_hook( __FILE__, 'wcs_deactivate_plugin' );

 
function wcs_run_plugin() {
	 
	if ( get_option( 'wcs_db_version' ) !== WCS_DB_VERSION ) {
		require_once WCS_PLUGIN_DIR . 'includes/class-wcs-install.php';
		WCS_Install::install();
	}

	$loader = new WCS_Loader();
	$loader->run();
}
add_action( 'plugins_loaded', 'wcs_run_plugin' );
