<?php

namespace DachcomDigital\Payum\Saferpay\Action\Api;

use DachcomDigital\Payum\Saferpay\Request\Api\CapturePayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use DachcomDigital\Payum\Saferpay\Api;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

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

        $details->replace(
            $this->api->authorizeDirect((array)$details)
        );

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
