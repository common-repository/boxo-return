<?php
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

if (!class_exists('Boxo_Options')) {
    class Boxo_Options {
        private const DEFAULT_OPTIONS = [
            'api_key' => '',
            'deposit_cents' => 395,
            'info_url' => ''
        ];

        private static function options() {
            return get_option('boxo_options', self::DEFAULT_OPTIONS);
        }

        public static function api_key() {
            return self::options()["api_key"];
        }

        public static function deposit_cents() {
            return self::options()["deposit_cents"];
        }

        public static function info_url() {
            if (isset(self::options()["info_url"])) {
                return self::options()["info_url"];
            }
            return null;
        }
    }
}
