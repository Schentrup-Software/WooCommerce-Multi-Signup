<?php
/**
 * Tests for the "Groups I Manage" dashboard section.
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class WoocommerceMultiSignupDashboardTest extends TestCase {

    /** @var Woocommerce_Multi_Signup_Dashboard */
    protected $dashboard;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        wcms_default_stubs();

        require_once __DIR__ . '/../php/woocommerce-multi-signup-dashboard.php';

        $this->dashboard = new Woocommerce_Multi_Signup_Dashboard();
    }

    protected function tearDown(): void {
        Mockery::close();
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * A student enrolled in the given groups, with the given roles.
     */
    private function studentInGroups(array $group_ids, array $roles, $user_id = 456) {
        $student = Mockery::mock('LLMS_Student');
        $student->shouldReceive('get_enrollments')->with('llms_group', Mockery::type('array'))->andReturn([
            'found' => count($group_ids),
            'results' => $group_ids,
        ]);
        Functions\when('llms_get_student')->justReturn($student);
        LLMS_Groups_Enrollment::$roles[$user_id] = $roles;
    }

    private function groupsById(array $groups) {
        Functions\when('get_llms_group')->alias(function($id) use ($groups) {
            return $groups[$id] ?? null;
        });
    }

    public function testSectionIsHookedAfterMyCourses() {
        $this->assertSame(15, has_action('lifterlms_student_dashboard_index', [$this->dashboard, 'output_managed_groups_section']));
    }

    public function testOnlyGroupsTheUserAdministersOrLeadsAreReturned() {
        $this->studentInGroups([10, 20, 30], [10 => 'admin', 20 => 'member', 30 => 'leader']);

        $admin = createMockGroup(10, 'Smith Family', ['total' => 4, 'used' => 3, 'open' => 1]);
        $admin->props['post_id'] = 900;
        $leader = createMockGroup(30, 'Precinct 9', ['total' => 10, 'used' => 10, 'open' => 0]);
        $leader->props['post_id'] = 901;
        $this->groupsById([10 => $admin, 20 => createMockGroup(20), 30 => $leader]);

        $groups = $this->dashboard->get_managed_groups(456);

        // Sorted by title: "Precinct 9" before "Smith Family".
        $this->assertSame([30, 10], array_column($groups, 'id'));
        $this->assertSame('Leader', $groups[0]['role_label']);
        $this->assertSame([
            'id' => 10,
            'title' => 'Smith Family',
            'role' => 'admin',
            'role_label' => 'Group Administrator',
            'course' => 'Title 900',
            'seats_used' => 3,
            'seats_total' => 4,
            'url' => 'https://example.com/groups/10',
            'manage_url' => 'https://example.com/groups/10/members/',
        ], $groups[1]);
    }

    public function testGroupsTheUserOwnsAreIncludedEvenWithoutMembership() {
        // The buyer owns group 40 (post author) but is not enrolled in it and has no role meta yet.
        Functions\when('get_posts')->alias(function($args) {
            return 456 === $args['author'] && 'llms_group' === $args['post_type'] ? [40, 10] : [];
        });
        $this->studentInGroups([10, 20], [10 => 'admin', 20 => 'member']);
        $owned = createMockGroup(40, 'Agency Cohort');
        $this->groupsById([10 => createMockGroup(10, 'Smith Family'), 20 => createMockGroup(20), 40 => $owned]);

        $groups = $this->dashboard->get_managed_groups(456);

        $this->assertSame([40, 10], array_column($groups, 'id'));
        $this->assertSame('admin', $groups[0]['role']);
        $this->assertSame('Group Administrator', $groups[0]['role_label']);
    }

    public function testOwnedGroupsAreFoundWhenTheUserHasNoGroupEnrollmentsAtAll() {
        Functions\when('get_posts')->justReturn([40]);
        Functions\when('llms_get_student')->justReturn(null);
        $this->groupsById([40 => createMockGroup(40, 'Agency Cohort')]);

        $this->assertSame([40], array_column($this->dashboard->get_managed_groups(456), 'id'));
    }

    public function testGroupsThatNoLongerExistAreSkipped() {
        $this->studentInGroups([10, 11], [10 => 'admin', 11 => 'admin']);
        $this->groupsById([10 => createMockGroup(10)]);

        $this->assertSame([10], array_column($this->dashboard->get_managed_groups(456), 'id'));
    }

    public function testNoGroupsForLoggedOutOrUnknownUsers() {
        $this->assertSame([], $this->dashboard->get_managed_groups(0));

        Functions\when('llms_get_student')->justReturn(null);
        $this->assertSame([], $this->dashboard->get_managed_groups(456));
    }

    public function testNothingIsPrintedWhenTheUserManagesNoGroups() {
        $this->studentInGroups([20], [20 => 'member']);
        $this->groupsById([20 => createMockGroup(20)]);

        ob_start();
        $this->dashboard->output_managed_groups_section();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testSectionListsManagedGroupsWithManageLinksAndSeatUsage() {
        $this->studentInGroups([10], [10 => 'admin']);
        $group = createMockGroup(10, 'Smith <Family>', ['total' => 4, 'used' => 3, 'open' => 1]);
        $group->props['post_id'] = 900;
        $this->groupsById([10 => $group]);

        ob_start();
        $this->dashboard->output_managed_groups_section();
        $output = ob_get_clean();

        $this->assertStringContainsString('<section class="llms-sd-section llms-my-managed-groups wcms-managed-groups">', $output);
        $this->assertStringContainsString('<h3 class="llms-sd-section-title">Groups I Manage</h3>', $output);
        $this->assertStringContainsString('href="https://example.com/groups/10"', $output);
        $this->assertStringContainsString('Smith &lt;Family&gt;', $output);
        $this->assertStringContainsString('Title 900 · Group Administrator · 3 of 4 seats used', $output);
        $this->assertStringContainsString('href="https://example.com/groups/10/members/">Manage students</a>', $output);
        // "View all" goes to the WooCommerce My Account page, not the LifterLMS dashboard page.
        $this->assertStringContainsString('<footer class="llms-sd-section-footer"><a class="llms-button-secondary" href="https://example.com/my-account/my-groups/">View All My Groups</a></footer>', $output);
    }

    public function testSectionAlsoRendersOnTheWooCommerceAccountDashboard() {
        $this->assertNotFalse(has_action('woocommerce_account_dashboard', [$this->dashboard, 'output_managed_groups_section']));
    }

    public function testViewAllLinkFallsBackToTheLifterLmsDashboardWhenMyAccountLacksTheTab() {
        wcms_stub_llms_wc_account_endpoints(['view-courses' => ['endpoint' => 'my-courses', 'title' => 'My Courses']]);

        $this->assertSame('https://example.com/dashboard/view-groups/', $this->dashboard->get_my_groups_url());

        LLMS_Student_Dashboard::$enabled = [];
        $this->assertSame('', $this->dashboard->get_my_groups_url());
    }

    public function testViewAllLinkIsOmittedWhenTheGroupsTabIsDisabled() {
        wcms_stub_llms_wc_account_endpoints([]);
        LLMS_Student_Dashboard::$enabled = [];
        $this->studentInGroups([10], [10 => 'leader']);
        $this->groupsById([10 => createMockGroup(10)]);

        ob_start();
        $this->dashboard->output_managed_groups_section();
        $output = ob_get_clean();

        $this->assertStringContainsString('Manage students', $output);
        $this->assertStringNotContainsString('View All My Groups', $output);
        $this->assertStringNotContainsString('<footer', $output);
    }

    public function testOrderHistoryTabLinksToWooCommerceOrders() {
        $this->assertSame(50, has_filter('llms_get_student_dashboard_tabs', [$this->dashboard, 'link_order_history_to_woocommerce']));

        $tabs = [
            'dashboard' => ['endpoint' => false, 'title' => 'Dashboard', 'url' => 'https://example.com/dashboard/'],
            'orders' => ['content' => 'cb', 'endpoint' => 'orders', 'nav_item' => true, 'title' => 'Order History'],
            'signout' => ['endpoint' => false, 'title' => 'Sign Out'],
        ];

        $result = $this->dashboard->link_order_history_to_woocommerce($tabs);

        // Same tab, same title and position; only the destination changes.
        $this->assertSame(['dashboard', 'orders', 'signout'], array_keys($result));
        $this->assertSame('https://example.com/my-account/orders/', $result['orders']['url']);
        $this->assertSame('Order History', $result['orders']['title']);
        $this->assertSame($tabs['dashboard'], $result['dashboard']);
        $this->assertSame($tabs['signout'], $result['signout']);
    }

    public function testOrderHistoryTabIsUntouchedWithoutWooCommerceOrTab() {
        $tabs = ['orders' => ['endpoint' => 'orders', 'title' => 'Order History']];

        Functions\when('wc_get_account_endpoint_url')->justReturn('');
        $this->assertSame($tabs, $this->dashboard->link_order_history_to_woocommerce($tabs));

        $this->assertSame(['dashboard' => []], $this->dashboard->link_order_history_to_woocommerce(['dashboard' => []]));
    }

    public function testOldOrderHistoryEndpointRedirectsToWooCommerce() {
        $this->assertNotFalse(has_action('template_redirect', [$this->dashboard, 'maybe_redirect_order_history']));

        global $wp;
        $wp = (object) ['query_vars' => ['orders' => '']];
        Functions\when('is_llms_account_page')->justReturn(true);

        $this->assertSame('https://example.com/my-account/orders/', $this->dashboard->get_order_history_redirect_url());

        $redirected_to = null;
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirected_to) {
            $redirected_to = $url;
            throw new RuntimeException('exit'); // Stand-in for exit after the redirect.
        });
        try {
            $this->dashboard->maybe_redirect_order_history();
        } catch (RuntimeException $e) {
        }
        $this->assertSame('https://example.com/my-account/orders/', $redirected_to);
    }

    public function testNoRedirectOutsideTheLifterLmsOrdersEndpoint() {
        global $wp;

        // Not the orders endpoint.
        $wp = (object) ['query_vars' => ['view-courses' => '']];
        Functions\when('is_llms_account_page')->justReturn(true);
        $this->assertSame('', $this->dashboard->get_order_history_redirect_url());

        // WooCommerce's own orders page uses the same query var but is not the LifterLMS dashboard.
        $wp = (object) ['query_vars' => ['orders' => '']];
        Functions\when('is_llms_account_page')->justReturn(false);
        $this->assertSame('', $this->dashboard->get_order_history_redirect_url());

        // LifterLMS and WooCommerce share one account page: the endpoint is already WooCommerce's.
        Functions\when('is_llms_account_page')->justReturn(true);
        Functions\when('llms_get_page_id')->justReturn(20);
        $this->assertSame('', $this->dashboard->get_order_history_redirect_url());
    }

    public function testSeatUsageIsOmittedWhenUnknown() {
        $html = $this->dashboard->render_section([
            [
                'id' => 10,
                'title' => 'Precinct 9',
                'role' => 'leader',
                'role_label' => 'Leader',
                'course' => '',
                'seats_used' => null,
                'seats_total' => null,
                'url' => 'https://example.com/groups/10',
                'manage_url' => 'https://example.com/groups/10/members/',
            ],
        ]);

        $this->assertStringContainsString('<p class="wcms-managed-group-meta">Leader</p>', $html);
        $this->assertStringNotContainsString('seats used', $html);
    }
}
