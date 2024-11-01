<?php

namespace Stalkfish\API;

use Stalkfish\Options;
use Stalkfish\Packages\Crypto\PublicKey;

/**
 * Class for handling server API requests.
 */
class StalkfishAPI {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );
	}

	/**
	 * Return a list of valid requests.
	 *
	 * @return string[]
	 */
	private function valid_requests() {
		return array(
			'ping',
			'save_credentials',
			'verify_connection',
		);
	}

	public function add_endpoint() {
		add_rewrite_endpoint( 'sf-api', EP_ALL );
	}

	/**
	 * Add new query vars.
	 *
	 * @param array $vars Query vars.
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'sf-api';
		return $vars;
	}

	protected function clean_request( $var ) {
		return sanitize_text_field( $var );
	}

	/**
	 * API output.
	 *
	 * @param mixed $out API request output, can be string or array.
	 * @param int   $code HTTP status code.
	 *
	 * @return void
	 */
	protected function json( $out = false, $code = 200 ) {
		header( 'Content-Type: application/json' );
		header( "status: $code" );

		if ( ! $out ) {
			$out = array( 'success' => true );
		}
		echo wp_json_encode( $out );
		exit;
	}

	/**
	 * Verify the request signatures.
	 *
	 * @return void
	 */
	private function verify_signature() {
		$secret    = wp_unslash( $_SERVER['HTTP_X_STALKFISH_SECRET'] ) ?? null; // phpcs:ignore
		$signature = wp_unslash( $_SERVER['HTTP_X_STALKFISH_SIGNATURE'] ) ?? null; // phpcs:ignore

		if ( empty( $secret ) || empty( $signature ) ) {
			$this->json( 'Bad request', 400 );
		}

		$key = Options::get_instance()->get( 'public_key' );

		if ( empty( $key ) ) {
			$this->json( 'Uninitialized', 406 );
		}

		$key = base64_decode( $key );

		$public_key = PublicKey::fromString( $key );

		$verified = $public_key->verify( $secret, $signature );
		if ( ! $verified ) {
			$this->json( 'Bad request', 401 );
		}
	}

	/**
	 * Handle API requests.
	 *
	 * @return void
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['sf-api'] ) ) {
			$wp->query_vars['sf-api'] = sanitize_key( wp_unslash( $_GET['sf-api'] ) );
		}

		if ( ! empty( $wp->query_vars['sf-api'] ) ) {
			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			nocache_headers();

			// Clean the API request.
			$api_request = strtolower( $this->clean_request( $wp->query_vars['sf-api'] ) );

			// Trigger generic action before request hook.
			do_action( 'stalkfish_api_request', $api_request );

			// Redirect to homepage if it is a GET request.
			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				status_header( 400 );
				wp_redirect( home_url() );
				exit;
			}

			$this->verify_signature();

			// Is there actually a method for this API request? If not trigger 400 - Bad request.
			status_header( method_exists( __CLASS__, 'handle_' . $api_request ) ? 200 : 400 );

			// Call the method fulfill the request.
			call_user_func( array( $this, 'handle_' . $api_request ) );

			// Done, clear buffer and exit.
			ob_end_clean();
			die( '-1' );
		}
	}

	/**
	 * Used to ping from the server to see if the plugin is active.
	 *
	 * @return void
	 */
	public function handle_ping() {
		$this->json( 'Stalkfish ping' );
	}

	public function handle_save_credentials() {
		$token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_STRING );
		$site_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $token || ! $site_id ) {
			$this->json( ['success' => false] );
		}

		$options = Options::get_instance();
		$options->set( 'sf_app_api_key', $token );
		$options->set( 'site_id', $site_id );

		$this->json([
			'success' => true,
		]);
	}

	public function handle_verify_connection() {
		$token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_STRING );
		$site_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $token || ! $site_id ) {
			$this->json( ['success' => false] );
		}

		if ($token !== Options::get_instance()->get( 'sf_app_api_key' ) ) {
			$this->json( ['success' => false] );
		}

		Options::get_instance()->set( 'site_id', $site_id );

		$this->json([
			'success' => true,
		]);
	}
}
