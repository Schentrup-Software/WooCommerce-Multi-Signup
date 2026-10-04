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
		$students = is_array($parsedData) && isset($parsedData['students']) && is_array($parsedData['students'])
			? $parsedData['students']
			: [];

		$student_data = [];
		foreach ($students as $product_id => $course_student) {
			if (!is_array($course_student)) {
				continue;
			}
			foreach ($course_student as $student) {
				if (!is_array($student)) {
					continue;
				}
				$student_data[] = new Woocommerce_Multi_Signup_Data_Student(
					(string) $product_id,
					$student['courseName'] ?? 'test',
					$student['firstName'] ?? '',
					$student['lastName'] ?? '',
					$student['email'] ?? throw new Exception('Email is required'),
				);
			}
		}

		$this->student_data = $student_data;
	}

	/**
	 * Students registered for any of the given WooCommerce product (or variation) IDs.
	 *
	 * @param array $product_ids Product and/or variation IDs.
	 * @return Woocommerce_Multi_Signup_Data_Student[]
	 */
	public function get_students_for_products(array $product_ids): array {
		$ids = array_map('strval', $product_ids);

		return array_values(array_filter(
			$this->student_data,
			fn($student) => in_array((string) $student->course_product_id, $ids, true)
		));
	}
}
