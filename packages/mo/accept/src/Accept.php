<?php

namespace Mo\Accept;

use Illuminate\Support\Facades\Log;
use Mo\Accept\Exceptions\AcceptException;
use GuzzleHttp\Exception\ClientException;

class Accept{

    public function __construct() 
    {
        $this->apiKey = config('paymob.paymob_key');
        $this->integrations = config('paymob.integrations');
        
        $this->authUrl = config('paymob.auth_url');
        $this->payout = config('paymob.payout');
        $this->orderRegistrationUrl = config('paymob.order_registration_url');
        $this->payRequestUrl = config('paymob.pay_request_url');
        $this->payWithTokenUrl = config('paymob.pay_with_token_url');
        $this->iframeUrl = config('paymob.iframe_url');
        $this->kioskUrl = config('paymob.kiosk_url');
        $this->walletUrl = config('paymob.wallet_url');
        $this->voidUrl = config('paymob.void_url');
        
        $this->client = new \GuzzleHttp\Client();
        $this->header = ['Content-Type' => 'application/json'];

        $this->authToken = null;
    }

    public function log($message, $body){
        Log::channel('paymob')->error($message);
        Log::channel('paymob')->error($body);
    }

    public function getHeader($param = []){
        return $param ? array_merge($this->header, $param) : $this->header;
    }

    public function send($method, $url, $header, $body, $bodyFormat = 'json', $tryAuth = true)
    {
        try{
            $res = $this->client->request($method, $url,  ['header' => $header, $bodyFormat => $body]);
        }catch(ClientException $e){
            if($tryAuth){
                $statusCode = $e->getResponse()->getStatusCode();
                if($statusCode == 401){
                    $this->auth();
                    $body['auth_token'] = $this->authToken;
                    return $this->send($method, $url, $header, $body);
                }else{
                    throw $e;
                }
            }else{
                throw $e;
            }
        }

        return [
            'body' => json_decode($res->getBody()->getContents()),
        ];
        
    }

