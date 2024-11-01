<?php

namespace Stalkfish\ErrorTracker\Context;

interface Context_Detector_Interface {

	public function detectCurrentContext(): Context_Interface;
}
