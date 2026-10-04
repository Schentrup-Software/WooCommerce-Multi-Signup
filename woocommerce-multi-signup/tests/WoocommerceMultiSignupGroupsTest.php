<?php
/**
 * Tests for Woocommerce_Multi_Signup_Groups: skipping the buyer's enrollment and
 * fulfilling multi-student purchases through LifterLMS Groups.
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class WoocommerceMultiSignupGroupsTest extends TestCase {

    /** @var Woocommerce_Multi_Signup_Groups */
    protected $groups;

    /** @var array Messages passed to wp_mail(). */
    protected $emails = [];

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        wcms_default_stubs();

        require_once __DIR__ . '/../php/woocommerce-multi-signup-data.php';
        require_once __DIR__ . '/../php/woocommerce-multi-signup-groups.php';

        $this->emails = [];
        Functions\when('wp_mail')->alias(function($to, $subject, $message) {
            $this->emails[] = $message;
            return true;
        });

        $this->groups = new Woocommerce_Multi_Signup_Groups();
    }

    protected function tearDown(): void {
        Mockery::close();
        Monkey\tearDown();
        parent::tearDown();
    }

    private function studentData(array $students, $product_id = '123', $course = 'Math Course') {
        $rows = [];
        foreach ($students as $email => $name) {
            $rows[] = [
                'courseName' => $course,
                'firstName' => $name[0],
                'lastName' => $name[1],
                'email' => $email,
            ];
        }

        return json_encode(['students' => [$product_id => $rows]]);
    }

    /** @var array<string,array> Calls recorded by record(). */
    protected $calls = [];

    /**
     * Stub a function (Brain Monkey expect() cannot override an earlier when() stub)
     * and record every call to it.
     */
    private function record($function, $return = true) {
        $this->calls[$function] = [];
        Functions\when($function)->alias(function(...$args) use ($function, $return) {
            $this->calls[$function][] = $args;
            return $return;
        });
    }

    private function usersByEmail(array $users) {
        Functions\when('get_user_by')->alias(function($field, $value) use ($users) {
            foreach ($users as $user) {
                if (('email' === $field && $user->user_email === $value) || ('id' === $field && $user->ID === $value)) {
                    return $user;
                }
            }
            return false;
        });
    }

    public function testDefaultEnrollmentIsSkippedWhenStudentsAreListedForTheItem() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['a@example.com' => ['A', 'One']])]);
        $item = createMockOrderItem(123, 1);

        $this->assertFalse($this->groups->maybe_skip_default_enrollment(true, createMockPlan(), $item, $order));
    }

    public function testDefaultEnrollmentRunsWhenNoStudentsAreListed() {
        $order = createMockOrder(123, 456, []);
        $item = createMockOrderItem(123, 1);

        $this->assertTrue($this->groups->maybe_skip_default_enrollment(true, createMockPlan(), $item, $order));
    }

    public function testDefaultEnrollmentRunsForItemsWithoutStudents() {
        // Students were listed for product 123 only; the buyer bought product 999 for themselves.
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['a@example.com' => ['A', 'One']])]);
        $item = createMockOrderItem(999, 1);

        $this->assertTrue($this->groups->maybe_skip_default_enrollment(true, createMockPlan(), $item, $order));
    }

    public function testDefaultEnrollmentStaysSkippedWhenAnotherPluginSkippedIt() {
        $order = createMockOrder(123, 456, []);

        $this->assertFalse($this->groups->maybe_skip_default_enrollment(false, createMockPlan(), createMockOrderItem(), $order));
    }

    public function testStudentsAreMatchedByVariationId() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['a@example.com' => ['A', 'One']], '321')]);
        $item = createMockOrderItem(123, 1, 55, 321);

        $this->assertCount(1, $this->groups->get_students_for_item($order, $item));
        $this->assertFalse($this->groups->maybe_skip_default_enrollment(true, createMockPlan(), $item, $order));
    }

    public function testFulfillDoesNothingWithoutStudents() {
        $order = createMockOrder(123, 456, []);
        $this->record('llms_create_group', createMockGroup());

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 1), createMockPlan(), 1);

        $this->assertEmpty($order->notes);
        $this->assertEmpty($this->calls['llms_create_group']);
        $this->assertEmpty(LLMS_Groups_Enrollment::$calls);
        $this->assertEmpty($this->emails);
    }

    public function testFulfillCreatesGroupOwnedByBuyerAndAddsStudents() {
        $students = ['john@example.com' => ['John', 'Doe'], 'jane@example.com' => ['Jane', 'Smith']];
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData($students)]);
        $item = createMockOrderItem(123, 2, 55);
        $plan = createMockPlan(900, 456);
        $group = createMockGroup(777, 'Title 456 - Pat Buyer');

        $buyer = createMockUser(456, 'buyer@example.com', 'buyer');
        $john = createMockUser(789, 'john@example.com', 'john');
        $jane = createMockUser(790, 'jane@example.com', 'jane');
        $this->usersByEmail([$buyer, $john, $jane]);

        $looked_up = [];
        Functions\when('llms_groups_get_user_groups_for_product')->alias(function($user_id, $product_id) use (&$looked_up) {
            $looked_up[] = [$user_id, $product_id];
            return [];
        });

        $created_with = null;
        Functions\when('llms_create_group')->alias(function($args) use (&$created_with, $group) {
            $created_with = $args;
            return $group;
        });

        $this->groups->fulfill_order_item($order, $item, $plan, 2);

        $this->assertCount(1, $order->notes);
        $this->assertStringContainsString('2 of 2 student(s) added to the new group', $order->notes[0]);
        $this->assertStringContainsString('https://example.com/groups/777', $order->notes[0]);

        // No existing group for this buyer and course, so one was created.
        $this->assertSame([[456, 456]], $looked_up);
        // The buyer owns the group and the group is tied to the purchased course.
        $this->assertSame(456, $created_with['post_author']);
        $this->assertSame('Title 456 - Pat Buyer', $created_with['post_title']);
        $this->assertSame(456, $group->props['post_id']);
        $this->assertSame(123, $group->props['wc_order_id']);
        $this->assertSame(55, $group->props['wc_order_item_id']);
        // 2 purchased seats plus one for the buyer as administrator.
        $this->assertSame(3, $group->props['seats']);

        // Both students became members through the group (not direct course enrollment).
        $this->assertCount(2, LLMS_Groups_Enrollment::$calls);
        $this->assertSame([789, 777, 'wc_order_123', 'member'], array_values(LLMS_Groups_Enrollment::$calls[0]));
        $this->assertSame([790, 777, 'wc_order_123', 'member'], array_values(LLMS_Groups_Enrollment::$calls[1]));

        // The order item remembers the group.
        $this->assertSame(777, $item->meta['_llms_group_id']);
        $this->assertSame(777, $item->meta['_wcms_group_id_900']);

        $this->assertEmpty($this->emails);
    }

    public function testFulfillCreatesAccountsForNewStudents() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['new@example.com' => ['New', 'User']])]);
        $item = createMockOrderItem(123, 1);
        $group = createMockGroup(777);
        Functions\when('llms_create_group')->justReturn($group);

        $buyer = createMockUser(456, 'buyer@example.com', 'buyer');
        $new = createMockUser(791, 'new@example.com', 'new');
        Functions\when('get_user_by')->alias(function($field, $value) use ($buyer, $new) {
            if ('id' === $field && 456 === $value) {
                return $buyer;
            }
            if ('id' === $field && 791 === $value) {
                return $new;
            }
            return false; // Nobody exists by email yet.
        });

        $this->record('wp_create_user', 791);
        $this->record('wp_update_user', true);
        $this->record('wp_send_new_user_notifications', true);

        $this->groups->fulfill_order_item($order, $item, createMockPlan(900, 456), 1);

        $this->assertSame([['new@example.com', 'random_password_123', 'new@example.com']], $this->calls['wp_create_user']);
        $this->assertCount(1, $this->calls['wp_update_user']);
        $this->assertSame([[791, 'user']], $this->calls['wp_send_new_user_notifications']);
        $this->assertSame('New', $new->first_name);
        $this->assertSame('User', $new->last_name);
        $this->assertCount(1, LLMS_Groups_Enrollment::$calls);
        $this->assertSame(791, LLMS_Groups_Enrollment::$calls[0]['user_id']);
        $this->assertEmpty($this->emails);
    }

    public function testRepeatPurchaseReusesTheBuyersExistingGroupAndAddsSeats() {
        $students = ['kid2@example.com' => ['Kid', 'Two'], 'kid3@example.com' => ['Kid', 'Three']];
        $order = createMockOrder(124, 456, ['Student Data' => $this->studentData($students)]);
        $item = createMockOrderItem(123, 2, 66);
        $plan = createMockPlan(900, 456);

        // The buyer bought this course before; the oldest group (lowest ID) must win.
        $existing = createMockGroup(555, 'Title 456 - Pat Buyer');
        $existing->props['seats'] = 2;
        Functions\when('llms_groups_get_user_groups_for_product')->alias(function($user_id, $product_id) {
            $this->assertSame(456, $user_id);
            $this->assertSame(456, $product_id);
            return [(object) ['ID' => 777], (object) ['ID' => 555]];
        });
        Functions\when('get_llms_group')->alias(function($id) use ($existing) {
            return 555 === $id ? $existing : createMockGroup($id);
        });
        $this->record('llms_create_group', createMockGroup(1));
        $this->record('llms_groups_lock_seats', true);
        $this->record('llms_groups_release_seats_lock', null);

        $this->usersByEmail([
            createMockUser(456, 'buyer@example.com'),
            createMockUser(2, 'kid2@example.com'),
            createMockUser(3, 'kid3@example.com'),
        ]);

        $this->groups->fulfill_order_item($order, $item, $plan, 2);

        // No new group; two purchased seats added to the existing one under the seat lock.
        $this->assertEmpty($this->calls['llms_create_group']);
        $this->assertSame(4, $existing->props['seats']);
        $this->assertSame([[555]], $this->calls['llms_groups_lock_seats']);
        $this->assertSame([[555]], $this->calls['llms_groups_release_seats_lock']);
        // The existing group keeps its course, owner and title.
        $this->assertArrayNotHasKey('post_id', $existing->props);
        $this->assertSame('Title 456 - Pat Buyer', $existing->props['title']);

        // The new students joined the existing group and the new order item points at it.
        $this->assertSame([2, 3], array_column(LLMS_Groups_Enrollment::$calls, 'user_id'));
        $this->assertSame([555, 555], array_column(LLMS_Groups_Enrollment::$calls, 'group_id'));
        $this->assertSame(555, $item->meta['_llms_group_id']);
        $this->assertSame(555, $item->meta['_wcms_group_id_900']);

        $this->assertCount(1, $order->notes);
        $this->assertStringContainsString('2 of 2 student(s) added to the existing group', $order->notes[0]);
        $this->assertEmpty($this->emails);
    }

    public function testRepeatPurchaseByBuyerListedAsStudentAddsOneSeatLess() {
        $order = createMockOrder(124, 456, ['Student Data' => $this->studentData(['buyer@example.com' => ['Pat', 'Buyer'], 'kid@example.com' => ['Kid', 'One']])]);
        $existing = createMockGroup(555);
        $existing->props['seats'] = 3;
        Functions\when('llms_groups_get_user_groups_for_product')->justReturn([(object) ['ID' => 555]]);
        Functions\when('get_llms_group')->justReturn($existing);
        $this->record('llms_create_group', createMockGroup(1));
        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(789, 'kid@example.com')]);

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 2, 66), createMockPlan(900, 456), 2);

        // The buyer already holds a seat as administrator, so only the other student needs one.
        $this->assertEmpty($this->calls['llms_create_group']);
        $this->assertSame(4, $existing->props['seats']);
        $this->assertSame([789], array_column(LLMS_Groups_Enrollment::$calls, 'user_id'));
    }

    public function testRepeatPurchaseDoesNotAddSeatsTwiceForTheSameItem() {
        $order = createMockOrder(124, 456, ['Student Data' => $this->studentData(['kid@example.com' => ['Kid', 'One']])]);
        $item = createMockOrderItem(123, 1, 66);
        $item->meta['_wcms_group_id_900'] = 555; // Fulfilled once already.
        $existing = createMockGroup(555);
        $existing->props['seats'] = 3;
        Functions\when('llms_groups_get_user_groups_for_product')->justReturn([(object) ['ID' => 555]]);
        Functions\when('get_llms_group')->justReturn($existing);
        Functions\when('llms_is_user_enrolled')->justReturn(true);
        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(789, 'kid@example.com')]);

        $this->groups->fulfill_order_item($order, $item, createMockPlan(900, 456), 1);

        $this->assertSame(3, $existing->props['seats']);
        $this->assertEmpty(LLMS_Groups_Enrollment::$calls);
    }

    public function testSecondItemForTheSameCourseInOneOrderJoinsTheFirstItemsGroup() {
        // Groups created earlier in the same order are published and owned by the buyer, so the
        // lookup finds them exactly like a group from a previous order.
        $order = createMockOrder(123, 456, ['Student Data' => json_encode(['students' => [
            '123' => [['courseName' => 'Math', 'firstName' => 'A', 'lastName' => 'One', 'email' => 'a@example.com']],
            '124' => [['courseName' => 'Math', 'firstName' => 'B', 'lastName' => 'Two', 'email' => 'b@example.com']],
        ]])]);
        $plan = createMockPlan(900, 456);
        $group = createMockGroup(777);
        $created = [];
        Functions\when('llms_create_group')->alias(function($args) use (&$created, $group) {
            $created[] = $args;
            return $group;
        });
        Functions\when('llms_groups_get_user_groups_for_product')->alias(function() use (&$created) {
            return $created ? [(object) ['ID' => 777]] : [];
        });
        Functions\when('get_llms_group')->justReturn($group);
        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(1, 'a@example.com'), createMockUser(2, 'b@example.com')]);

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 1, 1), $plan, 1);
        $this->groups->fulfill_order_item($order, createMockOrderItem(124, 1, 2), $plan, 1);

        $this->assertCount(1, $created);
        // 1 seat + buyer from the first item, then 1 more seat from the second item.
        $this->assertSame(3, $group->props['seats']);
        $this->assertSame([777, 777], array_column(LLMS_Groups_Enrollment::$calls, 'group_id'));
    }

    public function testFulfillReusesGroupCreatedByLifterLmsGroupsForGroupPlans() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['john@example.com' => ['John', 'Doe']])]);
        $item = createMockOrderItem(123, 1, 55);
        $item->meta['_llms_group_id'] = 555; // Set by LifterLMS Groups' own fulfillment.
        $plan = createMockPlan(900, 456, 'yes');
        $group = createMockGroup(555, 'Groups Made Me', ['total' => 1, 'used' => 2, 'open' => -1]);

        Functions\when('get_llms_group')->alias(function($id) use ($group) {
            return 555 === $id ? $group : null;
        });
        $this->record('llms_create_group', createMockGroup(1));

        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(789, 'john@example.com')]);

        $this->groups->fulfill_order_item($order, $item, $plan, 1);

        $this->assertEmpty($this->calls['llms_create_group']);
        $this->assertSame(789, LLMS_Groups_Enrollment::$calls[0]['user_id']);
        $this->assertSame(555, LLMS_Groups_Enrollment::$calls[0]['group_id']);
        // Seats were topped up so the buyer plus the student both fit.
        $this->assertSame(2, $group->props['seats']);
        $this->assertSame(555, $item->meta['_wcms_group_id_900']);
    }

    public function testFulfillIsIdempotentAcrossRepeatedStatusChanges() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['john@example.com' => ['John', 'Doe']])]);
        $item = createMockOrderItem(123, 1, 55);
        $item->meta['_wcms_group_id_900'] = 777; // Created on the first run.
        $group = createMockGroup(777);

        Functions\when('get_llms_group')->alias(function($id) use ($group) {
            return 777 === $id ? $group : null;
        });
        $this->record('llms_create_group', createMockGroup(1));
        // John is already a member of the group.
        Functions\when('llms_is_user_enrolled')->alias(function($user_id, $post_id) {
            return 789 === $user_id && 777 === $post_id;
        });

        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(789, 'john@example.com')]);

        $this->groups->fulfill_order_item($order, $item, createMockPlan(900, 456), 1);

        $this->assertCount(1, $order->notes);
        $this->assertStringContainsString('0 of 1 student(s)', $order->notes[0]);
        $this->assertEmpty($this->calls['llms_create_group']);
        $this->assertEmpty(LLMS_Groups_Enrollment::$calls);
        $this->assertEmpty($this->emails);
    }

    public function testBuyerListedAsStudentIsEnrolledInTheCourseDirectly() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['buyer@example.com' => ['Pat', 'Buyer'], 'kid@example.com' => ['Kid', 'Buyer']])]);
        $item = createMockOrderItem(123, 2);
        $group = createMockGroup(777);
        Functions\when('llms_create_group')->justReturn($group);
        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(789, 'kid@example.com')]);

        $this->record('llms_enroll_student', true);

        $this->groups->fulfill_order_item($order, $item, createMockPlan(900, 456), 2);

        // The buyer is enrolled in the course with the group as the trigger, like any member.
        $this->assertSame([[456, 456, 'group_777']], $this->calls['llms_enroll_student']);
        // The buyer already occupies a seat as administrator, so no extra seat is added.
        $this->assertSame(2, $group->props['seats']);
        // Only the other student goes through group enrollment.
        $this->assertCount(1, LLMS_Groups_Enrollment::$calls);
        $this->assertSame(789, LLMS_Groups_Enrollment::$calls[0]['user_id']);
    }

    public function testExtraStudentsBeyondQuantityAreReportedAndSkipped() {
        $students = ['a@example.com' => ['A', 'One'], 'b@example.com' => ['B', 'Two'], 'c@example.com' => ['C', 'Three']];
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData($students)]);
        $item = createMockOrderItem(123, 2);
        Functions\when('llms_create_group')->justReturn(createMockGroup(777));
        $this->usersByEmail([
            createMockUser(456, 'buyer@example.com'),
            createMockUser(1, 'a@example.com'),
            createMockUser(2, 'b@example.com'),
            createMockUser(3, 'c@example.com'),
        ]);

        $this->groups->fulfill_order_item($order, $item, createMockPlan(900, 456), 2);

        $this->assertCount(2, LLMS_Groups_Enrollment::$calls);
        $this->assertCount(1, $this->emails);
        $this->assertStringContainsString('lists 3 students', $this->emails[0]);
        $this->assertStringContainsString('only 2 were purchased', $this->emails[0]);
    }

    public function testGroupAddFailureIsReported() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['john@example.com' => ['John', 'Doe']])]);
        Functions\when('llms_create_group')->justReturn(createMockGroup(777));
        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(789, 'john@example.com')]);
        LLMS_Groups_Enrollment::$return = false;

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 1), createMockPlan(900, 456), 1);

        $this->assertCount(1, $this->emails);
        $this->assertStringContainsString('Error adding student john@example.com to group 777', $this->emails[0]);
    }

    public function testGroupCreationFailureIsReported() {
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData(['john@example.com' => ['John', 'Doe']])]);
        $this->usersByEmail([createMockUser(456, 'buyer@example.com')]);
        Functions\when('llms_create_group')->justReturn(null);

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 1), createMockPlan(900, 456), 1);

        $this->assertEmpty($order->notes);
        $this->assertEmpty(LLMS_Groups_Enrollment::$calls);
        $this->assertCount(1, $this->emails);
        $this->assertStringContainsString('Could not create a group', $this->emails[0]);
    }

    public function testOrderWithoutCustomerIsReported() {
        $order = createMockOrder(123, 0, ['Student Data' => $this->studentData(['john@example.com' => ['John', 'Doe']])]);
        $this->record('llms_create_group', createMockGroup(1));

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 1), createMockPlan(900, 456), 1);

        $this->assertEmpty($this->calls['llms_create_group']);
        $this->assertCount(1, $this->emails);
        $this->assertStringContainsString('no customer account', $this->emails[0]);
    }

    public function testUserCreationFailureIsReportedAndOthersStillProceed() {
        $students = ['bad@example.com' => ['Bad', 'One'], 'good@example.com' => ['Good', 'Two']];
        $order = createMockOrder(123, 456, ['Student Data' => $this->studentData($students)]);
        Functions\when('llms_create_group')->justReturn(createMockGroup(777));
        $this->usersByEmail([createMockUser(456, 'buyer@example.com'), createMockUser(2, 'good@example.com')]);
        Functions\when('wp_create_user')->justReturn(new WP_Error('existing_user_login', 'Could not create'));

        $this->groups->fulfill_order_item($order, createMockOrderItem(123, 2), createMockPlan(900, 456), 2);

        $this->assertCount(1, LLMS_Groups_Enrollment::$calls);
        $this->assertSame(2, LLMS_Groups_Enrollment::$calls[0]['user_id']);
        $this->assertCount(1, $this->emails);
        $this->assertStringContainsString('Error creating user for student bad@example.com', $this->emails[0]);
    }

    public function testGroupsForOrderOnlyIncludesExistingGroups() {
        $linked = createMockOrderItem(123, 1, 1);
        $linked->meta['_llms_group_id'] = 777;
        $dangling = createMockOrderItem(124, 1, 2);
        $dangling->meta['_llms_group_id'] = 999;
        $plain = createMockOrderItem(125, 1, 3);

        $order = createMockOrder(123, 456, [], [$linked, $dangling, $plain]);
        Functions\when('get_llms_group')->alias(function($id) {
            return 777 === $id ? createMockGroup(777) : null;
        });

        $this->assertSame([777 => 'Title 777'], Woocommerce_Multi_Signup_Groups::get_groups_for_order($order));
    }
}
