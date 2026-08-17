<?php
 

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$settings = get_option( 'wcs_settings', array() );

 
 
$keep_data = ! empty( $settings['keep_data_on_uninstall'] );

if ( ! $keep_data ) {
	$table = $wpdb->prefix . 'wcs_snippets';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );  

	delete_option( 'wcs_settings' );
	delete_option( 'wcs_db_version' );
	delete_option( 'wcs_currently_running' );

	 
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_wcs\_admin\_notice\_%' OR option_name LIKE '\_transient\_timeout\_wcs\_admin\_notice\_%'" );  
}
