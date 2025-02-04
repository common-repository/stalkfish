<?php

namespace Stalkfish\Dependencies\Facade\IgnitionContracts;

interface Solution
{
    public function getSolutionTitle() : string;
    public function getSolutionDescription() : string;
    public function getDocumentationLinks() : array;
}
