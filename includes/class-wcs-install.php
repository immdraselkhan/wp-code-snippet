<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Install {

	 
	public static function install() {
		global $wpdb;

		$table_name      = $wpdb->prefix . WCS_TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL DEFAULT '',
			description TEXT NULL,
			code LONGTEXT NULL,
			type VARCHAR(20) NOT NULL DEFAULT 'php',
			location VARCHAR(50) NOT NULL DEFAULT 'run_everywhere',
			conditions LONGTEXT NULL,
			priority SMALLINT NOT NULL DEFAULT 10,
			status VARCHAR(20) NOT NULL DEFAULT 'inactive',
			shortcode_tag VARCHAR(100) NULL,
			error_message TEXT NULL,
			run_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
			last_run_at DATETIME NULL,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:01',
			updated_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:01',
			PRIMARY KEY  (id),
			KEY status (status),
			KEY type (type),
			KEY location (location)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( 'wcs_db_version', WCS_DB_VERSION );

		if ( false === get_option( 'wcs_settings' ) ) {
			add_option(
				'wcs_settings',
				array(
					'safe_mode'          => 1,  
					'disable_editor_php' => 0,
					'load_frontend_css'  => 1,
				)
			);
		}
	}
}
