<?php
/**
 * Filters input values
 *
 * @package Stalkfish
 */

namespace Stalkfish;

/**
 * Class Filterpipe
 *
 * @package Stalkfish
 */
class Filterpipe {
	/**
	 * Helper callbacks for validation.
	 *
	 * @var array
	 */
	public static $filter_callbacks = array(
		FILTER_DEFAULT                => null,
		// Validate.
		FILTER_VALIDATE_BOOLEAN       => 'is_bool',
		FILTER_VALIDATE_EMAIL         => 'is_email',
		FILTER_VALIDATE_FLOAT         => 'is_float',
		FILTER_VALIDATE_INT           => 'is_int',
		FILTER_VALIDATE_IP            => array( __CLASS__, 'is_ip' ),
		FILTER_VALIDATE_REGEXP        => array( __CLASS__, 'is_regex' ),
		FILTER_VALIDATE_URL           => 'wp_http_validate_url',
		// Sanitize.
		FILTER_SANITIZE_EMAIL         => 'sanitize_email',
		FILTER_SANITIZE_ENCODED       => 'esc_url_raw',
		FILTER_SANITIZE_NUMBER_FLOAT  => 'floatval',
		FILTER_SANITIZE_NUMBER_INT    => 'intval',
		FILTER_SANITIZE_SPECIAL_CHARS => 'htmlspecialchars',
		FILTER_SANITIZE_STRING        => 'sanitize_text_field',
		FILTER_SANITIZE_URL           => 'esc_url_raw',
		// Other.
		FILTER_UNSAFE_RAW             => null,
	);

	/**
	 * Returns input value
	 *
	 * @param int    $type           Input type.
	 * @param string $variable_name  Variable key.
	 * @param int    $filter         Filter callback.
	 * @param array  $options        Filter callback parameters.
	 * @throws \Exception  Invalid input type provided.
	 * @return mixed
	 */
	public static function pipe( $type, $variable_name, $filter = null, $options = array() ) {
		$pipe = null;

		// @codingStandardsIgnoreStart
		switch ( $type ) {
			case INPUT_POST :
				$pipe = $_POST;
				break;
			case INPUT_GET :
				$pipe = $_GET;
				break;
			case INPUT_COOKIE :
				$pipe = $_COOKIE;
				break;
			case INPUT_ENV :
				$pipe = $_ENV;
				break;
			case INPUT_SERVER :
				$pipe = $_SERVER;
				break;
		}
		// @codingStandardsIgnoreEnd

		if ( is_null( $pipe ) ) {
			// Invalid use, type must be one of INPUT_* family.
			return false;
		}

		$var = $pipe[ $variable_name ] ?? null;
		$var = self::filter( sanitize_text_field( wp_unslash( $var ) ), $filter, $options );

		return $var;
	}

	/**
	 * Filters provided input.
	 *
	 * @param mixed $var      Raw value.
	 * @param int   $filter   Filter callback.
	 * @param array $options  Filter callback params.
	 * @throws \Exception Unsupported filter provided.
	 * @return mixed
	 */
	public static function filter( $var, $filter = null, $options = array() ) {
		// Default filter is a sanitizer, not validator.
		$filter_type = 'sanitizer';

		// Only filter value if it is not null.
		if ( isset( $var ) && $filter && FILTER_DEFAULT !== $filter ) {
			if ( ! isset( self::$filter_callbacks[ $filter ] ) ) {
				throw new \Exception( esc_html__( 'Filter value not supported.', 'stalkfish' ) );
			}

			$filter_callback = self::$filter_callbacks[ $filter ];
			$result          = call_user_func( $filter_callback, $var );

			/**
			 * "filter_var / filter_input" treats validation/sanitization filters the same
			 * they both return output and change the var value, this shouldn't be the case here.
			 * We'll do a boolean check on validation function, and let sanitizers change the value
			 */
			$filter_type = ( $filter < 500 ) ? 'validator' : 'sanitizer';
			if ( 'validator' === $filter_type ) { // Validation functions.
				if ( ! $result ) {
					$var = false;
				}
			} else { // Santization functions.
				$var = $result;
			}
		}

		// Detect FILTER_REQUIRE_ARRAY flag.
		if ( isset( $var ) && is_int( $options ) && FILTER_REQUIRE_ARRAY === $options ) {
			if ( ! is_array( $var ) ) {
				$var = ( 'validator' === $filter_type ) ? false : null;
			}
		}

		// Polyfill the `default` attribute only, for now.
		if ( is_array( $options ) && ! empty( $options['options']['default'] ) ) {
			if ( 'validator' === $filter_type && false === $var ) {
				$var = $options['options']['default'];
			} elseif ( 'sanitizer' === $filter_type && null === $var ) {
				$var = $options['options']['default'];
			}
		}

		return $var;
	}


	/**
	 * Tells whether the provided string is a Regular Expression or not
	 *
	 * @param string $var  Raw value.
	 * @return boolean
	 */
	public static function is_regex( $var ) {
		$test = preg_match( $var, '' );

		return false !== $test;
	}

	/**
	 * Tells whether the provided string is an IP address or not
	 *
	 * @param string $var Raw value.
	 * @return boolean
	 */
	public static function is_ip( $var ) {
		return false !== \WP_Http::is_ip_address( $var );
	}
}
