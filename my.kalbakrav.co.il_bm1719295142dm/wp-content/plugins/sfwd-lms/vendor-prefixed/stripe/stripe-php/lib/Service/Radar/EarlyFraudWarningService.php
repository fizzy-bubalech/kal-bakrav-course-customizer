<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service\Radar;

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
class EarlyFraudWarningService extends \StellarWP\Learndash\Stripe\Service\AbstractService
{
    /**
     * Returns a list of early fraud warnings.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Collection<\Stripe\Radar\EarlyFraudWarning>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/radar/early_fraud_warnings', $params, $opts);
    }

    /**
     * Retrieves the details of an early fraud warning that has previously been
     * created.
     *
     * Please refer to the <a href="#early_fraud_warning_object">early fraud
     * warning</a> object reference for more details.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Radar\EarlyFraudWarning
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/radar/early_fraud_warnings/%s', $id), $params, $opts);
    }
}
