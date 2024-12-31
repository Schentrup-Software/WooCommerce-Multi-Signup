import { useState, useEffect, useMemo } from '@wordpress/element';
import { Select } from '../../components/select/select';

export const FrontendBlock = ({
	className,
	label,
	helpText,
	fieldId,
	fieldName,
	validationSettings: validationSettingsRaw,
	defaultValue,
	metaName,
	checkoutExtensionData,
	options: optionsRaw,
	placeholder,
}) => {
	const { setExtensionData } = checkoutExtensionData;

	const [inputValue, setInputValue] = useState(defaultValue || '');
	const validationErrorId = `${fieldId}-error`;

	const validationSettings = useMemo(
		() => JSON.parse(validationSettingsRaw || '{}'),
		[validationSettingsRaw]
	);

	const options = useMemo(() => JSON.parse(optionsRaw || '[]'), [optionsRaw]);

	useEffect(() => {
		setExtensionData('checkout-fields-for-blocks', metaName, inputValue);
	}, [setExtensionData, metaName, inputValue]);

	return (
			<Select
				id={fieldId}
				name={fieldName}
				label={label}
				placeholder={placeholder}
				value={inputValue}
				onChange={(value) => setInputValue(value)}
				options={options}
				readOnly={false}
				required={validationSettings.required}
				help={helpText}
				errorId={validationErrorId}
				className={className}
			/>
	);
};

export default FrontendBlock;
