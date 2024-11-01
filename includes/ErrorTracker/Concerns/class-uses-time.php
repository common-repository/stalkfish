<?php

namespace Stalkfish\ErrorTracker\Concerns;

use Stalkfish\ErrorTracker\Time\System_Time;
use Stalkfish\ErrorTracker\Time\Time;

trait Uses_Time {

	/** @var Time */
	public static $time;

	public static function useTime( Time $time ) {
		self::$time = $time;
	}

	public function getCurrentTime(): int {
		$time = self::$time ?? new System_Time();

		return $time->getCurrentTime();
	}
}
