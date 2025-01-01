import metadata from './block.json';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';


// Global import
const { registerCheckoutBlock } = wc.blocksCheckout;
console.log(wc.blocksCheckout);


const Block = ({ children, checkoutExtensionData }) => {
    return (
        <div className={'example-fields'}>
            <ValidatedTextInput
                id="student_data"
                type="text"
                required={false}
                className={'student_data'}
                label={
                    'Student Data'
                }
                value={''}
            />
        </div>
    )
}

const options = {
    metadata,
    component: Block
};

registerCheckoutBlock(options);
