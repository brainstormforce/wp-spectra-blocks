module.exports = function ( grunt ) {
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		copy: {
			main: {
				options: {
					mode: true,
				},
				src: [
					'**',
					'!node_modules/**',
					'!.git/**',
					'!*.sh',
					'!*.zip',
					'!eslintrc.json',
					'!README.md',
					'!Gruntfile.js',
					'!package.json',
					'!package-lock.json',
					'!.gitignore',
					'!.gitattributes',
					'!*.zip',
					'!*.neon',
					'!Optimization.txt',
					'!composer.json',
					'!composer.lock',
					'!phpcs.xml.dist',
					'!phpunit.xml.dist',
					'!vendor/**',
					'!src/**',
					'!scripts/**',
					'!config/**',
					'!tests/**',
					'!bin/**',
					'!webpack.config.js',
					'!auth.json',
					'!spectra/node_modules/**',
					'!spectra/src/**',
					'!spectra/composer.json',
					'!spectra/composer.lock',
					'!spectra/package.json',
					'!spectra/package-lock.json',
					'!spectra/README.md',
				],
				dest: 'spectra/',
			},
		},
		compress: {
			main: {
				options: {
					archive:
						'spectra-<%= pkg.version %>.zip',
					mode: 'zip',
				},
				files: [
					{
						src: ['./spectra/**'],
					},
				],
			},
		},
		clean: {
			main: ['spectra'],
			zip: ['*.zip'],
		},
		makepot: {
			target: {
				options: {
					domainPath: '/',
					mainFile: 'spectra.php',
					potFilename: 'languages/spectra.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
					},
					type: 'wp-plugin',
					updateTimestamp: true,
				},
			},
		},

		addtextdomain: {
			options: {
				textdomain: 'spectra',
				updateDomains: true,
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!vendor/**',
					],
				},
			},
		},
	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );

	grunt.registerTask( 'i18n', [ 'addtextdomain', 'makepot' ] );
	grunt.registerTask( 'release', [
		'clean:zip',
		'copy',
		'compress',
		'clean:main',
	] );
};
