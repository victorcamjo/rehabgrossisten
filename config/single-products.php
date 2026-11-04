<?php
/**
 * Created by Victor Bishti.
 * User: Victor Bishti
 * Date: 2019-12-20
 * Time: 18:47
 */

add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );

/**
 *
 * Change number of related products on product page
 * Set your own value for 'posts_per_page'
 *
 */
function output_related_products() {
    global $post;

    $args = [
        'posts_per_page' => -1,
        'columns'        => 4,
        'orderby'        => 'rand',
    ];

    echo '<div class="fusion-clearfix"></div>';
    woocommerce_related_products( apply_filters( 'woocommerce_output_related_products_args', $args ) );
}

function ajax_qty_cart() {

    if ( ! isset( $_POST['hash'], $_POST['quantity'] ) ) {
        wp_send_json_error( 'Invalid request: missing parameters.' );
        return;
    }

    $cart_item_key = sanitize_text_field( $_POST['hash'] );
    $quantity = intval( $_POST['quantity'] );

    if ( $quantity <= 0 ) {
        wp_send_json_error( 'Invalid quantity: must be greater than zero.' );
        return;
    }

    // Get the array of values owned by the product we're updating
    // $threeball_product_values = WC()->cart->get_cart_item( $cart_item_key );
    $cart_item = WC()->cart->get_cart_item( $cart_item_key );

    $passed_validation = apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $cart_item, $quantity );

    // Get the quantity of the item in the cart
    // $threeball_product_quantity = apply_filters( 'woocommerce_stock_amount_cart_item', apply_filters( 'woocommerce_stock_amount', preg_replace( "/[^0-9\.]/", '', filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT)) ), $cart_item_key );
    // Update cart validation
    // $passed_validation  = apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $threeball_product_values, $threeball_product_quantity );

    // Update the quantity of the item in the cart
    // if ( $passed_validation ) {
    //     WC()->cart->set_quantity( $cart_item_key, $threeball_product_quantity, true );
    // }

    if ( $passed_validation ) {
        try {
            WC()->cart->set_quantity( $cart_item_key, $quantity, true );
            wp_send_json_success( 'Cart updated successfully.' );
        } catch ( Exception $e ) {
            wp_send_json_error( 'Error updating cart: ' . $e->getMessage() );
        }
    } else {
        wp_send_json_error( 'Validation failed: could not update cart.' );
    }
}
add_action('wp_ajax_qty_cart', 'ajax_qty_cart');
add_action('wp_ajax_nopriv_qty_cart', 'ajax_qty_cart');


/*
 * === Get Variation Status ===
 *  AJAX-call
 */
add_action( 'wp_ajax_nopriv_get_variation_status', 'get_variation_status' );
add_action( 'wp_ajax_get_variation_status', 'get_variation_status' );

/*
 * Get Stock Status of Product with Attributes
 * Variations Status
 */
function get_variation_status( $variation_id = false, $product_id = false ) {

    if ( !$variation_id || !$product_id ) {
        if ( isset($_POST['variation_id']) && isset($_POST['product_id']) ) {
            $variation_id = $_POST['variation_id'];
            $product_id = $_POST['product_id'];
        } else {
            return;
        }
    }
    
    $product = wc_get_product( $product_id );
    $variation_obj = new WC_Product_variation($variation_id);
    $stock = $variation_obj->get_stock_quantity();
    $price = $variation_obj->get_price_html();
    $out_of_stock = false;
    ob_start();
    ?>

    <b>Leveranstid: </b>

    <?php if ($stock >= 1) : ?>
        <span class="variation-stock in-stock" id="variation_<?php echo $variation_id; ?>">
            <span class="time">1-3 arbetsdagar</span>
            <i class="fa fa-check-circle"></i>
        </span>
    <?php else : ?>
        <span class="variation-stock out-of-stock" id="variation_<?php echo $variation_id; ?>">

            <?php if ($variation_obj->backorders_allowed()) : ?>
                <span class="time">7-10 arbetsdagar</span>

                <div class="rg-tooltip">
                    <i class="fa fa-info-circle"></i>
                    <span class="rg-tooltiptext">
                        <h3>Leveranstid</h3>
                        Vald artikel är slut på lagret men vi beräknar att kunna leverera den till dig om ca. 7-10 arbetsdagar.
                        Kontakta vår kundsupport på 08-410 248 80 om du har några frågor.
                    </span>
                </div>

            <?php else : ?>
                <?php $out_of_stock = true; ?>
                <span class="backorder-not-allowed">Slut i lager</span>
            <?php endif; ?>
        </span>

    <?php endif;

    $response = (object) [
        'price'         => $price,
        'stock'         => ob_get_clean(),
        'out_of_stock'  => $out_of_stock,
        'product_name'  => $variation_obj->get_title(),
        'product_sku'   => $variation_obj->get_sku()
    ];

    wp_send_json($response);
    wp_die();
}

/* Add To Cart Redirection */
// add_filter( 'woocommerce_add_to_cart_redirect', 'custom_add_to_cart_message' );
function custom_add_to_cart_message() {

    if (is_checkout()) {
        return false;
    }

    /*
    if (isset($_POST['add-to-cart']))
    {
        $product_id = $_POST['add-to-cart'];
    }

    if (isset($_GET['add-to-cart']))
    {
        $product_id = $_GET['add-to-cart'];
    }

    if (empty($product_id))
    {
        return get_home_url();
    }

    $variation = ( !empty($_POST['variation_id']) ) ? '&variation_id=' . $_POST['variation_id'] : '';

    $url = get_home_url() . '/tillagd-produkt?product_id=' . $product_id . $variation;
    return $url;
    */
}

