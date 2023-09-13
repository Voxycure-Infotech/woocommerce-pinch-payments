<?php

/**
 * File is being useed to create pich releated settings on edit prohect page.
 */

defined('ABSPATH') || exit;

// Added custom Tab
add_filter('woocommerce_product_data_tabs', function ($tabs) {
    $tabs['pinch_payment_tab'] = array(
        'label'    => __('Pinch Payment', 'wc-pinch'),
        'target'   => 'pinch_payment_tab_options',
        'class'    => array('show_if_simple', 'show_if_variable'),
    );
    return $tabs;
});

// Added custom fields on Tab.
add_action('woocommerce_product_data_panels', function () {

    echo '<div id="pinch_payment_tab_options" class="panel woocommerce_options_panel">';

    // Select Box for product type.
    woocommerce_wp_select(
        array(
            'id'      => 'pinch_product_type',
            'label'   => __('Product type', 'wc-pinch'),
            'options' => array(
                'one_time' => __('One Time', 'wc-pinch'),
                'subscription' => __('Subscription', 'wc-pinch'),
            ),
        )
    );

    // Text Box to enter plan ID if it's subscription id. 
    woocommerce_wp_text_input(
        array(
            'id'          => 'pinch_plan_id',
            'label'       => __('Plan ID', 'wc-pinch'),
            'placeholder' => __('pln_HU62zijHYPoIOx', 'wc-pinch'),
            'desc_tip'    => 'true',
            'description' => __('If is Subscription Product than added Plan id.', 'wc-pinch'),
        )
    );

    echo '</div>';
});

// Save custom fields.
add_action('woocommerce_process_product_meta', function ($post_id) {

    // Save Select Box
    $custom_select = isset($_POST['pinch_product_type']) ? sanitize_text_field($_POST['pinch_product_type']) : '';
    update_post_meta($post_id, 'pinch_product_type', $custom_select);

    // Save Text Box
    $custom_text = isset($_POST['pinch_plan_id']) ? sanitize_text_field($_POST['pinch_plan_id']) : '';
    update_post_meta($post_id, 'pinch_plan_id', $custom_text);
});
