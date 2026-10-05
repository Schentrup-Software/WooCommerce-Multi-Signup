<?php
/**
 * LifterLMS Groups fulfillment for multi-student purchases.
 *
 * When a buyer registers other students at checkout the WooCommerce -> LifterLMS
 * integration must not enroll the buyer. Instead a LifterLMS Group is created for
 * the purchased course (or membership), the buyer becomes the group's primary
 * administrator and every listed student is added to the group as a member.
 * Group membership cascades into course enrollment, so the buyer can later add,
 * remove and move students from the group's profile page.
 *
 * The buyer manages the group without being a member of it: they hold no seat and
 * get no course access unless they list themselves as one of the students.
 *
 * Relies on the hooks added in LifterLMS WooCommerce 3.1.0:
 *  - `llms_wc_do_default_enrollment` (filter) to skip the buyer's enrollment.
 *  - `llms_wc_order_item_fulfill` (action) to fulfill each order item / access plan.
 *
 * @package woocommerce-multi-signup
 */

class Woocommerce_Multi_Signup_Groups {

	/**
	 * Order item meta key LifterLMS Groups uses to link an order item to the group it
	 * created. Reusing it lets the Groups "Your Groups" thank-you section list our
	 * groups as well.
	 */
	const ITEM_META_GROUP_ID = '_llms_group_id';

	/**
	 * Prefix of the per-access-plan order item meta that keeps fulfillment idempotent
	 * when an order reaches its enrollment status more than once.
	 */
	const ITEM_META_PLAN_GROUP_PREFIX = '_wcms_group_id_';

	/**
	 * Run after LifterLMS Groups' own handlers (priority 10) so that a group created
	 * for a "group enrolment" access plan already exists when ours runs.
	 */
	const HOOK_PRIORITY = 20;

	public function __construct() {
		add_filter( 'llms_wc_do_default_enrollment', array( $this, 'maybe_skip_default_enrollment' ), self::HOOK_PRIORITY, 4 );
		add_action( 'llms_wc_order_item_fulfill', array( $this, 'fulfill_order_item' ), self::HOOK_PRIORITY, 4 );
		add_filter( 'llms_groups_profile_serve_404', array( $this, 'allow_managers_to_view_group' ), 10, 2 );
	}

	/**
	 * Let a group's administrators and leaders open its page even when they are not members.
	 *
	 * LifterLMS Groups hides "closed" groups from logged-in users who are not enrolled in
	 * them, which would lock a buyer out of the group they manage.
	 *
	 * @param bool       $serve_404 Whether Groups intends to serve a 404.
	 * @param LLMS_Group $group     Group being viewed.
	 * @return bool
	 */
	public function allow_managers_to_view_group( $serve_404, $group ) {
		if ( ! $serve_404 || ! $group || ! class_exists( 'LLMS_Groups_Enrollment' ) ) {
			return $serve_404;
		}

		$user_id  = get_current_user_id();
		$group_id = absint( $group->get( 'id' ) );
		if ( ! $user_id || ! $group_id ) {
			return $serve_404;
		}

		if ( function_exists( 'llms_group_is_user_primary_admin' ) && llms_group_is_user_primary_admin( $user_id, $group_id ) ) {
			return false;
		}

		return ! in_array( LLMS_Groups_Enrollment::get_role( $user_id, $group_id ), array( 'admin', 'leader' ), true );
	}

	/**
	 * Stop LifterLMS WooCommerce from enrolling (or unenrolling) the buyer for order
	 * items that were purchased for other students.
	 *
	 * @param bool             $do_enrollment Whether the integration should run its default enrollment.
	 * @param LLMS_Access_Plan $plan          Access plan linked to the order item.
	 * @param WC_Order_Item    $item          Order line item.
	 * @param WC_Order         $order         WooCommerce order.
	 * @return bool
	 */
	public function maybe_skip_default_enrollment( $do_enrollment, $plan, $item, $order ) {
		if ( ! $do_enrollment || ! $order || ! $item ) {
			return $do_enrollment;
		}

		return empty( $this->get_students_for_item( $order, $item ) );
	}

