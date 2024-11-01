<?php

namespace Stalkfish\ErrorTracker\Truncation;

abstract class Abstract_Truncation_Strategy implements Truncation_Strategy
{
    /** @var Report_Trimmer */
    protected $reportTrimmer;

    public function __construct(Report_Trimmer $reportTrimmer)
    {
        $this->reportTrimmer = $reportTrimmer;
    }
}
