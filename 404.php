<?php
/**
 * The templates used for 404 pages.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
?>
<?php get_header(); ?>
<section id="content" class="full-width">
	<div id="post-404page">
		<div class="post-content">
			<?php
			// Render the page titles.
			$subtitle = esc_html__( 'Oops, This Page Could Not Be Found!', 'Avada' );
			Avada()->template->title_template( $subtitle );
			?>
			<div class="fusion-clearfix"></div>
			<div class="error-page">
				<div class="fusion-columns fusion-columns-2">
					<div class="fusion-column col-lg-4 col-md-4 col-sm-4">
						<div class="error-message">404</div>
					</div>
					<div class="fusion-column col-lg-8 col-md-8 col-sm-8">
						<p>REHABgrossisten i Norden är en bifirma till Embreis AB som grundades 2008 av Thomas Hammarlund och Ulf Odenblad.
                            Rehabgrossisten.com vänder sig i första hand till privatpersoner som är i behov av ett enklare hjälpmedel, stöd eller
                            bandage som inte kräver utprovning av legitimerade ortopedingenjör eller annan sjukvårdsutbildad personal.
                            I sortimentet finns även produkter som berör rehabilitering och träning så som kompressionsplagg, rehab- och
                            träningsprodukter.</p>
					</div>
				</div>
			</div>
            <div class="fusion-clearfix"></div>

            <div class="featured-products">

                <div class="exclude-padding">

					<!-- 404 page - Retargeted - Box 1 -->
					<!-- <div class="hr-skeleton" id="hr-recom-k6656f70a2ae1c251fa2b09b4"></div> -->

					<!-- 404 page - Retargeted - Box 1 -->
					<div class="hr-skeleton" id="hr-recom-k6655dfb8f72036773fb25144">
						<div class="skeleton-cards">
							<div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>
							<div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>
							<div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>
							<div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>							
						</div>
					</div>
					
					<!-- 404 page - Top products - Box 1 -->
					<!-- <div class="hr-skeleton" id="hr-recom-k6655dfb8f72036773fb25148"></div> -->

					<!-- <?php // echo do_shortcode('[featured_products limit="4" columns="4" orderby="date" order="desc"]'); ?> -->
                </div>
				
            </div>
		</div>
	</div>
</section>
<?php
get_footer();

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