	/**
	 * Create the group for an order item and add the listed students to it.
	 *
	 * Fired once per access plan linked to an order item when the order reaches the
	 * enrollment status configured in LifterLMS WooCommerce.
	 *
	 * @param WC_Order         $order    WooCommerce order.
	 * @param WC_Order_Item    $item     Order line item.
	 * @param LLMS_Access_Plan $plan     Access plan linked to the order item.
	 * @param int              $quantity Line item quantity.
	 * @return void
	 */
	public function fulfill_order_item( $order, $item, $plan, $quantity ) {
		$students = $this->get_students_for_item( $order, $item );
		if ( empty( $students ) ) {
			return;
		}

		$order_id   = $order->get_id();
		$order_link = $this->get_order_link( $order_id );

		if ( ! $this->is_groups_available() ) {
			$this->send_error_email( "LifterLMS Groups is not active, so no group could be created for order $order_link. The listed students were not enrolled." );
			return;
		}

		$buyer_id = absint( $order->get_user_id() );
		if ( ! $buyer_id ) {
			$this->send_error_email( "Order $order_link has no customer account, so no group could be created. The listed students were not enrolled." );
			return;
		}

		$quantity = absint( $quantity );
		if ( count( $students ) > $quantity ) {
			$this->send_error_email( sprintf( 'Order %1$s lists %2$d students for "%3$s" but only %4$d were purchased. The extra students were not added.', $order_link, count( $students ), $students[0]->course_name, $quantity ) );
			$students = array_slice( $students, 0, $quantity );
		}

		$reused = false;
		$group  = $this->get_or_create_group( $order, $item, $plan, $quantity, $buyer_id, $reused );
		if ( ! $group ) {
			$this->send_error_email( "Could not create a group for order $order_link. The listed students were not enrolled." );
			return;
		}

		// Link the item to the group before touching members so a retry reuses this group.
		$this->link_item_to_group( $item, $plan, $group );

		$added = 0;
		foreach ( $students as $student ) {
			$user = $this->get_or_create_student_user( $student, $order_id );
			if ( ! $user ) {
				continue;
			}

			if ( true === $this->add_student_to_group( $user, $group, $order_id, $buyer_id ) ) {
				$added++;
			}
		}

		$this->ensure_seats( $group );

		$order->add_order_note(
			sprintf(
				'Woocommerce Multi Signup: %1$d of %2$d student(s) added to %3$s group "%4$s" (%5$s).',
				$added,
				count( $students ),
				$reused ? 'the existing' : 'the new',
				$group->get( 'title' ),
				get_permalink( $group->get( 'id' ) )
			)
		);
	}

	/**
	 * Groups linked to an order's items, keyed by group ID.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array<int,string> Group ID => group title.
	 */
	public static function get_groups_for_order( $order ) {
		$groups = array();

		if ( ! $order || ! function_exists( 'get_llms_group' ) ) {
			return $groups;
		}

		foreach ( $order->get_items() as $item ) {
			$group_id = absint( $item->get_meta( self::ITEM_META_GROUP_ID, true ) );
			if ( $group_id && get_llms_group( $group_id ) ) {
				$groups[ $group_id ] = get_the_title( $group_id );
			}
		}

		return $groups;
	}

	/**
	 * Students registered at checkout for the product (or variation) of an order item.
	 *
	 * @param WC_Order      $order WooCommerce order.
	 * @param WC_Order_Item $item  Order line item.
	 * @return Woocommerce_Multi_Signup_Data_Student[]
	 */
	public function get_students_for_item( $order, $item ) {
		$raw = $order->get_meta( 'Student Data', true );
		if ( empty( $raw ) ) {
			return array();
		}

		try {
			$data = new Woocommerce_Multi_Signup_Data( $raw );
		} catch ( Throwable $e ) {
			return array();
		}

		$ids = array();
		if ( is_callable( array( $item, 'get_variation_id' ) ) && $item->get_variation_id() ) {
			$ids[] = $item->get_variation_id();
		}
		$ids[] = $item->get_product_id();

		return $data->get_students_for_products( $ids );
	}

