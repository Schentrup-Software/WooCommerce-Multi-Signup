/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
/**
 * Internal dependencies
 */
import { runValidations } from '../../validations/validations';

const FrontendBlock = ({
	fieldId,
	fieldName,
	metaName,
	label,
	className,
	defaultValue,
	validationSettings: validationSettingsRaw,
	inputType,
	helpText,
	checkoutExtensionData,
}) => {
	const { setExtensionData } = checkoutExtensionData;
	const [inputValue, setInputValue] = useState(defaultValue || '');
	const validationErrorId = `${metaName}-${fieldId}`;

	const validationSettings = useMemo(() => {
		return JSON.parse(validationSettingsRaw || '{}');
	}, [validationSettingsRaw]);

	const customValidationHandler = (inputObject, validationSettings) => {
		const error = runValidations(inputObject.value, validationSettings);
		if (error) {
			inputObject.setCustomValidity(error);
			return false;
		}
		inputObject.setCustomValidity('');
		return true;
	};

	useEffect(() => {
		setExtensionData('checkout-fields-for-blocks', metaName, inputValue);
	}, [setExtensionData, metaName, inputValue]);

	return (
		<div className={className}>
			<ValidatedTextInput
				id={fieldId}
				type={inputType}
				name={fieldName}
				label={label}
				value={inputValue}
				customValidation={(inputObject) =>
					customValidationHandler(inputObject, validationSettings)
				}
				help={helpText}
				onChange={(value) => setInputValue(value)}
				errorId={validationErrorId}
				showError={true}
			/>
		</div>
	);
};

export default FrontendBlock;
