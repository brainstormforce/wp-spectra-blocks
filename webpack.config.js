const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const path = require( 'path' );
const fs = require( 'fs' );
const glob = require( 'glob' );

/**
 * Swiper externalization helpers.
 *
 * Maps 'swiper' and 'swiper/modules' to the global `Swiper` variable
 * provided by the standalone swiper-bundle.min.js so the library is not
 * bundled into webpack output (avoids WP.org compliance flags).
 *
 * @since x.x.x
 */
const SWIPER_REQUESTS = new Set( [ 'swiper', 'swiper/modules' ] );

function requestToExternal( request ) {
	if ( SWIPER_REQUESTS.has( request ) ) {
		return 'Swiper';
	}
}

function requestToHandle( request ) {
	if ( SWIPER_REQUESTS.has( request ) ) {
		return 'spectra-blocks-swiper-script';
	}
}

/**
 * Replace the default DependencyExtractionWebpackPlugin with a custom
 * instance that externalizes Swiper, while keeping all default @wordpress/*
 * externalization behaviour.
 *
 * @param {Array} plugins The plugins array from a webpack config.
 * @return {Array} Updated plugins array.
 */
function withSwiperExternals( plugins ) {
	return [
		...( plugins || [] ).filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			requestToExternal,
			requestToHandle,
		} ),
	];
}

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
	'@spectra-blocks': path.resolve( __dirname, 'src/blocks/' ),
	'@spectra-components': path.resolve( __dirname, 'src/components/' ),
	'@spectra-helpers': path.resolve( __dirname, 'src/helpers/' ),
	'@spectra-hooks': path.resolve( __dirname, 'src/hooks/' ),
	'@spectra-assets': path.resolve( __dirname, 'assets/' ),
	'@spectra-config': path.resolve( __dirname, 'src/helpers/plugin-config.js' ),
};

// Swiper CSS imports are handled by the registered 'spectra-blocks-swiper-style' handle — ignore them in webpack.
// Value must be the literal string 'null' (not ''): an empty-string external makes
// webpack 5 emit `const __WEBPACK_NAMESPACE_OBJECT__ = ;` when these imports are pulled
// into a block's editor module-concatenation chain (e.g. post-template/render.js), failing
// the build with `Unexpected token`. 'null' emits valid JS and the runtime value is unused.
const swiperCssExternals = {
	'swiper/css': 'null',
	'swiper/css/navigation': 'null',
	'swiper/css/pagination': 'null',
};

module.exports = [
	{
		...defaultConfig[ 0 ],
		performance: {
			hints: false,
		},
		externals: {
			...( typeof defaultConfig[ 0 ].externals === 'object' && ! Array.isArray( defaultConfig[ 0 ].externals )
				? defaultConfig[ 0 ].externals
				: {} ),
			...swiperCssExternals,
		},
		resolve: {
			...defaultConfig[ 0 ].resolve,
			alias: {
				...defaultConfig[ 0 ].resolve.alias,
				...commonAliases,
			},
			modules: [
				...( defaultConfig[ 0 ].resolve.modules || [] ),
				'node_modules',
			],
		},
		plugins: [
			...withSwiperExternals( defaultConfig[ 0 ].plugins ),
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
				const name = file.replace( /^(?:\.\/)?src\/styles\//, '' ).replace( '.scss', '' );
				entries[ `styles/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Add extension files
			extensionFiles.forEach( ( file ) => {
				const name = file
					.replace( /^(?:\.\/)?src\/extensions\//, '' )
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
		externals: {
			...( typeof defaultConfig[ 1 ].externals === 'object' && ! Array.isArray( defaultConfig[ 1 ].externals )
				? defaultConfig[ 1 ].externals
				: {} ),
			...swiperCssExternals,
		},
		resolve: {
			...defaultConfig[ 1 ].resolve,
			alias: {
				...defaultConfig[ 1 ].resolve.alias,
				...commonAliases,
			},
			modules: [
				...( defaultConfig[ 1 ].resolve.modules || [] ),
				'node_modules',
			],
		},
		plugins: [
			...withSwiperExternals( defaultConfig[ 1 ].plugins ),
			new AbsPathGuardPlugin(),
		],
	},
];
