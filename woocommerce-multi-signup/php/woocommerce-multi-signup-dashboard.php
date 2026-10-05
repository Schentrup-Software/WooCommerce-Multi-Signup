<?php
/**
 * "Groups I Manage" section on the LifterLMS student dashboard.
 *
 * LifterLMS Groups lists every group a student belongs to under "My Groups", but a
 * buyer who registered other students has no quick way to reach the groups they
 * administer. This adds a section to the dashboard home that lists only the groups
 * the current user manages (administrator or leader), with seat usage and a direct
 * link to each group's Members tab.
 *
 * It also points the dashboard's "Order History" tab at WooCommerce's order list,
 * since purchases on this site go through WooCommerce and the LifterLMS order
 * history would always be empty.
 *
 * @package woocommerce-multi-signup
 */

class Woocommerce_Multi_Signup_Dashboard {

	/**
	 * Group roles that can manage members.
	 */
	const MANAGER_ROLES = array( 'admin', 'leader' );

	/**
	 * Right after the core "My Courses" section (priority 10).
	 */
	const SECTION_PRIORITY = 15;

	public function __construct() {
		add_action( 'lifterlms_student_dashboard_index', array( $this, 'output_managed_groups_section' ), self::SECTION_PRIORITY );
		add_action( 'woocommerce_account_dashboard', array( $this, 'output_managed_groups_section' ) );
		add_filter( 'llms_get_student_dashboard_tabs', array( $this, 'link_order_history_to_woocommerce' ), 50 );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_order_history' ) );
	}

	/**
	 * Point the dashboard's "Order History" tab at the WooCommerce order list.
	 *
	 * The dashboard navigation uses a tab's `url` when one is set, so the title and
	 * position stay the same and only the destination changes.
	 *
	 * @param array[] $tabs Dashboard tabs keyed by query var.
	 * @return array[]
	 */
	public function link_order_history_to_woocommerce( $tabs ) {
		$url = $this->get_woocommerce_orders_url();
		if ( $url && isset( $tabs['orders'] ) && is_array( $tabs['orders'] ) ) {
			$tabs['orders']['url'] = $url;
		}

		return $tabs;
	}

