<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

use Requests;


class FundAccount extends Entity
{
    /**
     * Create a Fund Account .
     *
     * @param array $attributes
     *
     * @return FundAccount
     */
    public function create($attributes = array())
    {
       return parent::create($attributes);
    }

    /**
     * Fetch all Fund Accounts
     *
     * @param array $options
     *
     * @return Collection
     */
    public function all($options = array())
    {
        return parent::all($options);
    }

}
