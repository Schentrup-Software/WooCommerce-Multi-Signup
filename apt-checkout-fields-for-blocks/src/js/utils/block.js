import { useSelect } from '@wordpress/data';

export const getParentBlockName = (clientId) => {
	return useSelect(
		(select) => {
			const { getBlockRootClientId, getBlockName } =
				select('core/block-editor');
			const parentBlockClientId = getBlockRootClientId(clientId);
			return parentBlockClientId
				? getBlockName(parentBlockClientId)
				: null;
		},
		[clientId]
	);
};
