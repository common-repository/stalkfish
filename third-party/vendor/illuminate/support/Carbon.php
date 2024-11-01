<?php

namespace Stalkfish\Dependencies\Illuminate\Support;

use Stalkfish\Dependencies\Carbon\Carbon as BaseCarbon;
use Stalkfish\Dependencies\Carbon\CarbonImmutable as BaseCarbonImmutable;
class Carbon extends BaseCarbon
{
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
}
