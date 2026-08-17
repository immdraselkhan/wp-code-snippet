<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Labels {

	public static function types() {
		return array(
			'php'  => array(
				'label' => 'PHP',
				'class' => 'wcs-badge-info',
			),
			'html' => array(
				'label' => 'HTML',
				'class' => 'wcs-badge-warning',
			),
			'css'  => array(
				'label' => 'CSS',
				'class' => 'wcs-badge-outline',
			),
			'js'   => array(
				'label' => 'JS',
				'class' => 'wcs-badge-neutral',
			),
		);
	}

	public static function locations() {
		return array(
			'run_everywhere' => __( 'Everywhere (PHP)', 'wp-code-snippet' ),
			'frontend_only'  => __( 'Frontend only', 'wp-code-snippet' ),
			'admin_only'     => __( 'Admin only', 'wp-code-snippet' ),
			'wp_head'        => __( 'Header', 'wp-code-snippet' ),
			'wp_footer'      => __( 'Footer', 'wp-code-snippet' ),
			'admin_head'     => __( 'Admin header', 'wp-code-snippet' ),
			'admin_footer'   => __( 'Admin footer', 'wp-code-snippet' ),
			'login_head'     => __( 'Login page', 'wp-code-snippet' ),
			'shortcode'      => __( 'Shortcode', 'wp-code-snippet' ),
		);
	}
}
