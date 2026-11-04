<?php
/**
 * Additional Information tab
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/additional-information.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author        WooThemes
 * @package       WooCommerce/Templates
 * @version       3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

$heading = esc_html( apply_filters( 'woocommerce_product_additional_information_heading', __( 'Vanliga frågor', 'woocommerce' ) ) );
?>

<?php

    global $posts;

    $faq_ids = explode(',', get_field('faq_id', get_queried_object()));

    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'avada_faq',
        'post_status'    => 'published',
        'fields'         => 'ids',
		'orderby' 		 => 'menu_order',
		'order'			 => 'DESC'
    );

    $query = new WP_Query( $args );

    if ( ! empty( $query->posts ) && is_array( $query->posts ) ) : ?>

		<div class="fusion-faq-shortcode product-faq">
			<div class="fusion-faqs-wrapper" style="display: block;">
				<div class="accordian fusion-accordian">
					<div class="panel-group " id="accordian-1">

		<?php foreach ( $query->posts as $post_id ) :
		
			$categories = wp_get_object_terms( $post_id, "faq_category" );
			$general_product_faq = 4093;
			$category_ids = array();
			foreach($categories as $category){
				array_push($category_ids, $category->term_id);
			}

            if ( in_array($post_id, $faq_ids) || in_array($general_product_faq, $category_ids) ) :

                $faq = get_post( $post_id );

                ob_start();
                ?>
					<div class="fusion-panel panel-default fusion-faq-post">
						<span class="entry-title rich-snippet-hidden"><?php echo $faq->post_title; ?></span>
						<span class="updated rich-snippet-hidden"><?php echo $faq->post_date_gmt; ?></span>

						<div class="panel-heading">
							<h4 class="panel-title toggle">
								<a data-toggle="collapse" class="collapsed" data-parent="#accordian-1" data-target="#collapse-1-<?php echo $faq->ID; ?>" href="#collapse-1-<?php echo $faq->ID; ?>">
									<div class="fusion-toggle-icon-wrapper">
										<div class="fusion-toggle-icon-wrapper-main">
											<div class="fusion-toggle-icon-wrapper-sub">
												<i class="fa-fusion-box inactive-icon awb-icon-plus" aria-hidden="true"></i>
												<i class="fa-fusion-box active-icon awb-icon-minus" aria-hidden="true"></i>
												<!--<i class="fas fa-plus"></i>-->
											</div>
										</div>
									</div>
									<div class="fusion-toggle-heading"><?php echo $faq->post_title; ?></div>
								</a>
							</h4>
						</div>

						<div id="collapse-1-<?php echo $faq->ID; ?>" class="panel-collapse collapse">
							<div class="panel-body toggle-content post-content">
								<p><?php echo $faq->post_content; ?></p>
							</div>
						</div>
					</div>

                <?php echo ob_get_clean();
            endif;
        endforeach; ?>


					</div>
				</div>
			</div>
		</div>
    <?php endif;

//do_action( 'woocommerce_product_additional_information', $product );

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
