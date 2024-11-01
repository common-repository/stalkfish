<?php
/**
 * Plugin initialization file.
 *
 * @package Stalkfish
 */

use Stalkfish\Plugin;

/**
 * Handles plugin activation.
 *
 * Throws an error if the site is running on PHP < 7.0
 *
 * @param bool $network_wide Whether to activate network-wide.
 *
 * @return void
 */
function stalkfish_activate( $network_wide ) {
	if ( version_compare( PHP_VERSION, STALKFISH_MINIMUM_PHP_VERSION, '<' ) ) {
		wp_die(
			/* translators: %s: PHP version number */
			esc_html( sprintf( __( 'Stalkfish requires PHP %s or higher.', 'stalkfish' ), STALKFISH_MINIMUM_PHP_VERSION ) ),
			esc_html__( 'Plugin could not be activated', 'stalkfish' )
		);
	}

	do_action( 'stalkfish_activation', $network_wide );
}

/**
 * Handles plugin deactivation.
 *
 * @param bool $network_wide Whether to deactivate network-wide.
 *
 * @return void
 */
function stalkfish_deactivate( $network_wide ) {
	if ( version_compare( PHP_VERSION, STALKFISH_MINIMUM_PHP_VERSION, '<' ) ) {
		return;
	}

	do_action( 'stalkfish_deactivation', $network_wide );
}

register_activation_hook( STALKFISH_PLUGIN_FILE, 'stalkfish_activate' );
register_deactivation_hook( STALKFISH_PLUGIN_FILE, 'stalkfish_deactivate' );

/**
 * Load action scheduler as it is supposed to be loaded.
 * @see https://actionscheduler.org/usage/
 */
add_action( 'plugins_loaded', static function() {
	require_once STALKFISH_PLUGIN_DIR_PATH . '/libraries/action-scheduler/action-scheduler.php';
}, -10 );

// Register error tracker.
require_once STALKFISH_PLUGIN_DIR_PATH . '/includes/ErrorTracker/register.php';

/**
 * Instantiates plugin instance.
 */
add_action(
	'plugins_loaded',
	static function() {
			// Load helpers.
			require_once STALKFISH_PLUGIN_DIR_PATH . '/includes/helpers.php';
			// Register settings page.
			require_once STALKFISH_PLUGIN_DIR_PATH . '/includes/register-settings.php';
			require_once STALKFISH_PLUGIN_DIR_PATH . '/includes/settings-helpers.php';

			global $stalkfish;

			$stalkfish = new Plugin();
			$stalkfish->register();
	}
);
