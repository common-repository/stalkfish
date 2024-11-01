<?php

namespace Stalkfish\ErrorTracker\Base;

use Exception;
use Stalkfish\ErrorTracker\Http\Client;
use Stalkfish\ErrorTracker\Truncation\Report_Trimmer;

class Api {

	/** @var Client */
	protected $client;

	/** @var bool */
	public static $sendInBatches = true;

	/** @var array */
	protected $queue = array();

	public function __construct( Client $client ) {
		$this->client = $client;

		register_shutdown_function( array( $this, 'sendQueuedReports' ) );
	}

	public static function sendReportsInBatches( bool $batchSending = true ) {
		static::$sendInBatches = $batchSending;
	}

	public function report( Report $report ) {
		try {
			if ( static::$sendInBatches ) {
				$this->addReportToQueue( $report );
			} else {
				$this->sendReportToApi( $report );
			}
		} catch ( Exception $e ) {
		}
	}

	protected function addReportToQueue( Report $report ) {
		$this->queue[] = $report;
	}

	public function sendQueuedReports() {
		try {
			foreach ( $this->queue as $report ) {
				$this->sendReportToApi( $report );
			}
		} catch ( Exception $e ) {
		} finally {
			$this->queue = array();
		}
	}

	protected function sendReportToApi( Report $report ) {
		$this->client->post( 'errors', $this->truncateReport( $report->toArray() ) );
	}

	protected function truncateReport( array $payload ): array {
		return ( new Report_Trimmer() )->trim( $payload );
	}
}
