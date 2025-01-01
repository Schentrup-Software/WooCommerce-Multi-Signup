<?php
/**
 * Plugin Name: Woocommerce Multi Signup
 * Description: This plugin allows customers sign up multiple students for a class in a single checkout.
 * Author: Schentrup Software LLC
 * Version: 1.0.0
 * Author URI: https://www.schentrupsoftware.com/
 * Contributor: Joey Schentrup, https://www.schentrupsoftware.com/
 * Text Domain: woocommerce-multi-signup
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 7.1.0
 *
 * @package  woocommerce-multi-signup-Lite-for-WooCommerce
 */
require_once 'woocommerce-multi-signup-init.php';

class Woocommerce_Multi_Signup {
    public function __construct() {
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'orddd_update_block_order_meta_student_data' ), 10, 2 );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_student_data_on_admin_order_details' ) );
        add_action( 'woocommerce_order_details_after_order_table_items', array( $this, 'display_student_data_on_thankyou_page' ) );
    }
    public function orddd_update_block_order_meta_student_data( $order, $request ) {
        $data = isset( $request['extensions']['woocommerce-multi-signup'] ) ? $request['extensions']['woocommerce-multi-signup'] : array();
        // Update the order meta with the delivery date from the request
        if ( isset( $data['student_data'] ) ) {
            $order->update_meta_data( 'Student Data', $data['student_data'] );
            $order->save(); // Save the order to persist changes
        }
    }
    public function display_student_data_on_admin_order_details( $order ) {
        $student_data = $order->get_meta( 'Student Data', true );
        if ( $student_data ) {
            echo '<div class="delivery-date">';
            echo '<p><strong>' . esc_html__( 'Student Data:', 'woocommerce-multi-signup' ) . '</strong> ' . esc_html( $student_data ) . '</p>';
            echo '</div>';
        }
    }
    public function display_student_data_on_thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );
        $student_data = $order->get_meta( 'Student Data', true );
        if ( $student_data ) {
            echo '<p>' . esc_html__( 'Student Data:', 'woocommerce-multi-signup' ) . ' ' . esc_html( $student_data ) . '</p>';
        }
    }
}

$woocommerce_multi_signup = new Woocommerce_Multi_Signup();
