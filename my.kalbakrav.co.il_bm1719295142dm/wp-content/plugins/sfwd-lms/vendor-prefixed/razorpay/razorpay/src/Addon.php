<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

class Addon extends Entity
{
    // To create an Addon,
    // use the createAddon method of the Subscription class

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function delete()
    {
        $entityUrl = $this->getEntityUrl();

        return $this->request('DELETE', $entityUrl . $this->id);
    }

    public function fetchAll($attributes = array())
    {
        $entityUrl = $this->getEntityUrl(); 

        return $this->request('GET', $entityUrl , $attributes);
    }
}
