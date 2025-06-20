<?php
/**
 * Simple tests for Woocommerce_Multi_Signup class
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Mockery;

class WoocommerceMultiSignupTest extends TestCase {

    protected $plugin;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

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

    public function testPluginInstantiation() {
        $this->assertInstanceOf(Woocommerce_Multi_Signup::class, $this->plugin);
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
        $studentData = json_encode([
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

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);

        ob_start();
        $this->plugin->display_student_data_on_admin_order_details($order);
        $output = ob_get_clean();

        $this->assertStringContainsString('Student Data', $output);
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('john.doe@example.com', $output);
        $this->assertStringContainsString('Math Course', $output);
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

    public function testPrivateGetOrderLink() {
        Functions\when('network_site_url')->returnArg();

        $reflection = new ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_order_link');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin, 123);

        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('wp-admin', $result);
    }

    public function testPrivateSendErrorEmail() {
        Functions\when('get_option')->justReturn('admin@test.com');
        Functions\when('wp_mail')->justReturn(true);

        $reflection = new ReflectionClass($this->plugin);
        $method = $reflection->getMethod('send_error_email');
        $method->setAccessible(true);

        // Should not throw any exceptions
        $method->invoke($this->plugin, 'Test error message');

        $this->assertTrue(true);
    }

    public function testStudentEnrollmentWithoutStudentData() {
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn('');

        Functions\when('wc_get_order')->justReturn($order);

        // Should return early without doing anything
        $this->plugin->student_enroll_on_woocommerce_payment_complete(123);

        $this->assertTrue(true); // Test passes if no exceptions
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
    }

    public function testStudentEnrollmentWithMultipleStudents() {
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
                ]
            ]
        ]);

        // Mock order and order items
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);
        $order->shouldReceive('get_user_id')->andReturn(456);
        $order->shouldReceive('get_id')->andReturn(123);

        $orderItem = Mockery::mock('WC_Order_Item_Product');
        $orderItem->shouldReceive('get_product_id')->andReturn(123);
        $orderItem->shouldReceive('get_quantity')->andReturn(2);

        $order->shouldReceive('get_items')->andReturn([$orderItem]);

        Functions\when('wc_get_order')->justReturn($order);

        // Mock user functions - simulate existing users
        Functions\when('get_user_by')->alias(function($field, $value) {
            if ($value === 'john.doe@example.com') {
                return createMockUser(789, 'john.doe@example.com', 'john.doe');
            } elseif ($value === 'jane.smith@example.com') {
                return createMockUser(790, 'jane.smith@example.com', 'jane.smith');
            }
            return false;
        });

        // Mock LifterLMS functions
        Functions\when('llms_wc_get_order_item_products')->justReturn([456]);
        Functions\when('llms_unenroll_student')->justReturn(true);
        Functions\when('llms_is_user_enrolled')->justReturn(false);
        Functions\when('llms_enroll_student')->justReturn(true);

        // Execute enrollment
        $this->plugin->student_enroll_on_woocommerce_payment_complete(123);

        $this->assertTrue(true); // Test passes if no exceptions
    }

    public function testStudentEnrollmentWithMixedExistingAndNewUsers() {
        $studentData = json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Existing',
                        'lastName' => 'User',
                        'email' => 'existing@example.com'
                    ],
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'New',
                        'lastName' => 'User',
                        'email' => 'new@example.com'
                    ]
                ]
            ]
        ]);

        // Mock order
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);
        $order->shouldReceive('get_user_id')->andReturn(456);
        $order->shouldReceive('get_id')->andReturn(123);

        $orderItem = Mockery::mock('WC_Order_Item_Product');
        $orderItem->shouldReceive('get_product_id')->andReturn(123);
        $orderItem->shouldReceive('get_quantity')->andReturn(2);
        $order->shouldReceive('get_items')->andReturn([$orderItem]);

        Functions\when('wc_get_order')->justReturn($order);

        // Mock user functions - one exists, one doesn't
        Functions\when('get_user_by')->alias(function($field, $value) {
            if ($value === 'existing@example.com') {
                return createMockUser(789, 'existing@example.com', 'existing');
            }
            return false; // new@example.com doesn't exist
        });

        Functions\when('wp_create_user')->justReturn(791);
        Functions\when('wp_update_user')->justReturn(true);
        Functions\when('wp_send_new_user_notifications')->justReturn(true);
        Functions\when('remove_all_filters')->justReturn(true);
        Functions\when('wp_generate_password')->justReturn('random_password_123');

        // Mock get_user_by for the newly created user
        Functions\when('get_user_by')->alias(function($field, $value) {
            if ($value === 'existing@example.com') {
                return createMockUser(789, 'existing@example.com', 'existing');
            } elseif ($field === 'id' && $value === 791) {
                return createMockUser(791, 'new@example.com', 'new');
            }
            return false;
        });

        // Mock LifterLMS functions
        Functions\when('llms_wc_get_order_item_products')->justReturn([456]);
        Functions\when('llms_unenroll_student')->justReturn(true);
        Functions\when('llms_is_user_enrolled')->justReturn(false);
        Functions\when('llms_enroll_student')->justReturn(true);

        // Execute enrollment
        $this->plugin->student_enroll_on_woocommerce_payment_complete(123);

        $this->assertTrue(true); // Test passes if no exceptions
    }

    public function testStudentEnrollmentWithInsufficientQuantity() {
        $studentData = json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Student',
                        'lastName' => 'One',
                        'email' => 'student1@example.com'
                    ],
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Student',
                        'lastName' => 'Two',
                        'email' => 'student2@example.com'
                    ],
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Student',
                        'lastName' => 'Three',
                        'email' => 'student3@example.com'
                    ]
                ]
            ]
        ]);

        // Mock order with insufficient quantity (only 2 but 3 students)
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);
        $order->shouldReceive('get_user_id')->andReturn(456);
        $order->shouldReceive('get_id')->andReturn(123);

        $orderItem = Mockery::mock('WC_Order_Item_Product');
        $orderItem->shouldReceive('get_product_id')->andReturn(123);
        $orderItem->shouldReceive('get_quantity')->andReturn(2); // Only 2 quantity for 3 students
        $order->shouldReceive('get_items')->andReturn([$orderItem]);

        Functions\when('wc_get_order')->justReturn($order);
        Functions\when('wp_mail')->justReturn(true); // Mock error email
        Functions\when('llms_unenroll_student')->justReturn(true);
        Functions\when('remove_all_filters')->justReturn(true);
        Functions\when('wp_generate_password')->justReturn('random_password_123');
        Functions\when('wp_create_user')->justReturn(793);
        Functions\when('wp_update_user')->justReturn(true);
        Functions\when('wp_send_new_user_notifications')->justReturn(true);
        Functions\when('network_site_url')->returnArg();
        Functions\when('get_option')->justReturn('admin@test.com');

        // Mock get_user_by for newly created users
        Functions\when('get_user_by')->alias(function($field, $value) {
            if ($field === 'id' && $value === 793) {
                return createMockUser(793, 'created@example.com', 'created');
            }
            return false; // All email lookups return false (new users)
        });

        // Mock LifterLMS functions
        Functions\when('llms_wc_get_order_item_products')->justReturn([456]);
        Functions\when('llms_is_user_enrolled')->justReturn(false);
        Functions\when('llms_enroll_student')->justReturn(true);

        // Should handle the insufficient quantity gracefully
        $this->plugin->student_enroll_on_woocommerce_payment_complete(123);

        $this->assertTrue(true); // Test passes if no exceptions
    }

    public function testStudentEnrollmentWithMultipleCoursesAndStudents() {
        $studentData = json_encode([
            'students' => [
                '123' => [ // Math course
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Math',
                        'lastName' => 'Student1',
                        'email' => 'math1@example.com'
                    ],
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'Math',
                        'lastName' => 'Student2',
                        'email' => 'math2@example.com'
                    ]
                ],
                '456' => [ // Science course
                    [
                        'courseName' => 'Science Course',
                        'firstName' => 'Science',
                        'lastName' => 'Student1',
                        'email' => 'science1@example.com'
                    ]
                ]
            ]
        ]);

        // Mock order with multiple products
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('Student Data', true)->andReturn($studentData);
        $order->shouldReceive('get_user_id')->andReturn(456);
        $order->shouldReceive('get_id')->andReturn(123);

        $mathItem = Mockery::mock('WC_Order_Item_Product');
        $mathItem->shouldReceive('get_product_id')->andReturn(123);
        $mathItem->shouldReceive('get_quantity')->andReturn(2);

        $scienceItem = Mockery::mock('WC_Order_Item_Product');
        $scienceItem->shouldReceive('get_product_id')->andReturn(456);
        $scienceItem->shouldReceive('get_quantity')->andReturn(1);

        $order->shouldReceive('get_items')->andReturn([$mathItem, $scienceItem]);

        Functions\when('wc_get_order')->justReturn($order);

        // Mock all users as new
        Functions\when('get_user_by')->justReturn(false);
        Functions\when('wp_create_user')->justReturn(792);
        Functions\when('wp_update_user')->justReturn(true);
        Functions\when('wp_send_new_user_notifications')->justReturn(true);
        Functions\when('remove_all_filters')->justReturn(true);
        Functions\when('wp_generate_password')->justReturn('random_password_123');

        // Mock get_user_by for newly created users
        Functions\when('get_user_by')->alias(function($field, $value) {
            if ($field === 'id' && $value === 792) {
                return createMockUser(792, 'created@example.com', 'created');
            }
            return false; // All email lookups return false (new users)
        });

        // Mock LifterLMS functions
        Functions\when('llms_wc_get_order_item_products')->justReturn([789]); // Mock course ID
        Functions\when('llms_unenroll_student')->justReturn(true);
        Functions\when('llms_is_user_enrolled')->justReturn(false);
        Functions\when('llms_enroll_student')->justReturn(true);

        // Execute enrollment
        $this->plugin->student_enroll_on_woocommerce_payment_complete(123);

        $this->assertTrue(true); // Test passes if no exceptions
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
}