    public function auth()
    {
        $body = ['api_key' => $this->apiKey];
        $url = $this->authUrl;
        $header = $this->getHeader();
        try{
            $res = $this->send('post', $url, $header, $body);
            $authToken = $res['body']->token;
            $this->authToken = $authToken;      
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
    }

    public function orderRegistration($user, $amountCents, $currency, $items = [])
    {
        if(!$this->authToken){
            $this->auth();
        }

        $body = [
            "auth_token" =>  $this->authToken,
            "delivery_needed" => "false",
            "amount_cents" => (int) $amountCents,
            "currency" => $currency,
            "items" => $items,
            "shipping_data" => [
              "apartment" => "NA", 
              "email" => $user->email, 
              "floor" => "NA", 
              "first_name" => $user->first_name ?: 'sagtechs', 
              "street" => "NA", 
              "building" => "NA", 
              "phone_number" => $user->phone ?: '0123456789', 
              "postal_code" => "NA", 
               "extra_description" => "NA",
              "city" => "NA", 
              "country" => "NA", 
              "last_name" => $user->last_name ?: 'company', 
              "state" => "NA"
            ]
        ];
        $url = $this->orderRegistrationUrl;
        $header = $this->getHeader();

        try{
            $res = $this->send('post', $url, $header, $body);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        
        return $res['body'];
        
        // // 11085759
        // return [
        //     "id" => rand(1000, 9999),
        //     "created_at" => "2021-05-07T11:50:48.955222",
        //     "delivery_needed" => false,
        //     "merchant" => [
        //         "id" => 99515,
        //         "created_at" => "2021-05-05T14:51:05.130719",
        //         "phones" => [
        //             "01008578785"
        //         ],
        //         "company_emails" => [
        //             "dev@sagtechs.com"
        //         ],
        //         "company_name" => "sagtechs",
        //         "state" => "",
        //         "country" => "EGY",
        //         "city" => "cairo",
        //         "postal_code" => "",
        //         "street" => ""
        //     ],
        //     "collector" => null,
        //     "amount_cents" => $amountCents,
        //     "shipping_data" => null,
        //     "shipping_details" => null,
        //     "currency" => $currency,
        //     "is_payment_locked" => false,
        //     "is_return" => false,
        //     "is_cancel" => false,
        //     "is_returned" => false,
        //     "is_canceled" => false,
        //     "merchant_order_id" => null,
        //     "wallet_notification" => null,
        //     "paid_amount_cents" => 0,
        //     "notify_user_with_email" => false,
        //     "items" => [],
        //     "order_url" => "https://accept.paymobsolutions.com/standalone?ref=i_abENP",
        //     "commission_fees" => 0,
        //     "delivery_fees_cents" => 0,
        //     "delivery_vat_cents" => 0,
        //     "payment_method" => "tbc",
        //     "merchant_staff_tag" => null,
        //     "api_source" => "OTHER",
        //     "pickup_data" => null,
        //     "delivery_status" => [],
        //     "data" => [],
        //     "token" => "abENP",
        //     "url" => "https://accept.paymobsolutions.com/standalone?ref=i_abENP"
        // ];
        
    }

    public function payRequest($integrationId, $order, $user)
    {
        if(!$this->authToken){
            $this->auth();
        }

        $body = [
            "auth_token" => $this->authToken,
            "amount_cents" => (int) $order->amount_cents,
            "currency" => $order->currency,
            "expiration" => 3600,
            "order_id" => $order->id,
            "billing_data" => [
                "apartment" => "NA", 
                "email" => $user->email, 
                "floor" => "NA", 
                "first_name" => $user->first_name ?: 'sagtechs', 
                "last_name" => $user->last_name ?: 'company', 
                "building" => "NA", 
                "phone_number" => $user->phone ?: '0123456789', 
                "street" => "NA", 
                "shipping_method" => "NA", 
                "postal_code" => "NA", 
                "city" => "NA", 
                "country" => "NA", 
                "state" => "NA"
            ],
            "integration_id" => $integrationId,
            "lock_order_when_paid" => "false"
        ];
        $url = $this->payRequestUrl;
        $header = $this->getHeader();

        try{
            $res = $this->send('post', $url, $header, $body);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        
        return $res['body'];
    }

    public function payWithToken($cardToken, $paymentToken)
    {
        if(!$this->authToken){
            $this->auth();
        }

        $body = [
            'source' => [
                'identifier' => $cardToken,
                'subtype' => 'TOKEN',
            ],
            'payment_token' => $paymentToken,
        ];
        $url = $this->payWithTokenUrl;
        $header = $this->getHeader();

        try{
            $res = $this->send('post', $url, $header, $body);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        
        return $res['body'];  
    }

    public function payWallet($integrationType, $order, $user, $mobile)
    {
        if(!$this->authToken){
            $this->auth();
        }
        $payRequest = $this->payRequest($integrationType, $order, $user);

        $body = [
            "source" => [
                "identifier" => $mobile,
                "subtype" => "WALLET",
            ],
            "payment_token" => $payRequest->token,
        ];

        $url = $this->walletUrl;
        $header = $this->getHeader();

        try{
            $res = $this->send('post', $url, $header, $body);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        
        return $res['body'];
    }

    public function void($refCode)
    {
        if(!$this->authToken){
            $this->auth();
        }

        $body = [
            "transaction_id" => $refCode,
        ];

        $url = "{$this->voidUrl}?token={$this->authToken}";

        try{
            $res = $this->send('post', $url, [], $body);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        
        return $res['body'];
    }

    public function iframe($iframeId, $integrationType, $user, $order)
    {
        $payRequest = $this->payRequest($integrationType, $order, $user);
        $iframe = str_replace('++IFRAME_ID++', $iframeId, $this->iframeUrl);
        $iframe = str_replace('++PAYMENT_TOKEN++', $payRequest->token, $iframe);
        return $iframe;
        // return https://accept.paymob.com/api/acceptance/iframes/{{iframe_id}}?payment_token={{payment_token_obtained_from_step_3}}
    }

    public function kiosk($integrationType, $order, $user)
    {
        if(!$this->authToken){
            $this->auth();
        }
        $payRequest = $this->payRequest($integrationType, $order, $user);

        $body = [
            "source" => [
                "identifier" => "AGGREGATOR",
                "subtype" => "AGGREGATOR",
            ],
            "payment_token" => $payRequest->token,
        ];

        $url = $this->kioskUrl;
        $header = $this->getHeader();

        try{
            $res = $this->send('post', $url, $header, $body);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        
        return $res['body'];
    }

    public function payoutAuth()
    {
        $body = [
            'client_id' => $this->payout['client_id'],
            'client_secret' => $this->payout['client_secret'],
            'username' => $this->payout['username'],
            'password' => $this->payout['password'],
            'grant_type' => 'password',
        ];
        
        $url = $this->payout['auth'];
        $header = $this->getHeader();
        try{
            $res = $this->send('post', $url, $header, $body, 'form_params', false);
            $payoutAuthToken = $res['body']->access_token;
            $this->payoutAuthToken = $payoutAuthToken;
            return $payoutAuthToken;
        }catch(ClientException $e){
            $this->log($e->getMessage(), $header);
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
    }

    public function budget()
    {
        $auth = $this->payoutAuth();
        $url = $this->payout['budget'];
        $header = $this->getHeader(['Authorization' => "Bearer {$auth}"]);
        $body = [];
        try{
            $res = $this->client->request('get', $url,  ['headers' => $header, 'json' => $body]);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw new PaymentException(__('messages.server_error'));
        }
        $budget = json_decode($res->getBody()->getContents())->current_budget;
        $budget = str_replace(['Your current budget is ', ' LE'], '', $budget);
        return $budget;

    }

    public function payout($issuer, $amount, $attributes)
    {
        $auth = $this->payoutAuth();
        $url = $this->payout['payout'];
        $header = $this->getHeader(['Authorization' => "Bearer {$auth}"]);

        $body = $attributes + ['amount' => $amount, 'issuer' => $issuer];
        try{
            $res = $this->client->request('post', $url,  ['headers' => $header, 'json' => $body]);
        }catch(ClientException $e){
            $this->log($e->getMessage(), $body);
            throw $e;
        }
        $res = json_decode($res->getBody()->getContents());

        if($res->disbursement_status == 'failed'){
            $this->log('failed_to_send_payout', (array)$res);
        }
        return $res;
    }

    public function getPayoutFee($amount, $type)
    {
        $commissionPercentage = config('paymob.payout_fee_percentage.' . $type);
        $fee = $amount * 100 / (100 - $commissionPercentage) - $amount;
        
        if($type == 'bank_card'){
            if($fee < config('paymob.payout_fee_boundaries_for_bank_card.min')){
                $fee = config('paymob.payout_fee_boundaries_for_bank_card.min');
            }
            elseif($fee > config('paymob.payout_fee_boundaries_for_bank_card.max')){
                $fee = config('paymob.payout_fee_boundaries_for_bank_card.max');
            }
        }

        return $fee;
    }

    public function getPayinFee($transactionCount)
    {
        return $transactionCount * config('paymob.accept_payin_fixed_fee');
    }
    
}
?>