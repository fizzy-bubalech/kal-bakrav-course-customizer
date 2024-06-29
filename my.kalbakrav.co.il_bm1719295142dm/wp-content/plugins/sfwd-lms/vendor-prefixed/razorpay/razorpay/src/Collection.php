<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

use Countable;

class Collection extends Entity implements Countable
{
    public function count():int
    {
        $count = 0;

        if (isset($this->attributes['count']))
        {
            return $this->attributes['count'];
        }

        return $count;
    }
}
