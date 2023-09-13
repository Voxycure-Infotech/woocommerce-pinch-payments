<?php

defined('ABSPATH') || exit;

/**
 * Init Pinch payments Gateway for woocommerce.
 */

add_action('plugins_loaded', 'init_pinch_payment_gateway');

if (!function_exists('init_pinch_payment_gateway')) {
    function init_pinch_payment_gateway()
    {
        /**
         * After plugin loaded so WC_Payment_Gateway is avaiable to use 
         * added pinch payment class support
         */
        add_filter('woocommerce_payment_gateways', function ($methods) {
            $methods[] = 'WC_Pinch_Payment_Gateway';
            return $methods;
        });


        class WC_Pinch_Payment_Gateway extends WC_Payment_Gateway
        {
            /** Public vars */
            public $id;
            public $icon;
            public $has_fields;
            public $method_title;
            public $method_description;
            public $supports;

            public $title;
            public $description;
            public $enabled;
            public $testmode;
            public $merchant_key;
            public $secret_key;
            public $publishable_key;

            public $form_fields;
            public $pinch_holder;
            public $is_subscription;
            public $card_number;
            public $expiry_date;
            public $cvc;

            /**
             * Class constructor, more about it in Step 3
             */
            public function __construct()
            {
                $this->id = 'wc-pinch'; // payment gateway plugin ID
                $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
                $this->has_fields = true; // in case you need a custom credit card form
                $this->method_title = 'Pinch Payments Gateway';
                $this->method_description = 'Pinch Payment Gateway for WooCommerce.'; // will be displayed on the options page

                // gateways can support subscriptions, refunds, saved payment methods,
                // but in this tutorial we begin with simple payments
                $this->supports = array(
                    'products'
                );

                // Method with all the options fields
                $this->init_form_fields();

                // Load the settings.
                $this->init_settings();
                $this->title = $this->get_option('pinch_title');
                $this->description = $this->get_option('pinch_description');
                $this->enabled = $this->get_option('pinch_enabled');
                $this->testmode = 'yes' === $this->get_option('pinch_testmode');
                $this->is_subscription = 0; // default set it to onetime payment mode.
                $this->merchant_key = $this->testmode ? $this->get_option('pinch_test_merchant_key') : $this->get_option('pinch_merchant_key');
                $this->secret_key = $this->testmode ? $this->get_option('pinch_test_secret_key') : $this->get_option('pinch_secret_key');
                $this->publishable_key = $this->testmode ? $this->get_option('pinch_test_publishable_key') : $this->get_option('pinch_publishable_key');

                // This action hook saves the settings
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                // We need custom JavaScript to obtain a token
                add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

                // You can also register a webhook here
                // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
            }

            /**
             * Plugin options, we deal with it in Step 3 too
             */
            public function init_form_fields()
            {

                $this->form_fields = array(
                    'pinch_enabled' => array(
                        'title'       => 'Enable/Disable',
                        'label'       => 'Enable Pinch Gateway',
                        'type'        => 'checkbox',
                        'description' => '',
                        'default'     => 'no'
                    ),
                    'pinch_title' => array(
                        'title'       => 'Title',
                        'type'        => 'text',
                        'description' => 'This controls the title which the user sees during checkout.',
                        'default'     => 'Credit Card',
                        'desc_tip'    => true,
                    ),
                    'pinch_description' => array(
                        'title'       => 'Description',
                        'type'        => 'textarea',
                        'description' => 'This controls the description which the user sees during checkout.',
                        'default'     => 'Pay with your credit card via Pinch payment gateway.',
                    ),
                    'pinch_testmode' => array(
                        'title'       => 'Test mode',
                        'label'       => 'Enable Test Mode',
                        'type'        => 'checkbox',
                        'description' => 'Place the payment gateway in test mode using test API keys.',
                        'default'     => 'yes',
                        'desc_tip'    => true,
                    ),
                    'pinch_test_merchant_key' => array(
                        'title'       => 'Test Merchant Key',
                        'type'        => 'text'
                    ),
                    'pinch_test_secret_key' => array(
                        'title'       => 'Test Secret Key',
                        'type'        => 'text'
                    ),
                    'pinch_test_publishable_key' => array(
                        'title'       => 'Test Publishable Key',
                        'type'        => 'text',
                    ),
                    'pinch_merchant_key' => array(
                        'title'       => 'Live Merchant Key',
                        'type'        => 'text'
                    ),
                    'pinch_secret_key' => array(
                        'title'       => 'Live Secret Key',
                        'type'        => 'text'
                    ),
                    'pinch_publishable_key' => array(
                        'title'       => 'Live Publishable Key',
                        'type'        => 'text'
                    )
                );
            }

            /**
             * You will need it if you want your custom credit card form, Step 4 is about it
             */
            public function payment_fields()
            {
                // ok, let's display some description before the payment form
                if ($this->description) {
                    // you can instructions for test mode, I mean test card numbers etc.
                    if ($this->testmode) {
                        $this->description .= '<br> <strong>TEST MODE ENABLED</strong>. In test mode, you can use the card numbers listed in <a href="https://docs.getpinch.com.au/docs/test-and-live-mode">documentation</a>.';
                        $this->description  = trim($this->description);
                    }
                    // display the description with <p> tags etc.
                    echo wpautop(wp_kses_post($this->description));
                }


                // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
                echo '<div id="pinch-container">
                        <div class="form-row form-row-wide">
                            <label>Card Holder Name<span class="required">*</span></label>
                            <input class="input-text" name="pinch_holder" id="pinch_holder" type="text">
                        </div>
                        <div class="form-row form-row-wide">
                            <label>Card Number <span class="required">*</span></label>
                            <input class="input-text" name="pinch_card" id="pinch_card" type="text" autocomplete="off">
                        </div>
                        <div class="form-row form-row-first">
                            <label>Expiry Date <span class="required">*</span></label>
                            <input class="input-text" name="pinch_exp" id="pinch_exp" type="text" autocomplete="off" placeholder="MM / YY">
                        </div>
                        <div class="form-row form-row-last">
                            <label>Card Code (CVC) <span class="required">*</span></label>
                            <input class="input-text" name="pinch_cvv" id="pinch_cvv" type="text" autocomplete="off" placeholder="CVC">
                        </div>
                        <div class="clear"></div>
                    </div>';
            }

            /*
             * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
             */
            public function payment_scripts()
            {
                // // we need JavaScript to process a token only on cart/checkout pages, right?
                if (!is_cart() && !is_checkout()) {
                    return;
                }

                // if our payment gateway is disabled, we do not have to enqueue JS too
                if ('no' === $this->enabled) {
                    return;
                }

                // and this is our custom JS in your plugin directory that works with token.js
                wp_enqueue_script('pinch_js', PINTCH_PATH . '/assets/js/checkout.js', [], PINTCH_VER);
            }

            /*
              * Fields validation, more in Step 5
             */
            public function validate_fields()
            {
                // Initialize variables
                $onetime_count = 0;
                $subscription_count = 0;

                // Loop through cart items
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

                    $product_id = $cart_item['product_id'] ?? '';

                    if (empty($product_id)) continue;

                    $pinch_product_type = get_post_meta($product_id, 'pinch_product_type', true);

                    // Count the number of products with Option 1 and Option 2
                    if (empty($pinch_product_type) || $pinch_product_type === 'one_time') {
                        $onetime_count++;
                    } elseif ($pinch_product_type === 'subscription') {
                        $this->is_subscription = 1; // If you have a subscription product, then enable subscription mode.
                        $subscription_count++;
                    }
                }

                // Check conditions and display an error if necessary
                if ($onetime_count > 0 && $subscription_count > 0) {
                    // Prevent checkout and display an error message
                    wc_add_notice(__('You cannot purchase a one-time or subscription product at once.', 'wc-pinch'), 'error');
                    return;
                }

                // Set maximum 1 subscription allowsd at one time.
                if ($subscription_count > 1) {
                    wc_add_notice(__('Please buy one membership at a time.', 'wc-pinch'), 'error');
                    return;
                }

                // Retrieve credit card details from POST data
                $pinch_holder = sanitize_text_field($_POST['pinch_holder']);
                $card_number = sanitize_text_field($_POST['pinch_card']);
                $expiry_date = sanitize_text_field($_POST['pinch_exp']);
                $cvc = sanitize_text_field($_POST['pinch_cvv']);

                if (empty($pinch_holder) || empty($card_number) || empty($expiry_date) || empty($cvc)) {
                    wc_add_notice(__('Please fill out card details', 'wc-pinch'), 'error');
                    return;
                }

                $this->pinch_holder = $pinch_holder;
                $this->card_number  = $card_number;
                $this->expiry_date  = $expiry_date;
                $this->cvc          = $cvc;
            }

            /*
             * We're processing the payments here, everything about it is in Step 5
             */
            public function process_payment($order_id)
            {
                $pinch = new PINCH_API($this->testmode, $this->merchant_key, $this->secret_key, $this->publishable_key);
                $pinch->generate_bearer_token(); // Genrate token for feture api integration

                $user_id = get_current_user_id();
                $pinch_user_id = get_user_meta($user_id, 'pinch_user_id', true);

                // If user is already created in than no need to create it again.
                if (empty($pinch_user_id)) {

                    // 
                    $user_info = get_userdata($user_id);
                    $user_args = [
                        'first_name' => $user_info->first_name ?? '',
                        'last_name' => $user_info->last_name ?? '',
                        'user_email' => $user_info->user_email ?? '',
                    ];

                    $pich_user = $pinch->generate_user($user_args);

                    if (!isset($pich_user->id)) {
                        if (!empty($pich_user[0]->errorMessage)) { // if have API error 
                            wc_add_notice($pich_user[0]->errorMessage, 'error');
                            return;
                        }
                        wc_add_notice(__('Somthing went wrong', 'wc-pinch'), 'error'); // somthing wrong so token not generated
                        return;
                    }

                    $pinch_user_id = $pich_user->id ?? '';
                    update_user_meta($user_id, 'pinch_user_id', $pinch_user_id);
                }

                // now create credit card token. 
                $card_obj = $pinch->generate_card_token(
                    $this->pinch_holder,
                    $this->card_number,
                    $this->expiry_date,
                    $this->cvc,
                );

                if (!isset($card_obj->token)) {
                    if (!empty($card_obj[0]->errorMessage)) { // if have API error 
                        wc_add_notice($card_obj[0]->errorMessage, 'error');
                        return;
                    }
                    wc_add_notice(__('Somthing went wrong', 'wc-pinch'), 'error'); // somthing wrong so token not generated
                    return;
                }

                $card_token = $card_obj->token ?? '';


                // default set to one time use this hooks to change it to membership and set Subscription.
                $type = apply_filters('pinch_set_type',  $this->is_subscription, $order_id);

                // default onetime product
                $order = wc_get_order($order_id);

                // Initialize payments.
                if ($type) {
                    // For subscription type.

                    $pinch_payment_source = get_user_meta($user_id, 'pinch_payment_source', true);

                    // If user don't have payment source than create new :)
                    if (empty($pinch_payment_source)) {
                        $pich_payment_source = $pinch->create_payment_source($pinch_user_id, $card_token);

                        if (!isset($pich_payment_source->id)) {
                            if (!empty($pich_payment_source[0]->errorMessage)) { // if have API error 
                                wc_add_notice($pich_payment_source[0]->errorMessage, 'error');
                                return;
                            }
                            wc_add_notice(__('Somthing went wrong 456', 'wc-pinch'), 'error'); // somthing wrong so Payment Source not being generated.
                            return;
                        }

                        update_user_meta($user_id, 'pinch_payment_source', $pich_payment_source->id);
                    }

                    // Loop through all subscription cart items
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

                        $product_id = $cart_item['product_id'] ?? '';

                        if (empty($product_id)) continue;

                        $pinch_product_type = get_post_meta($product_id, 'pinch_product_type', true);
                        $plan_id = get_post_meta($product_id, 'pinch_plan_id', true);

                        // Count the number of products with Option 1 and Option 2
                        if (!empty($pinch_product_type) && $pinch_product_type === 'subscription') {
                            if (empty($plan_id)) {
                                wc_add_notice("One of your subscription products doesn't have a plan ID; please contact administration.", 'error'); // somthing wrong so token not generated
                                return;
                            }

                            $pich_payment = $pinch->init_subscription_payment($pinch_user_id, $plan_id); // init all subscriptions.

                            if (!empty($pich_payment->id)) {
                                update_user_meta($user_id, 'pinch_active_subscription', $pich_payment->id);
                            }
                        }
                    }
                } else {
                    $total = $order->get_total();
                    $pich_payment = $pinch->init_onetime_payment($pinch_user_id, $card_token, $total);
                }


                if (!isset($pich_payment->status) || !isset($pich_payment->id)) {
                    if (!empty($pich_payment[0]->errorMessage)) { // if have API error 
                        wc_add_notice($pich_payment[0]->errorMessage, 'error');
                        return;
                    }
                    wc_add_notice(__('Somthing went wrong 789', 'wc-pinch'), 'error'); // somthing wrong while processing payment
                    return;
                }

                // After payment successfully completed.
                do_action('pinch_payment_completed', [
                    'order_id' => $order_id,
                    'is_subscription' => $this->is_subscription,
                    'payment' => $pich_payment,
                ]);

                // Update payment status
                if ($order) {
                    $order->add_order_note(esc_html__('Pinch payment completed', 'wc-pinch'));
                    $order->payment_complete($pich_payment->id);
                }

                // make card empty.
                WC()->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }

            /*
             * In case you need a webhook, like PayPal IPN etc
             */
            public function webhook()
            {
            }
        }
    }
}
