<?php
if (!defined('ABSPATH')) { exit; }

final class WCAIA_Demo {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_shortcode('wc_ai_agent', [__CLASS__, 'shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
    }

    public static function register_assets() {
        wp_register_style('wcaia-chat', WCAIA_URL . 'assets/css/chat.css', [], WCAIA_VERSION);
        wp_register_script('wcaia-chat', WCAIA_URL . 'assets/js/chat.js', [], WCAIA_VERSION, true);
        wp_localize_script('wcaia-chat', 'WCAIA', [
            'endpoint' => esc_url_raw(rest_url('wcaia/v1/chat')),
            'nonce' => wp_create_nonce('wp_rest'),
            'strings' => [
                'placeholder' => __('اكتب رسالتك هنا...', 'wc-ai-agent'),
                'send' => __('إرسال', 'wc-ai-agent'),
                'error' => __('حصل خطأ. حاول مرة أخرى.', 'wc-ai-agent'),
            ],
        ]);
    }

    public static function shortcode() {
        wp_enqueue_style('wcaia-chat');
        wp_enqueue_script('wcaia-chat');
        ob_start(); ?>
        <section class="wcaia-widget" dir="rtl" aria-label="<?php esc_attr_e('مساعد المتجر', 'wc-ai-agent'); ?>">
            <div class="wcaia-heading"><strong><?php esc_html_e('مساعد المتجر', 'wc-ai-agent'); ?></strong><span><?php esc_html_e('نسخة تجريبية بدون API', 'wc-ai-agent'); ?></span></div>
            <div class="wcaia-messages" role="log" aria-live="polite">
                <div class="wcaia-message wcaia-bot"><?php esc_html_e('أهلًا بيك! 👋 أقدر أساعدك في البحث عن المنتجات، متابعة الطلبات، ومعرفة سياسات المتجر. تحب أساعدك في إيه؟', 'wc-ai-agent'); ?></div>
            </div>
            <form class="wcaia-form">
                <input class="wcaia-input" type="text" maxlength="500" autocomplete="off" required placeholder="<?php esc_attr_e('اكتب رسالتك هنا...', 'wc-ai-agent'); ?>" />
                <button type="submit"><?php esc_html_e('إرسال', 'wc-ai-agent'); ?></button>
            </form>
        </section>
        <?php return ob_get_clean();
    }

    public static function admin_menu() {
        add_submenu_page('woocommerce', __('AI Agent Demo', 'wc-ai-agent'), __('AI Agent Demo', 'wc-ai-agent'), 'manage_woocommerce', 'wcaia-demo', [__CLASS__, 'settings_page']);
    }

    public static function settings_page() {
        if (!current_user_can('manage_woocommerce')) { return; }
        if (isset($_POST['wcaia_save']) && check_admin_referer('wcaia_settings')) {
            update_option('wcaia_faq', sanitize_textarea_field(wp_unslash($_POST['wcaia_faq'] ?? '')));
            update_option('wcaia_policy', sanitize_textarea_field(wp_unslash($_POST['wcaia_policy'] ?? '')));
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'wc-ai-agent') . '</p></div>';
        }
        $faq = get_option('wcaia_faq', 'الشحن: راجع صفحة الشحن لمعرفة المدة والتكلفة.\nالاسترجاع: تطبق سياسة الاسترجاع المنشورة في المتجر.');
        $policy = get_option('wcaia_policy', 'سياسة الشحن والاسترجاع: يرجى مراجعة صفحات السياسات الرسمية في المتجر.');
        ?>
        <div class="wrap"><h1><?php esc_html_e('WooCommerce AI Agent — Demo', 'wc-ai-agent'); ?></h1>
        <p><?php esc_html_e('هذه النسخة تعمل محليًا ولا تحتاج إلى OpenAI API Key. أضف الشورت كود التالي إلى أي صفحة:', 'wc-ai-agent'); ?> <code>[wc_ai_agent]</code></p>
        <form method="post"><?php wp_nonce_field('wcaia_settings'); ?>
            <table class="form-table"><tr><th><label for="wcaia_faq"><?php esc_html_e('الأسئلة الشائعة', 'wc-ai-agent'); ?></label></th><td><textarea class="large-text" rows="5" id="wcaia_faq" name="wcaia_faq"><?php echo esc_textarea($faq); ?></textarea></td></tr>
            <tr><th><label for="wcaia_policy"><?php esc_html_e('سياسات المتجر', 'wc-ai-agent'); ?></label></th><td><textarea class="large-text" rows="5" id="wcaia_policy" name="wcaia_policy"><?php echo esc_textarea($policy); ?></textarea></td></tr></table>
            <p><button class="button button-primary" name="wcaia_save" value="1"><?php esc_html_e('حفظ الإعدادات', 'wc-ai-agent'); ?></button></p>
        </form></div><?php
    }

    public static function respond($message) {
        $message = trim(wp_strip_all_tags((string) $message));
        if ($message === '') { return ['reply' => 'اكتب سؤالك وأنا هساعدك.']; }
        $lower = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);

