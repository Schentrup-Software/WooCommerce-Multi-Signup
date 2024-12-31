import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	TextControl,
	Button,
	Dashicon,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { generateSlug } from '../../utils/string';

export const OptionsPanel = ({ options, onChange }) => {
	const [newOptionLabel, setNewOptionLabel] = useState('');
	const [newOptionValue, setNewOptionValue] = useState('');

	const addOption = () => {
		if (newOptionLabel && newOptionValue) {
			onChange([
				...options,
				{ label: newOptionLabel, value: newOptionValue },
			]);
			setNewOptionLabel('');
			setNewOptionValue('');
		}
	};

	const removeOption = (index) => {
		const newOptions = [...options];
		newOptions.splice(index, 1);
		onChange(newOptions);
	};

	const handleLabelChange = (index, newLabel) => {
		const newOptions = [...options];
		newOptions[index].label = newLabel;
		const newSlug = generateSlug(newLabel);
		if (
			newOptions[index].value === '' ||
			newOptions[index].value === generateSlug(options[index].label)
		) {
			newOptions[index].value = newSlug;
		}
		onChange(newOptions);
	};

	return (
		<PanelBody
			title={__('Options', 'checkout-fields-for-blocks')}
			initialOpen={false}
		>
			{options.map((option, index) => (
				<div key={index}>
					<div
						style={{
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
						}}
					>
						<h4 style={{ margin: '0' }}>
							{__('Option', 'checkout-fields-for-blocks')} {index + 1}
						</h4>
						<Button
							isDestructive
							isSmall
							onClick={() => removeOption(index)}
							icon={<Dashicon icon="trash" />}
							label={__('Remove Option', 'checkout-fields-for-blocks')}
						/>
					</div>
					<TextControl
						label={__('Label', 'checkout-fields-for-blocks')}
						value={option.label}
						onChange={(newLabel) =>
							handleLabelChange(index, newLabel)
						}
					/>
					<TextControl
						label={__('Value', 'checkout-fields-for-blocks')}
						value={option.value}
						onChange={(newValue) => {
							const newOptions = [...options];
							newOptions[index].value = newValue;
							onChange(newOptions);
						}}
					/>
					{index < options.length - 1 && (
						<hr style={{ margin: '10px 0' }} />
					)}
				</div>
			))}
			<hr style={{ margin: '20px 0' }} />
			<TextControl
				label={__('New Option Label', 'checkout-fields-for-blocks')}
				value={newOptionLabel}
				onChange={(newLabel) => {
					setNewOptionLabel(newLabel);
					const newSlug = generateSlug(newLabel);
					if (
						newOptionValue === '' ||
						newOptionValue === generateSlug(newOptionLabel)
					) {
						setNewOptionValue(newSlug);
					}
				}}
			/>
			<TextControl
				label={__('New Option Value', 'checkout-fields-for-blocks')}
				value={newOptionValue}
				onChange={setNewOptionValue}
			/>
			<Button isPrimary onClick={addOption}>
				{__('Add Option', 'checkout-fields-for-blocks')}
			</Button>
		</PanelBody>
	);
};
