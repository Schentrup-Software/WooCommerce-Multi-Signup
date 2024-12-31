import { isEmail } from '@wordpress/url';
import { __, sprintf } from '@wordpress/i18n';

export const validations = {
	required: {
		validate: (value) => {
			if (!value || value.trim() === '') {
				return __('This field is required.', 'checkout-fields-for-blocks');
			}
			return null;
		},
	},
	email: {
		validate: (value) => {
			if (!isEmail(value)) {
				return __(
					'Please enter a valid email address.',
					'checkout-fields-for-blocks'
				);
			}
			return null;
		},
	},
	phone: {
		validate: (value) => {
			const phoneRegex = /^\+?[\d\s()-]{10,}$/;
			if (value && !phoneRegex.test(value)) {
				return __(
					'Please enter a valid phone number.',
					'checkout-fields-for-blocks'
				);
			}
			return null;
		},
	},
	url: {
		validate: (value) => {
			try {
				new URL(value);
				return null;
			} catch {
				return __('Please enter a valid URL.', 'checkout-fields-for-blocks');
			}
		},
	},
	minLength: {
		validate: (value, settings) => {
			if (value.length > 0 && value.length < settings.value) {
				return sprintf(
					/* translators: %d is the number of characters. */
					__(
						'This field must be at least %d characters long.',
						'checkout-fields-for-blocks'
					),
					settings.value
				);
			}
			return null;
		},
	},
	maxLength: {
		validate: (value, settings) => {
			if (value.length > 0 && value.length > settings.value) {
				return sprintf(
					/* translators: %d is the number of characters. */
					__(
						'This field must not exceed %d characters.',
						'checkout-fields-for-blocks'
					),
					settings.value
				);
			}
			return null;
		},
	},
	pattern: {
		validate: (value, settings) => {
			const regex = new RegExp(settings.value);
			if (!regex.test(value)) {
				return __(
					'This field does not match the required pattern.',
					'checkout-fields-for-blocks'
				);
			}
			return null;
		},
	},
};

export const runValidations = (value, validationAttributes) => {
	for (const [key, settings] of Object.entries(validationAttributes)) {
		if (settings.enabled && validations[key]) {
			const error = validations[key].validate(value, settings);
			if (error) return error;
		}
	}
	return null;
};