	/**
	 * Send visitors of the old LifterLMS order history endpoint to the WooCommerce order list.
	 *
	 * @return void
	 */
	public function maybe_redirect_order_history() {
		$url = $this->get_order_history_redirect_url();
		if ( $url ) {
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Where the current request should be redirected, or an empty string to stay put.
	 *
	 * Only the LifterLMS dashboard's own orders endpoint is redirected. WooCommerce's
	 * account page uses the same `orders` query var, so it is left alone to avoid a loop.
	 *
	 * @return string
	 */
	public function get_order_history_redirect_url() {
		global $wp;

		if ( empty( $wp ) || ! isset( $wp->query_vars['orders'] ) ) {
			return '';
		}

		if ( ! function_exists( 'is_llms_account_page' ) || ! is_llms_account_page() ) {
			return '';
		}

		// When LifterLMS and WooCommerce share one account page the orders endpoint is WooCommerce's already.
		if ( function_exists( 'llms_get_page_id' ) && function_exists( 'wc_get_page_id' ) && absint( llms_get_page_id( 'myaccount' ) ) === absint( wc_get_page_id( 'myaccount' ) ) ) {
			return '';
		}

		return $this->get_woocommerce_orders_url();
	}

	/**
	 * URL of the WooCommerce "Orders" account page, or an empty string when WooCommerce is unavailable.
	 *
	 * @return string
	 */
	public function get_woocommerce_orders_url() {
		if ( ! function_exists( 'wc_get_account_endpoint_url' ) ) {
			return '';
		}

		return (string) wc_get_account_endpoint_url( 'orders' );
	}

	/**
	 * Print the section on the dashboard home. Prints nothing for users who manage no groups.
	 *
	 * @return void
	 */
	public function output_managed_groups_section() {
		$groups = $this->get_managed_groups( get_current_user_id() );
		if ( empty( $groups ) ) {
			return;
		}

		echo $this->render_section( $groups, $this->get_my_groups_url() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in render_section().
	}

	/**
	 * Groups the user administers or leads.
	 *
	 * @param int $user_id WP_User ID.
	 * @return array[] Each entry has id, title, role, role_label, course, seats_used, seats_total, url and manage_url.
	 */
	public function get_managed_groups( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id || ! function_exists( 'llms_get_student' ) || ! function_exists( 'get_llms_group' ) || ! class_exists( 'LLMS_Groups_Enrollment' ) ) {
			return array();
		}

		$managed = array();
		foreach ( $this->get_candidate_group_ids( $user_id ) as $group_id => $is_owner ) {
			$role = LLMS_Groups_Enrollment::get_role( $user_id, $group_id );
			if ( $is_owner && ! $role ) {
				$role = 'admin';
			}
			if ( ! in_array( $role, self::MANAGER_ROLES, true ) ) {
				continue;
			}

			$group = get_llms_group( $group_id );
			if ( ! $group ) {
				continue;
			}

			$seats     = is_callable( array( $group, 'get_seats' ) ) ? (array) $group->get_seats() : array();
			$course_id = absint( $group->get( 'post_id' ) );

			$managed[] = array(
				'id'          => $group_id,
				'title'       => (string) $group->get( 'title' ),
				'role'        => $role,
				'role_label'  => function_exists( 'llms_groups_get_role_name' ) ? llms_groups_get_role_name( $role ) : ucfirst( $role ),
				'course'      => $course_id ? (string) get_the_title( $course_id ) : '',
				'seats_used'  => isset( $seats['used'] ) ? (int) $seats['used'] : null,
				'seats_total' => isset( $seats['total'] ) ? (int) $seats['total'] : null,
				'url'         => get_permalink( $group_id ),
				'manage_url'  => $this->get_members_url( $group_id ),
			);
		}

		usort(
			$managed,
			function ( $a, $b ) {
				return strcasecmp( $a['title'], $b['title'] );
			}
		);

		return $managed;
	}

	/**
	 * IDs of the groups the user owns or is enrolled in, keyed by group ID.
	 *
	 * Buyers own the groups created for their purchases without being members, so
	 * enrollments alone would miss exactly the groups this section is for.
	 *
	 * @param int $user_id WP_User ID.
	 * @return array<int,bool> Group ID => whether the user is the group's owner (primary administrator).
	 */
	protected function get_candidate_group_ids( $user_id ) {
		$ids = array();

		$owned = get_posts(
			array(
				'post_type'      => 'llms_group',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 100,
				'fields'         => 'ids',
			)
		);
		foreach ( (array) $owned as $group_id ) {
			$ids[ absint( $group_id ) ] = true;
		}

		$student = llms_get_student( $user_id );
		if ( $student ) {
			$enrollments = $student->get_enrollments(
				'llms_group',
				array(
					'limit'  => 100,
					'status' => 'enrolled',
				)
			);
			foreach ( (array) ( isset( $enrollments['results'] ) ? $enrollments['results'] : array() ) as $group_id ) {
				$group_id = absint( $group_id );
				if ( ! isset( $ids[ $group_id ] ) ) {
					$ids[ $group_id ] = false;
				}
			}
		}

		unset( $ids[0] );

		return $ids;
	}

	/**
	 * URL of the group's Members tab, where students are invited, removed and promoted.
	 *
	 * @param int $group_id Group ID.
	 * @return string
	 */
	public function get_members_url( $group_id ) {
		$slug = class_exists( 'LLMS_Groups_Profile' ) ? LLMS_Groups_Profile::get_tab_slug( 'members' ) : 'members';

		return user_trailingslashit( trailingslashit( get_permalink( $group_id ) ) . $slug );
	}

	/**
	 * URL of the "My Groups" tab, or an empty string when it is disabled everywhere.
	 *
	 * Prefers the WooCommerce My Account page, where LifterLMS WooCommerce shows the
	 * LifterLMS tabs, and falls back to the LifterLMS dashboard page.
	 *
	 * @return string
	 */
	public function get_my_groups_url() {
		$url = $this->get_woocommerce_account_endpoint_url( 'view-groups' );
		if ( $url ) {
			return $url;
		}

		if ( ! class_exists( 'LLMS_Student_Dashboard' ) || ! LLMS_Student_Dashboard::is_endpoint_enabled( 'view-groups' ) ) {
			return '';
		}

		return llms_get_endpoint_url( 'view-groups', '', llms_get_page_url( 'myaccount' ) );
	}

	/**
	 * URL of a LifterLMS dashboard tab on the WooCommerce My Account page.
	 *
	 * Empty when LifterLMS WooCommerce is inactive or does not show that tab there.
	 *
	 * @param string $tab_key LifterLMS dashboard tab key, e.g. "view-groups".
	 * @return string
	 */
	public function get_woocommerce_account_endpoint_url( $tab_key ) {
		if ( ! function_exists( 'LLMS_WooCommerce' ) || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
			return '';
		}

		$plugin      = LLMS_WooCommerce();
		$integration = $plugin && is_callable( array( $plugin, 'get_integration' ) ) ? $plugin->get_integration() : null;
		if ( ! $integration || ! is_callable( array( $integration, 'get_account_endpoints' ) ) ) {
			return '';
		}

		$endpoints = (array) $integration->get_account_endpoints();
		if ( empty( $endpoints[ $tab_key ]['endpoint'] ) ) {
			return '';
		}

		return (string) wc_get_account_endpoint_url( $endpoints[ $tab_key ]['endpoint'] );
	}

	/**
	 * Build the section markup, mirroring LifterLMS' own dashboard sections.
	 *
	 * @param array[] $groups   Groups from get_managed_groups().
	 * @param string  $more_url "View all" link, or an empty string for none.
	 * @return string
	 */
	public function render_section( $groups, $more_url = '' ) {
		$html  = '<section class="llms-sd-section llms-my-managed-groups wcms-managed-groups">';
		$html .= '<h3 class="llms-sd-section-title">' . esc_html__( 'Groups I Manage', 'woocommerce-multi-signup' ) . '</h3>';
		$html .= '<ul class="wcms-managed-groups-list">';

		foreach ( $groups as $group ) {
			$meta = array_filter(
				array(
					$group['course'],
					$group['role_label'],
					null === $group['seats_total'] ? '' : sprintf(
						/* translators: 1: seats used, 2: seats total */
						__( '%1$d of %2$d seats used', 'woocommerce-multi-signup' ),
						$group['seats_used'],
						$group['seats_total']
					),
				)
			);

			$html .= '<li class="wcms-managed-group">';
			$html .= '<a class="wcms-managed-group-title" href="' . esc_url( $group['url'] ) . '">' . esc_html( $group['title'] ) . '</a>';
			if ( ! empty( $meta ) ) {
				$html .= '<p class="wcms-managed-group-meta">' . esc_html( implode( ' · ', $meta ) ) . '</p>';
			}
			$html .= '<a class="llms-button-secondary small wcms-managed-group-manage" href="' . esc_url( $group['manage_url'] ) . '">' . esc_html__( 'Manage students', 'woocommerce-multi-signup' ) . '</a>';
			$html .= '</li>';
		}

		$html .= '</ul>';

		if ( $more_url ) {
			$html .= '<footer class="llms-sd-section-footer">';
			$html .= '<a class="llms-button-secondary" href="' . esc_url( $more_url ) . '">' . esc_html__( 'View All My Groups', 'woocommerce-multi-signup' ) . '</a>';
			$html .= '</footer>';
		}

		$html .= '</section>';

		return $html;
	}
}
