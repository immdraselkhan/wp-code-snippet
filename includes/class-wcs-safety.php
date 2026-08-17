<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Safety {

	const TOKEN_TTL = 60;

	 
	public static function verify_php_snippet( $snippet ) {
		if ( empty( $snippet ) || 'php' !== ( $snippet['type'] ?? '' ) ) {
			return true;
		}

		$syntax = WCS_Validator::check_php_syntax( $snippet['code'] ?? '' );
		if ( is_wp_error( $syntax ) ) {
			return $syntax;
		}

		$location = sanitize_key( $snippet['location'] ?? 'run_everywhere' );
		$contexts = array();

		if ( 'admin_only' === $location ) {
			$contexts[] = 'admin';
		} elseif ( 'frontend_only' === $location ) {
			$contexts[] = 'frontend';
		} else {
			 
			$contexts[] = 'frontend';
			$contexts[] = 'admin';
		}

		foreach ( $contexts as $context ) {
			$result = self::run_probe( $snippet, $context );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	protected static function run_probe( $snippet, $context ) {
		$token = wp_generate_password( 24, false, false );
		$key   = 'wcs_probe_' . $token;
		$out   = 'wcs_probe_result_' . $token;

		set_transient(
			$key,
			array(
				'snippet' => $snippet,
				'context' => $context,
			),
			self::TOKEN_TTL
		);
		delete_transient( $out );

		if ( 'admin' === $context ) {
			$url = add_query_arg(
				array(
					'action'          => 'wcs_probe_ping',
					'wcs_probe_token' => rawurlencode( $token ),
					'_wcs_nocache'    => time(),
				),
				admin_url( 'admin-ajax.php' )
			);
		} else {
			$url = add_query_arg(
				array(
					'wcs_probe_token' => rawurlencode( $token ),
					'_wcs_nocache'    => time(),
				),
				home_url( '/' )
			);
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'headers'     => array(
					'Cache-Control' => 'no-cache, no-store, must-revalidate',
					'Pragma'        => 'no-cache',
				),
				'user-agent'  => 'WP-Code-Snippet-Safety/' . WCS_VERSION,
			)
		);

		$probe_result = get_transient( $out );
		delete_transient( $key );
		delete_transient( $out );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'wcs_runtime_probe_failed',
				sprintf(
					 
					__( 'Safety check could not reach the site: %s', 'wp-code-snippet' ),
					$response->get_error_message()
				)
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status >= 500 ) {
			$message = is_array( $probe_result ) && ! empty( $probe_result['message'] )
				? $probe_result['message']
				: sprintf( __( 'The site returned HTTP %d during the safety check.', 'wp-code-snippet' ), $status );
			return new WP_Error( 'wcs_runtime_probe_http_error', $message );
		}

		if ( ! is_array( $probe_result ) || empty( $probe_result['success'] ) ) {
			$message = is_array( $probe_result ) && ! empty( $probe_result['message'] )
				? $probe_result['message']
				: __( 'The snippet did not complete a normal WordPress bootstrap during the safety check.', 'wp-code-snippet' );
			return new WP_Error( 'wcs_runtime_probe_incomplete', $message );
		}

		return true;
	}
}
