<?php
/**
 * Plugin Name: Woocommerce Multi Signup
 * Description: Lets a customer register multiple students, or a different student, for a course in a single checkout. The students are added to a LifterLMS Group the customer manages.
 * Author: Schentrup Software LLC
 * Version: 1.1.0
 * Author URI: https://www.schentrupsoftware.com/
 * Contributor: Joey Schentrup, https://www.schentrupsoftware.com/
 * Text Domain: woocommerce-multi-signup
 * Requires PHP: 8.1
 * WC requires at least: 3.0.0
 * WC tested up to: 11.1.2
 * LLMS WC requires at least: 3.1.0
 * LLMS Groups requires at least: 3.0.0
 *
 * @package  woocommerce-multi-signup-Lite-for-WooCommerce
 */
require_once __DIR__ . '/php/woocommerce-multi-signup-init.php';
require_once __DIR__ . '/php/woocommerce-multi-signup-data.php';
require_once __DIR__ . '/php/woocommerce-multi-signup-groups.php';

// Declare compatibility with WooCommerce features (HPOS and Cart/Checkout Blocks).
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

class Woocommerce_Multi_Signup {

	/**
	 * LifterLMS WooCommerce version that introduced the fulfillment hooks we rely on.
	 */
	const MIN_LLMS_WC_VERSION = '3.1.0';

	/**
	 * @var Woocommerce_Multi_Signup_Groups
	 */
	public $groups;

    public function __construct() {
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'orddd_update_block_order_meta_student_data' ), 10, 2 );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_student_data_on_admin_order_details' ) );
        add_action( 'woocommerce_order_details_before_order_table', array( $this, 'display_student_data_on_thankyou_page' ) );
		add_filter( 'wp_new_user_notification_email', array( $this, 'custom_wp_new_user_notification_email'), 10, 3 );
		add_action( 'admin_notices', array( $this, 'maybe_show_requirements_notice' ) );

		$this->groups = new Woocommerce_Multi_Signup_Groups();
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
		$wp_new_user_notification_email['subject'] = "Welcome to $blogname!";

		return $wp_new_user_notification_email;
	}

    public function orddd_update_block_order_meta_student_data( $order, $request ) {
        $data = isset( $request['extensions']['woocommerce-multi-signup'] ) ? $request['extensions']['woocommerce-multi-signup'] : array();
        if ( !isset( $data['student_data'] ) ) {
            return;
        }

		$this->require_account_for_students( $data['student_data'], $request );

		$order->update_meta_data( 'Student Data', $data['student_data'] );
		$order->save(); // Save the order to persist changes
    }

	/**
	 * Block the final checkout submission when other students are registered but the
	 * buyer will not have an account. The buyer's account owns the students' group.
	 *
	 * @param string $student_data_json Student data submitted by the checkout block.
	 * @param mixed  $request           Store API request.
	 * @return void
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When no account will exist for the buyer.
	 */
	public function require_account_for_students( $student_data_json, $request ) {
		if ( ! ( $request instanceof WP_REST_Request ) || 'POST' !== strtoupper( (string) $request->get_method() ) ) {
			return;
		}

		if ( ! $this->has_students( $student_data_json ) || is_user_logged_in() ) {
			return;
		}

		$checkout = function_exists( 'WC' ) && WC() && is_callable( array( WC(), 'checkout' ) ) ? WC()->checkout() : null;
		if ( $checkout && $checkout->is_registration_enabled() ) {
			$creates_account = $checkout->is_registration_required() || filter_var( $request->get_param( 'create_account' ), FILTER_VALIDATE_BOOLEAN );
			if ( $creates_account ) {
				return;
			}
		}

		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
			'woocommerce_multi_signup_account_required',
			__( 'Please log in or create an account to register other students. Your account is used to manage the students\' group.', 'woocommerce-multi-signup' ),
			400
		);
	}

	/**
	 * Warn administrators when the plugins this integration depends on are missing or outdated.
	 *
	 * @return void
	 */
	public function maybe_show_requirements_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$problems = array();

		if ( ! class_exists( 'LifterLMS_WooCommerce' ) ) {
			$problems[] = 'LifterLMS WooCommerce is not active.';
		} elseif ( function_exists( 'LLMS_WooCommerce' ) && version_compare( (string) LLMS_WooCommerce()->version, self::MIN_LLMS_WC_VERSION, '<' ) ) {
			$problems[] = sprintf( 'LifterLMS WooCommerce %1$s or newer is required (version %2$s is active).', self::MIN_LLMS_WC_VERSION, LLMS_WooCommerce()->version );
		}

		if ( ! function_exists( 'llms_create_group' ) ) {
			$problems[] = 'LifterLMS Groups is not active.';
		}

		if ( empty( $problems ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>Woocommerce Multi Signup:</strong> %s Students registered at checkout will not be enrolled until this is fixed.</p></div>',
			esc_html( implode( ' ', $problems ) )
		);
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
					<strong>Name:</strong> ' . esc_html( $student->student_first_name . ' ' . $student->student_last_name ) . '<br>
					<strong>Email:</strong> ' . esc_html( $student->student_email ) . '<br>
					<strong>Course:</strong> ' . esc_html( $student->course_name ) . '
				</p>
			';
		};
		foreach ( Woocommerce_Multi_Signup_Groups::get_groups_for_order( $order ) as $group_id => $title ) {
			echo '
				<p>
					<strong>Group:</strong> <a href="' . esc_url( get_permalink( $group_id ) ) . '">' . esc_html( $title ) . '</a>
				</p>
			';
		}
		echo '
			</div>
		';
    }

    public function display_student_data_on_thankyou_page( $order ) {
        $order = $order instanceof WC_Order ? $order : wc_get_order( $order );
        if ( ! $order ) {
            return;
        }

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
						' . esc_html( $student->student_first_name . ' ' . $student->student_last_name ) . '
					</td>
					<td>
						' . esc_html( $student->student_email ) . '
					</td>
					<td>
						' . esc_html( $student->course_name ) . '
					</td>
				</tr>
			';
		}
		echo '
				</tbody>
			</table>
		';

		$groups = Woocommerce_Multi_Signup_Groups::get_groups_for_order( $order );
		if ( ! empty( $groups ) ) {
			echo '<p>The students are members of the following group(s), which you can manage from your account:</p><ul>';
			foreach ( $groups as $group_id => $title ) {
				echo '<li><a href="' . esc_url( get_permalink( $group_id ) ) . '">' . esc_html( $title ) . '</a></li>';
			}
			echo '</ul>';
		}
    }

	private function has_students( $student_data_json ) {
		try {
			$data = new Woocommerce_Multi_Signup_Data( (string) $student_data_json );
		} catch ( Throwable $e ) {
			return false;
		}

		return count( $data->student_data ) > 0;
	}
}

$woocommerce_multi_signup = new Woocommerce_Multi_Signup();
