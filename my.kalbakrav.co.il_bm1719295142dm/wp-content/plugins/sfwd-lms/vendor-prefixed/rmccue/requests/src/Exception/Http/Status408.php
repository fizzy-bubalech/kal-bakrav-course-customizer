<?php
/**
 * Exception for 408 Request Timeout responses
 *
 * @package Requests\Exceptions
 *
 * @license ISC
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\WpOrg\Requests\Exception\Http;

use StellarWP\Learndash\WpOrg\Requests\Exception\Http;

/**
 * Exception for 408 Request Timeout responses
 *
 * @package Requests\Exceptions
 */
final class Status408 extends Http {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 408;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Request Timeout';
}