// Get primary category
function get_post_primary_category($post_id, $term='category', $return_all_categories=false){
    $return = array();

    if (class_exists('WPSEO_Primary_Term')){
        // Show Primary category by Yoast if it is enabled & set
        $wpseo_primary_term = new WPSEO_Primary_Term( $term, $post_id );
        $primary_term = get_term($wpseo_primary_term->get_primary_term());

        if (!is_wp_error($primary_term)){
            $return['primary_category'] = $primary_term;
        }
    }

    if (empty($return['primary_category']) || $return_all_categories){
        $categories_list = get_the_terms($post_id, $term);

        if (empty($return['primary_category']) && !empty($categories_list)){
            $return['primary_category'] = $categories_list[0];  //get the first category
        }
        if ($return_all_categories){
            $return['all_categories'] = array();

            if (!empty($categories_list)){
                foreach($categories_list as &$category){
                    $return['all_categories'][] = $category->term_id;
                }
            }
        }
    }

    return $return;
}

function rg_upsell_display(){
    global $product, $post;

    echo '<div class="fusion-clearfix"></div>';

    woocommerce_upsell_display( - 1, 4 );
}


function custom_get_gallery_image_html( $attachment_id, $main_image = false ) {
    $flexslider        = (bool) apply_filters( 'woocommerce_single_product_flexslider_enabled', get_theme_support( 'wc-product-gallery-slider' ) );
    $gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
    $thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
    $image_size        = apply_filters( 'woocommerce_gallery_image_size', $flexslider || $main_image ? 'woocommerce_single' : $thumbnail_size );
    $full_size         = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
    $thumbnail_src     = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
    $full_src          = wp_get_attachment_image_src( $attachment_id, $full_size );
    $alt_text          = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
    $image             = wp_get_attachment_image(
      $attachment_id,
      $image_size,
      false,
      apply_filters(
        'woocommerce_gallery_image_html_attachment_image_params',
        array(
          'title'                   => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
          'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
          'data-src'                => esc_url( $full_src[0] ),
          'data-large_image'        => esc_url( $full_src[0] ),
          'data-large_image_width'  => esc_attr( $full_src[1] ),
          'data-large_image_height' => esc_attr( $full_src[2] ),
          'class'                   => esc_attr( $main_image ? 'wp-post-image disable-lazyload' : '' ),
        ),
        $attachment_id,
        $image_size,
        $main_image
      )
    );
  
    return '<div data-thumb="' . esc_url( $thumbnail_src[0] ) . '" data-thumb-alt="' . esc_attr( $alt_text ) . '" class="woocommerce-product-gallery__image">' . $image . '</div>';
    
    // <a href="' . esc_url( $full_src[0] ) . '">' . $image . '</a>
}


add_filter("wpseo_breadcrumb_single_link", "remove_shop", 10 ,2);
function remove_shop($link_output, $link ){
    if( $link["text"] == "Sortiment" ) {
        $link_output = "";
    }
    return $link_output;
}

add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_custom_product_add_to_cart_text', 10, 2 );
function woocommerce_custom_product_add_to_cart_text( $button_text, $product ) {

    $is_in_stock = $product->is_purchasable() && $product->is_in_stock();

    $button_text = $is_in_stock ? __('Köp', 'woocommerce') : 'Slut i lager';

    return $button_text;
}

add_action('fusion_woocommerce_after_shop_loop_item', 'carousel_add_to_cart_button');

function carousel_add_to_cart_button($args) {
	global $product;

	if ( $product ) {
		$defaults = array(
			'quantity'   => 1,
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						wc_wp_theme_get_element_class_name( 'button' ), // escaped in the template.
						'product_type_' . $product->get_type(),
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : 'out_of_stock_button',
						$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
					)
				)
			),
			'attributes' => array(
				'data-product_id'  => $product->get_id(),
				'data-product_sku' => $product->get_sku(),
				'aria-label'       => $product->add_to_cart_description(),
				'rel'              => 'nofollow',
			),
		);
		

        $args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );

		if ( isset( $args['attributes']['aria-label'] ) ) {
			$args['attributes']['aria-label'] = wp_strip_all_tags( $args['attributes']['aria-label'] );
		}

		wc_get_template( 'loop/add-to-cart.php', $args );
	}
}

function filter_woocommerce_get_price_html( $price, $product ) {
    global $woocommerce_loop;

    if ( is_product() && $woocommerce_loop['name'] !== 'related' && $woocommerce_loop['name'] !== 'up-sells'  ) {
        $price .= ' <small> inkl. moms</small>';
    }
    
    return apply_filters( 'woocommerce_get_price', $price );
}
add_filter( 'woocommerce_get_price_html', 'filter_woocommerce_get_price_html', 10, 2 );

function single_product_brand() {
    global $product;

    $brand_terms = wc_get_product_terms( $product->get_id(), 'pa_varumarke', array( 'fields' => 'all' ) );

    if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
        $brand = $brand_terms[0];
        $brand_name = $brand->name;
        $brand_link = get_term_link( $brand );

        if ( ! is_wp_error( $brand_link ) ) {
            echo '<div class="brand"><a href="' . esc_url( $brand_link ) . '">' . esc_html( $brand_name ) . '</a></div>';
        }
    }
}
add_action( 'woocommerce_single_product_summary', 'single_product_brand', 6 );
