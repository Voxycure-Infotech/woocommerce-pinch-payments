<?php

defined('ABSPATH') || exit;

if (!class_exists('PINCH_API')) {

    /**
     * Manage whole pinch API here.
     */
    class PINCH_API
    {
        // Variables 
        public $auth_url;
        public $base_url;
        public $environment;
        public $bearer_token;
        public $testmode;
        public $merchant_key;
        public $secret_key;
        public $publishable_key;

        /**
         * Init Pinch payments
         */
        public function __construct($testmode = '', $merchant_key = '', $secret_key = '', $publishable_key = '')
        {

            if (empty($testmode) || empty($merchant_key) || empty($secret_key) || empty($publishable_key)) {
                $options = get_option('woocommerce_wc-pinch_settings');
            }

            if (empty($testmode)) {
                $testmode = $options['pinch_testmode'] ?? '';
            }

            if (empty($merchant_key)) {
                $merchant_key = $testmode ? $options['pinch_test_merchant_key'] :  $options['pinch_merchant_key'];
            }
            if (empty($secret_key)) {
                $secret_key = $testmode ?  $options['pinch_test_secret_key'] :  $options['pinch_secret_key'];
            }
            if (empty($publishable_key)) {
                $publishable_key = $testmode ?  $options['pinch_test_publishable_key'] :  $options['pinch_publishable_key'];
            }

            $this->auth_url = 'https://auth.getpinch.com.au';
            $environment = 'yes' == $testmode ? 'test' : 'live';
            $this->base_url = "https://api.getpinch.com.au/{$environment}";

            $this->merchant_key = $merchant_key;
            $this->secret_key = $secret_key;
            $this->publishable_key = $publishable_key;
        }

        /**
         * Generate bearer token.
         */
        public function generate_bearer_token()
        {
            $url = $this->auth_url . '/connect/token';

            $headers = array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode("{$this->merchant_key}" . ':' . "{$this->secret_key}")
            );

            $body = array(
                'grant_type' => 'client_credentials',
                'scope' => 'api1'
            );

            $response = wp_remote_post($url, array(
                'headers' => $headers,
                'body' => $body
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            $res = json_decode($body);

            $this->bearer_token = $res->access_token ?? '';
        }

        /**
         * Create User 
         */
        public function generate_user($user_args)
        {
            $url = $this->base_url . '/payers';

            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearer_token
            );

            $body = array(
                'firstName' => $user_args['first_name'] ?? '',
                'lastName' => $user_args['last_name'] ?? '',
                'emailAddress' => $user_args['user_email'] ?? '',
            );

            $response = wp_remote_post($url, array(
                'headers' => $headers,
                'body' => wp_json_encode($body)
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body);
        }

        /**
         * Genrate card token
         */
        public function generate_card_token($holder, $card_number, $expiry_date, $cvc)
        {
            $expiry = explode("/", $expiry_date);

            $expiryMonth = isset($expiry[0]) ? trim($expiry[0]) : '';
            $expiryYear = isset($expiry[1]) ? trim($expiry[1]) : '';
            $expiryYear = '20' . $expiryYear;

            $url = $this->base_url . '/tokens';

            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearer_token
            );

            $body = array(
                'publishableKey' => $this->publishable_key,
                'cardHolderName' => $holder,
                'cardNumber' => $card_number,
                'expiryMonth' => $expiryMonth,
                'expiryYear' => $expiryYear,
                'cvc' => $cvc,
            );

            $response = wp_remote_post($url, array(
                'headers' => $headers,
                'body' => wp_json_encode($body)
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body);
        }

        /**
         * Initialize onetime payment
         */
        public function init_onetime_payment($pinch_user_id, $card_token, $total)
        {
            $url = $this->base_url . '/payments/realtime';

            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearer_token
            );

            $body = array(
                'payerId' => $pinch_user_id,
                'amount' => $total * 100,
                'creditCardToken' => $card_token,
            );

            $response = wp_remote_post($url, array(
                'headers' => $headers,
                'body' => wp_json_encode($body)
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body);
        }

        /**
         * create payment source for user Account.
         */
        public function create_payment_source($pinch_user_id, $card_token)
        {
            $url = $this->base_url . "/payers/{$pinch_user_id}/sources";
            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearer_token
            );

            $body = array(
                "payerId" => $pinch_user_id,
                "creditCardToken" => $card_token,
                "sourceType" => "credit-card",
            );

            $response = wp_remote_post($url, array(
                'headers' => $headers,
                'body' => wp_json_encode($body)
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body);
        }

        /**
         * Initialize subscription payment
         */
        public function init_subscription_payment($pinch_user_id, $plan_id)
        {
            if (empty($plan_id)) return;

            $url = $this->base_url . '/subscriptions';

            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearer_token
            );

            $body = array(
                'payerId' => $pinch_user_id,
                'planId' => $plan_id,
                "startDate" => date('Y-M-d H:i:s'),
            );

            $response = wp_remote_post($url, array(
                'headers' => $headers,
                'body' => wp_json_encode($body)
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body);
        }

        /**
         * Cancel pinch Subscription 
         */
        public function cancel_subscription($subscription_id)
        {

            if (empty($subscription_id)) {
                return false;
            }

            $url = $this->base_url . "/subscriptions/{$subscription_id}";

            $headers = array(
                'Authorization' => 'Bearer ' . $this->bearer_token
            );

            $response = wp_remote_request($url, array(
                'method' => 'DELETE',
                'headers' => $headers,
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);

            // remove active subscription from user.
            delete_user_meta(get_current_user_id(), 'pinch_active_subscription');

            return json_decode($body);
        }
    }
}
