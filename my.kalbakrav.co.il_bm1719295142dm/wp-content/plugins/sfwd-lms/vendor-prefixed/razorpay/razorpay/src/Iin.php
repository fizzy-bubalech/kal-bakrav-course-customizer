<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

class Iin extends Entity
{   
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        $relativeUrl = $this->getEntityUrl(). 'list';

        return $this->request('GET', $relativeUrl, $options);
    }
}
