<?php
/**
 * APIs.
 *
 * @package Stalkfish
 */

namespace Stalkfish\API;

use Stalkfish\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Local APIs.
 *
 * @package Stalkfish\API
 */
class Local {
	/**
	 * Holds the plugin instance.
	 *
	 * @var Local
	 * @access protected
	 * @static
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
	 * Local constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Register API endpoints.
	 *
	 * @return void
	 */
	public function register_endpoints() {
		register_rest_route(
			'stalkfish/v1',
			'/api-key',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_api_key' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'key' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Stalkfish API key. Available at stalkfish.com', 'stalkfish' ),
					),
				),
			)
		);

		register_rest_route(
			'stalkfish/v1',
			'/triggers',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_triggers' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @return WP_Error|bool
	 */
	public function rest_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Update API key.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_api_key( WP_REST_Request $request ) {
		Options::get_instance()->set( 'sf_app_api_key', $request->get_param( 'key' ) );

		return new WP_REST_Response( array( 'status' => true ), 200 );
	}

	/**
	 * Get pipe triggers.
	 */
	public function get_triggers() {
		$triggers = stalkfish_get_instance()->pipes->triggers;

		return new WP_REST_Response( $triggers, 200 );
	}
}
