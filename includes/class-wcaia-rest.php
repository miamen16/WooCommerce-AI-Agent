<?php
if (!defined('ABSPATH')) { exit; }

final class WCAIA_REST {
    public static function init() {
        add_action('rest_api_init', function () {
            register_rest_route('wcaia/v1', '/chat', [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'chat'],
                'permission_callback' => '__return_true',
                'args' => [
                    'message' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                    'order_id' => ['required' => false, 'type' => 'integer'],
                    'email' => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_email'],
                ],
            ]);
        });
    }

    public static function chat(WP_REST_Request $request) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $key = 'wcaia_rate_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count >= 30) {
            return new WP_Error('wcaia_rate_limited', 'طلبات كثيرة. انتظر دقيقة ثم حاول مرة أخرى.', ['status' => 429]);
        }
        set_transient($key, $count + 1, MINUTE_IN_SECONDS);

        $message = trim((string) $request->get_param('message'));
        if (strlen($message) > 500) {
            return new WP_Error('wcaia_message_too_long', 'الرسالة طويلة جدًا.', ['status' => 400]);
        }

        $order_id = absint($request->get_param('order_id'));
        $email = sanitize_email((string) $request->get_param('email'));
        if ($order_id && is_email($email)) {
            $order = wc_get_order($order_id);
            if (!$order || !hash_equals(strtolower($order->get_billing_email()), strtolower($email))) {
                return rest_ensure_response(['reply' => 'لم أتمكن من مطابقة رقم الطلب مع البريد الإلكتروني. راجع البيانات وحاول مرة أخرى.']);
            }
            $status = wc_get_order_status_name($order->get_status());
            $reply = 'الطلب #' . $order->get_order_number() . ' — الحالة: ' . $status . ' — الإجمالي: ' . wp_strip_all_tags($order->get_formatted_order_total());
            return rest_ensure_response(['reply' => $reply]);
        }

        return rest_ensure_response(WCAIA_Demo::respond($message));
    }
}
