<?php
/**
 * PHPUnit bootstrap file for WooCommerce Multi Signup plugin tests
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

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

// Mock WordPress functions that are commonly used
Functions\when('__')->returnArg();
Functions\when('esc_html')->returnArg();
Functions\when('esc_attr')->returnArg();
Functions\when('wp_kses_post')->returnArg();
Functions\when('sanitize_text_field')->returnArg();
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
Functions\when('llms_wc_get_order_item_products')->justReturn([]);
Functions\when('llms_unenroll_student')->justReturn(true);
Functions\when('llms_is_user_enrolled')->justReturn(false);
Functions\when('llms_enroll_student')->justReturn(true);

// Common test helper functions
function createMockOrder($order_id = 123, $user_id = 456, $meta_data = []) {
    $order = Mockery::mock('WC_Order');
    $order->shouldReceive('get_id')->andReturn($order_id);
    $order->shouldReceive('get_user_id')->andReturn($user_id);
    $order->shouldReceive('get_meta')->andReturnUsing(function($key, $single = true) use ($meta_data) {
        return isset($meta_data[$key]) ? $meta_data[$key] : '';
    });
    $order->shouldReceive('update_meta_data')->andReturnSelf();
    $order->shouldReceive('save')->andReturnSelf();

    return $order;
}

function createMockOrderItem($product_id = 789, $quantity = 1) {
    $item = Mockery::mock('WC_Order_Item_Product');
    $item->shouldReceive('get_product_id')->andReturn($product_id);
    $item->shouldReceive('get_quantity')->andReturn($quantity);

    return $item;
}

function createMockUser($id = 123, $email = 'test@example.com', $login = 'testuser') {
    $user = Mockery::mock('WP_User');
    $user->ID = $id;
    $user->user_login = $login;
    $user->user_email = $email;
    $user->first_name = '';
    $user->last_name = '';

    return $user;
}
