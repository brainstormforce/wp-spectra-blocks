import React from 'react';

import './Rectangle.scss';

function RectSkeleton( props ) {
	const { width, height, style } = props;

	return (
		<div
			className="spectra-blocks-skeleton spectra-blocks-skeleton--rect spectra-blocks-skeleton--wave"
			style={ {
				width,
				height,
				...style,
			} }
		></div>
	);
}

export default RectSkeleton;
