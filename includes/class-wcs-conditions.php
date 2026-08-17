<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCS_Conditions {

	public static function matches( $conditions ) {
		if ( empty( $conditions ) || ! is_array( $conditions ) ) {
			return true;
		}

		 
		if ( empty( $conditions['rules'] ) || ! is_array( $conditions['rules'] ) ) {
			return self::matches_legacy( $conditions );
		}

		$logic   = 'or' === ( $conditions['logic'] ?? 'and' ) ? 'or' : 'and';
		$results = array();

		foreach ( $conditions['rules'] as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['field'] ) ) {
				continue;
			}
			$results[] = self::evaluate( $rule );
		}

		if ( empty( $results ) ) {
			$matched = true;
		} elseif ( 'or' === $logic ) {
			$matched = in_array( true, $results, true );
		} else {
			$matched = ! in_array( false, $results, true );
		}

		return 'exclude' === ( $conditions['scope'] ?? 'include' ) ? ! $matched : $matched;
	}

	protected static function evaluate( $rule ) {
		$field = sanitize_key( $rule['field'] ?? '' );
		$op    = sanitize_key( $rule['operator'] ?? 'equals' );
		$value = isset( $rule['value'] ) ? (string) $rule['value'] : '';

		switch ( $field ) {
			case 'request_context':
				return self::compare( self::request_context(), $op, in_array( $op, array( 'in', 'not_in' ), true ) ? self::csv( $value ) : $value );

			case 'page_type':
				return self::compare( self::page_types(), $op, self::csv( $value ) );

			case 'post_type':
				$current = get_post_type( self::current_object_id() );
				if ( ! $current && is_post_type_archive() ) {
					$obj     = get_queried_object();
					$current = isset( $obj->name ) ? $obj->name : '';
				}
				return self::compare( (string) $current, $op, self::csv( $value ) );

			case 'post_id':
				return self::compare( (string) self::current_object_id(), $op, self::csv( $value ) );

			case 'post_status':
				$post = get_post( self::current_object_id() );
				return self::compare( $post ? (string) $post->post_status : '', $op, self::csv( $value ) );

			case 'post_author':
				$post = get_post( self::current_object_id() );
				return self::compare( $post ? (string) $post->post_author : '', $op, self::csv( $value ) );

			case 'taxonomy_term':
				return self::match_taxonomy_term( $op, $value );

			case 'url':
				return self::compare( self::current_url(), $op, $value );

			case 'query_string':
				$query = isset( $_SERVER['QUERY_STRING'] ) ? wp_unslash( $_SERVER['QUERY_STRING'] ) : '';  
				return self::compare( (string) $query, $op, $value );

			case 'query_param':
				return self::match_key_value_source( $_GET, $op, $value );  

			case 'request_method':
				$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
				return self::compare( $method, $op, in_array( $op, array( 'in', 'not_in' ), true ) ? array_map( 'strtoupper', self::csv( $value ) ) : strtoupper( $value ) );

			case 'referrer':
				$ref = wp_get_referer();
				return self::compare( $ref ? $ref : '', $op, $value );

			case 'logged_in':
				return self::compare( is_user_logged_in() ? 'yes' : 'no', $op, $value );

			case 'user_role':
				$roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();
				return self::compare( $roles, $op, self::csv( $value ) );

			case 'user_id':
				return self::compare( (string) get_current_user_id(), $op, self::csv( $value ) );

			case 'capability':
				$has = '' !== $value && current_user_can( $value );
				return in_array( $op, array( 'not_equals', 'not_exists' ), true ) ? ! $has : $has;

			case 'device':
				return self::compare( wp_is_mobile() ? 'mobile' : 'desktop', $op, $value );

			case 'locale':
				return self::compare( determine_locale(), $op, self::csv( $value ) );

			case 'weekday':
				return self::compare( strtolower( wp_date( 'l' ) ), $op, array_map( 'strtolower', self::csv( $value ) ) );

			case 'date':
				return self::compare_date( wp_date( 'Y-m-d' ), $op, $value );

			case 'time':
				return self::compare_time( wp_date( 'H:i' ), $op, $value );

			case 'cookie':
				return self::match_key_value_source( $_COOKIE, $op, $value );  

			case 'template':
				$template = function_exists( 'get_page_template_slug' ) ? (string) get_page_template_slug( self::current_object_id() ) : '';
				return self::compare( $template, $op, $value );

			case 'woocommerce':
				return self::match_woocommerce( $op, $value );

			case 'woo_product_type':
				return self::match_woo_product_type( $op, $value );

			case 'woo_category':
				return self::match_woo_category( $op, $value );

			case 'woo_cart_product':
				return self::match_woo_cart_products( $op, $value );

			case 'woo_cart_category':
				return self::match_woo_cart_categories( $op, $value );

			default:
				return true;
		}
	}

	protected static function compare( $actual, $op, $expected ) {
		$actual_arr   = is_array( $actual ) ? array_map( 'strval', $actual ) : array( (string) $actual );
		$expected_arr = is_array( $expected ) ? array_map( 'strval', $expected ) : array( (string) $expected );
		$a            = (string) reset( $actual_arr );
		$e            = (string) reset( $expected_arr );

		switch ( $op ) {
			case 'not_equals':
				return empty( array_intersect( $actual_arr, $expected_arr ) );
			case 'in':
				return ! empty( array_intersect( $actual_arr, $expected_arr ) );
			case 'not_in':
				return empty( array_intersect( $actual_arr, $expected_arr ) );
			case 'contains':
				return false !== stripos( $a, $e );
			case 'not_contains':
				return false === stripos( $a, $e );
			case 'starts_with':
				return '' === $e || 0 === stripos( $a, $e );
			case 'ends_with':
				return '' === $e || strtolower( substr( $a, -strlen( $e ) ) ) === strtolower( $e );
			case 'regex':
				if ( '' === $e ) {
					return true;
				}
				$pattern = '~' . str_replace( '~', '\\~', $e ) . '~i';
				return false !== @preg_match( $pattern, $a ) && 1 === @preg_match( $pattern, $a );  
			case 'exists':
				return '' !== $a;
			case 'not_exists':
				return '' === $a;
			case 'greater_than':
				return is_numeric( $a ) && is_numeric( $e ) && (float) $a > (float) $e;
			case 'less_than':
				return is_numeric( $a ) && is_numeric( $e ) && (float) $a < (float) $e;
			case 'equals':
			default:
				return ! empty( array_intersect( $actual_arr, $expected_arr ) );
		}
	}

	protected static function match_key_value_source( $source, $op, $value ) {
		$parts = explode( '=', $value, 2 );
		$key   = sanitize_key( trim( $parts[0] ) );
		$want  = isset( $parts[1] ) ? trim( $parts[1] ) : '';
		$has   = $key && isset( $source[ $key ] );

		if ( 'exists' === $op ) {
			return $has;
		}
		if ( 'not_exists' === $op ) {
			return ! $has;
		}
		if ( ! $has ) {
			return false;
		}
		$actual = is_array( $source[ $key ] ) ? implode( ',', array_map( 'sanitize_text_field', wp_unslash( $source[ $key ] ) ) ) : sanitize_text_field( wp_unslash( $source[ $key ] ) );
		return self::compare( $actual, $op, $want );
	}

	protected static function compare_date( $actual, $op, $expected ) {
		$a = strtotime( $actual );
		$e = strtotime( $expected );
		if ( ! $e ) {
			return true;
		}
		if ( 'before' === $op ) {
			return $a < $e;
		}
		if ( 'after' === $op ) {
			return $a > $e;
		}
		return $a === $e;
	}

	protected static function compare_time( $actual, $op, $expected ) {
		if ( 'between' === $op ) {
			$parts = array_map( 'trim', explode( '-', $expected, 2 ) );
			if ( 2 !== count( $parts ) ) {
				return true;
			}
			 
			if ( $parts[0] <= $parts[1] ) {
				return $actual >= $parts[0] && $actual <= $parts[1];
			}
			return $actual >= $parts[0] || $actual <= $parts[1];
		}
		if ( 'before' === $op ) {
			return $actual < $expected;
		}
		if ( 'after' === $op ) {
			return $actual > $expected;
		}
		return self::compare( $actual, $op, $expected );
	}

	protected static function current_object_id() {
		$id = (int) get_queried_object_id();
		if ( $id ) {
			return $id;
		}
		if ( is_admin() ) {
			if ( isset( $_GET['post'] ) ) {  
				return absint( $_GET['post'] );  
			}
			if ( isset( $_POST['post_ID'] ) ) {  
				return absint( $_POST['post_ID'] );  
			}
		}
		return 0;
	}

	protected static function request_context() {
		if ( wp_doing_ajax() ) {
			return 'ajax';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest';
		}
		if ( wp_doing_cron() ) {
			return 'cron';
		}
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			return 'login';
		}
		return is_admin() ? 'admin' : 'frontend';
	}

	protected static function current_url() {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';  
		return $scheme . $host . $uri;
	}

	protected static function page_types() {
		$types = array();
		if ( is_front_page() ) { $types[] = 'front_page'; }
		if ( is_home() ) { $types[] = 'blog_home'; }
		if ( is_singular() ) { $types[] = 'singular'; }
		if ( is_archive() ) { $types[] = 'archive'; }
		if ( is_category() ) { $types[] = 'category'; }
		if ( is_tag() ) { $types[] = 'tag'; }
		if ( is_tax() ) { $types[] = 'taxonomy'; }
		if ( is_search() ) { $types[] = 'search'; }
		if ( is_404() ) { $types[] = '404'; }
		if ( is_feed() ) { $types[] = 'feed'; }
		return $types;
	}

	protected static function match_woocommerce( $op, $value ) {
		$matched = false;
		switch ( $value ) {
			case 'shop': $matched = function_exists( 'is_shop' ) && is_shop(); break;
			case 'product': $matched = function_exists( 'is_product' ) && is_product(); break;
			case 'product_category': $matched = function_exists( 'is_product_category' ) && is_product_category(); break;
			case 'product_tag': $matched = function_exists( 'is_product_tag' ) && is_product_tag(); break;
			case 'cart': $matched = function_exists( 'is_cart' ) && is_cart(); break;
			case 'checkout': $matched = function_exists( 'is_checkout' ) && is_checkout(); break;
			case 'account': $matched = function_exists( 'is_account_page' ) && is_account_page(); break;
			case 'order_received': $matched = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ); break;
			default: $matched = false;
		}
		return in_array( $op, array( 'not_equals', 'not_in' ), true ) ? ! $matched : $matched;
	}

	protected static function match_taxonomy_term( $op, $value ) {
		$parts    = explode( '=', (string) $value, 2 );
		$taxonomy = sanitize_key( trim( $parts[0] ?? '' ) );
		$wanted   = isset( $parts[1] ) ? sanitize_title( trim( $parts[1] ) ) : '';
		if ( ! $taxonomy ) {
			return true;
		}
		$object_id = self::current_object_id();
		$slugs     = array();
		if ( $object_id ) {
			$terms = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $terms ) ) {
				$slugs = (array) $terms;
			}
		}
		if ( empty( $slugs ) && is_tax( $taxonomy ) ) {
			$obj = get_queried_object();
			if ( isset( $obj->slug ) ) {
				$slugs[] = (string) $obj->slug;
			}
		}
		return self::compare( $slugs, $op, $wanted );
	}

	protected static function current_wc_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return false;
		}
		$product_id = self::current_object_id();
		if ( ! $product_id && isset( $GLOBALS['product'] ) && is_object( $GLOBALS['product'] ) && method_exists( $GLOBALS['product'], 'get_id' ) ) {
			$product_id = $GLOBALS['product']->get_id();
		}
		return $product_id ? wc_get_product( $product_id ) : false;
	}

	protected static function match_woo_product_type( $op, $value ) {
		$product = self::current_wc_product();
		$type    = $product && method_exists( $product, 'get_type' ) ? $product->get_type() : '';
		return self::compare( $type, $op, self::csv( $value ) );
	}

	protected static function match_woo_category( $op, $value ) {
		$product = self::current_wc_product();
		$slugs   = array();
		if ( $product ) {
			$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $terms ) ) {
				$slugs = (array) $terms;
			}
		}
		return self::compare( $slugs, $op, self::csv( $value ) );
	}

	protected static function match_woo_cart_products( $op, $value ) {
		$ids = array();
		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$ids[] = (string) ( $item['product_id'] ?? 0 );
				if ( ! empty( $item['variation_id'] ) ) {
					$ids[] = (string) $item['variation_id'];
				}
			}
		}
		return self::compare( array_unique( $ids ), $op, self::csv( $value ) );
	}

	protected static function match_woo_cart_categories( $op, $value ) {
		$slugs = array();
		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$product_id = absint( $item['product_id'] ?? 0 );
				if ( ! $product_id ) {
					continue;
				}
				$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $terms ) ) {
					$slugs = array_merge( $slugs, (array) $terms );
				}
			}
		}
		return self::compare( array_unique( $slugs ), $op, self::csv( $value ) );
	}

	protected static function csv( $value ) {
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
	}

	protected static function matches_legacy( $c ) {
		if ( 'everywhere' === ( $c['rule'] ?? 'everywhere' ) ) {
			return true;
		}
		$matched = true;
		switch ( $c['rule'] ?? '' ) {
			case 'specific':
				$parts = array();
				if ( ! empty( $c['post_types'] ) ) {
					$parts[] = is_singular( $c['post_types'] ) || is_post_type_archive( $c['post_types'] ) || ( in_array( 'front_page', (array) $c['post_types'], true ) && is_front_page() ) || ( in_array( 'blog', (array) $c['post_types'], true ) && is_home() );
				}
				if ( ! empty( $c['post_ids'] ) ) {
					$parts[] = in_array( (int) get_queried_object_id(), array_map( 'intval', (array) $c['post_ids'] ), true );
				}
				$matched = empty( $parts ) || ! in_array( false, $parts, true );
				break;
			case 'device':
				$matched = 'mobile' === ( $c['device'] ?? '' ) ? wp_is_mobile() : ! wp_is_mobile();
				break;
			case 'url_contains':
				$matched = false !== strpos( self::current_url(), (string) ( $c['url_value'] ?? '' ) );
				break;
			case 'user_role':
				$state = $c['logged_in'] ?? 'any';
				$matched = ! ( 'in' === $state && ! is_user_logged_in() ) && ! ( 'out' === $state && is_user_logged_in() );
				if ( $matched && ! empty( $c['roles'] ) && is_user_logged_in() ) {
					$matched = (bool) array_intersect( (array) $c['roles'], (array) wp_get_current_user()->roles );
				}
				break;
		}
		return 'exclude' === ( $c['scope'] ?? 'include' ) ? ! $matched : $matched;
	}
}
