<?php

namespace Stalkfish\ErrorTracker;

use Error;
use ErrorException;
use Exception;
use Stalkfish\ErrorTracker\Base\Api;
use Stalkfish\ErrorTracker\Base\Report;
use Stalkfish\ErrorTracker\Concerns\Has_Context;
use Stalkfish\ErrorTracker\Context\Context_Detector;
use Stalkfish\ErrorTracker\Context\Context_Detector_Interface;
use Stalkfish\ErrorTracker\Http\Client;
use Stalkfish\Dependencies\Illuminate\Contracts\Container\Container;
use Stalkfish\Dependencies\Illuminate\Pipeline\Pipeline;
use Throwable;

/**
 * Class Init.
 */
class Init {

	use Has_Context;

	/**
	 * Client object.
	 *
	 * @var Client
	 */
	protected $client;

	/**
	 * Api object.
	 *
	 * @var Api
	 */
	protected $api;

	/**
	 * Middlewares array.
	 *
	 * @var array
	 */
	protected $middleware = array();

	/**
	 * Container object.
	 *
	 * @var Container|null
	 */
	protected $container;

	/**
	 * Context Detector Interface object.
	 *
	 * @var Context_Detector_Interface
	 */
	protected $contextDetector;

	/**
	 * Previous exception handler.
	 *
	 * @var callable|null
	 */
	protected $previousExceptionHandler;

	/**
	 * Previous error handler.
	 *
	 * @var callable|null
	 */
	protected $previousErrorHandler;

	/**
	 * Report error levels.
	 *
	 * @var int|null
	 */
	protected $reportErrorLevels;
	/**
	 * Filter exceptions.
	 *
	 * @var callable|null
	 */
	protected $filterExceptionsCallable;

	/**
	 * Register and instantiate error client.
	 *
	 * @param string                        $apiKey App API key.
	 * @param Context_Detector_Interface|null $contextDetector Context detector.
	 * @param Container|null                $container Container instance.
	 *
	 * @return static
	 * @throws Http\Exceptions\Missing_Parameter
	 */
	public static function register( string $apiKey, string $apiUrl = '', Context_Detector_Interface $contextDetector = null, Container $container = null ) {
		$client = new Client( $apiKey, $apiUrl );

		return new static( $client, $contextDetector, $container );
	}

	/**
	 * Constructor
	 *
	 * @param Client                        $client Client object.
	 * @param Context_Detector_Interface|null $contextDetector Context detector.
	 * @param Container|null                $container Container instance.
	 * @param array                         $middleware Middlewares.
	 */
	public function __construct( Client $client, Context_Detector_Interface $contextDetector = null, Container $container = null, array $middleware = array() ) {
		$this->client          = $client;
		$this->contextDetector = $contextDetector ?? new Context_Detector();
		$this->container       = $container;
		$this->middleware      = $middleware;
		$this->api             = new Api( $this->client );
	}

	/**
	 * Registers tracker handlers.
	 *
	 * @return $this
	 */
	public function registerTrackerHandlers() {
		$this->registerExceptionHandler();
		$this->registerErrorHandler();

		return $this;
	}

	/**
	 * Register exception handler.
	 *
	 * @return $this
	 */
	public function registerExceptionHandler() {
		$this->previousExceptionHandler = set_exception_handler( array( $this, 'handleException' ) );

		return $this;
	}

	/**
	 * Register exception handler.
	 *
	 * @return $this
	 */
	public function registerErrorHandler() {

		$this->previousErrorHandler = set_error_handler( array( $this, 'handleError' ) );

		return $this;
	}

	/**
	 * Handles exception.
	 *
	 * @param Throwable $throwable
	 *
	 * @return void
	 */
	public function handleException( Throwable $throwable ) {
		$this->report( $throwable );

		if ( $this->previousExceptionHandler ) {
			call_user_func( $this->previousExceptionHandler, $throwable );
		}
	}

	/**
	 * Handles error.
	 *
	 * @param $code
	 * @param $message
	 * @param $file
	 * @param $line
	 *
	 * @return mixed|void
	 */
	public function handleError( $code, $message, $file = '', $line = 0 ) {
		$exception = new ErrorException( $message, 0, $code, $file, $line );

		$this->report( $exception );

		if ( $this->previousErrorHandler ) {
			return call_user_func(
				$this->previousErrorHandler,
				$message,
				$code,
				$file,
				$line
			);
		}
	}

	/**
	 * Report handler.
	 *
	 * @param Throwable $throwable
	 * @param callable|null $callback
	 *
	 * @return Report|null
	 */
	public function report( Throwable $throwable, callable $callback = null ): ?Report {
		if ( ! $this->shouldSendReport( $throwable ) ) {
			return null;
		}

		$report = $this->createReport( $throwable );

		if ( ! is_null( $callback ) ) {
			call_user_func( $callback, $report );
		}

		$this->sendReportToApi( $report );

		return $report;
	}

	/**
	 * Check if report should be sent.
	 *
	 * @param Throwable $throwable
	 *
	 * @return bool
	 */
	protected function shouldSendReport( Throwable $throwable ): bool {
		if ( $this->reportErrorLevels && $throwable instanceof Error ) {
			return $this->reportErrorLevels & $throwable->getCode();
		}

		if ( $this->reportErrorLevels && $throwable instanceof ErrorException ) {
			return $this->reportErrorLevels & $throwable->getSeverity();
		}

		if ( $this->filterExceptionsCallable && $throwable instanceof Exception ) {
			return call_user_func( $this->filterExceptionsCallable, $throwable );
		}

		return true;
	}

	/**
	 * Sends report to API.
	 *
	 * @param Report $report
	 *
	 * @return void
	 */
	private function sendReportToApi( Report $report ) {
		try {
			$this->api->report( $report );
		} catch ( Exception $exception ) {
		}
	}

	/**
	 * Add additional params to report.
	 *
	 * @param Report $report
	 *
	 * @return void
	 */
	private function applyAdditionalParameters( Report $report ) {
		$report
			->userProvidedContext( $this->userProvidedContext );
	}

	/**
	 * Creates report.
	 *
	 * @param Throwable $throwable
	 *
	 * @return Report
	 */
	public function createReport( Throwable $throwable ): Report {
		$report = Report::createForThrowable(
			$throwable,
			$this->contextDetector->detectCurrentContext()
		);

		return $this->applyMiddlewareToReport( $report );
	}

	/**
	 * Apply middleware to report.
	 *
	 * @param Report $report
	 *
	 * @return Report
	 */
	protected function applyMiddlewareToReport( Report $report ): Report {
		$this->applyAdditionalParameters( $report );

		$report = ( new Pipeline( $this->container ) )
			->send( $report )
			->through( $this->middleware )
			->then(
				function ( $report ) {
					return $report;
				}
			);

		return $report;
	}
}
