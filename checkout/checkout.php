<?php
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

// Add Boxo features to checkout page after WooCommerce has loaded
add_action('plugins_loaded', 'Boxo_Checkout::init', 10);

if (!class_exists('Boxo_Checkout')) {
    class Boxo_Checkout {
        private const DEFAULT_INFO_URL = 'https://www.boxo.nu';

        public static function init() {
            // Add styles
            add_action('wp_head', 'Boxo_Checkout::add_styles');

            // Add javascript
            add_action('wp_enqueue_scripts', 'Boxo_Checkout::enqueue_scripts');

            // Add packaging field
            add_action('woocommerce_review_order_before_shipping', 'Boxo_Checkout::add_packaging_field');

            // Add deposit to total price 
            add_action('woocommerce_cart_calculate_fees', 'Boxo_Checkout::add_deposit_fee');

            // Validate packaging field on submit 
            add_action('woocommerce_checkout_process', 'Boxo_Checkout::validate_packaging_field');

            // Add packaging field to order meta
            add_action('woocommerce_checkout_update_order_meta', 'Boxo_Checkout::update_order_meta');
        }

        public static function add_styles() {
            if (!Boxo_Checkout::should_load()) {
                return;
            } ?>
            <style>
                #boxo_container:not([data-visible]) {
                    display: none;
                }

                /* Reset */
                #boxo_container * {
                    border: none;
                    box-shadow: none;
                }

                /* Hide automatically inserted br tags */
                #boxo_container br {
                    display: none !important;
                }

                .boxo_option_icon {
                    display: inline-block;
                    width: 22px !important;
                    height: 22px !important;
                    vertical-align: text-bottom;
                }

                .boxo_option_icon svg {
                    display: block !important;
                    width: 100% !important;
                    height: 100% !important;
                }

                .boxo_info_container {
                    /* Reset */
                    display: inline !important;
                    position: relative;
                }

                .boxo_info_container,
                .boxo_info_container * {
                    /* Reset */
                    color: inherit !important;
                }

                .boxo_info_container:hover .boxo_info_tooltip {
                    visibility: visible;
                    opacity: 1;
                }

                .boxo_info_icon {
                    display: inline-block;
                    width: 12px !important;
                    height: 12px !important;
                    vertical-align: middle;
                }

                .boxo_info_icon svg {
                    display: block !important;
                    width: 100% !important;
                    height: 100% !important;
                }

                .boxo_info_tooltip {
                    /* Reset */
                    white-space: normal;
                    text-align: left;
                    text-indent: initial;
                    font-weight: normal;
                    font-style: normal;
                    text-transform: none;

                    z-index: 999;
                    position: absolute;
                    top: calc(100% + 8px);
                    right: 0;
                    transform: translateX(33.33%);
                    width: max-content;
                    max-width: 360px;
                    border: 1px solid black !important;
                    background-color: white;
                    padding: 16px;
                    word-break: keep-all;
                    visibility: hidden;
                    opacity: 0;
                    transition: visibility .2s, opacity .2s;
                }
            </style>
        <?php
        }

        public static function enqueue_scripts() {
            if (!Boxo_Checkout::should_load()) {
                return;
            }
            wp_enqueue_script_module('boxo_checkout', plugins_url('checkout.js', __FILE__));
        }

        public static function add_packaging_field() {
            if (!Boxo_Checkout::should_load()) {
                return;
            }

            $deposit_formatted = '€' . number_format(Boxo_Options::deposit_cents() / 100, 2, ',', '.');
            $value = WC()->checkout->get_value('boxo_packaging');
            $shop_country = WC()->countries->get_base_country();
            $info_url = Boxo_Options::info_url() ?: self::DEFAULT_INFO_URL;

            // The HTML for the input elements is based on the HTML for standard shipping inputs in WooCommerce.
            // A hidden input shows whether Boxo is available so that validation can run accordingly.
        ?>
            <div id="boxo-data" data-service-available-url="<?= rest_url('boxo/service-available') ?>" data-shop-country="<?= $shop_country ?>" style="display: none;"></div>
            <tr id="boxo_container" class="woocommerce-shipping-totals shipping">
                <th>Verzendverpakking</th>
                <td data-title="Verzendverpakking">
                    <div id="boxo_inputs_container"></div>
                    <template id="boxo_inputs_template">
                        <input type="hidden" name="boxo_available" value="true">
                        <ul id="shipping_method" class="woocommerce-shipping-methods">
                            <li>
                                <input type="radio" name="boxo_packaging" id="boxo_packaging_true" value="true" class="shipping_method" <?= $value == 'true' ? 'checked' : '' ?> />
                                <label for="boxo_packaging_true">Herbruikbaar
                                    <span class="boxo_option_icon">
                                        <?= file_get_contents(plugin_dir_path(__FILE__) . 'heart.svg') ?>
                                    </span>
                                    (<?= $deposit_formatted ?> statiegeld)
                                    <a class="boxo_info_container" href="<?= $info_url ?>" target="_blank">
                                        <span class="boxo_info_icon">
                                            <?= file_get_contents(plugin_dir_path(__FILE__) . 'info.svg') ?>
                                        </span>
                                        <div class="boxo_info_tooltip">
                                            We versturen je bestelling in een herbruikbare verzendverpakking. Lever de verpakking in bij een inleverpunt en ontvang direct je statiegeld terug.
                                            Klik op de i voor meer informatie.
                                        </div>
                                    </a>
                                </label>
                            </li>
                            <li>
                                <input type="radio" name="boxo_packaging" id="boxo_packaging_false" value="false" class="shipping_method" <?= $value == 'false' ? 'checked' : '' ?> />
                                <label for="boxo_packaging_false">Eenmalig
                                    <span class="boxo_option_icon">
                                        <?= file_get_contents(plugin_dir_path(__FILE__) . 'container.svg') ?>
                                    </span>
                                </label>
                            </li>
                        </ul>
                    </template>
                </td>
            </tr>
<?php
        }

        public static function add_deposit_fee() {
            if (!Boxo_Checkout::should_load() || !$_POST) {
                return;
            }

            if (isset($_POST['post_data'])) {
                parse_str($_POST['post_data'], $post_data);
            } else {
                // Fallback for final checkout (non-ajax)
                $post_data = $_POST;
            }

            if (isset($post_data['boxo_packaging']) && $post_data['boxo_packaging'] == 'true') {
                WC()->cart->add_fee('Statiegeld', Boxo_Options::deposit_cents() / 100);
            }
        }

        public static function validate_packaging_field() {
            if (!Boxo_Checkout::should_load() || !$_POST) {
                return;
            }

            if (isset($_POST['post_data'])) {
                parse_str($_POST['post_data'], $post_data);
            } else {
                // Fallback for final checkout (non-ajax)
                $post_data = $_POST;
            }

            $boxo_available = isset($post_data['boxo_available']) && $post_data['boxo_available'] == "true";
            if (!$boxo_available) {
                return;
            }

            if (!isset($post_data['boxo_packaging'])) {
                wc_add_notice(esc_html__('Selecteer een verzendverpakking.'), 'error');
            }
        }

        public static function update_order_meta($order_id) {
            if (!Boxo_Checkout::should_load()) {
                return;
            }

            if (!empty($_POST['boxo_packaging'])) {
                error_log(print_r($_POST, true) . PHP_EOL, 3, 'php://stdout');
                $order = wc_get_order($order_id);
                $order->update_meta_data('boxo_packaging', sanitize_text_field($_POST['boxo_packaging']));
                $order->save_meta_data();
            }
        }

        public static function should_load() {
            // Only load on the actual checkout page, not on the order received page.
            return is_checkout() && !is_wc_endpoint_url('order-received');
        }
    }
}
