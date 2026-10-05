<?php
/**
 * PHPUnit bootstrap file for WooCommerce Multi Signup plugin tests
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs.php';

// Initialize Brain Monkey
Monkey\setUp();

// Define WordPress constants that might be used in the plugin
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

/**
 * Default stubs for the WordPress / WooCommerce / LifterLMS functions the plugin calls.
 *
 * Brain Monkey resets function redefinitions on tearDown, so tests call this from setUp()
 * and override individual functions as needed.
 */
function wcms_default_stubs() {
    Functions\when('__')->returnArg();
    Functions\when('esc_html')->alias(function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    });
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('absint')->alias(function ($value) {
        return abs((int) $value);
    });
    Functions\when('get_option')->justReturn('test@example.com');
    Functions\when('network_site_url')->returnArg();
    Functions\when('wp_generate_password')->justReturn('random_password_123');
    Functions\when('wp_mail')->justReturn(true);
    Functions\when('get_password_reset_key')->justReturn('test_key_123');
    Functions\when('wp_create_user')->justReturn(123);
    Functions\when('wp_update_user')->justReturn(true);
    Functions\when('wp_send_new_user_notifications')->justReturn(true);
    Functions\when('remove_all_filters')->justReturn(true);
    Functions\when('get_user_by')->justReturn(false);
    Functions\when('wc_get_order')->justReturn(null);
    Functions\when('get_the_title')->alias(function ($id) {
        return 'Title ' . $id;
    });
    Functions\when('get_permalink')->alias(function ($id) {
        return 'https://example.com/groups/' . $id;
    });
    Functions\when('is_user_logged_in')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);
    Functions\when('get_current_user_id')->justReturn(456);
    Functions\when('esc_html__')->returnArg();
    Functions\when('trailingslashit')->alias(function ($value) {
        return rtrim($value, '/') . '/';
    });
    Functions\when('user_trailingslashit')->alias(function ($value) {
        return rtrim($value, '/') . '/';
    });
    Functions\when('llms_get_page_url')->justReturn('https://example.com/dashboard/');
    Functions\when('llms_get_endpoint_url')->alias(function ($endpoint, $value = '', $permalink = '') {
        return $permalink . $endpoint . '/';
    });
    Functions\when('llms_get_student')->justReturn(null);
    Functions\when('wc_get_account_endpoint_url')->alias(function ($endpoint) {
        return 'https://example.com/my-account/' . $endpoint . '/';
    });
    Functions\when('is_llms_account_page')->justReturn(false);
    wcms_stub_llms_wc_account_endpoints(['view-groups' => ['endpoint' => 'my-groups', 'title' => 'My Groups']]);
    Functions\when('llms_get_page_id')->justReturn(10);
    Functions\when('wc_get_page_id')->justReturn(20);
    Functions\when('wp_safe_redirect')->justReturn(true);
    Functions\when('llms_groups_get_role_name')->alias(function ($role) {
        return 'admin' === $role ? 'Group Administrator' : ucfirst($role);
    });

    // LifterLMS core + Groups.
    Functions\when('llms_is_user_enrolled')->justReturn(false);
    Functions\when('llms_enroll_student')->justReturn(true);
    Functions\when('get_llms_group')->justReturn(null);
    Functions\when('llms_create_group')->justReturn(null);
    Functions\when('llms_groups_get_group_from_purchase_source')->justReturn(false);
    Functions\when('llms_groups_get_user_groups_for_product')->justReturn([]);
    Functions\when('llms_group_is_user_primary_admin')->justReturn(false);
    Functions\when('get_posts')->justReturn([]);
    Functions\when('llms_groups')->justReturn(new class {
        public function get_integration() {
            return new class {
                public function get_option($key, $default = '') {
                    return 'visibility' === $key ? 'closed' : $default;
                }
            };
        }
    });
    Functions\when('llms_groups_lock_seats')->justReturn(false);
    Functions\when('llms_groups_release_seats_lock')->justReturn(null);

    LLMS_Groups_Enrollment::reset();
    LLMS_Student_Dashboard::$enabled = ['view-groups'];
}

