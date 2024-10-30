<?php
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

// Add settings page
add_action('admin_init', 'Boxo_Settings::settings_init');

if (!class_exists('Boxo_Settings')) {
    class Boxo_Settings {
        public static function settings_init() {
            if (!current_user_can('manage_options')) {
                return;
            }

            register_setting('boxo', 'boxo_options');

            add_settings_section(
                'section',
                'Settings',
                'Boxo_Settings::add_section_header',
                'boxo'
            );

            add_settings_field(
                'api_key',
                'API Key',
                'Boxo_Settings::add_api_key_field',
                'boxo',
                'section',
                [
                    'label_for' => 'boxo_api-key-input',
                    'class' => 'boxo_row',
                ]
            );

            add_settings_field(
                'deposit_cents',
                'Deposit amount in cents',
                'Boxo_Settings::add_deposit_cents_field',
                'boxo',
                'section',
                [
                    'label_for' => 'boxo_deposit-cents-input',
                    'class' => 'boxo_row',
                ]
            );

            add_settings_field(
                'info_url',
                'URL to information page (optional)',
                'Boxo_Settings::add_info_url_field',
                'boxo',
                'section',
                [
                    'label_for' => 'boxo_info-url-input',
                    'class' => 'boxo_row',
                ]
            );
        }
        public static function add_section_header() {
            // Empty but required
        }
        public static function add_api_key_field() {
            $value = Boxo_Options::api_key();
?>
            <input id="boxo_api-key-input" class="regular-text" name="boxo_options[api_key]" type="password" value="<?php echo esc_attr($value) ?>">
        <?php
        }
        public static function add_deposit_cents_field() {
            $value = Boxo_Options::deposit_cents();
        ?>
            <input id="boxo_deposit-cents-input" name="boxo_options[deposit_cents]" type="number" min="0" max="10000" value="<?php echo esc_attr($value) ?>">
        <?php
        }
        public static function add_info_url_field() {
            $value = Boxo_Options::info_url();
        ?>
            <input id="boxo_info-url-input" class="regular-text" name="boxo_options[info_url]" type="url" value="<?php echo esc_attr($value) ?>">
<?php
        }
    }
}
