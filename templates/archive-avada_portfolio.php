<?php
/**
 * Portfolio Template.
 *
 * @package Fusion-Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
if ( ! class_exists( 'Avada' ) ) {
	exit( 'This feature requires the Avada theme.' );
}

global $post;
$parentPortfolio = false; // Highest level of portfolio "Skadeguiden"

if (strpos(get_the_archive_title(), "Arkiv:") === 0) {
    $title = "Skadeguiden";
    $parentPortfolio = true;
} else {
    $title = str_replace("Portfolio Kategorier:","Skadeguiden", get_the_archive_title());
}

console_log("Skadeguiden");

?>
<?php get_header(); ?>
	<div id="content" <?php Avada()->layout->add_class( 'content_class' ); ?> <?php Avada()->layout->add_style( 'content_style' ); ?>>

        <div style="margin-bottom: 0;" class="fusion-title title fusion-sep-none fusion-title-center fusion-title-size-one fusion-border-below-title">
            <h1 class="title-heading-center"><?php echo $title; ?></h1>
        </div>

        <?php
        if($parentPortfolio) {
            get_template_part( 'templates/parent-portfolio' );
        }

        require 'portfolio-archive-layout.php'; ?>

	</div>
	<?php do_action( 'avada_after_content' ); ?>
<?php
get_footer();

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
