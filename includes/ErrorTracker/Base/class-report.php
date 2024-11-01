<?php

namespace Stalkfish\ErrorTracker\Base;

use Stalkfish\ErrorTracker\Concerns\Has_Context;
use Stalkfish\ErrorTracker\Concerns\Uses_Time;
use Stalkfish\ErrorTracker\Context\Context_Interface;
use Stalkfish\ErrorTracker\Contracts\Provides_Tracker_Context;
use Stalkfish\ErrorTracker\Stacktrace\Stacktrace;
use Throwable;

class Report {

	use Uses_Time;
	use Has_Context;

	/** @var Stacktrace */
	private $stacktrace;

	/** @var string */
	private $exceptionClass;

	/** @var string */
	private $message;

	/** @var Context_Interface */
	private $context;

	/** @var array */
	private $userProvidedContext = array();

	/** @var array */
	private $exceptionContext = array();

	/** @var Throwable */
	private $throwable;

	/** @var string */
	private $languageVersion;

	/** @var string */
	private $groupBy;

	/** @var string */
	private $trackingUuid;

	/** @var null string|null */
	public static $fakeTrackingUuid = null;

	public static function createForThrowable(
		Throwable $throwable,
		Context_Interface $context
	): self {
		return ( new static() )
			->throwable( $throwable )
			->useContext( $context )
			->exceptionClass( self::getClassForThrowable( $throwable ) )
			->message( $throwable->getMessage() )
			->stackTrace( Stacktrace::createForThrowable( $throwable ) )
			->exceptionContext( $throwable );
	}

	protected static function getClassForThrowable( Throwable $throwable ): string {
		if ( $throwable instanceof \Stalkfish\Dependencies\Facade\Ignition\Exceptions\ViewException ) {
			if ( $previous = $throwable->getPrevious() ) {
				return get_class( $previous );
			}
		}

		return get_class( $throwable );
	}

	public static function createForMessage( string $message, string $logLevel, Context_Interface $context ): self {
		$stacktrace = Stacktrace::create();

		return ( new static() )
			->message( $message )
			->useContext( $context )
			->exceptionClass( $logLevel )
			->stacktrace( $stacktrace );
	}

	public function __construct() {
		$this->trackingUuid = self::$fakeTrackingUuid ?? $this->generateUuid();
	}

	public function trackingUuid(): string {
		return $this->trackingUuid;
	}

	public function exceptionClass( string $exceptionClass ) {
		$this->exceptionClass = $exceptionClass;

		return $this;
	}

	public function getExceptionClass(): string {
		return $this->exceptionClass;
	}

	public function throwable( Throwable $throwable ) {
		$this->throwable = $throwable;

		return $this;
	}

	public function getThrowable(): ?Throwable {
		return $this->throwable;
	}

	public function message( string $message ) {
		$this->message = $message;

		return $this;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function stacktrace( Stacktrace $stacktrace ) {
		$this->stacktrace = $stacktrace;

		return $this;
	}

	public function useContext( Context_Interface $request ) {
		$this->context = $request;

		return $this;
	}

	public function view( ?View $view ) {
		$this->view = $view;

		return $this;
	}

	public function userProvidedContext( array $userProvidedContext ) {
		$this->userProvidedContext = $userProvidedContext;

		return $this;
	}

	public function allContext(): array {
		$context = $this->context->toArray();

		$context = stalkfish_array_merge_recursive_distinct( $context, $this->exceptionContext );

		return stalkfish_array_merge_recursive_distinct( $context, $this->userProvidedContext );
	}

	private function exceptionContext( Throwable $throwable ) {
		if ( $throwable instanceof Provides_Tracker_Context ) {
			$this->exceptionContext = $throwable->context();
		}

		return $this;
	}

	public function toArray() {

		global $wpdb;

		$user_data = array();

		if ( function_exists( '\is_user_logged_in' ) && \is_user_logged_in() ) {
			$current_user              = wp_get_current_user();
			$user_data['id']           = $current_user->ID;
			$user_data['username']     = $current_user->user_login;
			$user_data['display_name'] = $current_user->display_name;
			$user_data['avatar']       = get_avatar_url( $current_user->ID );
			$user_data['role']         = $current_user->roles[0] ?? '';
		}

		if ( ! function_exists( '\get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$wp_version     = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : null;
		$plugin_version = defined( 'STALKFISH_VERSION' ) ? STALKFISH_VERSION : null;
		$environment    = function_exists( '\wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		return array(
			'exception_class'  => $this->exceptionClass,
			'occurred_at'      => $this->getCurrentTime(),
			'message'          => $this->message,
			'stacktrace'       => $this->stacktrace->toArray(),
			'request'          => $this->allContext(),
			'runtime'          => array(
				'language'         => 'PHP',
				'language_version' => $this->languageVersion ?? phpversion(),
				'plugin_version'   => $plugin_version,
				'wp_version'       => $wp_version,
				'environment'      => $environment,
				'mysql_version'    => $wpdb->db_version() ?? null,
			),
			'user'             => $user_data,
			'application_path' => \get_home_path(),
			'tracking_uuid'    => $this->trackingUuid,
		);
	}

	/*
	* Found on https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid/15875555#15875555
	*/
	private function generateUuid(): string {
		// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
		$data = $data ?? random_bytes( 16 );

		// Set version to 0100
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
		// Set bits 6-7 to 10
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

		// Output the 36 character UUID.
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}
}
