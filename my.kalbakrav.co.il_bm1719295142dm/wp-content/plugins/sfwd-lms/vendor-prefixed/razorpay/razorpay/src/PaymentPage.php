<?php
/**
 * @license MIT
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\Razorpay\Api;

class PaymentPage extends Entity
{

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all($options);
    }

    public function activate($id)
    {
        $relativeUrl = $this->getEntityUrl() . $id . '/activate';

        return $this->request('PATCH', $relativeUrl);
    }

    public function deactivate($id)
    {
        $relativeUrl = $this->getEntityUrl() . $id . '/deactivate';

        return $this->request('PATCH', $relativeUrl);
    }
}