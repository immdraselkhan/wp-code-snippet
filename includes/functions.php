<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

 
function wcs_get_param( $key, $default = '' ) {
	if ( isset( $GLOBALS['wcs_view_params'] ) && array_key_exists( $key, $GLOBALS['wcs_view_params'] ) ) {
		return $GLOBALS['wcs_view_params'][ $key ];
	}
	if ( isset( $_REQUEST[ $key ] ) ) {  
		return wp_unslash( $_REQUEST[ $key ] );  
	}
	return $default;
}
