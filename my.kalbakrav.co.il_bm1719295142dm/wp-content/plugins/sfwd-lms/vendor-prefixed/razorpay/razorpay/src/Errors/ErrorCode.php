<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api\Errors;

class ErrorCode
{
    const BAD_REQUEST_ERROR                 = 'BAD_REQUEST_ERROR';
    const SERVER_ERROR                      = 'SERVER_ERROR';
    const GATEWAY_ERROR                     = 'GATEWAY_ERROR';

    public static function exists($code)
    {
        $code = strtoupper($code);

        return defined(get_class() . '::' . $code);
    }
}