	/**
	 * Whether every LifterLMS Groups and LifterLMS function we rely on exists.
	 *
	 * @return bool
	 */
	protected function is_groups_available() {
		return class_exists( 'LLMS_Group' )
			&& function_exists( 'get_llms_group' )
			&& class_exists( 'LLMS_Groups_Enrollment' )
			&& function_exists( 'llms_enroll_student' )
			&& function_exists( 'llms_is_user_enrolled' );
	}

	/**
	 * Find the group for this order item and access plan, creating it when needed.
	 *
	 * @param WC_Order         $order    WooCommerce order.
	 * @param WC_Order_Item    $item     Order line item.
	 * @param LLMS_Access_Plan $plan     Access plan linked to the order item.
	 * @param int              $quantity Line item quantity (the number of seats purchased).
	 * @param int              $buyer_id WP_User ID of the buyer.
	 * @param bool             $reused   Set to `true` when an existing group was reused rather than created.
	 * @return LLMS_Group|null
	 */
	protected function get_or_create_group( $order, $item, $plan, $quantity, $buyer_id, &$reused = false ) {
		$plan_id = absint( $plan->get( 'id' ) );
		$reused  = false;

		// Created by an earlier run for this item and plan.
		$group = $this->get_group( $item->get_meta( self::ITEM_META_PLAN_GROUP_PREFIX . $plan_id, true ) );
		if ( $group ) {
			$reused = true;
			return $group;
		}

		// A "group enrolment" access plan: LifterLMS Groups created the group before us.
		if ( 'yes' === $plan->get( 'group_enrolment' ) ) {
			$group = $this->get_group( $item->get_meta( self::ITEM_META_GROUP_ID, true ) );
			if ( ! $group && function_exists( 'llms_groups_get_group_from_purchase_source' ) ) {
				$group = $this->get_group( llms_groups_get_group_from_purchase_source( array( 'wc_order_item_id' => $item->get_id() ) ) );
			}
			$reused = (bool) $group;
			return $group;
		}

		$course_id = absint( $plan->get( 'product_id' ) );

		// The buyer already owns a group for this course: add the purchased seats to it.
		$group = $this->get_existing_buyer_group( $buyer_id, $course_id );
		if ( $group ) {
			$this->add_seats( $group, $quantity );
			$reused = true;
			return $group;
		}

		$group = $this->create_group_post(
			array(
				'post_status' => 'publish',
				'post_author' => $buyer_id,
				'post_title'  => $this->get_group_title( $course_id, $buyer_id, $order ),
				'meta_input'  => array(
					'_llms_visibility' => $this->get_default_visibility(),
				),
			)
		);
		if ( ! $group || ! $group->get( 'id' ) ) {
			return null;
		}

		$group->set( 'post_id', $course_id );
		// Seats are exactly what was purchased; the buyer manages the group without holding one.
		$group->set( 'seats', $quantity );
		$group->set( 'wc_order_id', absint( $order->get_id() ) );
		$group->set( 'wc_order_item_id', absint( $item->get_id() ) );

		/*
		 * Make the buyer the primary administrator without enrolling them in the group.
		 * LifterLMS Groups grants management capabilities from this role meta, while
		 * membership (and the course access that comes with it) is only given to students.
		 */
		LLMS_Groups_Enrollment::update_role( $buyer_id, $group->get( 'id' ), 'primary_admin' );

		return $group;
	}

	/**
	 * Create the group post.
	 *
	 * Deliberately bypasses the Groups add-on's group creation helper, which would
	 * enroll the author as a member.
	 *
	 * @param array $args Arguments for wp_insert_post().
	 * @return LLMS_Group|null
	 */
	protected function create_group_post( $args ) {
		$group = new LLMS_Group( 'new', $args );

		return $group->get( 'id' ) ? $group : null;
	}

