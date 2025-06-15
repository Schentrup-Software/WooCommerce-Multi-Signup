<?php
/**
 * Tests for Woocommerce_Multi_Signup_Data classes
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class WoocommerceMultiSignupDataTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Include the classes we're testing
        require_once __DIR__ . '/../php/woocommerce-multi-signup-data.php';
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testStudentDataStudentConstruction() {
        $student = new Woocommerce_Multi_Signup_Data_Student(
            '123',
            'Test Course',
            'John',
            'Doe',
            'john.doe@example.com'
        );

        $this->assertEquals('123', $student->course_product_id);
        $this->assertEquals('Test Course', $student->course_name);
        $this->assertEquals('John', $student->student_first_name);
        $this->assertEquals('Doe', $student->student_last_name);
        $this->assertEquals('john.doe@example.com', $student->student_email);
    }

    public function testMultiSignupDataConstruction() {
        $jsonData = json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'john.doe@example.com'
                    ],
                    [
                        'courseName' => 'Science Course',
                        'firstName' => 'Jane',
                        'lastName' => 'Smith',
                        'email' => 'jane.smith@example.com'
                    ]
                ],
                '456' => [
                    [
                        'courseName' => 'History Course',
                        'firstName' => 'Bob',
                        'lastName' => 'Johnson',
                        'email' => 'bob.johnson@example.com'
                    ]
                ]
            ]
        ]);

        $data = new Woocommerce_Multi_Signup_Data($jsonData);

        $this->assertCount(3, $data->student_data);

        // Test first student
        $this->assertEquals('123', $data->student_data[0]->course_product_id);
        $this->assertEquals('Math Course', $data->student_data[0]->course_name);
        $this->assertEquals('John', $data->student_data[0]->student_first_name);
        $this->assertEquals('Doe', $data->student_data[0]->student_last_name);
        $this->assertEquals('john.doe@example.com', $data->student_data[0]->student_email);

        // Test second student
        $this->assertEquals('123', $data->student_data[1]->course_product_id);
        $this->assertEquals('Science Course', $data->student_data[1]->course_name);
        $this->assertEquals('Jane', $data->student_data[1]->student_first_name);

        // Test third student
        $this->assertEquals('456', $data->student_data[2]->course_product_id);
        $this->assertEquals('History Course', $data->student_data[2]->course_name);
        $this->assertEquals('Bob', $data->student_data[2]->student_first_name);
    }

    public function testMultiSignupDataWithMissingEmail() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email is required');

        $jsonData = json_encode([
            'students' => [
                '123' => [
                    [
                        'courseName' => 'Math Course',
                        'firstName' => 'John',
                        'lastName' => 'Doe'
                        // Missing email
                    ]
                ]
            ]
        ]);

        new Woocommerce_Multi_Signup_Data($jsonData);
    }

    public function testMultiSignupDataWithDefaultValues() {
        $jsonData = json_encode([
            'students' => [
                '123' => [
                    [
                        'email' => 'test@example.com'
                        // Missing other fields should use defaults
                    ]
                ]
            ]
        ]);

        $data = new Woocommerce_Multi_Signup_Data($jsonData);

        $this->assertCount(1, $data->student_data);
        $this->assertEquals('123', $data->student_data[0]->course_product_id);
        $this->assertEquals('test', $data->student_data[0]->course_name); // Default value
        $this->assertEquals('', $data->student_data[0]->student_first_name); // Default value
        $this->assertEquals('', $data->student_data[0]->student_last_name); // Default value
        $this->assertEquals('test@example.com', $data->student_data[0]->student_email);
    }

    public function testMultiSignupDataWithInvalidJson() {
        $this->expectError();
        $this->expectErrorMessage('Trying to access array offset on value of type null');

        $invalidJson = "invalid json string";
        new Woocommerce_Multi_Signup_Data($invalidJson);
    }

    public function testMultiSignupDataWithEmptyStudents() {
        $jsonData = json_encode([
            'students' => []
        ]);

        $data = new Woocommerce_Multi_Signup_Data($jsonData);

        $this->assertCount(0, $data->student_data);
        $this->assertIsArray($data->student_data);
    }
}
