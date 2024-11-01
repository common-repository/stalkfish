<?php

namespace Stalkfish\ErrorTracker\Concerns;

trait Has_Context
{

	/** @var array */
	private $userProvidedContext = [];

	public function context($key, $value)
	{
		return $this->group('context', [$key => $value]);
	}

	public function group(string $groupName, array $properties)
	{
		$group = $this->userProvidedContext[$groupName] ?? [];

		$this->userProvidedContext[$groupName] = stalkfish_array_merge_recursive_distinct(
			$group,
			$properties
		);

		return $this;
	}
}
