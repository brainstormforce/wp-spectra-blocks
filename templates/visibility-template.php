<?php
/**
 * Minimal template for Coming Soon / Maintenance Mode page.
 * Renders only the page content — no theme header or footer.
 *
 * @package Spectra_Blocks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
wp_body_open();

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

wp_footer();
?>
</body>
</html>
