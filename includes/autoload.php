<?php
/**
 * Custom Autoloader for Spectra Namespace.
 *
 * @since 3.0.0
 *
 * @package Spectra
 */

defined( 'ABSPATH' ) || exit;

/**
 * Autoload handler factory — maps a namespace prefix to the includes/ directory.
 *
 * @param string $namespace_prefix Namespace prefix (with trailing backslash).
 * @return Closure
 */
function spectra_blocks_make_autoloader( $namespace_prefix ) {
	return function ( $class_name ) use ( $namespace_prefix ) {
		if ( strpos( $class_name, $namespace_prefix ) !== 0 ) {
			return;
		}

		$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

		$relative_class = substr( $class_name, strlen( $namespace_prefix ) );

		$parts         = explode( '\\', $relative_class );
		$class_segment = array_pop( $parts );
		$file_name     = 'class-' . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_segment ) ) . '.php';

		$dir_path = empty( $parts ) ? '' : implode( DIRECTORY_SEPARATOR, $parts ) . DIRECTORY_SEPARATOR;
		$file     = $base_dir . $dir_path . $file_name;

		$real_path = realpath( $file );

		if ( $real_path && file_exists( $real_path ) && strpos( $real_path, realpath( $base_dir ) ) === 0 ) {
			require_once $real_path;
		}
	};
}

spl_autoload_register( spectra_blocks_make_autoloader( 'Spectra\\' ) );
spl_autoload_register( spectra_blocks_make_autoloader( 'SpectraBlocks\\' ) );

// Legacy explicit autoloader kept for backward compatibility.
spl_autoload_register(
	function ( $class_name ) {
		// Define the base namespace.
		$namespace = 'SpectraBlocks\\';

		// Ensure the class belongs to the Spectra namespace.
		if ( strpos( $class_name, $namespace ) !== 0 ) {
			return; // Not part of Spectra, ignore.
		}

		// Define the base directory for class files.
		$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

		// Get the relative class name (strip the Spectra\ prefix).
		$relative_class = substr( $class_name, strlen( $namespace ) );

		// Split into path segments on namespace separators.
		$parts = explode( '\\', $relative_class );

		// Directory segments stay as-is (PascalCase directories).
		// The last segment is the class name — convert to WordPress filename convention:
		// Example: HtmlSanitizer becomes class-html-sanitizer.php.
		$class_segment = array_pop( $parts );
		$file_name     = 'class-' . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_segment ) ) . '.php';

		// Rebuild the path: directories keep original casing, then the WP-style filename.
		$dir_path = empty( $parts ) ? '' : implode( DIRECTORY_SEPARATOR, $parts ) . DIRECTORY_SEPARATOR;
		$file     = $base_dir . $dir_path . $file_name;

		// Normalize path to prevent directory traversal attacks.
		$real_path = realpath( $file );

		// Check and load the class file.
		if ( $real_path && file_exists( $real_path ) && strpos( $real_path, realpath( $base_dir ) ) === 0 ) {
			require_once $real_path;
		}
	}
);
