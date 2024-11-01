<?php
/**
 * Plugin initialization file.
 *
 * @package Stalkfish
 */

namespace Stalkfish;

use Stalkfish\API\Local;
use Stalkfish\API\StalkfishAPI;

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Controls pipes.
	 *
	 * @var Pipes
	 */
	public $pipes;

	/**
	 * Controls logs.
	 *
	 * @var Logpipe
	 */
	public $logpipe;

	/**
	 * Initialize plugin functionality.
	 *
	 * @return void
	 */
	public function register() {
		// Prioritize plugin load.
		$this->update_plugins_load_order();

		new StalkfishAPI();

		// Instantiate Local API.
		new Local();

		// Load logpipe for remote logging.
		$this->logpipe = new Logpipe();

		// Load events after widgets_init and before the default init priority.
		add_action( 'init', array( $this, 'init' ), 9 );
		add_action( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
	}

	/**
	 * Updates the order to make Stalkfish load at the very first.
	 *
	 * @return void
	 */
	public function update_plugins_load_order() {
		$active_plugins = get_option( 'active_plugins' );

		if ( ! is_array( $active_plugins ) ) {
			return;
		}

		$plugins_count = count( $active_plugins );

		if ( ! $plugins_count ) {
			return;
		}

		$stalkfish_base = plugin_basename( STALKFISH_PLUGIN_FILE );

		$updated_order   = array();
		$updated_order[] = $stalkfish_base;

		for ( $i = 0; $i <= $plugins_count; $i++ ) {
			if ( ! isset( $active_plugins[ $i ] ) ) {
				continue;
			}

			if ( $active_plugins[ $i ] !== $stalkfish_base ) {
				$updated_order[] = $active_plugins[ $i ];
			}
		}

		if ( $updated_order !== $active_plugins ) {
			update_option( 'active_plugins', $updated_order );
		}
	}

	/**
	 * Alerts on the admin front for an api key.
	 *
	 * @return void
	 */
	public function api_key_notice() {
		$html_message = sprintf(
		/* translators: %1s: Div open, %2$s: Anchor open, %3$s: Anchor close, %4$s: Div close, %5$s: Anchor open */
			__( '%1$sStalkfish is currently not working, please set an %2$sAPI key%3$s or restart the %5$sonboarding%3$s.%4$s', 'stalkfish' ),
			'<div id="message" class="error notice"><p>',
			'<a href="' . get_admin_url() . 'options-general.php?page=sf-settings">',
			'</a>',
			'</p></div>',
			'<a href="' . admin_url() . 'admin.php?page=stalkfish-setup">',
		);

		echo wp_kses_post( $html_message );
	}

	/**
	 * Load plugin hooks & events.
	 *
	 * @return void
	 */
	public function init() {
		$options = Options::get_instance();

		// Conditionally generate site keys.
		$stalkfish_connect_timeout = get_transient( 'stalkfish_connect_timeout' );

		if ( ! $options->has( 'public_key' ) && ! $stalkfish_connect_timeout ) {

			$connection_status = \Stalkfish\KeyManager::generate();

			if ( ! $connection_status ) {
				set_transient( 'stalkfish_connect_timeout', true, MINUTE_IN_SECONDS );
			}
		}

		$api_key            = Options::get_instance()->get( 'sf_app_api_key' );
		$onboarding_version = Options::get_instance()->get( 'sf_onboarding_version' );

		// Only redirect if API key and onboarding version is not.
		if ( empty( $api_key ) && empty( $onboarding_version ) ) {
			wp_safe_redirect( admin_url() . 'admin.php?page=stalkfish-setup' );
			Options::get_instance()->set( 'sf_onboarding_version', STALKFISH_VERSION );
		}

		if ( empty( $api_key ) ) {
			add_action( 'admin_notices', array( $this, 'api_key_notice' ) );
			return;
		}

		$activity_logs = Options::get_instance()->get( 'sf_activity_logs', true );

		if ( ! $activity_logs ) {
			return;
		}

		$this->pipes = new Pipes();
	}

	/**
	 * Add plugin settings page link to plugin action links.
	 *
	 * @param array  $links Existing links.
	 * @param string $file The plugin file.
	 *
	 * @return array New links.
	 */
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( STALKFISH_PLUGIN_FILE ) === $file ) {
			$settings_link = '<a href="'. self_admin_url( 'options-general.php?page=sf-settings' ) .'">Settings</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
}
