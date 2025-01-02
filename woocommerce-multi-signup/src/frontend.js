import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';

import metadata from './block.json';


// Global import
const { registerCheckoutBlock, registerCheckoutFilters } = wc.blocksCheckout;

const Block = ({ children, checkoutExtensionData }) => {
    const [studentData, setStudentData] = useState({ students: {} });
    const { setExtensionData } = checkoutExtensionData;

    const cartItems = useSelect((select) => {
        const store = select('wc/store/cart');
        return store.getCartData().items;
    });

    const onInputChange = useCallback(
        (value, item, number, field) => {
            if (!studentData.students[item.id]) {
                studentData.students[item.id] = {};
            }
            if (!studentData.students[item.id][number]) {
                studentData.students[item.id][number] = {
                    firstName: '',
                    lastName: '',
                    email: '',
                    courseName: item.name,
                };
            }
            studentData.students[item.id][number][field] = value;

            setStudentData(studentData);
            setExtensionData('woocommerce-multi-signup', 'student_data', JSON.stringify(studentData));
        },
        [setStudentData, setExtensionData]
    )

    const getFormatFromItem = function (item) {
        return [...Array(item.quantity)].map((_, index) => {
            const studentNumber = index + 1;

            return <div className={'wc-block-checkout__billing-fields'}>
                <h3
                    style={{ margin: "0 0 12px 0", fontSize: "1em" }}
                    class={"wc-block-components-title"}
                >
                    {item.name} Student {studentNumber} Info
                </h3>
                <div className={'wc-block-components-address-form'}>
                    <ValidatedTextInput
                        id={"student_data_first_name_" + studentNumber}
                        type="text"
                        required={true}
                        className={'wc-block-components-text-input'}
                        label={
                            'First Name'
                        }
                        value={studentData.students?.[item.key]?.[studentNumber]?.firstName || ''}
                        onChange={(value) => onInputChange(value, item, studentNumber, 'firstName')}
                    />
                    <ValidatedTextInput
                        id={"student_data_last_name_" + studentNumber}
                        type="text"
                        required={true}
                        className={'wc-block-components-text-input'}
                        label={
                            'Last Name'
                        }
                        value={studentData.students?.[item.key]?.[studentNumber]?.lastName || ''}
                        onChange={(value) => onInputChange(value, item, studentNumber, 'lastName')}
                    />
                    <ValidatedTextInput
                        id={"student_data_email_" + studentNumber}
                        type="email"
                        required={true}
                        className={'wc-block-components-text-input wc-block-components-address-form__address_1'}
                        label={
                            'Email'
                        }
                        value={studentData.students?.[item.key]?.[studentNumber]?.email || ''}
                        onChange={(value) => onInputChange(value, item, studentNumber, 'email')}
                    />
                </div>

            </div>
        });
    }

    if (cartItems?.length == 0 || cartItems.reduce((acc, item) => acc + item.quantity, 0) < 2) {
        return null;
    }

    return (
        <>
            <h2 class={"wc-block-components-title"}>Student Info</h2>
            <p
                class={"wc-block-components-checkout-step__description"}
                style={{ marginTop: "1em" }}
            >
                This infomation will be used to register the students. Information to set up their account will be sent to the provided email.
            </p>
            {cartItems.map((item) => getFormatFromItem(item))}
        </>
    )
}

const options = {
    metadata,
    component: Block
};

registerCheckoutBlock(options);
