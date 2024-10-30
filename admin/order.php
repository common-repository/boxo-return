<?php
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

// Add boxo selection to admin order detail page
add_action('woocommerce_admin_order_data_after_shipping_address', 'Boxo_Order::show_order_meta', 10);

if (!class_exists('Boxo_Order')) {
    class Boxo_Order {
        public static function show_order_meta($order) {
            $result = $order->get_meta('boxo_packaging', true) == 'true' ? "🔄 Herbruikbare verzendverpakking" : "Eenmalige verzendverpakking";
            echo '<h3>' . htmlspecialchars('Verpakking') . '</h3><br>' . $result;
        }
    }
}
