<?php
/*
Plugin Name: Payment Gateway for LiqPay for Woocommerce
Plugin URI:
Description: Plugin for paying for products through the LiqPay service. Works in conjunction with the Woocommerce plugin
Version: 1.13
Requires at least: 5.7.2
Requires PHP: 7.4
Author: komanda.dev
License: GPL v2 or later
Text Domain: wcliqpay
Domain Path: /languages
Author URI: https://komanda.dev/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', 'init_liqpay_gateway_plugin' );

function init_liqpay_gateway_plugin() {
    // Check if WooCommerce is active
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'liqpay_woocommerce_missing_notice');
        return;
    }

    // Load plugin text domains
    load_plugin_textdomain('wcliqpay', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    add_action('muplugins_loaded', 'load_liqpay_mu_textdomain');

    define('WC_LIQPAY_DIR', plugin_dir_url(__FILE__));

    // Include the necessary files
    require_once plugin_dir_path(__FILE__) . 'includes/LiqPay.php';
    require_once plugin_dir_path(__FILE__) . 'includes/WC_Gateway_kmnd_Liqpay.php';

    add_filter('woocommerce_payment_gateways', 'add_liqpay_gateway_class');
}

// Display an admin notice if WooCommerce is not active
function liqpay_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>LiqPay Gateway for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
}

function add_liqpay_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_kmnd_Liqpay';
    return $gateways;
}

function load_liqpay_mu_textdomain() {
    load_muplugin_textdomain('wcliqpay', dirname(plugin_basename(__FILE__)) . '/languages/');
}