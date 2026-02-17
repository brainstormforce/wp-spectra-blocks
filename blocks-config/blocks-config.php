<?php
/**
 * Blocks config loader.
 *
 * @package UAGB
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'SPECTRA_DIR' ) ) {
	exit; // Exit if accessed directly, or if SPECTRA_DIR is not defined.
}

// Require the popup builder class (used by both v2 and v3).
require_once SPECTRA_DIR . 'blocks-config/popup-builder/class-spectra-popup-builder.php';
