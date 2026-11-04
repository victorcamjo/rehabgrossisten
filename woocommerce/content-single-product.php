<?php
/**
 * The templates for displaying product content in the single-product.php templates
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook Woocommerce_before_single_product.
 *
 * @hooked wc_print_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

// Product information
$product_information = get_field('product_information', get_the_ID());
$product_info_values_list = [];

if ($product_information) {
	foreach ($product_information as $item) {
		if (isset($item['values'])) {
			foreach ($item['values'] as $value) {
				if (isset($value['value'])) {
					$product_info_values_list[] = $value['value'];
				}
			}
		}
	}
}

console_log(implode(', ', $product_info_values_list));

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product ); ?>>

    <div class="single-product-details-container">
	<?php
		/**
		 * Hook: woocommerce_before_single_product_summary.
		 *
		 * @hooked woocommerce_show_product_sale_flash - 10
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
	?>
    </div>

	<div class="summary entry-summary">
		<?php
			/**
			 * Hook: Woocommerce_single_product_summary.
			 *
			 * @hooked woocommerce_template_single_title - 5
			 * @hooked woocommerce_template_single_rating - 10
			 * @hooked woocommerce_template_single_price - 10
			 * @hooked woocommerce_template_single_excerpt - 20
			 * @hooked woocommerce_template_single_add_to_cart - 30
			 * @hooked woocommerce_template_single_meta - 40
			 * @hooked woocommerce_template_single_sharing - 50
			 * @hooked WC_Structured_Data::generate_product_data() - 60
			 */
			do_action( 'woocommerce_single_product_summary' );
		?>
	</div>
	
    <div class="fusion-clearfix"></div>

	<!-- Product page - Bought Togheter - PIE -->
	<div class="hr-skeleton" id="hr-recom-k667a7b2c75143e52a36f42f7">
		<div class="skeleton-cards">
            <div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>
            <div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>
            <div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>
            <div class="skeleton-card is-loading"><div class="image"></div><div class="content"><h2></h2><p></p></div></div>							
        </div>
	</div>

    <div class="fusion-clearfix"></div>

    <div class="shipping-payment-information-container">
		<div class="shipping-payment-content"> 
			<h3>Leveransvillkor</h3>
			<p>Fri frakt från 375.- och fria byten!</p> 
		</div>
		<div class="shipping-payment-content"> 
			<h3>Kundupplevelser</h3>
			<div class="shippment-payment-review">
				<a href="https://customerreviews.google.com/v/merchant?q=rehabgrossisten.com&c=SE&v=19&hl=sv" rel="nofollow" target="_blank">
					<img width="120" alt="Google Reviews" class="google-review-image" src="https://rehabgrossisten.com/wp-content/uploads/2022/10/google-reviews.png" />
				</a>
				<g:ratingbadge merchant_id=504635323></g:ratingbadge>
			</div>
		</div>
		<div class="shipping-payment-content"> 
			<h3>100% nöjd kundgaranti</h3>
			<p>Bytesrätt 45 dagar - Ångerrätt 14 dagar</p>
		</div>
    </div>

	<?php
		/**
		 * Hook: woocommerce_after_single_product_summary.
		 *
		 * @hooked woocommerce_output_product_data_tabs - 10
		 * @hooked woocommerce_upsell_display - 15
		 * @hooked woocommerce_output_related_products - 20
		 */
		do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
