<?php

class Woocommerce_Multi_Signup_Data_Student {
	public readonly array $student_data;

	public function __construct(
		public readonly string $course_product_id,
		public readonly string $course_name,
		public readonly string $student_first_name,
		public readonly string $student_last_name,
		public readonly string $student_email,
	) {
	}
}

class Woocommerce_Multi_Signup_Data
{
	/**
	 * @property Woocommerce_Multi_Signup_Data_Student[] $student_data
	 */
	public readonly array $student_data;

	public function __construct(string $data) {
		$parsedData = json_decode($data, true);
		$student_data = [];
		foreach ($parsedData['students'] as $product_id => $course_student) {
			foreach ($course_student as $student) {
				$student_data[] = new Woocommerce_Multi_Signup_Data_Student(
					$product_id,
					$student['courseName'] ?? 'test',
					$student['firstName'] ?? '',
					$student['lastName'] ?? '',
					$student['email'] ?? throw new Exception('Email is required'),
				);
			}
		}

		$this->student_data = $student_data;
	}
}
