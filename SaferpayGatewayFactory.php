<?php

namespace DachcomDigital\Payum\Saferpay;

use DachcomDigital\Payum\Saferpay\Action\AliasAction;
use DachcomDigital\Payum\Saferpay\Action\Api\CapturePaymentAction;
use DachcomDigital\Payum\Saferpay\Action\Api\CreateAliasAction;
use DachcomDigital\Payum\Saferpay\Action\Api\CreateTransactionAction;
use DachcomDigital\Payum\Saferpay\Action\Api\GetAliasDataAction;
use DachcomDigital\Payum\Saferpay\Action\Api\GetTransactionDataAction;
use DachcomDigital\Payum\Saferpay\Action\Api\RefundTransactionAction;
use DachcomDigital\Payum\Saferpay\Action\AuthorizeDirectAction;
use DachcomDigital\Payum\Saferpay\Action\CaptureAction;
use DachcomDigital\Payum\Saferpay\Action\ConvertPaymentAction;
use DachcomDigital\Payum\Saferpay\Action\NotifyAction;
use DachcomDigital\Payum\Saferpay\Action\RefundAction;
use DachcomDigital\Payum\Saferpay\Action\StatusAction;
use DachcomDigital\Payum\Saferpay\Action\SyncAction;
use DachcomDigital\Payum\Saferpay\Request\Api\GetAliasData;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class SaferpayGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'  => 'saferpay',
            'payum.factory_title' => 'Saferpay',

            'payum.action.capture'         => new CaptureAction(),
            'payum.action.authorize_direct' => new AuthorizeDirectAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.notify'          => new NotifyAction($config['payum.security.token_storage']),
            'payum.action.sync'            => new SyncAction(),
            'payum.action.refund'          => new RefundAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.alias' => new AliasAction(),

            'payum.action.api.create_transaction'   => new CreateTransactionAction(),
            'payum.action.api.get_transaction_data' => new GetTransactionDataAction(),
            'payum.action.api.refund_transaction'   => new RefundTransactionAction(),
            'payum.action.api.capture_payment'      => new CapturePaymentAction(),
            'payum.action.api.authorize_direct'      => new \DachcomDigital\Payum\Saferpay\Action\Api\AuthorizeDirectAction(),
            'payum.action.api.create_alias'      => new CreateAliasAction(),
            'payum.action.api.get_alias_data'      => new GetAliasDataAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'specVersion'        => '1.8', //https://saferpay.github.io/jsonapi/index.html
                'optionalParameters' => [],
                'scdEnabled'         => false,
                'sandbox'            => true,
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [
                'username',
                'password',
                'specVersion',
                'customerId',
                'terminalId',
                'lockPath'
            ];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    [
                        'sandbox'         => $config['sandbox'],
                        'username'        => $config['username'],
                        'password'        => $config['password'],
                        'spec_version'    => $config['specVersion'],
                        'customer_id'     => $config['customerId'],
                        'terminal_id'     => $config['terminalId'],
                        'lock_path'       => $config['lockPath'],
                        'scd_enabled'     => $config['scdEnabled'],
                        'optional_params' => isset($config['optionalParameters']) && is_array($config['optionalParameters']) ? $config['optionalParameters'] : []
                    ],
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };
        }
    }
}
