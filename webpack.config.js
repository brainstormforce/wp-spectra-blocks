const rawDefaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const glob = require( 'glob' );
const fs = require( 'fs' );

// Handle both array and single object formats from @wordpress/scripts.
const defaultConfigs = Array.isArray( rawDefaultConfig )
	? rawDefaultConfig
	: [ rawDefaultConfig ];

// Resolve swiper paths for webpack 4 (which doesn't support package.json "exports" field).
const swiperPath = path.resolve( __dirname, 'node_modules/swiper' );

// Define common aliases used in Spectra 3.
const commonAliases = {
	'@spectra-blocks': path.resolve( __dirname, 'src/blocks/' ),
	'@spectra-components': path.resolve( __dirname, 'src/components/' ),
	'@spectra-helpers': path.resolve( __dirname, 'src/helpers/' ),
	'@spectra-hooks': path.resolve( __dirname, 'src/hooks/' ),
	'@spectra-assets': path.resolve( __dirname, 'assets/' ),

	// Swiper v8 aliases — webpack 4 can't resolve "exports" in package.json.
	'swiper/css$': path.resolve( swiperPath, 'swiper.min.css' ),
	'swiper/css/navigation$': path.resolve(
		swiperPath,
		'modules/navigation/navigation.min.css'
	),
	'swiper/css/pagination$': path.resolve(
		swiperPath,
		'modules/pagination/pagination.min.css'
	),
	'swiper/modules$': path.resolve( swiperPath, 'swiper.esm.js' ),
};

/**
 * Custom webpack plugin to copy block.json and PHP files from src/blocks to build/blocks.
 * These files are required at runtime but are not processed by webpack.
 */
class CopyBlockFilesPlugin {
	apply( compiler ) {
		compiler.hooks.afterEmit.tapAsync(
			'CopyBlockFilesPlugin',
			( compilation, callback ) => {
				const srcBlocksDir = path.resolve( __dirname, 'src/blocks' );
				const buildBlocksDir = path.resolve(
					__dirname,
					'build/blocks'
				);

				const blockDirs = glob.sync( './src/blocks/*/', {
					absolute: false,
				} );

				blockDirs.forEach( ( blockDirRel ) => {
					const blockName = path.basename( blockDirRel );
					const srcDir = path.resolve( srcBlocksDir, blockName );
					const destDir = path.resolve( buildBlocksDir, blockName );

					// Ensure destination directory exists.
					if ( ! fs.existsSync( destDir ) ) {
						fs.mkdirSync( destDir, { recursive: true } );
					}

					// Copy block.json and PHP files.
					const filesToCopy = glob.sync(
						path.join( srcDir, '*.{json,php}' )
					);
					filesToCopy.forEach( ( srcFile ) => {
						const fileName = path.basename( srcFile );
						const destFile = path.resolve( destDir, fileName );
						fs.copyFileSync( srcFile, destFile );
					} );
				} );

				callback();
			}
		);
	}
}

const configs = [
	{
		...defaultConfigs[ 0 ],
		resolve: {
			...( defaultConfigs[ 0 ].resolve || {} ),
			alias: {
				...( defaultConfigs[ 0 ].resolve?.alias || {} ),
				...commonAliases,
			},
		},
		plugins: [
			...( defaultConfigs[ 0 ].plugins || [] ),
			new CopyBlockFilesPlugin(),
		],
		entry: () => {
			const entries = {};

			// Auto-discover block entries from block.json files.
			const blockJsonFiles = glob.sync(
				'./src/blocks/*/block.json'
			);

			blockJsonFiles.forEach( ( blockJsonPath ) => {
				const blockDir = path.dirname( blockJsonPath );
				const blockName = path.basename( blockDir );
				const blockJson = JSON.parse(
					fs.readFileSync( blockJsonPath, 'utf8' )
				);

				// Add editor script entry (index.js).
				if (
					blockJson.editorScript &&
					blockJson.editorScript.startsWith( 'file:' )
				) {
					const scriptFile = blockJson.editorScript.replace(
						'file:',
						''
					);
					const scriptPath = path.resolve(
						__dirname,
						blockDir,
						scriptFile
					);
					if ( fs.existsSync( scriptPath ) ) {
						entries[ `blocks/${ blockName }/index` ] =
							scriptPath;
					}
				}

				// Add view script entry (view.js) if it exists.
				const viewPath = path.resolve(
					__dirname,
					blockDir,
					'view.js'
				);
				if ( fs.existsSync( viewPath ) ) {
					entries[ `blocks/${ blockName }/view` ] = viewPath;
				}
			} );

			// Get all style files.
			const styleFiles = glob.sync( './src/styles/**/*.scss' );

			// Get all extension files (JS and SCSS).
			const extensionFiles = glob.sync(
				'./src/extensions/**/*.{js,scss}'
			);

			// For each file, just get the directory and file name, and add it to the entries.
			styleFiles.forEach( ( file ) => {
				const name = file
					.replace( './src/styles/', '' )
					.replace( '.scss', '' );
				entries[ `styles/${ name }` ] = path.resolve(
					__dirname,
					file
				);
			} );

			// Add extension files.
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
];

// Add second config if available (multi-compiler setup).
if ( defaultConfigs[ 1 ] ) {
	configs.push( {
		...defaultConfigs[ 1 ],
		resolve: {
			...( defaultConfigs[ 1 ].resolve || {} ),
			alias: {
				...( defaultConfigs[ 1 ].resolve?.alias || {} ),
				...commonAliases,
			},
		},
	} );
}

module.exports = configs;
