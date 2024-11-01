<?php
/**
 * Initiate error tracker.
 *
 * @package Stalkfish
 */

/**
 * Merge arrays recursively.
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array
 */
function stalkfish_array_merge_recursive_distinct( array $array1, array &$array2 ) {
	$merged = $array1;
	foreach ( $array2 as $key => &$value ) {
		if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
			$merged[ $key ] = stalkfish_array_merge_recursive_distinct( $merged[ $key ], $value );
		} else {
			$merged[ $key ] = $value;
		}
	}

	return $merged;
}


/**
 * Load and trigger error tracker.
 *
 * @return void
 */
function stalkfish_register_error_tracker() {
	$error_logs = \Stalkfish\Options::get_instance()->get( 'sf_error_logs', true );

	if ( ! $error_logs ) {
		return;
	}

	global $stalkfish;

	$api_key = \Stalkfish\Options::get_instance()->get( 'sf_app_api_key' );

	$stalkfish['error'] = \Stalkfish\ErrorTracker\Init::register( $api_key )->registerTrackerHandlers();
}

stalkfish_register_error_tracker();
