<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

class Document extends Entity
{

    public function create($attributes = array())
    {
        $attributes = $this->setFile($attributes);

        return parent::create($attributes);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

}
