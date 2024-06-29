<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

class Card extends Entity
{
    /**
     * @param $id Card id
     */
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function requestCardReference($attributes = array())
    {
        $entityUrl = $this->getEntityUrl() . '/fingerprints';

        return $this->request('POST', $entityUrl, $attributes);
    }
}
