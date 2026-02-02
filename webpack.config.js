const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const glob = require( 'glob' );

// Define common aliases used in Spectra 3.
const commonAliases = {
	'@spectra-blocks': path.resolve( __dirname, 'src/blocks/' ),
	'@spectra-components': path.resolve( __dirname, 'src/components/' ),
	'@spectra-helpers': path.resolve( __dirname, 'src/helpers/' ),
	'@spectra-hooks': path.resolve( __dirname, 'src/hooks/' ),
	'@spectra-assets': path.resolve( __dirname, 'assets/' ),
};

module.exports = [
	{
		...defaultConfig[ 0 ],
		resolve: {
			alias: {
				...defaultConfig[ 0 ].resolve.alias,
				...commonAliases,
			},
		},
		entry: () => {
			const entries = defaultConfig[ 0 ].entry();

			// Get all style files.
			const styleFiles = glob.sync( './src/styles/**/*.scss' );

			// For each file, just get the directory and file name, and add it to the entries.
			styleFiles.forEach( ( file ) => {
				const name = file.replace( './src/styles/', '' ).replace( '.scss', '' );
				entries[ `styles/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Get all extension files (JS and SCSS).
			const extensionFiles = glob.sync( './src/extensions/**/*.{js,scss}' );

			extensionFiles.forEach( ( file ) => {
				const name = file.replace( './src/extensions/', '' ).replace( /\.(js|scss)$/, '' );
				entries[ `extensions/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Return the modified entries.
			return entries;
		},
	},
	{
		...defaultConfig[ 1 ],
		resolve: {
			alias: {
				...defaultConfig[ 1 ].resolve.alias,
				...commonAliases,
			},
		},
	},
];
