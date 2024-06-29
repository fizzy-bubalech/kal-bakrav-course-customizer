<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service\TestHelpers;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class ConfirmationTokenService extends \StellarWP\Learndash\Stripe\Service\AbstractService
{
    /**
     * Creates a test mode Confirmation Token server side for your integration tests.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\ConfirmationToken
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/test_helpers/confirmation_tokens', $params, $opts);
    }
}
