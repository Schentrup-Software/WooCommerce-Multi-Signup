<?php
/**
 * Tests for the Woocommerce_Multi_Signup class
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

class WoocommerceMultiSignupTest extends TestCase {

    protected $plugin;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        wcms_default_stubs();

        // Include the classes we're testing
        require_once __DIR__ . '/../php/woocommerce-multi-signup-data.php';
        require_once __DIR__ . '/../woocommerce-multi-signup.php';

        $this->plugin = new Woocommerce_Multi_Signup();
    }

    protected function tearDown(): void {
        Mockery::close();
        Monkey\tearDown();
        parent::tearDown();
    }

    private function studentDataJson() {
        return json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'john.doe@example.com'
                    ]
                ]
            ]
        ]);
    }

    private function postRequest(array $extensions, $create_account = null) {
        $request = new WP_REST_Request();
        $request->method = 'POST';
        $request->params = ['create_account' => $create_account, 'extensions' => $extensions];

        return $request;
    }

    private function mockCheckout($enabled, $required) {
        $checkout = Mockery::mock('WC_Checkout');
        $checkout->shouldReceive('is_registration_enabled')->andReturn($enabled);
        $checkout->shouldReceive('is_registration_required')->andReturn($required);

        $wc = Mockery::mock('WooCommerce');
        $wc->shouldReceive('checkout')->andReturn($checkout);
        Functions\when('WC')->justReturn($wc);
    }

    public function testPluginInstantiation() {
        $this->assertInstanceOf(Woocommerce_Multi_Signup::class, $this->plugin);
        $this->assertInstanceOf(Woocommerce_Multi_Signup_Groups::class, $this->plugin->groups);
    }

    public function testHooksAreRegistered() {
        $this->assertNotFalse(has_action('woocommerce_store_api_checkout_update_order_from_request', [$this->plugin, 'orddd_update_block_order_meta_student_data']));
        $this->assertNotFalse(has_action('admin_notices', [$this->plugin, 'maybe_show_requirements_notice']));
        $this->assertNotFalse(has_filter('llms_wc_do_default_enrollment', [$this->plugin->groups, 'maybe_skip_default_enrollment']));
        $this->assertNotFalse(has_action('llms_wc_order_item_fulfill', [$this->plugin->groups, 'fulfill_order_item']));
        // The old direct enrollment on order completion is gone.
        $this->assertFalse(has_action('woocommerce_order_status_completed'));
    }

    public function testDisplayStudentDataWithoutData() {
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn('');

        ob_start();
        $this->plugin->display_student_data_on_admin_order_details($order);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testDisplayStudentDataWithValidData() {
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($this->studentDataJson());
        $order->shouldReceive('get_items')->andReturn([]);

        ob_start();
        $this->plugin->display_student_data_on_admin_order_details($order);
        $output = ob_get_clean();

        $this->assertStringContainsString('Student Data', $output);
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('john.doe@example.com', $output);
        $this->assertStringContainsString('Math Course', $output);
        $this->assertStringNotContainsString('Group:', $output);
    }

    public function testDisplayStudentDataShowsLinkedGroupInAdmin() {
        $item = createMockOrderItem(123, 1, 55);
        $item->meta['_llms_group_id'] = 777;

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($this->studentDataJson());
        $order->shouldReceive('get_items')->andReturn([$item]);

        Functions\when('get_llms_group')->justReturn(createMockGroup(777));

        ob_start();
        $this->plugin->display_student_data_on_admin_order_details($order);
        $output = ob_get_clean();

        $this->assertStringContainsString('Group:', $output);
        $this->assertStringContainsString('https://example.com/groups/777', $output);
        $this->assertStringContainsString('Title 777', $output);
    }

    public function testCustomEmailNotification() {
        $user = Mockery::mock('WP_User');
        $user->user_login = 'testuser';

        Functions\when('get_option')->justReturn('admin@test.com');
        Functions\when('get_password_reset_key')->justReturn('reset_key_123');
        Functions\when('network_site_url')->returnArg();

        $originalEmail = [
            'to' => 'test@example.com',
            'subject' => 'Original Subject',
            'message' => 'Original message',
            'headers' => 'Original headers'
        ];

        $result = $this->plugin->custom_wp_new_user_notification_email($originalEmail, $user, 'Test Site');

        $this->assertEquals('Welcome to Test Site!', $result['subject']);
        $this->assertStringContainsString('Welcome to Test Site', $result['message']);
        $this->assertStringContainsString('testuser', $result['message']);
        $this->assertStringContainsString('reset_key_123', $result['message']);
    }

    public function testOrderMetaUpdateWithoutStudentData() {
        $order = Mockery::mock('WC_Order');
        $request = []; // No student data

        // Should not call any order methods
        $order->shouldNotReceive('update_meta_data');
        $order->shouldNotReceive('save');

        $this->plugin->orddd_update_block_order_meta_student_data($order, $request);

        $this->assertTrue(true); // Test passes if no exceptions
    }

    public function testOrderMetaUpdateWithStudentData() {
        $order = Mockery::mock('WC_Order');
        $studentData = '{"test": "data"}';
        $request = [
            'extensions' => [
                'woocommerce-multi-signup' => [
                    'student_data' => $studentData
                ]
            ]
        ];

        $order->shouldReceive('update_meta_data')->once()->with('Student Data', $studentData);
        $order->shouldReceive('save')->once();

        $this->plugin->orddd_update_block_order_meta_student_data($order, $request);

        $this->assertTrue(true); // Test passes if expectations are met
    }

    public function testOrderMetaUpdateWithMultipleStudents() {
        $order = Mockery::mock('WC_Order');
        $multiStudentData = json_encode([
            'students' => [
                '123' => [
                    ['email' => 'student1@example.com', 'firstName' => 'Student', 'lastName' => 'One'],
                    ['email' => 'student2@example.com', 'firstName' => 'Student', 'lastName' => 'Two']
                ],
                '456' => [
                    ['email' => 'student3@example.com', 'firstName' => 'Student', 'lastName' => 'Three']
                ]
            ]
        ]);

        $request = [
            'extensions' => [
                'woocommerce-multi-signup' => [
                    'student_data' => $multiStudentData
                ]
            ]
        ];

        $order->shouldReceive('update_meta_data')->once()->with('Student Data', $multiStudentData);
        $order->shouldReceive('save')->once();

        $this->plugin->orddd_update_block_order_meta_student_data($order, $request);

        $this->assertTrue(true); // Test passes if expectations are met
    }

    public function testAccountIsNotRequiredWhenNoStudentsAreListed() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(false, false);

        $this->plugin->require_account_for_students(json_encode(['students' => []]), $this->postRequest([]));

        $this->assertTrue(true);
    }

    public function testAccountIsNotRequiredForLoggedInBuyer() {
        Functions\when('is_user_logged_in')->justReturn(true);

        $this->plugin->require_account_for_students($this->studentDataJson(), $this->postRequest([]));

        $this->assertTrue(true);
    }

    public function testAccountIsNotRequiredWhenAccountWillBeCreated() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(true, false);

        $this->plugin->require_account_for_students($this->studentDataJson(), $this->postRequest([], true));

        $this->assertTrue(true);
    }

    public function testAccountIsNotRequiredWhenStoreRequiresRegistration() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(true, true);

        $this->plugin->require_account_for_students($this->studentDataJson(), $this->postRequest([], false));

        $this->assertTrue(true);
    }

    public function testGuestBuyerRegisteringStudentsIsRejected() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(true, false);

        $this->expectException(RouteException::class);
        $this->expectExceptionMessage('log in or create an account');

        $this->plugin->require_account_for_students($this->studentDataJson(), $this->postRequest([], false));
    }

    public function testGuestBuyerIsRejectedWhenRegistrationIsDisabled() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(false, false);

        $this->expectException(RouteException::class);

        $this->plugin->require_account_for_students($this->studentDataJson(), $this->postRequest([], true));
    }

    public function testAccountCheckOnlyRunsOnFinalCheckoutSubmission() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(false, false);

        $request = $this->postRequest([], false);
        $request->method = 'PUT';

        $this->plugin->require_account_for_students($this->studentDataJson(), $request);

        $this->assertTrue(true);
    }

    public function testOrderMetaIsNotSavedWhenGuestIsRejected() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(false, false);

        $order = Mockery::mock('WC_Order');
        $order->shouldNotReceive('update_meta_data');
        $order->shouldNotReceive('save');

        $request = $this->postRequest(['woocommerce-multi-signup' => ['student_data' => $this->studentDataJson()]], false);

        $this->expectException(RouteException::class);

        $this->plugin->orddd_update_block_order_meta_student_data($order, $request);
    }

    public function testOrderMetaIsSavedWhenGuestCreatesAnAccount() {
        Functions\when('is_user_logged_in')->justReturn(false);
        $this->mockCheckout(true, false);

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('update_meta_data')->once()->with('Student Data', $this->studentDataJson());
        $order->shouldReceive('save')->once();

        $request = $this->postRequest(['woocommerce-multi-signup' => ['student_data' => $this->studentDataJson()]], true);

        $this->plugin->orddd_update_block_order_meta_student_data($order, $request);

        $this->assertTrue(true);
    }

    public function testDisplayMultipleStudentsOnAdminOrderDetails() {
        $studentData = json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'john.doe@example.com'
                    ],
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Jane',
                        'lastName' => 'Smith',
                        'email' => 'jane.smith@example.com'
                    ]
                ],
                '456' => [
                    [
                        'courseName' => 'Science Course',
                        'firstName' => 'Bob',
                        'lastName' => 'Wilson',
                        'email' => 'bob.wilson@example.com'
                    ]
                ]
            ]
        ]);

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);
        $order->shouldReceive('get_items')->andReturn([]);

        ob_start();
        $this->plugin->display_student_data_on_admin_order_details($order);
        $output = ob_get_clean();

        // Should contain all three students
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('john.doe@example.com', $output);
        $this->assertStringContainsString('Jane Smith', $output);
        $this->assertStringContainsString('jane.smith@example.com', $output);
        $this->assertStringContainsString('Bob Wilson', $output);
        $this->assertStringContainsString('bob.wilson@example.com', $output);
        $this->assertStringContainsString('Math Course', $output);
        $this->assertStringContainsString('Science Course', $output);
    }

    public function testDisplayMultipleStudentsOnThankYouPage() {
        $studentData = json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Alice',
                        'lastName' => 'Johnson',
                        'email' => 'alice.johnson@example.com'
                    ],
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Charlie',
                        'lastName' => 'Brown',
                        'email' => 'charlie.brown@example.com'
                    ]
                ]
            ]
        ]);

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);
        $order->shouldReceive('get_items')->andReturn([]);

        Functions\when('wc_get_order')->justReturn($order);

        ob_start();
        $this->plugin->display_student_data_on_thankyou_page(123);
        $output = ob_get_clean();

        // Should contain table structure and both students
        $this->assertStringContainsString('<table>', $output);
        $this->assertStringContainsString('Alice Johnson', $output);
        $this->assertStringContainsString('alice.johnson@example.com', $output);
        $this->assertStringContainsString('Charlie Brown', $output);
        $this->assertStringContainsString('charlie.brown@example.com', $output);
        $this->assertStringContainsString('Math Course', $output);
        $this->assertStringNotContainsString('manage from your account', $output);
    }

    public function testThankYouPageLinksToTheGroup() {
        $item = createMockOrderItem(123, 1, 55);
        $item->meta['_llms_group_id'] = 777;

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($this->studentDataJson());
        $order->shouldReceive('get_items')->andReturn([$item]);

        Functions\when('get_llms_group')->justReturn(createMockGroup(777));

        ob_start();
        $this->plugin->display_student_data_on_thankyou_page($order);
        $output = ob_get_clean();

        $this->assertStringContainsString('manage from your account', $output);
        $this->assertStringContainsString('https://example.com/groups/777', $output);
        $this->assertStringContainsString('Title 777', $output);
    }

    public function testRequirementsNoticeWhenGroupsIsMissing() {
        ob_start();
        $this->plugin->maybe_show_requirements_notice();
        $output = ob_get_clean();

        // The LifterLMS Groups functions are stubbed in this suite, so only the WooCommerce
        // integration (whose class is never defined here) shows up as missing.
        $this->assertStringContainsString('notice-warning', $output);
        $this->assertStringContainsString('LifterLMS WooCommerce is not active.', $output);
        $this->assertStringContainsString('will not be enrolled', $output);
    }

    public function testRequirementsNoticeIsHiddenFromNonAdmins() {
        Functions\when('current_user_can')->justReturn(false);

        ob_start();
        $this->plugin->maybe_show_requirements_notice();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
