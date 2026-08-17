<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Loader {

	 
	public function run() {
		load_plugin_textdomain( 'wp-code-snippet', false, dirname( WCS_PLUGIN_BASENAME ) . '/languages' );

		 
		new WCS_DB();

		if ( is_admin() ) {
			$admin = new WCS_Admin();
			$admin->init();

			$ajax = new WCS_Ajax();
			$ajax->init();
		}

		 
		 
		$engine = new WCS_Engine();
		$engine->init();
	}
}
