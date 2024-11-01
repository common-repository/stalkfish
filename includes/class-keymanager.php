<?php

namespace Stalkfish;

class KeyManager {
	/**
	 * @param bool $force Force generate a key.
	 * @return bool Returns true if key is set, false otherwise.
	 */
	public static function generate( bool $force = false ): bool {
		$options = Options::get_instance();

		if ( $options->has( 'public_key' ) && ! $force ) {
			return true;
		}

		$response = wp_remote_post(
			STALKFISH_APP_URL . '/api/generate-keys',
			array(
				'timeout' => 10,
				'headers' => array(
					'X-Stalkfish-Site-Url' => home_url(),
				),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$key = wp_remote_retrieve_header( $response, 'X-Stalkfish-Key' );
		if ( empty( $key ) ) {
			return false;
		}

		$options->set( 'public_key', $key );
		$options->set( 'public_key_modified_date', time() );

		$site_id = wp_remote_retrieve_header( $response, 'X-Stalkfish-Site-Id' );
		if ( ! empty( $site_id ) ) {
			$options->set( 'site_id', $site_id );
		}

		return true;
	}
}
