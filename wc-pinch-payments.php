<?php

/**
 * Plugin Name: WooCommerce Pinch Payments
 * Plugin URI: https://voxycureinfotech.com/
 * Description: Pinch Payment Gateway for WooCommerce.
 * Version: 1.0.0
 * Author: Voxycure Infotech
 * Author URI: https://voxycureinfotech.com
 * Text Domain: wc-pinch
 * Requires at least: 6.2
 * Requires PHP: 7.3
 */

defined('ABSPATH') || exit;

if (!defined('PINTCH_VER')) {
    define('PINTCH_VER', time());
}

if (!defined('PINTCH_PATH')) {
    define('PINTCH_PATH', plugin_dir_url(__FILE__));
}

require __DIR__ . '/include/pinch-api.php';
require __DIR__ . '/include/wc-payment.php';
require __DIR__ . '/include/wc-fields.php';
