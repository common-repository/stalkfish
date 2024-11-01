<?php
/**
 * Includes helper functions used throughout the plugin.
 *
 * @package Stalkfish
 */

/**
 * Returns global Stalkfish object.
 *
 * @return mixed Stalkfish global
 */
function stalkfish_get_instance() {
	return $GLOBALS['stalkfish'];
}

/**
 * Gets the current user IP address
 *
 * @return string
 */
function stalkfish_get_current_ip() {
	// Support proxy mode by checking the `X-Forwarded-For` header first.
	$ip_address = stalkfish_filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_VALIDATE_IP );

	return $ip_address ? $ip_address : stalkfish_filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );

}

/**
 * Gets a specific external variable by name and optionally filters it.
 *
 * This is a polyfill function intended to be used in place of PHP's
 * filter_input() function, which can occasionally be unreliable.
 *
 * @param int    $type           One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
 * @param string $variable_name  Name of a variable to get.
 * @param int    $filter         The ID of the filter to apply.
 * @param mixed  $options        Associative array of options or bitwise disjunction of flags. If filter accepts options, flags can be provided in "flags" field of array.
 *
 * @return Value of the requested variable on success, FALSE if the filter fails, or NULL if the $variable_name is not set.
 */
function stalkfish_filter_input( $type, $variable_name, $filter = null, $options = array() ) {
	return call_user_func_array( array( '\Stalkfish\Filterpipe', 'pipe' ), func_get_args() );
}

/**
 * Get the roles for a specific user.
 *
 * @param  object|int $user User object or ID to get roles for.
 *
 * @return array $labels    Assigned role labels
 */
function stalkfish_get_user_role_labels( $user ) {
	if ( is_int( $user ) ) {
		$user = get_user_by( 'id', $user );
	}

	if ( ! is_a( $user, 'WP_User' ) ) {
		return array();
	}

	global $wp_roles;

	$roles  = $wp_roles->get_names();
	$labels = array();

	foreach ( $roles as $role => $label ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			$labels[] = translate_user_role( $label );
		}
	}

	return $labels;
}


/**
 * Filter user roles.
 *
 * @param string $user_role - User role.
 * @return string
 */
function stalkfish_filter_role_names( $user_role ) {
	global $wp_roles;
	return isset( $wp_roles->role_names[ $user_role ] ) ? $wp_roles->role_names[ $user_role ] : false;
}

/**
 * Get current user roles.
 *
 * @param string $base_roles User roles.
 * @return mixed|string[]|null
 */
function stalkfish_get_current_user_roles( $base_roles = null ) {

	if ( null === $base_roles ) {
		$base_roles = wp_get_current_user()->roles;
	}

	if ( is_multisite() && function_exists( 'is_super_admin' ) && is_super_admin() ) {
		$base_roles[] = 'superadmin';
	}
	return reset( $base_roles );
}

/**
 * Get an array of user roles
 *
 * @return array
 */
function stalkfish_get_all_roles() {
	$wp_roles = new WP_Roles();
	$roles    = array();

	foreach ( $wp_roles->get_names() as $role => $label ) {
		$roles[ $role ] = translate_user_role( $label );
	}

	return $roles;
}

/**
 * Get request data.
 *
 * @return array
 */
function stalkfish_get_filtered_request_data() {
	$result = array();

	$get_data = filter_input_array( INPUT_GET );
	if ( is_array( $get_data ) ) {
		$result = array_merge( $result, $get_data );
	}

	$post_data = filter_input_array( INPUT_POST );
	if ( is_array( $post_data ) ) {
		$result = array_merge( $result, $post_data );
	}

	return $result;
}

/**
 * Global settings with multisite check.
 *
 * @param string $option Setting name.
 * @param bool   $default Default value.
 *
 * @return string Setting value.
 */
function stalkfish_get_global_settings( $option = '', $default = null ) {
	if ( empty( $option ) || ! is_string( $option ) ) {
		return;
	}

	if ( is_multisite() ) {
		switch_to_blog( get_main_network_id() );
	}

	$value = get_option( $option, $default );

	if ( is_multisite() ) {
		restore_current_blog();
	}

	return maybe_unserialize( $value );
}
