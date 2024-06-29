<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe;

/**
 * Issue a credit note to adjust an invoice's amount after the invoice is finalized.
 *
 * Related guide: <a href="https://stripe.com/docs/billing/invoices/credit-notes">Credit notes</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount The integer amount in cents (or local equivalent) representing the total amount of the credit note, including tax.
 * @property int $amount_shipping This is the sum of all the shipping amounts.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property string|\StellarWP\Learndash\Stripe\Customer $customer ID of the customer.
 * @property null|string|\StellarWP\Learndash\Stripe\CustomerBalanceTransaction $customer_balance_transaction Customer balance transaction related to this credit note.
 * @property int $discount_amount The integer amount in cents (or local equivalent) representing the total amount of discount that was credited.
 * @property \StellarWP\Learndash\Stripe\StripeObject[] $discount_amounts The aggregate amounts calculated per discount for all line items.
 * @property null|int $effective_at The date when this credit note is in effect. Same as <code>created</code> unless overwritten. When defined, this value replaces the system-generated 'Date of issue' printed on the credit note PDF.
 * @property string|\StellarWP\Learndash\Stripe\Invoice $invoice ID of the invoice.
 * @property \StellarWP\Learndash\Stripe\Collection<\Stripe\CreditNoteLineItem> $lines Line items that make up the credit note
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string $memo Customer-facing text that appears on the credit note PDF.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property string $number A unique number that identifies this particular credit note and appears on the PDF of the credit note and its associated invoice.
 * @property null|int $out_of_band_amount Amount that was credited outside of Stripe.
 * @property string $pdf The link to download the PDF of the credit note.
 * @property null|string $reason Reason for issuing this credit note, one of <code>duplicate</code>, <code>fraudulent</code>, <code>order_change</code>, or <code>product_unsatisfactory</code>
 * @property null|string|\StellarWP\Learndash\Stripe\Refund $refund Refund related to this credit note.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $shipping_cost The details of the cost of shipping, including the ShippingRate applied to the invoice.
 * @property string $status Status of this credit note, one of <code>issued</code> or <code>void</code>. Learn more about <a href="https://stripe.com/docs/billing/invoices/credit-notes#voiding">voiding credit notes</a>.
 * @property int $subtotal The integer amount in cents (or local equivalent) representing the amount of the credit note, excluding exclusive tax and invoice level discounts.
 * @property null|int $subtotal_excluding_tax The integer amount in cents (or local equivalent) representing the amount of the credit note, excluding all tax and invoice level discounts.
 * @property \StellarWP\Learndash\Stripe\StripeObject[] $tax_amounts The aggregate amounts calculated per tax rate for all line items.
 * @property int $total The integer amount in cents (or local equivalent) representing the total amount of the credit note, including tax and all discount.
 * @property null|int $total_excluding_tax The integer amount in cents (or local equivalent) representing the total amount of the credit note, excluding tax, but including discounts.
 * @property string $type Type of this credit note, one of <code>pre_payment</code> or <code>post_payment</code>. A <code>pre_payment</code> credit note means it was issued when the invoice was open. A <code>post_payment</code> credit note means it was issued when the invoice was paid.
 * @property null|int $voided_at The time that the credit note was voided.
 *
 * @license MIT
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
class CreditNote extends ApiResource
{
    const OBJECT_NAME = 'credit_note';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const REASON_DUPLICATE = 'duplicate';
    const REASON_FRAUDULENT = 'fraudulent';
    const REASON_ORDER_CHANGE = 'order_change';
    const REASON_PRODUCT_UNSATISFACTORY = 'product_unsatisfactory';

    const STATUS_ISSUED = 'issued';
    const STATUS_VOID = 'void';

    const TYPE_POST_PAYMENT = 'post_payment';
    const TYPE_PRE_PAYMENT = 'pre_payment';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\CreditNote the previewed credit note
     */
    public static function preview($params = null, $opts = null)
    {
        $url = static::classUrl() . '/preview';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \StellarWP\Learndash\Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Collection<\Stripe\CreditNoteLineItem> list of credit note line items
     */
    public static function previewLines($params = null, $opts = null)
    {
        $url = static::classUrl() . '/preview/lines';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \StellarWP\Learndash\Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\CreditNote the voided credit note
     */
    public function voidCreditNote($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/void';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }

    const PATH_LINES = '/lines';

    /**
     * @param string $id the ID of the credit note on which to retrieve the credit note line items
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Collection<\Stripe\CreditNoteLineItem> the list of credit note line items
     */
    public static function allLines($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_LINES, $params, $opts);
    }
}
