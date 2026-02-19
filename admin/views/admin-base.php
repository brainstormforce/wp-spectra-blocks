<?php
/**
 * Admin Base HTML.
 *
 * @package spectra-blocks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="spectra-blocks-menu-page-wrapper">
	<div id="spectra-blocks-menu-page">
		<div class="spectra-blocks-menu-page-content spectra-blocks-clear">
		<?php

			do_action( 'spectra_blocks_render_admin_content', $menu_page_slug, $page_action );
		?>
		</div>
	</div>
</div>
