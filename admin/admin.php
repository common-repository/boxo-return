<?php
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

// Add boxo menu item
add_action('admin_menu', 'Boxo_Admin::add_menu_item');

// Add settings link to plugin page
add_filter('plugin_action_links_boxo-return/boxo-return.php', 'Boxo_Admin::add_settings_link');

if (!class_exists('Boxo_Admin')) {
    class Boxo_Admin {
        public static function add_menu_item() {
            if (!current_user_can('manage_options')) {
                return;
            }

            add_menu_page(
                'Boxo Return',
                'Boxo Return',
                'manage_options',
                'boxo-return',
                'Boxo_Admin::add_options_page'
            );
        }
        public static function add_options_page() {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (isset($_GET['settings-updated'])) {
                add_settings_error('boxo_messages', 'settings_updated', 'Settings saved', 'updated');
            }
            settings_errors('boxo_messages');
?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('boxo');
                    do_settings_sections('boxo');
                    submit_button('Save');
                    ?>
                </form>
            </div>
<?php
        }

        public static function add_settings_link($links) {
            $url = admin_url('admin.php?page=boxo-return');
            $action_links = array(
                'settings' => '<a href="' . $url . '">Settings</a>',
            );
            return array_merge($action_links, $links);
        }
    }
}
