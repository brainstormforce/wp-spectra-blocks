module.exports = function ( grunt ) {
	const { execSync } = require( 'child_process' );
	const path = require( 'path' );
	const fs = require( 'fs' );

	const PLUGIN_SLUG = 'spectra-blocks';
	const PLUGIN_ROOT = __dirname;
	const PARENT_DIR = path.dirname( PLUGIN_ROOT );

	/**
	 * Run a shell command synchronously with visible output.
	 *
	 * @param {string} cmd  Shell command to execute.
	 * @param {Object} opts Options passed to execSync (cwd defaults to PLUGIN_ROOT).
	 */
	const run = ( cmd, opts = {} ) => {
		grunt.log.writeln( '→ ' + cmd );
		execSync( cmd, { stdio: 'inherit', cwd: PLUGIN_ROOT, ...opts } );
	};

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// ── Text replacements ────────────────────────────────────────────

		replace: {
			// Version bump: readme.txt stable tag.
			stable_tag: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [
					{
						from: /Stable tag:\ .*/g,
						to: 'Stable tag: <%= pkg.version %>',
					},
				],
			},

			// Version bump: plugin constant.
			plugin_const: {
				src: [ 'spectra-blocks.php' ],
				overwrite: true,
				replacements: [
					{
						from: /SPECTRA_BLOCKS_VER', '.*?'/g,
						to: "SPECTRA_BLOCKS_VER', '<%= pkg.version %>'",
					},
				],
			},

			// Version bump: plugin header Version line.
			plugin_main: {
				src: [ 'spectra-blocks.php' ],
				overwrite: true,
				replacements: [
					{
						from: /Version: \bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?(?:\+[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\b/g,
						to: 'Version: <%= pkg.version %>',
					},
				],
			},

			// Version bump: @since x.x.x → current version in PHP files.
			plugin_since: {
				src: [
					'*.php',
					'**/*.php',
					'!node_modules/**',
					'!vendor/**',
					'!bin/**',
					'!tests/**',
					'!lib/**',
				],
				overwrite: true,
				replacements: [
					{
						from: /x\.x\.x/gi,
						to: '<%= pkg.version %>',
					},
				],
			},

			// Sync lib JS text domains → spectra-blocks.
			lib_js_textdomain: {
				src: [
					'lib/zip-ai/admin/dashboard-app/build/*.js',
					'lib/zip-ai/sidebar/build/*.js',
					'lib/nps-survey/dist/*.js',
					'lib/zipwp-images/dist/*.js',
					'lib/gutenberg-templates/dist/*.js',
					'lib/gutenberg-templates/inc/block/dist/*.js',
				],
				overwrite: true,
				replacements: [
					{ from: /,"zip-ai"/gi, to: ',"spectra-blocks"' },
					{ from: /,"nps-survey"/gi, to: ',"spectra-blocks"' },
					{ from: /,"zipwp-images"/gi, to: ',"spectra-blocks"' },
					{ from: /,"ast-block-templates"/gi, to: ',"spectra-blocks"' },
					{ from: /,"astra-sites"/gi, to: ',"spectra-blocks"' },
					{ from: /,"gutenberg-templates"/gi, to: ',"spectra-blocks"' },
				],
			},

			// Sync lib PHP text domains → spectra-blocks (gettext + textdomain loading).
			lib_php_textdomain: {
				src: [
					'lib/*.php',
					'lib/*/*.php',
					'lib/*/*/*.php',
					'lib/*/*/*/*.php',
					'lib/*/*/*/*/*.php',
					'lib/*/*/*/*/*/*.php',
				],
				overwrite: true,
				replacements: [
					// Gettext calls: , 'wrong-domain' ) → , 'spectra-blocks' )
					{ from: ", 'ast-block-templates' )", to: ", 'spectra-blocks' )" },
					{ from: ", 'zipwp-images' )", to: ", 'spectra-blocks' )" },
					{ from: ", 'nps-survey' )", to: ", 'spectra-blocks' )" },
					{ from: ", 'astra-sites' )", to: ", 'spectra-blocks' )" },
					{ from: ", 'astra-notices' )", to: ", 'spectra-blocks' )" },
					{ from: ", 'zip-ai' )", to: ", 'spectra-blocks' )" },
					{ from: ", 'gutenberg-templates' )", to: ", 'spectra-blocks' )" },
					// load_textdomain first arg.
					{ from: "load_textdomain( 'ast-block-templates'", to: "load_textdomain( 'spectra-blocks'" },
					{ from: "load_textdomain( 'zipwp-images'", to: "load_textdomain( 'spectra-blocks'" },
					// load_plugin_textdomain first arg.
					{ from: "load_plugin_textdomain( 'ast-block-templates'", to: "load_plugin_textdomain( 'spectra-blocks'" },
					{ from: "load_plugin_textdomain( 'zipwp-images'", to: "load_plugin_textdomain( 'spectra-blocks'" },
					// sprintf mofile pattern.
					{ from: "'%1$s-%2$s.mo', 'ast-block-templates'", to: "'%1$s-%2$s.mo', 'spectra-blocks'" },
					{ from: "'%1$s-%2$s.mo', 'zipwp-images'", to: "'%1$s-%2$s.mo', 'spectra-blocks'" },
					// plugin_locale filter.
					{ from: "plugin_locale', $get_locale, 'ast-block-templates'", to: "plugin_locale', $get_locale, 'spectra-blocks'" },
					{ from: "plugin_locale', $get_locale, 'zipwp-images'", to: "plugin_locale', $get_locale, 'spectra-blocks'" },
				],
			},

			// Add missing text domain to lib i18n calls that have none.
			// Runs AFTER lib_php_textdomain (which fixes wrong domains).
			// Matches: __( 'text' ), _e( 'text' ), esc_html__( 'text' ), etc.
			// Skips calls that already have a domain (two+ parameters).
			lib_php_add_textdomain: {
				src: [
					'lib/*.php',
					'lib/*/*.php',
					'lib/*/*/*.php',
					'lib/*/*/*/*.php',
					'lib/*/*/*/*/*.php',
					'lib/*/*/*/*/*/*.php',
				],
				overwrite: true,
				replacements: [
					{
						// String-literal i18n calls missing domain: __( 'text' ) → __( 'text', 'spectra-blocks' )
						from: /((?:__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\(\s*'(?:[^'\\]|\\.)*')\s*\)/g,
						to: "$1, 'spectra-blocks' )",
					},
					{
						// Dynamic i18n calls missing domain: __( func() ) → __( func(), 'spectra-blocks' )
						from: /((?:__|_e)\(\s*[a-z_]+\(\s*\$[a-z_]+\s*\))\s*\)/gi,
						to: "$1, 'spectra-blocks' )",
					},
					{
						// Move translators comment from above sprintf() to above __().
						from: "/* translators: %s product name */\n\t\t\t\t$notice_string = sprintf(\n\t\t\t\t\t__(",
						to: "$notice_string = sprintf(\n\t\t\t\t\t/* translators: %s product name */\n\t\t\t\t\t__(",
					},
				],
			},
		},
	} );

	grunt.loadNpmTasks( 'grunt-text-replace' );

	// ── Custom tasks ────────────────────────────────────────────────────

	// Add missing ABSPATH direct-access guards to lib PHP files.
	// WordPress.org requires every PHP file to prevent direct access.
	grunt.registerTask( 'lib-abspath-guard', 'Add ABSPATH exit guard to lib PHP files', function () {
		const glob = require( 'glob' );
		const guard = "defined( 'ABSPATH' ) || exit;";
		const files = glob.sync( 'lib/**/*.php', { cwd: PLUGIN_ROOT } );
		let patched = 0;

		files.forEach( function ( relPath ) {
			const filePath = path.join( PLUGIN_ROOT, relPath );
			const content = fs.readFileSync( filePath, 'utf8' );

			// Skip files that already have an ABSPATH check.
			if ( /defined\s*\(\s*['"]ABSPATH['"]\s*\)/.test( content ) ) {
				return;
			}

			// Insert guard after the opening docblock, or after <?php if no docblock.
			let updated;
			const docblockEnd = content.indexOf( '*/\n' );
			if ( docblockEnd !== -1 && docblockEnd < 200 ) {
				const pos = docblockEnd + 3; // after "*/\n"
				updated = content.slice( 0, pos ) + '\n' + guard + '\n' + content.slice( pos );
			} else {
				updated = content.replace( /^<\?php\s*\n/, '<?php\n' + guard + '\n' );
			}

			fs.writeFileSync( filePath, updated, 'utf8' );
			patched++;
			grunt.log.writeln( '  ✓ ' + relPath );
		} );

		grunt.log.ok( 'Patched ' + patched + ' file(s) with ABSPATH guard.' );
	} );

	// ── Composite tasks ──────────────────────────────────────────────────

	// Sync all lib text domains (JS + PHP) to plugin slug.
	grunt.registerTask( 'synctextdomains', [
		'replace:lib_js_textdomain',
		'replace:lib_php_textdomain',
		'replace:lib_php_add_textdomain',
	] );


	// Version bump: grunt bump-version --ver=<version>
	grunt.registerTask( 'bump-version', function () {
		const newVersion = grunt.option( 'ver' );
		if ( newVersion ) {
			grunt.config.set( 'pkg.version', newVersion );
			grunt.task.run( [
				'replace:stable_tag',
				'replace:plugin_const',
				'replace:plugin_main',
				'replace:plugin_since',
			] );
		} else {
			grunt.fail.fatal( 'Usage: grunt bump-version --ver=1.0.0' );
		}
	} );

	// ── Zip pipeline ─────────────────────────────────────────────────────
	// Creates a production-ready zip for WordPress.org upload.
	// Usage: grunt zip   (or: npm run zip)
	//
	// Note: grunt-contrib-copy / grunt-contrib-compress use recursive glob
	// patterns that are broken on Node >= 23 (Symbol conversion error).
	// We use rsync + zip via execSync instead — reliable on macOS / Linux.
	//
	// Pipeline:
	//   1. zip-build          → npm run build:fresh + admin build
	//   2. zip-composer-prod  → composer install --no-dev
	//   3. synctextdomains    → sync lib text domains to plugin slug
	//   4. zip-stage          → rsync to staging dir (excludes dev artifacts)
	//   5. zip-package        → create zip from staging + cleanup
	//   6. zip-restore        → composer install (restore dev deps)

	grunt.registerTask( 'zip-build', 'Build block and admin assets', function () {
		grunt.log.subhead(
			'Building ' + PLUGIN_SLUG + ' v' + grunt.config.get( 'pkg.version' )
		);
		run( 'npm run build:fresh' );
		run( 'rm -rf admin/assets/build' );
		run( 'npm run build', { cwd: path.join( PLUGIN_ROOT, 'admin' ) } );
	} );

	grunt.registerTask( 'zip-composer-prod', 'Install production Composer deps', function () {
		run( 'composer install --no-dev --quiet' );
	} );

	grunt.registerTask( 'zip-stage', 'Stage plugin files via rsync', function () {
		const stagingDir = path.join( PARENT_DIR, PLUGIN_SLUG + '-staging', PLUGIN_SLUG );

		// Remove duplicate vendor packages before staging.
		const dupDir = path.join( PLUGIN_ROOT, 'vendor', 'brainstormforce' );
		if ( fs.existsSync( dupDir ) ) {
			fs.rmSync( dupDir, { recursive: true, force: true } );
			grunt.log.writeln( '→ Removed duplicate vendor/brainstormforce/' );
		}

		// Rsync to staging directory, excluding dev artifacts.
		// Uses .distignore as the exclude file for a single source of truth.
		run(
			'rsync -a --delete' +
			' --exclude-from=".distignore"' +
			' ./' +
			' "' + stagingDir + '/"'
		);

		grunt.log.ok( 'Staged to: ' + stagingDir );
	} );

	grunt.registerTask( 'zip-package', 'Create zip from staging and cleanup', function () {
		const version = grunt.config.get( 'pkg.version' );
		const stagingParent = path.join( PARENT_DIR, PLUGIN_SLUG + '-staging' );
		const zipFile = path.join( PARENT_DIR, PLUGIN_SLUG + '.' + version + '.zip' );

		// Remove old zip if it exists.
		if ( fs.existsSync( zipFile ) ) {
			fs.unlinkSync( zipFile );
			grunt.log.writeln( '→ Removed old zip' );
		}

		// Create zip from staging directory.
		run(
			'zip -rq "' + zipFile + '" "' + PLUGIN_SLUG + '"',
			{ cwd: stagingParent }
		);

		// Remove staging directory.
		fs.rmSync( stagingParent, { recursive: true, force: true } );
		grunt.log.writeln( '→ Cleaned up staging directory' );

		// Report file size.
		const sizeMB = ( fs.statSync( zipFile ).size / ( 1024 * 1024 ) ).toFixed( 1 );
		grunt.log.ok( 'Created: ' + zipFile );
		grunt.log.ok( 'Size: ' + sizeMB + ' MB' );
	} );

	grunt.registerTask( 'zip-restore', 'Restore dev Composer deps', function () {
		run( 'composer install --quiet' );
	} );

	// Main zip task — chains all steps in order.
	grunt.registerTask( 'zip', 'Create production-ready zip for WordPress.org', [
		'zip-build',
		'zip-composer-prod',
		'synctextdomains',
		'zip-stage',
		'zip-package',
		'zip-restore',
	] );
};
