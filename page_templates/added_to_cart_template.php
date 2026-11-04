<?php
/* Template Name: AddedToCartTemplate */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

$product_id = (empty($_GET['variation_id'])) ? $_GET['product_id'] : $_GET['variation_id'];
$variation = (empty($_GET['variation_id'])) ? false : true;
$_product = wc_get_product( $product_id );
$item = null;
$cart = WC()->cart->get_cart();

// If no product, send to checkout or homepage
if ( ! $_product ) {
    if ( ! $cart )
    {
        wp_redirect('/');
        exit;
    }
    else
    {
        wp_redirect( wc_get_checkout_url() );
        exit;
    }
}

$item_found_in_cart = false;
foreach ( $cart as $cart_item ) {
    if  ( $cart_item['data']->get_id() == $product_id ) {
        $item = $cart_item;
        $item_found_in_cart = true;
        break;
    }
}

if ( ! $item_found_in_cart && $cart )
{
    wp_redirect( wc_get_checkout_url() );
    exit;
}

?>
<?php get_header(); ?>

<div class="added-product-container">

    <section class="checkout-breadcrumb">
        <nav>
            <ol class="cd-breadcrumb triangle custom-icons">
                <li class="current added-product"><span>Vald produkt</span></li>
                <li class="cart"><span>Varukorg & Kassa</span></li>
                <li class="summary"><span>Sammanfattning</span></li>
            </ol>
        </nav>
    </section>

    <?php if ( $item_found_in_cart ) : ?>

    <?php $continue_shopping_url = ( empty( get_permalink( $product_id ) ) ) ? get_home_url() . '/sortiment' : get_permalink( $product_id ); ?>

    <div class="go-to-checkout">
        <div class="woocommerce-notices-wrapper">
            <div class="woocommerce-message" role="alert">
                <span>Produkt har blivit tillagd i varukorgen. <a href="<?php echo $continue_shopping_url; ?>">Fortsätt handla</a>.</span>
            </div>
        </div>

        <div class="wc-proceed-to-checkout">
            <?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
        </div>
    </div>

    <div class="fusion-clearfix"></div>

    <?php 
        $total = WC()->cart->subtotal;
        $free_shipping = 375;

        if ($total < $free_shipping) :
            $remaining_for_free_shipping = $free_shipping - $total;
            $message = 'Du har <span class="free-shipping-amount">' . wc_price($remaining_for_free_shipping) . '</span> kvar till <b>fri frakt</b>.';
    ?>
        <div class="free-shipping-notice">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>

    <form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

        <ul class="shop_table shop_table_responsive cart woocommerce-cart-form__contents">
            <?php do_action( 'woocommerce_before_cart_contents' ); ?>

            <?php

            if ( $_product && $_product->exists() ) :
                    $product_permalink = $_product->get_permalink();
            ?>

                <li class="woocommerce-cart-form__cart-item cart_item">

                    <div class="product-thumbnail">
                        <?php
                        $thumbnail = $_product->get_image();

                        if ( ! $product_permalink ) {
                            echo $thumbnail;
                        } else {
                            printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
                        }
                        ?>
                    </div>

                    <div class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">

                        <?php
                        if ( ! $product_permalink ) {
                            echo wp_kses_post( $_product->get_title() . '&nbsp;');
                        } else {
                            echo wp_kses_post( sprintf( '<a class="product-title" href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() ) );
                        }
                        ?>

                    </div>

                    <?php

                    $attributes = $_product->get_attributes();
                    $size = '-';
                    $extra = '-';
                    $extra_headers = "Övrigt";
                    $counter = 0;

                    foreach($attributes as $key => $attribute) {
                        if ( $key == 'pa_storlek' || $key == 'pa_skostorlek' )
                            $size = $_product->get_attribute( $key );
                        elseif ( is_string($attribute) ) {
                            $value = $_product->get_attribute( $key );
                            $header_value = ucwords(substr($key, 3, strlen($key)));
                            if ($counter == 0) {
                                $extra = $value;
                                $extra_headers = (($header_value == 'Farg') ? "Färg" : ($header_value == 'Hojd' ? "Höjd" : $header_value));
                            } else {
                                $extra .= ", " . $value;
                                $extra_headers .= ", " . (($header_value == 'Farg') ? "Färg" : ($header_value == 'Hojd' ? "Höjd" : $header_value));
                            }
                            $counter++;
                        }
                    } ?>

                    <div class="product-options <?php echo ($size == "-" && $extra == "-") ? "empty-attributes" : ""; ?>">
                        <div class="product-size">
                            <small>Storlek</small>
                            <span><?php echo wp_kses_post($size); ?></span>
                        </div>

                        <div class="product-extra">
                            <small><?php echo $extra_headers; ?></small>
                            <span><?php echo $extra; ?></span>
                        </div>

                        <?php
                        $unit = "";
                        if ($variation) {
                            $parent = wc_get_product( $_product->get_parent_id() );
                            $unit = $parent->get_attribute('pa_enhet');
                        } else {
                            $unit = $_product->get_attribute('pa_enhet');
                        }
                        ?>

                        <div class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
                            <small>Antal <?php echo (!empty($unit)) ? '(' . strtolower($unit) . ')' : ''; ?></small>
                            <?php
                            if ( $_product->is_sold_individually() ) {
                                $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $item['key'] );
                            } else {
                                $product_quantity = woocommerce_quantity_input(
                                    array(
                                        'input_name'   => "cart[{$item['key']}][qty]",
                                        'input_value'  => $item['quantity'],
                                        'max_value'    => $_product->get_max_purchase_quantity(),
                                        'min_value'    => '0',
                                        'product_name' => $_product->get_name(),
                                    ),
                                    $_product,
                                    false
                                );                                
                            }

                            echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $item['key'], $item ); ?>
                            
                            <input type="hidden" class="stock-status" 
                                data-backorder="<?php echo $_product->backorders_allowed() ? 'true' : 'false'; ?>" 
                                data-stock="<?php echo $_product->get_stock_quantity(); ?>" />
                        </div>
                    </div>

                    <div class="product-subtotal" data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>">
                        <div class="price">
                            <small>Pris</small>
                            <?php
                            echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $item['quantity'] ), $item, $item['key'] );
                            ?>
                        </div>
                    </div>

                    <div class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
                        <div class="price">
                            <small>À pris</small>
                            <?php
                            echo $_product->get_price_html();
                            ?>
                        </div>
                    </div>

                    <div class="product-remove">
                        <?php
                        echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            'woocommerce_cart_item_remove_link',
                            sprintf(
                                '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><i class="fas fa-times-circle"></i></a>',
                                esc_url( wc_get_cart_remove_url( $item['key'] ) ),
                                esc_html__( 'Remove this item', 'woocommerce' ),
                                esc_attr( $product_id ),
                                esc_attr( $_product->get_sku() )
                            ),
                            $item['key']
                        );
                        ?>
                    </div>
                </li>

            <?php endif; ?>

            <div class="action-row">
                <div class="actions">
                    <button type="submit" class="button" name="update_cart"
                            value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>">
                        <?php esc_html_e( 'Update cart', 'woocommerce' ); ?>
                    </button>

                    <?php do_action( 'woocommerce_cart_actions' ); ?>
                    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                </div>
            </div>

            <?php do_action( 'woocommerce_after_cart_contents' ); ?>
        </ul>

        <?php do_action( 'woocommerce_after_cart_table' ); ?>
    </form>

    <?php else : ?>

    <div class="empty-cart">
        <div class="woocommerce-notices-wrapper">
            <div class="woocommerce-message" role="alert">Din varukorg är tom!</div>
        </div>
        <div class="continue-shopping">
            <a href="/"><span>Fortsätt handla</span></a>
        </div>
    </div>

    <?php endif; ?>

    <div class="fusion-clearfix"></div>

