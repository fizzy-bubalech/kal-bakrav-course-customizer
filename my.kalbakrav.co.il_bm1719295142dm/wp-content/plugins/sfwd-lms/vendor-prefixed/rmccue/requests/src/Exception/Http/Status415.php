<?php
/**
 * Exception for 415 Unsupported Media Type responses
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
 * Exception for 415 Unsupported Media Type responses
 *
 * @package Requests\Exceptions
 */
final class Status415 extends Http {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 415;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Unsupported Media Type';
}
