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
require_once 'woocommerce-multi-signup-data.php';

class Woocommerce_Multi_Signup {
    public function __construct() {
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'orddd_update_block_order_meta_student_data' ), 10, 2 );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_student_data_on_admin_order_details' ) );
        add_action( 'woocommerce_order_details_before_order_table', array( $this, 'display_student_data_on_thankyou_page' ) );
		add_action( 'woocommerce_payment_complete', array( $this, 'student_enroll_on_woocommerce_payment_complete' ) );
		add_filter( 'wp_new_user_notification_email', 'custom_wp_new_user_notification_email', 10, 3 );
    }

	public function student_enroll_on_woocommerce_payment_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		$student_data = $order->get_meta( 'Student Data', true );
		if ( !$student_data ) {
			return;
		}

		//TODO: Check the order to make sure we are not enrolling more people than were paid for

		//TODO: Unenroll the person who paid for the course

		foreach ($student_data->student_data as $student) {
			$user = get_user_by( 'email', $student->student_email );
			if ( !$user ) {
				$user_id = wp_create_user( $student->student_email, wp_generate_password(), $student->student_email );
				$user = get_user_by( 'id', $user_id );
			}

			$llms_products = llms_wc_get_order_item_products( $student->course_product_id );

			if ( empty( $llms_products ) ) {
				wp_mail(
					get_option( 'admin_email' ),
					'Error enrolling student',
					'Could not find course for product ' . $student->course_product_id . ' for order ' . network_site_url( "wp-admin/admin.php?page=wc-orders&action=edit&id=$order_id" )
				);
				continue;
			}

			foreach ( $llms_products as $llms_product_id ) {
				if ( !llms_is_user_enrolled( $user->ID, $llms_product_id, 'wc_multi_order_' . $order_id ) ) {
					wp_mail(
						get_option( 'admin_email' ),
						'Error enrolling student',
						"Error enrolling student $student->student_email in course $student->course_name for order " . network_site_url( "wp-admin/admin.php?page=wc-orders&action=edit&id=$order_id" )
					);
					continue 2;
				}
			}
		}
	}

	public function custom_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
		$admin_email = get_option( 'admin_email' );
		$key = get_password_reset_key( $user );

		$message = "Welcome to $blogname," . "\r\n\r\n";
		$message .= "You have been registered for a course on our website. Your username is  $user->user_login." . "\r\n\r\n";
		$message .= "To set your password, visit the following address:" . "\r\n\r\n";
		$message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "\r\n\r\n";
		$message .= "After you have set up your password, you can see all the courses you are registered for here:" . "\r\n\r\n";
		$message .= network_site_url("my-account/my-courses") . "\r\n\r\n";
		$message .= "Thank you for doing business with $blogname! If you have any questions, please reach out to us at $admin_email." . "\r\n\r\n";
		$message .= "Kind regards," . "\r\n";
		$message .= "$blogname Team" . "\r\n";

		$wp_new_user_notification_email['message'] = $message;
		$wp_new_user_notification_email['headers'] = "From: $blogname<$admin_email>";

		return $wp_new_user_notification_email;
	}

    public function orddd_update_block_order_meta_student_data( $order, $request ) {
        $data = isset( $request['extensions']['woocommerce-multi-signup'] ) ? $request['extensions']['woocommerce-multi-signup'] : array();
        // Update the order meta with the delivery date from the request
        if ( !isset( $data['student_data'] ) ) {
            return;
        }

		$order->update_meta_data( 'Student Data', $data['student_data'] );
		$order->save(); // Save the order to persist changes
    }

    public function display_student_data_on_admin_order_details( $order ) {
        $student_data = $order->get_meta( 'Student Data', true );
        if ( !$student_data ) {
			return;
        }

		$student_data = new Woocommerce_Multi_Signup_Data( $student_data );

		echo '
			<div class="order_data_column">
				<h3>Student Data</h3>
		';
		foreach ( $student_data->student_data as $student ) {
			echo '
				<p>
					<strong>Name:</strong> ' . $student->student_first_name . ' ' . $student->student_last_name . '<br>
					<strong>Email:</strong> ' . $student->student_email . '<br>
					<strong>Course:</strong> ' . $student->course_name . '
				</p>
			';
		};
		echo '
			</div>
		';
    }

    public function display_student_data_on_thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );
        $student_data = $order->get_meta( 'Student Data', true );
        if ( !$student_data ) {
			return;
        }

		$student_data = new Woocommerce_Multi_Signup_Data( $student_data );

		echo '
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Course</th>
					</tr>
				</thead>
				<tbody>
			';
		foreach ( $student_data->student_data as $student ) {
			echo '
				<tr>
					<td>
						' . $student->student_first_name . ' ' . $student->student_last_name . '
					</td>
					<td>
						' . $student->student_email . '
					</td>
					<td>
						' . $student->course_name . '
					</td>
				</tr>
			';
		}
		echo '
				</tbody>
			</table>
		';
    }
}

$woocommerce_multi_signup = new Woocommerce_Multi_Signup();
