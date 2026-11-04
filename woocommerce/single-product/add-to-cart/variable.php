<?php
/**
 * Variable product add to cart
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
    <?php do_action( 'woocommerce_before_variations_form' ); ?>

    <?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
        <p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
    <?php else : ?>

        <div class="variations" style="display: flex; flex-wrap: wrap;">

            <?php
            $img =                  get_field('storleksguide', get_queried_object());
            $storleksguide_text =   get_field('storleksguide_text', get_queried_object());
            $storleksguide_table =  get_field('storleksguide_table', get_queried_object());

            if( $img || $storleksguide_text || $storleksguide_table ) : ?>
                <div class="storleksguiden-container">
                    <a class="btn" href="#storleksguiden">
                        <div class="action-button light">Storleksguide</div>
                    </a>
                </div>
            <?php endif;
            
            $variations_arr = array();
            $counter = 0;

            foreach ( $attributes as $attribute_name => $options ) :
                ob_start(); ?>
                <div class="value" style="order: <?php echo $counter; ?>">
                    <?php
                    $selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) : $product->get_variation_default_attribute( $attribute_name );
                    wc_dropdown_variation_attribute_options( 
                        array(
                            'options'   => $options,
                            'attribute' => $attribute_name,
                            'product'   => $product,
                            'selected'  => $selected
                        ) 
                    );
                    // echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
                    ?>
                </div>

                <?php
                $variations_ob = ob_get_clean();
                $variations_arr[wc_attribute_label($attribute_name)] = $variations_ob;
                $counter++;
            endforeach;

            foreach ($variations_arr as $name => $ob) {
                echo str_ireplace('Välj ett alternativ', 'Välj '.strtolower($name), $ob );
            } ?>
        </div>
        
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

        <div class="single_variation_wrap">
            <?php
            /**
             * Hook: woocommerce_before_single_variation.
             */
            do_action( 'woocommerce_before_single_variation' );

            /**
             * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
             *
             * @since 2.4.0
             * @hooked woocommerce_single_variation - 10 Empty div for variation data.
             * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
             */            
            do_action( 'woocommerce_single_variation' );

            /**
             * Hook: woocommerce_after_single_variation.
             */
            do_action( 'woocommerce_after_single_variation' );
            ?>
        </div>
    <?php endif; ?>

    <?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
    /*
     * Check if any variation have bacckorder allowed or is in stock
     * else: show out of stock message
     */
    $out_of_stock = true;
    foreach($product->get_available_variations() as $variation) {

        if ($variation['backorders_allowed'] || $variation['is_in_stock']) {
            $out_of_stock = false;
            break;
        }
    }
?>

    <div class="product-purchase-info">
        <div class="delivery-time">
            <b>Leveranstid</b>:
            <?php if ($out_of_stock) : ?>
                <span class="backorder-not-allowed">Slut i lager</span>
            <?php else : ?>
                <span class="no-variation-choosen">Välj alternativ först</span>
            <?php endif; ?>
        </div>
        <div class="payment-methods" style="display:flex;justify-content:center;margin-top:20px;">
            <img style="max-width:300px;" src="/wp-content/themes/Avada-Child-Theme/assets/images/betalmetoder.png" width="300" alt="Betalmetoder" />
        </div>
    </div>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );