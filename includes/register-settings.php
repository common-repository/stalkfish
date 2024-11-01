<?php
/**
 * Register admin screen.
 *
 * @package Stalkfish
 */

namespace Stalkfish\Settings;

/**
 * Register plugin menu.
 *
 * @return void
 */
function register_menu() {
	add_submenu_page(
		'options-general.php',
		__( 'Stalkfish Settings', 'stalkfish' ),
		__( 'Stalkfish', 'stalkfish' ),
		'manage_options',
		'sf-settings',
		'Stalkfish\Settings\settings_page'
	);

	add_submenu_page(
		'',
		__( 'Setup Stalkfish', 'stalkfish' ),
		__( 'Setup Stalkfish', 'stalkfish' ),
		'manage_options',
		'stalkfish-setup',
		'Stalkfish\Settings\onboarding_wizard',
	);
}

add_action( 'admin_menu', __NAMESPACE__ . '\register_menu' );


/**
 * Handle saving of settings.
 *
 * @return void
 */
function save_settings() {
	global $current_tab, $current_section;

	// We should only save on the settings page.
	if ( ! is_admin() || ! isset( $_GET['page'] ) || 'sf-settings' !== $_GET['page'] ) { // phpcs:ignore
		return;
	}

	// Include settings pages.
	Admin_Settings::get_settings_pages();

	// Get current tab/section.
	$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore
	$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // phpcs:ignore

	// Save settings if data has been posted.
	if ( '' !== $current_section && apply_filters( "sf_save_settings_{$current_tab}_{$current_section}", ! empty( $_POST['save'] ) ) ) { // phpcs:ignore
		Admin_Settings::save();
	} elseif ( '' === $current_section && apply_filters( "sf_save_settings_{$current_tab}", ! empty( $_POST['save'] ) ) ) { // phpcs:ignore
		Admin_Settings::save();
	}
}


// Handle saving settings earlier than load-{page} hook to avoid race conditions in conditional menus.
add_action( 'wp_loaded', __NAMESPACE__ . '\save_settings' );

/**
 * Add settings page.
 *
 * @return void
 */
function settings_page() {
	Admin_Settings::output();
}

/**
 * Add onboarding page.
 *
 * @since 1.0.4
 * @return void
 */
function onboarding_wizard() {
	wp_enqueue_style( 'stalkfish_onboarding', STALKFISH_ASSETS_URL . '/css/onboarding.css', array(), filemtime( STALKFISH_PLUGIN_DIR_PATH . 'assets/css/onboarding.css' ) );

	wp_enqueue_script(
		'stalkfish_onboarding',
		STALKFISH_ASSETS_URL . '/js/onboarding.js',
		array( 'jquery' ),
		filemtime( STALKFISH_PLUGIN_DIR_PATH . 'assets/js/onboarding.js' ),
		true
	);

	include dirname( __FILE__ ) . '/Settings/views/html-onboarding.php';
}


/**
 * Sets up the default options used on the settings page.
 *
 * @return bool|void
 */
function create_options() {
	if ( ! is_admin() ) {
		return false;
	}

	$settings = array_filter( Admin_Settings::get_settings_pages() );

	foreach ( $settings as $section ) {
		if ( ! method_exists( $section, 'get_settings' ) ) {
			continue;
		}
		$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

		foreach ( $subsections as $subsection ) {
			foreach ( $section->get_settings( $subsection ) as $value ) {
				if ( isset( $value['default'], $value['id'] ) ) {
					$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
					add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}
	}
}
add_action( 'init', 'Stalkfish\Settings\create_options' );

/**
 * Sends a mock log request to App.
 *
 * @return bool|void
 */
function send_mock_request() {
	$pipe = 'settings';
	$body = array(
		'message' => __( 'Test event', 'stalkfish' ),
		'meta'    => array(
			'id'   => 0,
		),
		'context' => 'sf_options',
		'action'  => 'created',
	);

	$immediate = true;

	$response = call_user_func_array( array( stalkfish_get_instance()->logpipe, 'pipe' ), compact( 'pipe', 'body', 'immediate' ) );

	if ( ! $response ) {
		wp_send_json_error();
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_mock_log_request', __NAMESPACE__ . '\send_mock_request' );
