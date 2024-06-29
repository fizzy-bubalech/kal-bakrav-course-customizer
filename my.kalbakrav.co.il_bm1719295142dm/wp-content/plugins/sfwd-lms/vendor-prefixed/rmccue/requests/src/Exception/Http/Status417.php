<?php
/**
 * Exception for 417 Expectation Failed responses
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
 * Exception for 417 Expectation Failed responses
 *
 * @package Requests\Exceptions
 */
final class Status417 extends Http {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 417;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Expectation Failed';
}
