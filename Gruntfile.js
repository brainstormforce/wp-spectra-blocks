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

			// plugin_since is handled by the custom 'replace-since' task below
			// to avoid Grunt's bundled glob breaking on Node >= 20.

			// Sync lib JS text domains → spectra-blocks.
			lib_js_textdomain: {
				src: [
					'lib/nps-survey/dist/*.js',
					'lib/zipwp-images/dist/*.js',
					'lib/gutenberg-templates/dist/*.js',
					'lib/gutenberg-templates/inc/block/dist/*.js',
				],
				overwrite: true,
				replacements: [
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
						from: '/* translators: %s product name */\n\t\t\t\t$notice_string = sprintf(\n\t\t\t\t\t__(',
						to: '$notice_string = sprintf(\n\t\t\t\t\t/* translators: %s product name */\n\t\t\t\t\t__(',
					},
				],
			},
		},
		wp_readme_to_markdown: {
			readme: {
				files: {
					'README.md': 'readme.txt',
				},
			},
		},
	} );

	grunt.loadNpmTasks( 'grunt-text-replace' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );

	// ── Custom tasks ────────────────────────────────────────────────────

	// Add missing ABSPATH direct-access guards to lib PHP files.
	// WordPress.org requires every PHP file to prevent direct access.
	// For namespaced files the guard goes AFTER namespace/use (PHP requirement).
	// For non-namespaced files the guard goes after the opening docblock.
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

			let updated;

			// Find the last `use ...;` line — guard goes after it.
			const lastUse = content.match( /^use\s+[^;]+;\s*\n/gm );
			const hasNamespace = /^namespace\s+/m.test( content );

			if ( hasNamespace && lastUse ) {
				// Insert after the last use statement.
				const lastUseStr = lastUse[ lastUse.length - 1 ];
				const lastUsePos = content.lastIndexOf( lastUseStr ) + lastUseStr.length;
				updated = content.slice( 0, lastUsePos ) + '\n' + guard + '\n' + content.slice( lastUsePos );
			} else if ( hasNamespace ) {
				// Namespace but no use — insert after namespace line.
				updated = content.replace( /(^namespace\s+[^;]+;\s*\n)/m, '$1\n' + guard + '\n' );
			} else {
				// No namespace — insert after opening docblock or after <?php.
				const docblockEnd = content.indexOf( '*/\n' );
				if ( docblockEnd !== -1 && docblockEnd < 200 ) {
					const pos = docblockEnd + 3;
					updated = content.slice( 0, pos ) + '\n' + guard + '\n' + content.slice( pos );
				} else {
					updated = content.replace( /^<\?php\s*\n/, '<?php\n' + guard + '\n' );
				}
			}

			fs.writeFileSync( filePath, updated, 'utf8' );
			patched++;
			grunt.log.writeln( '  ✓ ' + relPath );
		} );

		grunt.log.ok( 'Patched ' + patched + ' file(s) with ABSPATH guard.' );
	} );

	// Replace @since x.x.x → current version in PHP files.
	// Uses find + sed instead of Grunt's glob (broken on Node >= 20).
	grunt.registerTask( 'replace-since', 'Replace x.x.x with current version in PHP files', function () {
		const version = grunt.config.get( 'pkg.version' );
		if ( ! version ) {
			grunt.fail.fatal( 'pkg.version is not set.' );
		}
		// find all .php files, excluding dirs that should not be touched.
		// sed -i '' is macOS syntax; on Linux use sed -i (no quotes).
		const sedFlag = process.platform === 'darwin' ? "-i ''" : '-i';
		run(
			'find . -name "*.php"' +
			' -not -path "./node_modules/*"' +
			' -not -path "./vendor/*"' +
			' -not -path "./bin/*"' +
			' -not -path "./tests/*"' +
			' -not -path "./lib/*"' +
			' -exec sed ' + sedFlag + ' "s/x\\.x\\.x/' + version + '/g" {} +'
		);
	} );

	// ── Composite tasks ──────────────────────────────────────────────────

	// Sync all lib text domains (JS + PHP) to plugin slug.
	grunt.registerTask( 'synctextdomains', [
		'replace:lib_js_textdomain',
		'replace:lib_php_textdomain',
		'replace:lib_php_add_textdomain',
	] );


	// POT file generation: grunt i18n
	grunt.registerTask( 'makepot-cmd', 'Generate POT file via WP-CLI', function () {
		run( 'wp i18n make-pot . languages/spectra-blocks.pot --skip-audit --exclude=node_modules,vendor,lib,build,admin/assets/build,admin/assets/src,bin' );
	} );

	grunt.registerTask( 'i18n', [ 'synctextdomains', 'makepot-cmd' ] );

	// README.md generation: grunt readme
	grunt.registerTask( 'readme', [ 'wp_readme_to_markdown' ] );

	// Version bump: grunt bump-version --ver=<version>
	grunt.registerTask( 'bump-version', function () {
		const newVersion = grunt.option( 'ver' );
		if ( ! newVersion ) {
			grunt.fail.fatal( 'Usage: grunt bump-version --ver=1.0.0' );
			return;
		}

		// Write new version to package.json on disk.
		const pkgPath = path.join( PLUGIN_ROOT, 'package.json' );
		const pkg = JSON.parse( fs.readFileSync( pkgPath, 'utf8' ) );
		pkg.version = newVersion;
		fs.writeFileSync( pkgPath, JSON.stringify( pkg, null, '\t' ) + '\n', 'utf8' );
		grunt.log.ok( 'Updated package.json → ' + newVersion );

		// Update package-lock.json to match.
		run( 'npm install --package-lock-only --ignore-scripts --silent' );
		grunt.log.ok( 'Updated package-lock.json → ' + newVersion );

		grunt.config.set( 'pkg.version', newVersion );
		grunt.task.run( [
			'replace:stable_tag',
			'replace:plugin_const',
			'replace:plugin_main',
			'replace-since',
		] );
	} );

	// ── Zip pipeline ─────────────────────────────────────────────────────
	// Creates a production-ready zip for WordPress.org upload.
	// Usage: grunt zip   (or: npm run zip)
	//
	// Note: grunt-contrib-copy / grunt-contrib-compress use recursive glob
	// patterns that are broken on Node >= 23 (Symbol conversion error).
	// We use Node.js fs + glob + adm-zip instead — cross-platform (macOS / Linux / Windows).
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
		fs.rmSync( path.join( PLUGIN_ROOT, 'admin', 'assets', 'build' ), { recursive: true, force: true } );
		grunt.log.writeln( '→ rm -rf admin/assets/build' );
		run( 'npm run build', { cwd: path.join( PLUGIN_ROOT, 'admin' ) } );
	} );

	grunt.registerTask( 'zip-composer-prod', 'Install production Composer deps', function () {
		run( 'composer install --no-dev --quiet' );
	} );

	grunt.registerTask( 'zip-stage', 'Stage plugin files (cross-platform, no rsync)', function () {
		const glob = require( 'glob' );
		const stagingDir = path.join( PARENT_DIR, PLUGIN_SLUG + '-staging', PLUGIN_SLUG );

		// Remove duplicate vendor packages before staging.
		const dupDir = path.join( PLUGIN_ROOT, 'vendor', 'brainstormforce' );
		if ( fs.existsSync( dupDir ) ) {
			fs.rmSync( dupDir, { recursive: true, force: true } );
			grunt.log.writeln( '→ Removed duplicate vendor/brainstormforce/' );
		}

		// Parse .distignore into glob ignore patterns.
		const distignoreLines = fs
			.readFileSync( path.join( PLUGIN_ROOT, '.distignore' ), 'utf8' )
			.split( /\r?\n/ )
			.map( ( l ) => l.trim() )
			.filter( ( l ) => l && ! l.startsWith( '#' ) );

		// Convert each .distignore entry to glob ignore patterns.
		// Supports gitignore/rsync conventions:
		// - Leading slash (/src/) → root-anchored (only matches at repo root)
		// - Internal slash (lib/foo) → root-anchored
		// - No slash (*.log, node_modules) → matches at any depth
		const ignorePatterns = distignoreLines.flatMap( ( entry ) => {
			const clean = entry.replace( /\/$/, '' ); // strip trailing slash
			// Leading slash means root-anchored (gitignore convention).
			if ( clean.startsWith( '/' ) ) {
				const anchored = clean.slice( 1 );
				return [ anchored, anchored + '/**' ];
			}
			// A pattern is "anchored" if it contains an internal slash.
			const hasInternalSlash = clean.includes( '/' );
			if ( hasInternalSlash ) {
				// Anchored pattern — match relative to root only.
				return [ clean, clean + '/**' ];
			}
			// Un-anchored pattern — match at any depth in the tree.
			return [ clean, clean + '/**', '**/' + clean, '**/' + clean + '/**' ];
		} );

		// Collect all files to copy (directories are implicit).
		const filesToCopy = glob.sync( '**/*', {
			cwd: PLUGIN_ROOT,
			dot: true,
			nodir: true,
			ignore: ignorePatterns,
		} );

		// Reset staging dir cleanly.
		fs.rmSync( stagingDir, { recursive: true, force: true } );
		fs.mkdirSync( stagingDir, { recursive: true } );

		let copied = 0;
		filesToCopy.forEach( ( relPath ) => {
			const src = path.join( PLUGIN_ROOT, relPath );
			const dest = path.join( stagingDir, relPath );
			fs.mkdirSync( path.dirname( dest ), { recursive: true } );
			fs.copyFileSync( src, dest );
			copied++;
		} );

		grunt.log.writeln( '→ Copied ' + copied + ' files to staging' );
		grunt.log.ok( 'Staged to: ' + stagingDir );
	} );

	grunt.registerTask( 'zip-package', 'Create zip from staging (cross-platform, no zip CLI)', function () {
		const AdmZip = require( 'adm-zip' );
		const version = grunt.config.get( 'pkg.version' );
		const stagingParent = path.join( PARENT_DIR, PLUGIN_SLUG + '-staging' );
		const stagingDir = path.join( stagingParent, PLUGIN_SLUG );
		const zipFile = path.join( PARENT_DIR, PLUGIN_SLUG + '.' + version + '.zip' );

		// Remove old zip if it exists.
		if ( fs.existsSync( zipFile ) ) {
			fs.unlinkSync( zipFile );
			grunt.log.writeln( '→ Removed old zip' );
		}

		// Build zip using adm-zip (pure Node.js, cross-platform).
		const zip = new AdmZip();

		const addDirRecursive = ( dir, zipBasePath ) => {
			const entries = fs.readdirSync( dir, { withFileTypes: true } );
			entries.forEach( ( entry ) => {
				const fullPath = path.join( dir, entry.name );
				const entryPath = zipBasePath + '/' + entry.name;
				if ( entry.isDirectory() ) {
					addDirRecursive( fullPath, entryPath );
				} else {
					zip.addFile(
						entryPath,
						fs.readFileSync( fullPath )
					);
				}
			} );
		};

		addDirRecursive( stagingDir, PLUGIN_SLUG );
		zip.writeZip( zipFile );

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
		'lib-abspath-guard',
		'synctextdomains',
		'zip-stage',
		'zip-package',
		'zip-restore',
	] );

	// Alias matching UAGB convention: grunt release === grunt zip.
	grunt.registerTask( 'release', [ 'zip' ] );
};

