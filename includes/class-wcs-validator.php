<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Validator {

	 
	public static function check_php_syntax( $code ) {
		if ( trim( $code ) === '' ) {
			return true;
		}

		$code        = self::normalize_php_code( $code );
		$full_source = "<?php\n" . $code;

		 
		 
		return self::lint_via_tokenizer( $full_source );
	}

	 
	public static function normalize_php_code( $code ) {
		$code = (string) $code;
		$code = preg_replace( '/^\xEF\xBB\xBF/', '', $code );
		$code = preg_replace( '/^\s*<\?php\b/i', '', $code, 1 );
		$code = preg_replace( '/\?>\s*$/', '', $code, 1 );

		return ltrim( $code, "\r\n" );
	}

	protected static function can_shell_exec() {
		return function_exists( 'shell_exec' )
			&& function_exists( 'escapeshellarg' )
			&& ! in_array( 'shell_exec', array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) ), true );
	}

	protected static function lint_via_cli( $full_source ) {
		$tmp_file = wp_tempnam( 'wcs-lint' );
		if ( ! $tmp_file ) {
			return self::lint_via_tokenizer( $full_source );
		}

		file_put_contents( $tmp_file, $full_source );  

		$php_binary = defined( 'PHP_BINARY' ) && PHP_BINARY ? PHP_BINARY : 'php';
		$cmd        = escapeshellarg( $php_binary ) . ' -l ' . escapeshellarg( $tmp_file ) . ' 2>&1';
		$output     = shell_exec( $cmd );  

		wp_delete_file( $tmp_file );

		if ( null === $output ) {
			 
			 
			return self::lint_via_tokenizer( $full_source );
		}

		if ( false !== strpos( $output, 'No syntax errors detected' ) ) {
			return true;
		}

		return new WP_Error( 'wcs_syntax_error', trim( preg_replace( '/^PHP Parse error:\s*/', '', $output ) ) );
	}

	 
	protected static function lint_via_tokenizer( $full_source ) {
		if ( ! function_exists( 'token_get_all' ) ) {
			return true;  
		}

		try {
			$tokens = token_get_all( $full_source, TOKEN_PARSE );
		} catch ( Throwable $e ) {
			$message = trim( (string) $e->getMessage() );
			$message = preg_replace( '/\s+in \S+ on line \d+$/', '', $message );
			return new WP_Error( 'wcs_syntax_error', $message ? $message : __( 'PHP syntax error.', 'wp-code-snippet' ) );
		}

		$depth   = array(
			'{' => 0,
			'(' => 0,
			'[' => 0,
		);
		$closers = array(
			'}' => '{',
			')' => '(',
			']' => '[',
		);

		foreach ( $tokens as $token ) {
			$text = is_array( $token ) ? $token[1] : $token;
			if ( isset( $depth[ $text ] ) ) {
				$depth[ $text ]++;
			} elseif ( isset( $closers[ $text ] ) ) {
				$depth[ $closers[ $text ] ]--;
			}
		}

		foreach ( $depth as $char => $count ) {
			if ( 0 !== $count ) {
				return new WP_Error( 'wcs_syntax_error', sprintf(
					 
					__( 'Unbalanced "%s" — check your braces/parentheses/brackets.', 'wp-code-snippet' ),
					$char
				) );
			}
		}

		return true;
	}
}
