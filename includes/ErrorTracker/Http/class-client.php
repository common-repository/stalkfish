<?php

namespace Stalkfish\ErrorTracker\Http;

use Stalkfish\ErrorTracker\Http\Exceptions\Bad_Response_Code;
use Stalkfish\ErrorTracker\Http\Exceptions\Invalid_Data;
use Stalkfish\ErrorTracker\Http\Exceptions\Missing_Parameter;
use Stalkfish\ErrorTracker\Http\Exceptions\Not_Found;

class Client {

	/** @var null|string */
	private $apiToken;

	/** @var string */
	private $baseUrl;

	/** @var int */
	private $timeout;

	public function __construct(
		?string $apiToken,
		string $baseUrl,
		int $timeout = 10
	) {
		$this->apiToken = $apiToken;

		if ( ! $baseUrl ) {
			$this->baseUrl = defined( 'STALKFISH_APP_URL' ) ? STALKFISH_APP_URL : 'https://app.stalkfish.com';
		}

		if ( ! $timeout ) {
			throw Missing_Parameter::create( 'timeout' );
		}

		$this->timeout = $timeout;
	}

	/**
	 * @param string $url
	 * @param array  $arguments
	 *
	 * @return array|false
	 */
	public function get( string $url, array $arguments = array() ) {
		return $this->makeRequest( 'get', $url, $arguments );
	}

	/**
	 * @param string $url
	 * @param array  $arguments
	 *
	 * @return array|false
	 */
	public function post( string $url, array $arguments = array() ) {
		return $this->makeRequest( 'post', $url, $arguments );
	}

	/**
	 * @param string $url
	 * @param array  $arguments
	 *
	 * @return array|false
	 */
	public function patch( string $url, array $arguments = array() ) {
		return $this->makeRequest( 'patch', $url, $arguments );
	}

	/**
	 * @param string $url
	 * @param array  $arguments
	 *
	 * @return array|false
	 */
	public function put( string $url, array $arguments = array() ) {
		return $this->makeRequest( 'put', $url, $arguments );
	}

	/**
	 * @param string $method
	 * @param array  $arguments
	 *
	 * @return array|false
	 */
	public function delete( string $method, array $arguments = array() ) {
		return $this->makeRequest( 'delete', $method, $arguments );
	}

	/**
	 * @param $data
	 *
	 * @return mixed|string
	 */
	public function utf8ize( $data ) {
		if ( is_array( $data ) )
			foreach ( $data as $key => $value )
				$data[$key] = $this->utf8ize( $value );

		else if( is_object( $data ) )
			foreach ( $data as $key => $value )
				$data->$key = $this->utf8ize( $value );

		else
			return utf8_encode( $data );

		return $data;
	}


	/**
	 * @param string $httpVerb
	 * @param string $url
	 * @param array  $arguments
	 *
	 * @return array
	 */
	private function makeRequest( string $httpVerb, string $url, array $arguments = array() ) {
		if ( ! empty( $arguments ) && 'map_meta_cap' === $arguments['stacktrace'][2]['method'] ) return;

		$last_error = get_transient( 'stalkfish_last_error' );

		if ( ! empty( $last_error ) && $last_error['occurred_at'] <= $arguments['occurred_at'] + 1 ) {
			if ( $last_error['exception_class'] === $arguments['exception_class'] && ( $last_error['stacktrace'][0]['file'] === $arguments['stacktrace'][0]['file'] && $last_error['stacktrace'][0]['method'] === $arguments['stacktrace'][0]['method'] ) ) {
				return;
			}
		}

		$fullUrl = "{$this->baseUrl}/api/{$url}";

		$headers = array(
			'Authorization: Bearer ' . $this->apiToken,
		);

		$response = $this->makeCurlRequest( $httpVerb, $fullUrl, $headers, $arguments );

		if ( $response->getHttpResponseCode() === 422 ) {
			throw Invalid_Data::createForResponse( $response );
		}

		if ( $response->getHttpResponseCode() === 404 ) {
			throw Not_Found::createForResponse( $response );
		}

		if ( $response->getHttpResponseCode() !== 200 && $response->getHttpResponseCode() !== 204 ) {
			throw Bad_Response_Code::createForResponse( $response );
		}

		set_transient( 'stalkfish_last_error', $arguments, 1 );

		return $response->getBody();
	}

	public function makeCurlRequest( string $httpVerb, string $fullUrl, array $headers = array(), array $arguments = array() ): Response {

		$curlHandle = $this->getCurlHandle( $fullUrl, $headers );
		switch ( $httpVerb ) {
			case 'post':
				curl_setopt( $curlHandle, CURLOPT_POST, true );
				$this->attachRequestPayload( $curlHandle, $arguments );

				break;

			case 'get':
				curl_setopt( $curlHandle, CURLOPT_URL, $fullUrl . '&' . http_build_query( $arguments ) );

				break;

			case 'delete':
				curl_setopt( $curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE' );

				break;

			case 'patch':
				curl_setopt( $curlHandle, CURLOPT_CUSTOMREQUEST, 'PATCH' );
				$this->attachRequestPayload( $curlHandle, $arguments );

				break;

			case 'put':
				curl_setopt( $curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT' );
				$this->attachRequestPayload( $curlHandle, $arguments );

				break;
		}

		$body    = json_decode( curl_exec( $curlHandle ), true );
		$headers = curl_getinfo( $curlHandle );
		$error   = curl_error( $curlHandle );

		return new Response( $headers, $body, $error );
	}

	private function attachRequestPayload( &$curlHandle, array $data ) {
		$encoded = $this->utf8ize( $data );
		$encoded = json_encode( $encoded );

		$this->lastRequest['body'] = $encoded;
		curl_setopt( $curlHandle, CURLOPT_POSTFIELDS, $encoded );
	}

	/**
	 * @param string $fullUrl
	 * @param array  $headers
	 *
	 * @return resource
	 */
	private function getCurlHandle( string $fullUrl, array $headers = array() ) {
		$curlHandle = curl_init();

		curl_setopt( $curlHandle, CURLOPT_URL, $fullUrl );

		curl_setopt(
			$curlHandle,
			CURLOPT_HTTPHEADER,
			array_merge(
				array(
					'Accept: application/json',
					'Content-Type: application/json',
				),
				$headers
			)
		);

		curl_setopt( $curlHandle, CURLOPT_USERAGENT, 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) );
		curl_setopt( $curlHandle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curlHandle, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $curlHandle, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		curl_setopt( $curlHandle, CURLOPT_ENCODING, '' );
		curl_setopt( $curlHandle, CURLINFO_HEADER_OUT, true );
		curl_setopt( $curlHandle, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curlHandle, CURLOPT_MAXREDIRS, 1 );

		return $curlHandle;
	}
}
