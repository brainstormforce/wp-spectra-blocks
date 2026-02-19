// Load the default @wordpress/scripts config object
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
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
			chunks: 'all',
			cacheGroups: {
				// Extract @bsf/force-ui into a shared chunk (used on every page).
				forceUi: {
					test: /[\\/]node_modules[\\/]@bsf[\\/]/,
					name: 'vendor-bsf',
					chunks: 'all',
					priority: 30,
				},
				// Extract react-related packages shared across pages.
				reactVendor: {
					test: /[\\/]node_modules[\\/](react|react-dom|react-redux|react-router|redux|scheduler)[\\/]/,
					name: 'vendor-react',
					chunks: 'all',
					priority: 20,
				},
				// General vendor chunk for remaining node_modules.
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