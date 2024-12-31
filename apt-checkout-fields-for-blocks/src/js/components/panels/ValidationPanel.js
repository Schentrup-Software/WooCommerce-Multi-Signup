import { __ } from '@wordpress/i18n';
import { PanelBody, CheckboxControl, TextControl } from '@wordpress/components';
import { VALIDATION_LABELS } from '../../utils/labels';

const VALUE_REQUIRED_VALIDATIONS = ['minLength', 'maxLength', 'pattern'];

export const ValidationPanel = ({
	validationSettings,
	setValidationSettings,
}) => {
	return (
		<PanelBody
			title={__('Validation', 'checkout-fields-for-blocks')}
			initialOpen={false}
		>
			{Object.entries(validationSettings).map(([key, value]) => {
				const label = VALIDATION_LABELS[key] || key;

				return (
					<div key={key}>
						<CheckboxControl
							label={label}
							checked={value.enabled}
							onChange={(enabled) =>
								setValidationSettings({
									...validationSettings,
									[key]: { ...value, enabled },
								})
							}
						/>
						{VALUE_REQUIRED_VALIDATIONS.includes(key) &&
							value.enabled && (
								<TextControl
									label={__(
										`${label} Value`,
										'checkout-fields-for-blocks'
									)}
									value={value.value || ''}
									onChange={(newValue) =>
										setValidationSettings({
											...validationSettings,
											[key]: {
												...value,
												value: newValue,
											},
										})
									}
								/>
							)}
					</div>
				);
			})}
		</PanelBody>
	);
};
