<?php

namespace DachcomDigital\Payum\Saferpay\Action\Api;

use DachcomDigital\Payum\Saferpay\Request\Api\AuthorizeDirectPayment;
use DachcomDigital\Payum\Saferpay\Request\Api\CapturePayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use DachcomDigital\Payum\Saferpay\Api;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

/**
 * Class AuthorizeDirectAction
 * @package DachcomDigital\Payum\Saferpay\Action\Api
 *
 * @property Api $api
 */
class AuthorizeDirectAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;

    /**
     * CapturePaymentAction constructor.
     */
    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param $request CapturePayment
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty([
            'scd_alias',
        ]);

        // transaction already captured
        if(isset($details['transaction_captured']) && $details['transaction_captured'] === true) {
            return;
        }

        // transaction already authorized
        if(isset($details['transaction_authorized']) && $details['transaction_authorized'] === true) {
            return;
        }

        $details->replace(
            $this->api->authorizeDirect((array)$details)
        );

        if (isset($details['error'])) {
            $details['transaction_failed'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof AuthorizeDirectPayment &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
