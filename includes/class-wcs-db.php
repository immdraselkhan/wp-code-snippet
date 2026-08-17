<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_DB {

	 
	protected static $table;

	public function __construct() {
		global $wpdb;
		self::$table = $wpdb->prefix . WCS_TABLE_NAME;
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . WCS_TABLE_NAME;
	}

	 
	public static function get( $id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );  
		return $row ? self::unpack( $row ) : null;
	}

	 
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table = self::table();

		$defaults = array(
			'status'  => '',
			'type'    => '',
			'search'  => '',
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'number'  => 0,
			'offset'  => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'type = %s';
			$params[] = $args['type'];
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(title LIKE %s OR description LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = array( 'id', 'title', 'type', 'status', 'created_at', 'updated_at', 'priority', 'run_count' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order}";

		if ( ! empty( $args['number'] ) ) {
			$sql     .= ' LIMIT %d OFFSET %d';
			$params[] = (int) $args['number'];
			$params[] = (int) $args['offset'];
		}

		$prepared = $params ? $wpdb->prepare( $sql, $params ) : $sql;  
		$rows     = $wpdb->get_results( $prepared, ARRAY_A );  

		return array_map( array( __CLASS__, 'unpack' ), $rows ? $rows : array() );
	}

	 
	public static function get_active() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		$cache = self::get_all(
			array(
				'status'  => 'active',
				'orderby' => 'priority',
				'order'   => 'ASC',
			)
		);
		return $cache;
	}

	public static function count( $status = '' ) {
		global $wpdb;
		$table = self::table();
		if ( $status ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );  
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );  
	}

	 
	public static function count_filtered( $args = array() ) {
		global $wpdb;
		$table = self::table();

		$defaults = array(
			'status' => '',
			'type'   => '',
			'search' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'type = %s';
			$params[] = $args['type'];
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(title LIKE %s OR description LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$sql      = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		$prepared = $params ? $wpdb->prepare( $sql, $params ) : $sql;  

		return (int) $wpdb->get_var( $prepared );  
	}

	 
	public static function insert( $data ) {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql' );

		$packed = self::pack( $data );

		$packed['created_at'] = $now;
		$packed['updated_at'] = $now;
		$packed['created_by'] = get_current_user_id();

		$formats = self::formats_for( $packed );
		$result  = $wpdb->insert( $table, $packed, $formats );  

		if ( false === $result ) {
			return new WP_Error( 'wcs_db_insert_failed', __( 'Could not save the snippet to the database.', 'wp-code-snippet' ) );
		}

		return (int) $wpdb->insert_id;
	}

	 
	public static function update( $id, $data ) {
		global $wpdb;
		$table = self::table();

		$packed                = self::pack( $data );
		$packed['updated_at']  = current_time( 'mysql' );

		$formats = self::formats_for( $packed );
		$result  = $wpdb->update( $table, $packed, array( 'id' => (int) $id ), $formats, array( '%d' ) );  

		return false !== $result;
	}

	 
	public static function update_status( $id, $status, $error_message = '' ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->update(  
			$table,
			array(
				'status'        => sanitize_key( $status ),
				'error_message' => $error_message,
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function increment_run_count( $id ) {
		global $wpdb;
		$table = self::table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET run_count = run_count + 1, last_run_at = %s WHERE id = %d", current_time( 'mysql' ), $id ) );  
	}

	public static function delete( $id ) {
		global $wpdb;
		$table = self::table();
		return false !== $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );  
	}

	 
	protected static function pack( $data ) {
		$out = array();

		if ( isset( $data['title'] ) ) {
			$out['title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['description'] ) ) {
			$out['description'] = sanitize_textarea_field( $data['description'] );
		}
		if ( isset( $data['code'] ) ) {
			 
			 
			 
			$out['code'] = $data['code'];
		}
		if ( isset( $data['type'] ) ) {
			$allowed     = array( 'php', 'html', 'css', 'js' );
			$out['type'] = in_array( $data['type'], $allowed, true ) ? $data['type'] : 'php';
		}
		if ( isset( $data['location'] ) ) {
			$out['location'] = sanitize_key( $data['location'] );
		}
		if ( isset( $data['conditions'] ) ) {
			$out['conditions'] = is_string( $data['conditions'] )
				? wp_json_encode( json_decode( wp_unslash( $data['conditions'] ), true ) )
				: wp_json_encode( $data['conditions'] );
		}
		if ( isset( $data['priority'] ) ) {
			$out['priority'] = (int) $data['priority'];
		}
		if ( isset( $data['status'] ) ) {
			$allowed       = array( 'active', 'inactive', 'auto-deactivated' );
			$out['status'] = in_array( $data['status'], $allowed, true ) ? $data['status'] : 'inactive';
		}
		if ( isset( $data['shortcode_tag'] ) ) {
			$out['shortcode_tag'] = sanitize_key( $data['shortcode_tag'] );
		}
		if ( isset( $data['error_message'] ) ) {
			$out['error_message'] = sanitize_textarea_field( $data['error_message'] );
		}

		return $out;
	}

	 
	protected static function formats_for( $packed ) {
		$map = array(
			'title'         => '%s',
			'description'   => '%s',
			'code'          => '%s',
			'type'          => '%s',
			'location'      => '%s',
			'conditions'    => '%s',
			'priority'      => '%d',
			'status'        => '%s',
			'shortcode_tag' => '%s',
			'error_message' => '%s',
			'created_at'    => '%s',
			'updated_at'    => '%s',
			'created_by'    => '%d',
		);
		$formats = array();
		foreach ( array_keys( $packed ) as $key ) {
			$formats[] = isset( $map[ $key ] ) ? $map[ $key ] : '%s';
		}
		return $formats;
	}

	 
	protected static function unpack( $row ) {
		if ( isset( $row['conditions'] ) ) {
			$decoded             = json_decode( $row['conditions'], true );
			$row['conditions']   = is_array( $decoded ) ? $decoded : array( 'rule' => 'everywhere' );
		}
		return $row;
	}
}
