/**
 * Template for the countdown block.
 */
const TEMPLATE = [
	[
		'spectra/countdown-child-day',
		{
			lock: { move: true, remove: true },
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|10',
				},
			},
		},
	],
	[
		'spectra/countdown-child-separator',
		{
			lock: { move: true, remove: true },
			style: {
				typography: {
					textAlign: 'center',
				},
			},
		},
	],
	[
		'spectra/countdown-child-hour',
		{
			lock: { move: true, remove: true },
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|10',
				},
			},
		},
	],
	[
		'spectra/countdown-child-separator',
		{
			lock: { move: true, remove: true },
			style: {
				typography: {
					textAlign: 'center',
				},
			},
		},
	],
	[
		'spectra/countdown-child-minute',
		{
			lock: { move: true, remove: true },
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|10',
				},
			},
		},
	],
	[
		'spectra/countdown-child-separator',
		{
			lock: { move: true, remove: true },
			style: {
				typography: {
					textAlign: 'center',
				},
			},
		},
	],
	[
		'spectra/countdown-child-second',
		{
			lock: { move: true, remove: true },
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|10',
				},
			},
		},
	],
];

export default TEMPLATE;
