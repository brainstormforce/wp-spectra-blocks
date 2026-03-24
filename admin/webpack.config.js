// Load the default @wordpress/scripts config object
const path = require( 'path' );
const fs = require( 'fs' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

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
const newPath = path.join( __dirname, '../' );

// Use the defaultConfig but add the common aliases, modules and plugins.
const commonConfig = {
	...defaultConfig,
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			'@Admin': path.resolve( __dirname, 'assets/src/' ),
			'@Utils': path.resolve( __dirname, 'assets/src/utils/' ),
			'@Controls': path.resolve( __dirname, 'assets/src/controls/' ),
			'@Helpers': path.resolve( __dirname, 'assets/src/utils/' ),
			'@Fields': path.resolve(
				__dirname,
				'assets/src/fields/'
			),
			'@Skeleton': path.resolve(
				__dirname,
				'assets/src/common/skeleton/'
			),
			'@SettingsApp': path.resolve(
				__dirname,
				'assets/src/settings-app/'
			),
			'@DashboardApp': path.resolve(
				__dirname,
				'assets/src/dashboard-app/'
			),
			'@Common': path.resolve(
				__dirname,
				'assets/src/common/'
			),
			'@spectra-helpers': path.resolve( newPath, 'src/helpers/' ),
		},
	},
	module: {
		...defaultConfig.module,
	},
	plugins: [
		...defaultConfig.plugins.filter( function ( plugin ) {
			if ( plugin.constructor.name === 'LiveReloadPlugin' ) {
				return false;
			}

			return true;
		} ),
		new AbsPathGuardPlugin(),
	],
};

// Now using the commonConfig that inherits the defaultConfig, replace the entry and output properties for each app.

// Config for the Spectra Dashboard App.
const dashboardConfig = Object.assign( {}, commonConfig, {
	name: 'dashboard',
	entry: {
		'dashboard-app': path.resolve(
			__dirname,
			'assets/src/DashboardApp.js'
		)
	},
	output: {
		filename: '[name].js',
		chunkFilename: 'chunks/[name].[contenthash].js',
		path: path.resolve( __dirname, 'assets/build' ),
		publicPath: 'auto',
	},
	optimization: {
		...( commonConfig.optimization || {} ),
		splitChunks: {
			// Only split async chunks (loaded via import()). Initial (sync) chunks must be
			// explicitly registered with wp_enqueue_script — WordPress only knows about the
			// entry file, so splitting initial chunks causes a blank page.
			chunks: 'async',
			cacheGroups: {
				// Share node_modules code that appears in 2+ async page chunks.
				vendors: {
					test: /[\\/]node_modules[\\/]/,
					name: 'vendor',
					chunks: 'async',
					priority: 10,
					minChunks: 2,
				},
			},
		},
	},
} );

// Export all the configs.
module.exports = [
	dashboardConfig,
];