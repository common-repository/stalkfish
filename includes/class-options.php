<?php
/**
 * Options management.
 *
 * @package Stalkfish
 */

namespace Stalkfish;

/**
 * Stalkfish options registration and management.
 */
class Options {
	const OPTION_KEY = 'sf_options';

	/**
	 * Holds the plugin instance.
	 *
	 * @access protected
	 * @static
	 *
	 * @var Base
	 */
	private static $instances = array();

	/**
	 * Disable class cloning and throw an error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object. Therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'stalkfish' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access public
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'stalkfish' ), '1.0.0' );
	}

	/**
	 * Sets up a single instance of the plugin.
	 *
	 * @access public
	 * @static
	 *
	 * @return static An instance of the class.
	 */
	public static function get_instance() {
		$module = get_called_class();
		if ( ! isset( self::$instances[ $module ] ) ) {
			self::$instances[ $module ] = new $module();
		}

		return self::$instances[ $module ];
	}

	/**
	 * Checks whether or not a value is set for the given option.
	 *
	 * @param string $option Option name.
	 *
	 * @return bool True if value set, false otherwise.
	 * @since 1.6.0
	 */
	public function has( $option ) {
		$options = get_option( self::OPTION_KEY );

		return isset( $options[ $option ] );
	}

	/**
	 * Get a single option, or all if no key is provided.
	 *
	 * @param string|array|bool $key Option key.
	 *
	 * @return array|string|bool
	 */
	public function get( $key = false, $default = '' ) {
		$options = get_option( self::OPTION_KEY );

		if ( ! $options || ! is_array( $options ) ) {
			$options = array();
		}

		if ( false !== $key ) {
			if ( ! isset( $options[ $key ] ) && ! empty( $default ) ) {
				return $default;
			}

			return isset( $options[ $key ] ) ? $options[ $key ] : false;
		}

		return $options;
	}

	/**
	 * Update a single option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Option value.
	 *
	 * @return void
	 */
	public function set( $key, $value ) {
		$options         = $this->get();
		$options[ $key ] = $value;
		update_option( self::OPTION_KEY, $options );
	}

	/**
	 * Delete a single option.
	 *
	 * @param string $key Option key.
	 *
	 * @return void
	 */
	public function delete( $key ) {
		$options = $this->get();
		if ( ! isset( $options[ $key ] ) ) {
			return;
		}

		unset( $options[ $key ] );
		update_option( self::OPTION_KEY, $options );
	}
}
