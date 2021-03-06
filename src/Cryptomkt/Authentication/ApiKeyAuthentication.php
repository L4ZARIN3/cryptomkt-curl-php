<?php

namespace Cryptomkt\Authentication;

class ApiKeyAuthentication
{
    private $apiKey;
    private $apiSecret;

    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
    }

    public function getRequestHeaders($method, $path, $body)
    {
        $timestamp = $this->getTimestamp();

        switch ($path){
            case '/v1/orders/create':
                //check valid fields
                if (array_diff_key(array_flip(['amount','market','price','type']), $body)) {
                    throw new \Exception(
                        'The CryptoMarket API New Order only accepts valid fields'
                    );
                }
                $message_to_sign = $timestamp . $path . $body['amount'].$body['market'].$body['price'].$body['type'];
                break;

            case '/v1/orders/cancel':
                //check valid fields
                if (array_key_exists('id', $body)) {
                    throw new \Exception(
                        'The CryptoMarket API New Order only accepts valid fields'
                    );
                }
                $message_to_sign = $timestamp . $path . $body['id'];
                break;

            case '/v1/payment/status':
                $message_to_sign = $timestamp . $path;
                break;

            case '/v1/payment/orders':
                $message_to_sign = $timestamp . $path;
                break;

            case '/v1/payment/new_order':
                //check valid fields
                if (array_diff_key(array_flip(['callback_url','error_url','external_id','language','payment_receiver','refund_email','success_url','to_receive','to_receive_currency']), $body)) {
                    throw new \Exception(
                        'The CryptoMarket API New Pay Order only accepts valid fields'
                    );
                }
                $message_to_sign = $timestamp . $path . $body['callback_url'].$body['error_url'].$body['external_id'].$body['language'].$body['payment_receiver'].$body['refund_email'].$body['success_url'].$body['to_receive'].$body['to_receive_currency'];
                break;

            default:
                if(!is_string($body)){
                    $body = http_build_query($body);
                }
                $message_to_sign = $timestamp.$path.$body;
                break;
        }

        $signature = $this->getHash('sha384', $message_to_sign, $this->apiSecret);

        return [
            'X-MKT-APIKEY' => $this->apiKey,
            'X-MKT-SIGNATURE' => $signature,
            'X-MKT-TIMESTAMP' => (string)$timestamp,
        ];
    }

    // protected

    protected function getTimestamp()
    {
        return time();
    }

    protected function getHash($algo, $data, $key)
    {
        return hash_hmac($algo, $data, $key, FALSE);
    }

    static function checkHash($hash, $data, $key){
        return $hash === hash_hmac('sha384', $data, $key, FALSE);
    }
}
