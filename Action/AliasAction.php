<?php

namespace DachcomDigital\Payum\Saferpay\Action;

use DachcomDigital\Payum\Saferpay\Request\Alias;
use DachcomDigital\Payum\Saferpay\Request\Api\GetAliasData;
use DachcomDigital\Payum\Saferpay\Request\Api\InsertAlias;
use League\Uri\Http as HttpUri;
use League\Uri\Modifiers\MergeQuery;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Sync;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use DachcomDigital\Payum\Saferpay\Api;
use DachcomDigital\Payum\Saferpay\Request\Api\CreateTransaction;
use DachcomDigital\Payum\Saferpay\Request\Api\CapturePayment;
use Payum\Core\Request\GetHumanStatus;

class AliasAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;
    use ApiAwareTrait;

    /**
     * CaptureAction constructor.
     */
    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        /* @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (isset($httpRequest->query['cancelled'])) {
            $details['transaction_cancelled'] = true;
        }

        if (isset($httpRequest->query['failed'])) {
            $details['transaction_failed'] = true;
        }


        if (false == $details['token']) {

            if (false == $details['success_url'] && $request->getToken()) {
                $successUrl = HttpUri::createFromString($request->getToken()->getTargetUrl());
                $modifier = new MergeQuery('success=1');
                $successUrl = $modifier->process($successUrl);
                $details['success_url'] = (string)$successUrl;
            }

            if (false == $details['fail_url'] && $request->getToken()) {
                $failedUrl = HttpUri::createFromString($request->getToken()->getTargetUrl());
                $modifier = new MergeQuery('failed=1');
                $failedUrl = $modifier->process($failedUrl);
                $details['fail_url'] = (string)$failedUrl;
            }

            if (false == $details['abort_url'] && $request->getToken()) {
                $cancelUri = HttpUri::createFromString($request->getToken()->getTargetUrl());
                $modifier = new MergeQuery('cancelled=1');
                $cancelUri = $modifier->process($cancelUri);
                $details['abort_url'] = (string)$cancelUri;
            }

            $this->gateway->execute(new InsertAlias($details));
        }


        $this->gateway->execute(new GetAliasData($details));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        if (!$request instanceof Alias ) {
            return false;
        }

        $model = $request->getModel();

        return
            $model instanceof \ArrayAccess;
    }
}
