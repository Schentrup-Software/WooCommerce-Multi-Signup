import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const DisplayPanel = ({ display, onChange }) => {
	return (
		<PanelBody
			title={__('Display on', 'checkout-fields-for-blocks')}
			initialOpen={false}
		>
			<ToggleControl
				label={__('Order confirmation', 'checkout-fields-for-blocks')}
				checked={display.orderConfirmation}
				onChange={(v) => onChange({ ...display, orderConfirmation: v })}
			/>
			<ToggleControl
				label={__('Admin order', 'checkout-fields-for-blocks')}
				checked={display.orderAdmin}
				onChange={(v) => onChange({ ...display, orderAdmin: v })}
			/>
			<ToggleControl
				label={__('My Account - order', 'checkout-fields-for-blocks')}
				checked={display.orderMyAccount}
				onChange={(v) => onChange({ ...display, orderMyAccount: v })}
			/>
			<ToggleControl
				label={__('Order e-mail', 'checkout-fields-for-blocks')}
				checked={display.orderEmail}
				onChange={(v) => onChange({ ...display, orderEmail: v })}
			/>
		</PanelBody>
	);
};
