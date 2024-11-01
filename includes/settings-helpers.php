<?php
/**
 * Helper functions for Page Settings.
 *
 * @package Stalkfish
 */

namespace Stalkfish\Settings;

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 *
 * @return string|array
 */
function sf_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( __NAMESPACE__ . '\sf_clean', $var );
	}

	return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
}

/**
 * Output admin fields.
 *
 * Loops though the Stalkfish options array and outputs each field.
 *
 * @param array $options Opens array to output.
 */
function sf_admin_fields( $options ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/Settings/class-admin-settings.php';
	}

	Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options Option fields to save.
 * @param array $data Passed data.
 */
function sf_update_options( $options, $data = null ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/Settings/class-admin-settings.php';
	}

	Admin_Settings::save_fields( $options, $data );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name Option name to save.
 * @param mixed $default Default value to save.
 *
 * @return string
 */
function sf_settings_get_option( $option_name, $default = '' ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/Settings/class-admin-settings.php';
	}

	return Admin_Settings::get_option( $option_name, $default );
}


