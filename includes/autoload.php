<?php
/**
 * Custom Autoloader for Spectra Namespace.
 *
 * @since 1.0.0
 *
 * @package Spectra
 */

spl_autoload_register(
	function ( $class ) {
		// Define the base namespace.
		$namespace = 'Spectra\\';

		// Ensure the class belongs to the Spectra namespace.
		if ( strpos( $class, $namespace ) !== 0 ) {
			return; // Not part of Spectra, ignore.
		}

		// Define the base directory for class files.
		$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

		// Get the relative class name.
		$relative_class = substr( $class, strlen( $namespace ) );

		// Convert namespace separators to directory separators.
		$file = $base_dir . str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		// Normalize path to prevent directory traversal attacks.
		$real_path = realpath( $file );

		// Check and load the class file.
		if ( $real_path && file_exists( $real_path ) && strpos( $real_path, realpath( $base_dir ) ) === 0 ) {
			require_once $real_path;
		}
	}
);
