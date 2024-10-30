<?php
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

if (!class_exists('Boxo_Api')) {
    class Boxo_Api {
        private $boxo_return_base_url;

        public function __construct($boxo_return_base_url) {
            $this->boxo_return_base_url = $boxo_return_base_url;
        }

        public function add_service_available_endpoint() {
            register_rest_route('boxo', '/service-available', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'handle_service_available'],
                'permission_callback' => [$this, 'has_permission'],
            ));
        }

        public function handle_service_available($req) {
            $postal_code = $req['postal_code'];
            if (!$postal_code) {
                $res = rest_ensure_response('Missing query param: postal_code');
                $res->set_status(400);
                return $res;
            }

            if (!preg_match('/^\d{4}[a-z,A-Z]{2}$/', $postal_code)) {
                $res = rest_ensure_response('Invalid postal code');
                $res->set_status(400);
                return $res;
            }

            $url = $this->boxo_return_base_url . "/service-available/$postal_code";
            $boxo_res = wp_remote_get($url, ['headers' => ['X-Api-Key' => Boxo_Options::api_key()]]);

            if (is_wp_error($boxo_res)) {
                error_log('[Boxo Return] wp_remote_get error: ' . wp_json_encode($boxo_res->errors));
                $res = rest_ensure_response('Internal server error');
                $res->set_status(500);
                return $res;
            }

            if ($boxo_res['response']['code'] !== 200) {
                error_log(implode(" ", [
                    '[Boxo Return] Error:',
                    $url,
                    $boxo_res['response']['code'],
                    $boxo_res['response']['message'],
                    'Response:',
                    wp_json_encode($boxo_res)
                ]));
                $res = rest_ensure_response('Internal server error');
                $res->set_status(500);
                return $res;
            };

            $body = json_decode(wp_remote_retrieve_body($boxo_res));
            $available = $body->available;
            if (!is_bool($available)) {
                error_log('[Boxo Return] Unexpected response: ' . wp_json_encode($boxo_res));
                $res = rest_ensure_response('Internal server error');
                $res->set_status(500);
                return $res;
            }

            if ($available) {
                return ['available' => true];
            }
            return ['available' => false];
        }

        public function has_permission() {
            return true;
        }
    }
}

// Boxo Return base url can be overwritten when using wp-env
$boxo_api = new Boxo_Api(defined('BOXO_RETURN_BASE_URL') ? BOXO_RETURN_BASE_URL : 'https://api.boxo.nu');

// Add API endpoint
add_action('rest_api_init', [$boxo_api, 'add_service_available_endpoint']);
