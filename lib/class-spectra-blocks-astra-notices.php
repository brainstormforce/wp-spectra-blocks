<?php
/**
 * Astra Notices library loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astra_Notices' ) ) {
	require_once __DIR__ . '/astra-notices/class-astra-notices.php';
}
