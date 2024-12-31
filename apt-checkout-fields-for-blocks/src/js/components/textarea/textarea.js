/**
 * External dependencies
 */
import clsx from 'clsx';

export const Textarea = (props) => {
	const {
		className = '',
		disabled = false,
		onChange,
		onBlur,
		placeholder,
		value = '',
	} = props;

	return (
		<textarea
			className={clsx('wc-block-components-textarea', className)}
			disabled={disabled}
			onChange={(event) => {
				onChange(event.target.value);
			}}
			onBlur={(event) => onBlur && onBlur(event.target.value)}
			placeholder={placeholder}
			rows={2}
			value={value}
		/>
	);
};

export default Textarea;
