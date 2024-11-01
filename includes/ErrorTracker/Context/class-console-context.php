<?php

namespace Stalkfish\ErrorTracker\Context;

class Console_Context implements Context_Interface {

	/** @var array */
	private $arguments = array();

	public function __construct( array $arguments = array() ) {
		$this->arguments = $arguments;
	}

	public function toArray(): array {
		return array(
			'arguments' => $this->arguments,
		);
	}
}
