<?php

namespace DachcomDigital\Payum\Saferpay\Action\Api;

use DachcomDigital\Payum\Saferpay\Request\Api\InsertAlias;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use DachcomDigital\Payum\Saferpay\Api;
use DachcomDigital\Payum\Saferpay\Request\Api\CreateTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;

/**
 * Class CreateAliasAction
 * @package DachcomDigital\Payum\Saferpay\Action\Api
 * @property Api $api
 */
class CreateAliasAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;

    /**
     * CreateTransactionAction constructor.
     */
    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param $request CreateTransaction
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if ($details['token']) {
            throw new LogicException(sprintf('The alias insert token has already been created. token: %s', $details['token']));
        }

        $details->validateNotEmpty(['success_url', 'fail_url', 'abort_url']);

        $details->replace($this->api->insertAlias((array) $details));

        if (!empty($details['redirect_url'])) {
            throw new HttpRedirect($details['redirect_url']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof InsertAlias &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
