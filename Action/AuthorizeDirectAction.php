<?php

namespace DachcomDigital\Payum\Saferpay\Action;

use DachcomDigital\Payum\Saferpay\Request\Api\AuthorizeDirectPayment;
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

class AuthorizeDirectAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
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

        $this->gateway->execute(new AuthorizeDirectPayment($details));

        $this->gateway->execute($status = new GetHumanStatus($request->getToken()));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        if (!$request instanceof Capture ) {
            return false;
        }

        $model = $request->getModel();

        return
            $model instanceof \ArrayAccess &&
            isset($model['scd_alias']);
    }
}