	/**
	 * Site-wide default group visibility from the LifterLMS Groups settings.
	 *
	 * @return string
	 */
	protected function get_default_visibility() {
		if ( function_exists( 'llms_groups' ) && is_callable( array( llms_groups(), 'get_integration' ) ) ) {
			$visibility = llms_groups()->get_integration()->get_option( 'visibility' );
			if ( $visibility ) {
				return (string) $visibility;
			}
		}

		return 'closed';
	}

	/**
	 * The group the buyer already owns for a course, so repeat purchases top it up
	 * instead of creating another group.
	 *
	 * @param int $buyer_id  WP_User ID of the buyer.
	 * @param int $course_id Course or membership ID.
	 * @return LLMS_Group|null
	 */
	protected function get_existing_buyer_group( $buyer_id, $course_id ) {
		if ( ! function_exists( 'llms_groups_get_user_groups_for_product' ) ) {
			return null;
		}

		$posts = llms_groups_get_user_groups_for_product( $buyer_id, $course_id );
		if ( empty( $posts ) || ! is_array( $posts ) ) {
			return null;
		}

		// Always pick the buyer's oldest group so every purchase lands in the same one.
		usort(
			$posts,
			function ( $a, $b ) {
				return absint( $a->ID ) <=> absint( $b->ID );
			}
		);

		return $this->get_group( $posts[0]->ID );
	}

	/**
	 * Add purchased seats to a group.
	 *
	 * Uses the LifterLMS Groups seat lock (when available) so a concurrent fulfillment
	 * cannot lose an increment.
	 *
	 * @param LLMS_Group $group Group.
	 * @param int        $seats Seats to add.
	 * @return void
	 */
	protected function add_seats( $group, $seats ) {
		$seats = (int) $seats;
		if ( $seats <= 0 ) {
			return;
		}

		$group_id = absint( $group->get( 'id' ) );
		$locked   = function_exists( 'llms_groups_lock_seats' ) && llms_groups_lock_seats( $group_id );

		$group->set( 'seats', absint( $group->get( 'seats' ) ) + $seats );

		if ( $locked && function_exists( 'llms_groups_release_seats_lock' ) ) {
			llms_groups_release_seats_lock( $group_id );
		}
	}

	/**
	 * Resolve a group from an ID or LLMS_Group instance.
	 *
	 * @param mixed $group Group ID, LLMS_Group, or anything falsy.
	 * @return LLMS_Group|null
	 */
	protected function get_group( $group ) {
		if ( is_object( $group ) && is_callable( array( $group, 'get' ) ) ) {
			return $group;
		}

		$group_id = absint( $group );
		if ( ! $group_id ) {
			return null;
		}

		$group = get_llms_group( $group_id );

		return $group ? $group : null;
	}

	/**
	 * Store the group on the order item.
	 *
	 * @param WC_Order_Item    $item  Order line item.
	 * @param LLMS_Access_Plan $plan  Access plan.
	 * @param LLMS_Group       $group Group.
	 * @return void
	 */
	protected function link_item_to_group( $item, $plan, $group ) {
		$group_id = absint( $group->get( 'id' ) );

		$item->update_meta_data( self::ITEM_META_PLAN_GROUP_PREFIX . absint( $plan->get( 'id' ) ), $group_id );
		if ( ! absint( $item->get_meta( self::ITEM_META_GROUP_ID, true ) ) ) {
			$item->update_meta_data( self::ITEM_META_GROUP_ID, $group_id );
		}
		$item->save_meta_data();
	}

