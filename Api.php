<?php

namespace DachcomDigital\Payum\Saferpay;

use DachcomDigital\Payum\Saferpay\Handler\LockHandler;
use DachcomDigital\Payum\Saferpay\Handler\RequestHandler;
use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var RequestHandler
     */
    protected $requestHandler;

    /**
     * @var LockHandler
     */
    protected $lockHandler;

    const TEST = 'test';

    const PRODUCTION = 'production';

    protected $options = [
        'environment' => self::TEST,
        'scd_enabled' => false,
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'username',
            'password',
            'spec_version',
            'customer_id',
            'terminal_id',
            'lock_path'
        ]);

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        if (false == is_dir($options['lock_path'])) {
            throw new LogicException(sprintf('%s is not a valid lock_path'));
        }

        $this->options = $options;
        $this->requestHandler = new RequestHandler($client, $messageFactory, $this->options);
        $this->lockHandler = new LockHandler($this->options['lock_path']);
    }

    /**
     * @return LockHandler
     */
    public function getLockHandler()
    {
        return $this->lockHandler;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function createTransaction(array $fields)
    {
        $response = $this->requestHandler->createTransactionRequest($fields);

        return array_filter([
            'error'        => $response['has_error'] === true ? (isset($response['data']) ? $response['data'] : true) : false,
            'token'        => $response['token'],
            'redirect_url' => $response['redirect_url'],
        ]);
    }

    /**
     * @param $token
     *
     * @return array
     */
    public function authorizeDirect(array $fields)
    {
        $response = $this->requestHandler->createAuthorizeDirectRequest($fields);

        $params = [];

        if ($response['has_error'] === true) {
            $params['error'] = isset($response['error']) ? $response['error'] : true;
        } else {

            $transaction = isset($response['transaction'])  ? $response['transaction'] : [];
            $params['transaction_type'] = isset($transaction['Type']) ? $transaction['Type'] : null;
            $params['transaction_status'] = isset($transaction['Status']) ? $transaction['Status'] : null;
            $params['transaction_id'] = isset($transaction['Id'])  ? $transaction['Id'] : null;
            $params['transaction_date'] = isset($transaction['Date'])  ? $transaction['Date'] : null;

            $params['transaction_amount'] = isset($transaction['Amount']['Value'])  ? $transaction['Amount']['Value'] : null;
            $params['transaction_currency_code'] = isset($transaction['Amount']['CurrencyCode'])  ? $transaction['Amount']['CurrencyCode'] : null;

            $params['transaction_acquirer_name'] = isset($transaction['AcquirerName'])  ? $transaction['AcquirerName'] : null;
            $params['transaction_acquirer_reference'] = isset($transaction['AcquirerReference'])  ? $transaction['AcquirerReference'] : null;
            $params['transaction_six_transaction_reference'] = isset($transaction['SixTransactionReference'])  ? $transaction['SixTransactionReference'] : null;
            $params['transaction_approval_code'] = isset($transaction['ApprovalCode'])  ? $transaction['ApprovalCode'] : null;



            $paymentMeans = isset($response['payment_means'])  ? $response['payment_means'] : [];
            $params['payment_means_brand_payment_method'] = isset($paymentMeans['Brand']['PaymentMethod'])  ? $paymentMeans['Brand']['PaymentMethod'] : null;
            $params['payment_means_brand_name'] = isset($paymentMeans['Brand']['Name'])  ? $paymentMeans['Brand']['Name'] : null;

            $params['payment_means_display_text'] = isset($paymentMeans['DisplayText']) ? $paymentMeans['DisplayText'] : null;
            $params['payment_means_wallet'] = isset($paymentMeans['Wallet']) ? $paymentMeans['Wallet'] : null;

            $params['payment_means_cart_masked_number'] = isset($paymentMeans['Card']['MaskedNumber'])  ? $paymentMeans['Card']['MaskedNumber'] : null;
            $params['payment_means_cart_exp_year'] = isset($paymentMeans['Card']['ExpYear'])  ? $paymentMeans['Card']['ExpYear'] : null;
            $params['payment_means_cart_exp_month'] = isset($paymentMeans['Card']['ExpMonth'])  ? $paymentMeans['Card']['ExpMonth'] : null;
            $params['payment_means_cart_holder_name'] = isset($paymentMeans['Card']['HolderName'])  ? $paymentMeans['Card']['HolderName'] : null;
            $params['payment_means_cart_hash_value'] = isset($paymentMeans['Card']['HashValue'])  ? $paymentMeans['Card']['HashValue'] : null;

            $params['payment_means_bank_account_iban'] = isset($paymentMeans['BankAccount']['IBAN'])  ? $paymentMeans['BankAccount']['IBAN'] : null;
            $params['payment_means_bank_account_holder_name'] = isset($paymentMeans['BankAccount']['HolderName'])  ? $paymentMeans['BankAccount']['HolderName'] : null;
            $params['payment_means_bank_account_bic'] = isset($paymentMeans['BankAccount']['BIC'])  ? $paymentMeans['BankAccount']['BIC'] : null;
            $params['payment_means_bank_account_bank_name'] = isset($paymentMeans['BankAccount']['BankName'])  ? $paymentMeans['BankAccount']['BankName'] : null;
            $params['payment_means_bank_account_country_code'] = isset($paymentMeans['BankAccount']['CountryCode'])  ? $paymentMeans['BankAccount']['CountryCode'] : null;

            $payer = isset($response['payer'])  ? $response['payer'] : [];
            $params['payment_payer_ip_address'] = isset($payer['IpAddress'])  ? $payer['IpAddress'] : null;
            $params['payment_payer_ip_location'] = isset($payer['IpLocation'])  ? $payer['IpLocation'] : null;

            $registrationResult = isset($response['registration_result'])  ? $response['registration_result'] : [];
            if (isset($registrationResult['Success']) && $registrationResult['Success'] === true) {
                $params['payment_registration_alias_id'] = isset($registrationResult['Alias']['Id'])  ? $registrationResult['Alias']['Id'] : null;
                $params['payment_registration_alias_lifetime'] = isset($registrationResult['Alias']['Lifetime'])  ? $registrationResult['Alias']['Lifetime'] : null;
            }

            $params['transaction_authorized'] = true;
        }

        return array_filter($params);
    }

    /**
     * @param $token
     *
     * @return array
     */
    public function getTransactionData($token)
    {
        $response = $this->requestHandler->createTransactionAssertRequest($token);

        $params = [];

        if ($response['has_error'] === true) {
            $params['error'] = isset($response['error']) ? $response['error'] : true;
        } else {

            $transaction = isset($response['transaction'])  ? $response['transaction'] : [];
            $params['transaction_type'] = isset($transaction['Type']) ? $transaction['Type'] : null;
            $params['transaction_status'] = isset($transaction['Status']) ? $transaction['Status'] : null;
            $params['transaction_id'] = isset($transaction['Id'])  ? $transaction['Id'] : null;
            $params['transaction_date'] = isset($transaction['Date'])  ? $transaction['Date'] : null;

            $params['transaction_amount'] = isset($transaction['Amount']['Value'])  ? $transaction['Amount']['Value'] : null;
            $params['transaction_currency_code'] = isset($transaction['Amount']['CurrencyCode'])  ? $transaction['Amount']['CurrencyCode'] : null;

            $params['transaction_acquirer_name'] = isset($transaction['AcquirerName'])  ? $transaction['AcquirerName'] : null;
            $params['transaction_acquirer_reference'] = isset($transaction['AcquirerReference'])  ? $transaction['AcquirerReference'] : null;
            $params['transaction_six_transaction_reference'] = isset($transaction['SixTransactionReference'])  ? $transaction['SixTransactionReference'] : null;
            $params['transaction_approval_code'] = isset($transaction['ApprovalCode'])  ? $transaction['ApprovalCode'] : null;



            $paymentMeans = isset($response['payment_means'])  ? $response['payment_means'] : [];
            $params['payment_means_brand_payment_method'] = isset($paymentMeans['Brand']['PaymentMethod'])  ? $paymentMeans['Brand']['PaymentMethod'] : null;
            $params['payment_means_brand_name'] = isset($paymentMeans['Brand']['Name'])  ? $paymentMeans['Brand']['Name'] : null;

            $params['payment_means_display_text'] = isset($paymentMeans['DisplayText']) ? $paymentMeans['DisplayText'] : null;
            $params['payment_means_wallet'] = isset($paymentMeans['Wallet']) ? $paymentMeans['Wallet'] : null;

            $params['payment_means_cart_masked_number'] = isset($paymentMeans['Card']['MaskedNumber'])  ? $paymentMeans['Card']['MaskedNumber'] : null;
            $params['payment_means_cart_exp_year'] = isset($paymentMeans['Card']['ExpYear'])  ? $paymentMeans['Card']['ExpYear'] : null;
            $params['payment_means_cart_exp_month'] = isset($paymentMeans['Card']['ExpMonth'])  ? $paymentMeans['Card']['ExpMonth'] : null;
            $params['payment_means_cart_holder_name'] = isset($paymentMeans['Card']['HolderName'])  ? $paymentMeans['Card']['HolderName'] : null;
            $params['payment_means_cart_hash_value'] = isset($paymentMeans['Card']['HashValue'])  ? $paymentMeans['Card']['HashValue'] : null;

            $params['payment_means_bank_account_iban'] = isset($paymentMeans['BankAccount']['IBAN'])  ? $paymentMeans['BankAccount']['IBAN'] : null;
            $params['payment_means_bank_account_holder_name'] = isset($paymentMeans['BankAccount']['HolderName'])  ? $paymentMeans['BankAccount']['HolderName'] : null;
            $params['payment_means_bank_account_bic'] = isset($paymentMeans['BankAccount']['BIC'])  ? $paymentMeans['BankAccount']['BIC'] : null;
            $params['payment_means_bank_account_bank_name'] = isset($paymentMeans['BankAccount']['BankName'])  ? $paymentMeans['BankAccount']['BankName'] : null;
            $params['payment_means_bank_account_country_code'] = isset($paymentMeans['BankAccount']['CountryCode'])  ? $paymentMeans['BankAccount']['CountryCode'] : null;

            $payer = isset($response['payer'])  ? $response['payer'] : [];
            $params['payment_payer_ip_address'] = isset($payer['IpAddress'])  ? $payer['IpAddress'] : null;
            $params['payment_payer_ip_location'] = isset($payer['IpLocation'])  ? $payer['IpLocation'] : null;

            $registrationResult = isset($response['registration_result'])  ? $response['registration_result'] : [];
            if (isset($registrationResult['Success']) && $registrationResult['Success'] === true) {
                $params['payment_registration_alias_id'] = isset($registrationResult['Alias']['Id'])  ? $registrationResult['Alias']['Id'] : null;
                $params['payment_registration_alias_lifetime'] = isset($registrationResult['Alias']['Lifetime'])  ? $registrationResult['Alias']['Lifetime'] : null;
            }
        }

        return array_filter($params);
    }

    /**
     * @param $transactionId
     * @return array
     */
    public function captureTransaction($transactionId)
    {
        $response = $this->requestHandler->createTransactionCaptureRequest($transactionId);

        $params = [];

        if ($response['has_error'] === true) {
            $params['error'] = isset($response['error']) ? $response['error'] : true;
        } else {
            $params['transaction_id'] = $response['transaction_id'];
            $params['transaction_status'] = $response['transaction_status'];
            $params['transaction_date'] = $response['date'];
            $params['transaction_captured'] = true;
        }

        return array_filter($params);
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function refundTransaction(array $fields)
    {
        $response = $this->requestHandler->createRefundRequest($fields);

        $params = [];

        if ($response['has_error'] === true) {
            $params['error'] = isset($response['error']) ? $response['error'] : true;
        } else {
            $transaction = $response['transaction'];
            $params['transaction_id'] = $transaction['Id'];
            $params['transaction_type'] = $transaction['Type'];
            $params['transaction_status'] = $transaction['Status'];
            $params['transaction_date'] = $transaction['Date'];
            $params['transaction_amount'] = $transaction['Amount']['Value'];
            $params['transaction_currency_code'] = $transaction['Amount']['CurrencyCode'];
        }

        return array_filter($params);

    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function insertAlias(array $fields)
    {
        $response = $this->requestHandler->createAliasInsertRequest($fields);

        return array_filter([
            'error'        => $response['has_error'] === true ? (isset($response['data']) ? $response['data'] : true) : false,
            'token'        => $response['token'],
            'redirect_url' => $response['redirect_url'],
        ]);
    }

    /**
     * @param $token
     *
     * @return array
     */
    public function getAliasData($token)
    {
        $response = $this->requestHandler->createAliasAssertInsertRequest($token);

        $params = [];

        if ($response['has_error'] === true) {
            $params['error'] = isset($response['error']) ? $response['error'] : true;
        } else {


            $paymentMeans = isset($response['payment_means'])  ? $response['payment_means'] : [];
            $params['payment_means_brand_payment_method'] = isset($paymentMeans['Brand']['PaymentMethod'])  ? $paymentMeans['Brand']['PaymentMethod'] : null;
            $params['payment_means_brand_name'] = isset($paymentMeans['Brand']['Name'])  ? $paymentMeans['Brand']['Name'] : null;

            $params['payment_means_display_text'] = isset($paymentMeans['DisplayText']) ? $paymentMeans['DisplayText'] : null;
            $params['payment_means_wallet'] = isset($paymentMeans['Wallet']) ? $paymentMeans['Wallet'] : null;

            $params['payment_means_cart_masked_number'] = isset($paymentMeans['Card']['MaskedNumber'])  ? $paymentMeans['Card']['MaskedNumber'] : null;
            $params['payment_means_cart_exp_year'] = isset($paymentMeans['Card']['ExpYear'])  ? $paymentMeans['Card']['ExpYear'] : null;
            $params['payment_means_cart_exp_month'] = isset($paymentMeans['Card']['ExpMonth'])  ? $paymentMeans['Card']['ExpMonth'] : null;
            $params['payment_means_cart_holder_name'] = isset($paymentMeans['Card']['HolderName'])  ? $paymentMeans['Card']['HolderName'] : null;
            $params['payment_means_cart_hash_value'] = isset($paymentMeans['Card']['HashValue'])  ? $paymentMeans['Card']['HashValue'] : null;

            $params['payment_means_bank_account_iban'] = isset($paymentMeans['BankAccount']['IBAN'])  ? $paymentMeans['BankAccount']['IBAN'] : null;
            $params['payment_means_bank_account_holder_name'] = isset($paymentMeans['BankAccount']['HolderName'])  ? $paymentMeans['BankAccount']['HolderName'] : null;
            $params['payment_means_bank_account_bic'] = isset($paymentMeans['BankAccount']['BIC'])  ? $paymentMeans['BankAccount']['BIC'] : null;
            $params['payment_means_bank_account_bank_name'] = isset($paymentMeans['BankAccount']['BankName'])  ? $paymentMeans['BankAccount']['BankName'] : null;
            $params['payment_means_bank_account_country_code'] = isset($paymentMeans['BankAccount']['CountryCode'])  ? $paymentMeans['BankAccount']['CountryCode'] : null;


            $alias = isset($response['alias'])  ? $response['alias'] : [];
            $params['alias_id'] = isset($alias['Id'])  ? $alias['Id'] : null;
            $params['alias_lifetime'] = isset($alias['Lifetime'])  ? $alias['Lifetime'] : null;
        }

        return array_filter($params);
    }
}
