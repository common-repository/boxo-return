<?php
/*
* Plugin Name: BOXO Return
* Version: 0.0.21
* Description: Allows customers to select reusable packaging during checkout.
* Author: Boxo
* Author URI: https://www.boxo.nu
* Developer: Boxo
* Developer URI: https://www.boxo.nu
* License: GPLv2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Requires Plugins: woocommerce
*/

if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

include plugin_dir_path(__FILE__) . 'includes/options.php';
include plugin_dir_path(__FILE__) . 'includes/api.php';
if (is_admin()) {
    include plugin_dir_path(__FILE__) . 'admin/admin.php';
    include plugin_dir_path(__FILE__) . 'admin/settings.php';
    include plugin_dir_path(__FILE__) . 'admin/order.php';
}
include plugin_dir_path(__FILE__) . 'checkout/checkout.php';

// Boxo plugin is incompatible with Checkout Blocks
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
    }
});
