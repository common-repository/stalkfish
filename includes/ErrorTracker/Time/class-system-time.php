<?php

namespace Stalkfish\ErrorTracker\Time;

use DateTimeImmutable;

class System_Time implements Time {

	public function getCurrentTime(): int {
		return ( new DateTimeImmutable() )->getTimestamp();
	}
}