        // Greeting must be checked before product search.
        if (preg_match('/^(hi|hello|hey|welcome|مرحبا|مرحباً|اهلا|أهلا|أهلًا|السلام عليكم|صباح الخير|مساء الخير)[\s!.؟،]*$/iu', $message)) {
            return ['reply' => 'أهلًا بيك! 👋 أنا مساعد المتجر. أقدر أساعدك في البحث عن المنتجات، مقارنة الأسعار، متابعة الطلبات ومعرفة سياسات المتجر. تحب تدور على إيه؟'];
        }

        if (preg_match('/(سياسة|الشحن|استرجاع|استبدال|refund|shipping|return|faq|سؤال شائع)/iu', $message)) {
            $faq = trim((string) get_option('wcaia_faq', ''));
            $policy = trim((string) get_option('wcaia_policy', ''));
            return ['reply' => "معلومات المتجر:\n" . ($faq ?: 'لا توجد أسئلة شائعة مضافة بعد.') . "\n\nالسياسات:\n" . ($policy ?: 'يرجى مراجعة صفحات السياسات الرسمية في المتجر.')];
        }

        if (preg_match('/(مبيعات|تقرير|sales report|low stock|مخزون منخفض)/iu', $message)) {
            if (!current_user_can('manage_woocommerce')) {
                return ['reply' => 'تقارير المبيعات والمخزون متاحة لفريق إدارة المتجر فقط.'];
            }
            if (preg_match('/(مخزون منخفض|low stock)/iu', $message)) {
                $products = wc_get_products(['status' => 'publish', 'limit' => 10, 'stock_status' => 'instock', 'orderby' => 'meta_value_num', 'meta_key' => '_stock', 'order' => 'ASC']);
                $lines = [];
                foreach ($products as $product) {
                    if ($product->managing_stock() && $product->get_stock_quantity() !== null && $product->get_stock_quantity() <= wc_get_low_stock_amount($product)) {
                        $lines[] = $product->get_name() . ' — المتبقي: ' . $product->get_stock_quantity();
                    }
                }
                return ['reply' => $lines ? "المنتجات منخفضة المخزون:\n" . implode("\n", $lines) : 'لم أجد منتجات منخفضة المخزون ضمن النتائج الحالية.'];
            }
            $orders = wc_get_orders(['status' => array_keys(wc_get_order_statuses()), 'limit' => -1, 'return' => 'objects']);
            $total = 0;
            foreach ($orders as $order) { $total += (float) $order->get_total(); }
            return ['reply' => 'ملخص تجريبي: إجمالي قيمة الطلبات الموجودة حاليًا ' . wp_strip_all_tags(wc_price($total)) . '، وعدد الطلبات ' . count($orders) . '. يشمل الملخص الحالات الموجودة ولا يمثل بالضرورة صافي المبيعات المحاسبي.'];
        }

        if (preg_match('/(طلب|اوردر|أوردر|order|شحنة|الشحنة|تتبع|tracking)/iu', $message)) {
            return ['reply' => 'لمتابعة طلبك، اكتب رقم الطلب والبريد الإلكتروني المستخدم عند الشراء في نفس الرسالة. مثال: طلب 1234، البريد customer@example.com'];
        }

        // Search only when the message is not just a greeting and looks like a product request.
        $query = preg_replace('/^(ابحث عن|دور على|عايز|عايزة|أريد|اريد|منتج|products? for|search for)\s*/iu', '', $message);
        $query = trim($query);
        if ((function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query)) < 2 || preg_match('/^(شكرا|شكرًا|تمام|ok|okay|thanks|thank you)$/iu', $query)) {
            return ['reply' => 'أقدر أبحث لك عن منتج بالاسم، أو أساعدك في متابعة طلب أو معرفة سياسة الشحن والاسترجاع. اكتب اسم المنتج اللي بتدور عليه.'];
        }
        $products = wc_get_products(['status' => 'publish', 'limit' => 5, 's' => $query, 'orderby' => 'relevance']);
        if (!$products) {
            return ['reply' => 'ملقتش منتجات مطابقة للبحث ده. جرّب اسمًا مختلفًا أو جزءًا من اسم المنتج.'];
        }
        $lines = [];
        foreach ($products as $product) {
            // Use WooCommerce's formatted price HTML so variable products can show a price range.
            $formatted_price = $product->get_price_html();
            $price_text = ($formatted_price !== '') ? wp_strip_all_tags($formatted_price) : 'السعر غير محدد';
            $stock = $product->is_in_stock() ? 'متوفر' : 'غير متوفر';
            $lines[] = $product->get_name() . ' — السعر: ' . $price_text . ' — ' . $stock . ' — ' . get_permalink($product->get_id());
        }
        return ['reply' => "لقيت لك المنتجات التالية:\n" . implode("\n", $lines) . "\n\nتقدر تبعت اسم منتج تاني للبحث. هذه نسخة Demo بردود محلية."];
    }
}
