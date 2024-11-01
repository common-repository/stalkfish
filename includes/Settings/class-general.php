<?php
/**
 * Stalkfish General Settings
 *
 * @package Stalkfish
 */

namespace Stalkfish\Settings;

use Stalkfish\Options;

defined( 'ABSPATH' ) || exit;

/**
 * General.
 */
class General extends Settings_Page {


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'stalkfish' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array();

		return apply_filters( 'sf_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$app_api_key = trim( Options::get_instance()->get( 'sf_app_api_key' ) );

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'sf_app_api_key',
			),
			array(
				'title'   => esc_html_x( 'API Key', 'setting title', 'stalkfish' ),
				'desc'    => sprintf(
				/* translators: %s: Stalkfish Documentation link */
					__( 'Enter your Stalkfish account API key to enable activity monitoring on this site. %s.', 'stalkfish' ),
					'<a href="https://stalkfish.com/docs/setup" target="_blank">' . __( 'Get help', 'stalkfish' ) . '</a>'
				),
				'id'      => 'sf_app_api_key',
				'default' => get_option( 'sf_app_api_key' ),
				'type'    => 'text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'sf_app_api_key',
			),
			array(
				'type' => 'title',
				'id'   => 'sf_request_type_section',
			),
			array(
				'id'      => 'sf_request_type',
				'title'   => esc_html_x( 'How to send data', 'setting title', 'stalkfish' ),
				'default' => 'async',
				'type'    => 'radio',
				'options' => array(
					'immediate' => esc_html__( 'Immediately - Data is sent immediately to Stalkfish server.', 'stalkfish' ),
					'async'     => esc_html__( 'Async - Data is queued to be sent late using WP Cron, may take up to a minute before appearing in your Stalkfish dashboard.', 'stalkfish' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'sf_request_type_section',
			),
		);

		if ( $app_api_key ) {
			$settings = array_merge(
				$settings,
				array(
					// Mock request button.
					array(
						'type' => 'title',
						'id'   => 'sf_app_sample_action',
					),
					array(
						'type'  => 'action',
						'class' => 'button-secondary',
						'id'    => 'sf-mock_data',
						'value' => 'Send Sample Data',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'sf_app_sample_action',
					),
					// Log toggles.
					array(
						'title' => __( 'Toggle Logs', 'stalkfish' ),
						'desc'  => __( 'Turn on or off reporting for activity and error logs.', 'stalkfish' ),
						'type'  => 'title',
						'id'    => 'sf_report_toggles',
					),
					array(
						'id'      => 'sf_activity_logs',
						'title'   => esc_html_x( 'Activity Logs', 'settings title', 'stalkfish' ),
						'default' => true,
						'type'    => 'checkbox',
					),
					array(
						'id'      => 'sf_error_logs',
						'title'   => esc_html_x( 'Error Logs', 'settings title', 'stalkfish' ),
						'default' => true,
						'type'    => 'checkbox',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'sf_report_toggles',
					),
				)
			);
		}

		$settings = apply_filters(
			'sf_general_settings',
			$settings,
		);

		return apply_filters( 'sf_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		Admin_Settings::save_fields( $settings );
		if ( $current_section ) {
			do_action( 'sf_update_options_' . $this->id . '_' . $current_section );
		}
	}
}

return new General();
