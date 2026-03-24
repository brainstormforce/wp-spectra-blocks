const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const fs = require( 'fs' );
const glob = require( 'glob' );

/**
 * Webpack plugin to prepend ABSPATH guard to generated .asset.php files.
 * WordPress.org requires all PHP files to prevent direct access.
 */
class AbsPathGuardPlugin {
	apply( compiler ) {
		compiler.hooks.afterEmit.tap( 'AbsPathGuardPlugin', ( compilation ) => {
			const outputPath = compilation.outputOptions.path;
			const findAssetPhp = ( dir ) => {
				const entries = fs.readdirSync( dir, { withFileTypes: true } );
				entries.forEach( ( entry ) => {
					const fullPath = path.join( dir, entry.name );
					if ( entry.isDirectory() ) {
						findAssetPhp( fullPath );
					} else if ( entry.name.endsWith( '.asset.php' ) ) {
						const content = fs.readFileSync( fullPath, 'utf8' );
						if ( ! content.includes( 'ABSPATH' ) ) {
							fs.writeFileSync( fullPath, content.replace( '<?php', "<?php if ( ! defined( 'ABSPATH' ) ) { exit; } " ) );
						}
					}
				} );
			};
			findAssetPhp( outputPath );
		} );
	}
}

// Define common aliases used in Spectra Blocks.
const commonAliases = {
	'@spectra': path.resolve( __dirname, 'src/blocks/' ),
	'@spectra-components': path.resolve( __dirname, 'src/components/' ),
	'@spectra-helpers': path.resolve( __dirname, 'src/helpers/' ),
	'@spectra-hooks': path.resolve( __dirname, 'src/hooks/' ),
	'@spectra-assets': path.resolve( __dirname, 'assets/' ),
	'@spectra-config': path.resolve( __dirname, 'src/helpers/plugin-config.js' ),
};

module.exports = [
	{
		...defaultConfig[ 0 ],
		performance: {
			hints: false,
		},
		resolve: {
			alias: {
				...defaultConfig[ 0 ].resolve.alias,
				...commonAliases,
			},
		},
		plugins: [
			...( defaultConfig[ 0 ].plugins || [] ),
			new AbsPathGuardPlugin(),
		],
		entry: () => {
			const entries = defaultConfig[ 0 ].entry();

			// Get all style files.
			const styleFiles = glob.sync( './src/styles/**/*.scss' );

			// Get all extension files (JS and SCSS).
			const extensionFiles = glob.sync(
				'./src/extensions/**/*.{js,scss}'
			);

			// For each file, just get the directory and file name, and add it to the entries.
			styleFiles.forEach( ( file ) => {
				const name = file.replace( './src/styles/', '' ).replace( '.scss', '' );
				entries[ `styles/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Add extension files
			extensionFiles.forEach( ( file ) => {
				const name = file
					.replace( './src/extensions/', '' )
					.replace( /\.(js|scss)$/, '' );
				entries[ `extensions/${ name }` ] = path.resolve(
					__dirname,
					file
				);
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
		plugins: [
			...( defaultConfig[ 1 ].plugins || [] ),
			new AbsPathGuardPlugin(),
		],
	},
];
