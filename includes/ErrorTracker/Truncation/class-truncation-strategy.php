<?php

namespace Stalkfish\ErrorTracker\Truncation;

interface Truncation_Strategy
{
    public function execute(array $payload): array;
}
