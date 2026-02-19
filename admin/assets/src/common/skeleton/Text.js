import React from 'react';

import './Text.scss';

function TextSkeleton( props ) {
	const { fontSize, width, style } = props;

	return (
		<div
			className="spectra-blocks-skeleton spectra-blocks-skeleton--text spectra-blocks-skeleton--wave"
			style={ {
				fontSize,
				width,
				...style,
			} }
		></div>
	);
}

export default TextSkeleton;