<?php

    $cross_sell_products = array();
    $number_of_products = 0;
    $category = 55; // Rehab och träning
    $exclude_category = 551; // Okategoriserad

    if ( !empty(WC()->cart->get_cross_sells()) ) {
        foreach(WC()->cart->get_cross_sells() as $item) {
            $_cross_sell_product = wc_get_product( $item );
            if ($_cross_sell_product && $_cross_sell_product->is_in_stock() && $number_of_products < 4) {
                array_push($cross_sell_products, $item);
                $number_of_products++;
            }
        }
    }

    if ($number_of_products < 4) {

        $exclude = array();
        array_push($exclude, 23432, 23433);
        foreach( WC()->cart->get_cart() as $item ) {
            array_push($exclude, $item['product_id'] );
        }

        $products = new WP_Query( array(
            'post_type'         => 'product',
            'post__not_in'      => $exclude,
            'post_status'       => 'publish',
            'orderby'           => 'rand',
            'order'             => 'ASC',
            'posts_per_page'    =>  4 - $number_of_products,
            'fields'            => 'ids',
            'tax_query'         => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category,
                ),
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $exclude_category,
                    'operator' => 'NOT IN',
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '=',
                ),
            )
        ) );

        $cross_sell_products = array_merge($cross_sell_products, $products->posts);
    }
    
    ?>

    <div class="cross-sell-products">
        <h2 class="cross-sell-title"><span>Behöver du något mer? Vi rekommenderar även</span></h2>
        <?php echo do_shortcode('[products ids="' . implode(",", $cross_sell_products) . '"]'); ?>
    </div>
</div>
<?php
get_footer();

/* Omit closing PHP tag to avoid "Headers already sent" issues. */

