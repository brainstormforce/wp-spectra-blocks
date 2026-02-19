import React from 'react';

import './Spacer.scss';

function SpacerSkeleton( props ) {
	const { height, style } = props;

	return (
		<div
			className="spectra-blocks-skeleton-base spectra-blocks-skeleton--spacer"
			style={ {
				height,
				...style,
			} }
		></div>
	);
}

export default SpacerSkeleton;