/**
 * Stub LLMS_WooCommerce() so its integration reports the given active My Account endpoints.
 */
function wcms_stub_llms_wc_account_endpoints(array $endpoints) {
    $integration = new class($endpoints) {
        private $endpoints;
        public function __construct($endpoints) { $this->endpoints = $endpoints; }
        public function get_account_endpoints($active_only = true) { return $this->endpoints; }
    };
    $plugin = new class($integration) {
        public $version = '3.1.0';
        private $integration;
        public function __construct($integration) { $this->integration = $integration; }
        public function get_integration() { return $this->integration; }
    };
    Functions\when('LLMS_WooCommerce')->justReturn($plugin);
}

wcms_default_stubs();

// Common test helper functions
function createMockOrder($order_id = 123, $user_id = 456, $meta_data = [], $items = []) {
    $order = Mockery::mock('WC_Order');
    $order->shouldReceive('get_id')->andReturn($order_id);
    $order->shouldReceive('get_user_id')->andReturn($user_id);
    $order->shouldReceive('get_meta')->andReturnUsing(function($key, $single = true) use ($meta_data) {
        return isset($meta_data[$key]) ? $meta_data[$key] : '';
    });
    $order->shouldReceive('update_meta_data')->andReturnSelf();
    $order->shouldReceive('save')->andReturnSelf();
    $order->notes = [];
    $order->shouldReceive('add_order_note')->andReturnUsing(function($note) use ($order) {
        $order->notes[] = $note;
        return count($order->notes);
    });
    $order->shouldReceive('get_formatted_billing_full_name')->andReturn('Pat Buyer');
    $order->shouldReceive('get_items')->andReturn($items);

    return $order;
}

function createMockOrderItem($product_id = 789, $quantity = 1, $item_id = 55, $variation_id = 0) {
    $item = Mockery::mock('WC_Order_Item_Product');
    $item->meta = [];
    $item->shouldReceive('get_id')->andReturn($item_id);
    $item->shouldReceive('get_product_id')->andReturn($product_id);
    $item->shouldReceive('get_variation_id')->andReturn($variation_id);
    $item->shouldReceive('get_quantity')->andReturn($quantity);
    $item->shouldReceive('get_meta')->andReturnUsing(function($key, $single = true) use ($item) {
        return $item->meta[$key] ?? '';
    });
    $item->shouldReceive('update_meta_data')->andReturnUsing(function($key, $value) use ($item) {
        $item->meta[$key] = $value;
        return $item;
    });
    $item->shouldReceive('save_meta_data')->andReturn(null);

    return $item;
}

function createMockPlan($plan_id = 900, $product_id = 456, $group_enrolment = 'no') {
    $plan = Mockery::mock('LLMS_Access_Plan');
    $plan->shouldReceive('get')->andReturnUsing(function($key) use ($plan_id, $product_id, $group_enrolment) {
        $props = [
            'id' => $plan_id,
            'product_id' => $product_id,
            'group_enrolment' => $group_enrolment,
        ];
        return $props[$key] ?? '';
    });

    return $plan;
}

/**
 * A fake LLMS_Group that records set() calls and reports seat usage.
 */
function createMockGroup($group_id = 777, $title = 'Test Group', $seats = ['total' => 3, 'used' => 1, 'open' => 2]) {
    $group = Mockery::mock('LLMS_Group');
    $group->props = ['id' => $group_id, 'title' => $title];
    $group->shouldReceive('get')->andReturnUsing(function($key) use ($group) {
        return $group->props[$key] ?? '';
    });
    $group->shouldReceive('set')->andReturnUsing(function($key, $value) use ($group) {
        $group->props[$key] = $value;
        return true;
    });
    $group->shouldReceive('get_seats')->andReturn($seats);

    return $group;
}

function createMockUser($id = 123, $email = 'test@example.com', $login = 'testuser') {
    $user = Mockery::mock('WP_User');
    $user->ID = $id;
    $user->user_login = $login;
    $user->user_email = $email;
    $user->display_name = $login;
    $user->first_name = '';
    $user->last_name = '';

    return $user;
}