	/**
	 * Add a student to the group (and therefore to the course).
	 *
	 * @param WP_User    $user     Student.
	 * @param LLMS_Group $group    Group.
	 * @param int        $order_id WooCommerce order ID.
	 * @param int        $buyer_id WP_User ID of the buyer.
	 * @return bool|null `true` when added, `null` when already a member, `false` on failure (an error email is sent).
	 */
	protected function add_student_to_group( $user, $group, $order_id, $buyer_id ) {
		$group_id   = absint( $group->get( 'id' ) );
		$user_id    = absint( $user->ID );
		$order_link = $this->get_order_link( $order_id );

		if ( llms_is_user_enrolled( $user_id, $group_id ) ) {
			return null;
		}

		// A buyer who lists themselves becomes a member (and a student) too, keeping their administrator role.
		$role = $user_id === absint( $buyer_id ) ? 'admin' : 'member';

		if ( LLMS_Groups_Enrollment::add( $user_id, $group_id, 'wc_order_' . $order_id, $role ) ) {
			return true;
		}

		$this->send_error_email( "Error adding student $user->user_email to group $group_id for order $order_link" );
		return false;
	}

	/**
	 * Make sure the group has at least as many seats as it has members and pending invitations.
	 *
	 * @param LLMS_Group $group Group.
	 * @return void
	 */
	protected function ensure_seats( $group ) {
		if ( ! is_callable( array( $group, 'get_seats' ) ) ) {
			return;
		}

		$seats = $group->get_seats( false );
		if ( isset( $seats['used'], $seats['total'] ) && $seats['used'] > $seats['total'] ) {
			$group->set( 'seats', absint( $seats['used'] ) );
		}
	}

	/**
	 * Title for a group created from an order: "<course> - <buyer name>".
	 *
	 * @param int      $course_id Course or membership ID.
	 * @param int      $buyer_id  WP_User ID of the buyer.
	 * @param WC_Order $order     WooCommerce order.
	 * @return string
	 */
	protected function get_group_title( $course_id, $buyer_id, $order ) {
		$name = is_callable( array( $order, 'get_formatted_billing_full_name' ) ) ? trim( (string) $order->get_formatted_billing_full_name() ) : '';

		if ( '' === $name ) {
			$buyer = get_user_by( 'id', $buyer_id );
			$name  = $buyer && ! empty( $buyer->display_name ) ? $buyer->display_name : '';
		}

		if ( '' === $name ) {
			$name = 'Order #' . $order->get_id();
		}

		return sprintf( '%1$s - %2$s', get_the_title( $course_id ), $name );
	}

	/**
	 * Find the WordPress user for a student, creating one (and emailing them) when needed.
	 *
	 * @param Woocommerce_Multi_Signup_Data_Student $student  Student.
	 * @param int                                     $order_id WooCommerce order ID.
	 * @return WP_User|null
	 */
	protected function get_or_create_student_user( $student, $order_id ) {
		$order_link = $this->get_order_link( $order_id );

		if ( empty( $student->student_email ) ) {
			$this->send_error_email( "Student email is required for order $order_link" );
			return null;
		}

		$user = get_user_by( 'email', $student->student_email );
		if ( $user ) {
			return $user;
		}

		remove_all_filters( 'wp_pre_insert_user_data' ); // Remove any filters that might prevent user creation.
		$user_id = wp_create_user( $student->student_email, wp_generate_password(), $student->student_email );
		if ( is_wp_error( $user_id ) ) {
			$this->send_error_email( "Error creating user for student $student->student_email for order $order_link: " . $user_id->get_error_message() );
			return null;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			$this->send_error_email( "Error loading the new user for student $student->student_email for order $order_link" );
			return null;
		}

		$user->first_name = $student->student_first_name;
		$user->last_name  = $student->student_last_name;
		wp_update_user( $user );

		remove_all_filters( 'wp_send_new_user_notification_to_user' ); // Remove any filters that might prevent user notification.
		wp_send_new_user_notifications( $user_id, 'user' );

		return $user;
	}

	protected function send_error_email( $message ) {
		wp_mail(
			get_option( 'admin_email' ),
			'Error enrolling student',
			$message
		);
	}

	protected function get_order_link( $order_id ) {
		return network_site_url( "wp-admin/admin.php?page=wc-orders&action=edit&id=$order_id" );
	}
}
