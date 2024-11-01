<?php

namespace Stalkfish\ErrorTracker\Context;

class Context_Detector implements Context_Detector_Interface {

	public function detectCurrentContext(): Context_Interface {
		if ( $this->runningInConsole() ) {
			return new Console_Context( $_SERVER['argv'] ?? array() );
		}

		return new Request_Context();
	}

	private function runningInConsole(): bool {
		if ( isset( $_ENV['APP_RUNNING_IN_CONSOLE'] ) ) {
			return $_ENV['APP_RUNNING_IN_CONSOLE'] === 'true';
		}

		if ( isset( $_ENV['TRACKER_FAKE_WEB_REQUEST'] ) ) {
			return false;
		}

		return in_array( php_sapi_name(), array( 'cli', 'phpdb' ) );
	}
}
