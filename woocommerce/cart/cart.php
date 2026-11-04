<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */


defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

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

    <!--
    <?php if ( wc_coupons_enabled() ) { ?>
        <div class="coupon">
            <div class="coupon-text">
                <p><b>Har du en rabattkod?</b></p>
                <p><small>Rehabgrossistens rabattkoder ger dig som lojal kund förmånliga erbjudanden.</small></p>
            </div>
            <div class="coupon-fields">
                <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
                <input type="submit" class="button" name="apply_coupon" value="Tillämpa" />
                <?php do_action( 'woocommerce_cart_coupon' ); ?>
            </div>
        </div>
    <?php } ?>
    -->

    <div class="woocommerce-content-box full-width clearfix">

    <ul class="shop_table shop_table_responsive cart woocommerce-cart-form__contents">
        <?php do_action( 'woocommerce_before_cart_contents' ); ?>

        <?php
        $out_of_stock = false;

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( $out_of_stock || $_product->get_stock_quantity() < $cart_item['quantity'] ) {
                $out_of_stock = true;
            }

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) :
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
        ?>

        <li class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

            <div class="product-thumbnail">
                <?php
                $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

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
                    echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . '&nbsp;');
                } else {
                    echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() ), $cart_item, $cart_item_key ) );
                }

                do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

                // Meta data.
                echo wc_get_formatted_cart_item_data( $cart_item ); // PHPCS: XSS ok.
                ?>

                <?php
                // Backorder notification.
                if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
                    echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
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
                    if ($_product->get_type() == 'variation' ) {
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
                        $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
                    } else {
                        $product_quantity = woocommerce_quantity_input(
                            array(
                                'input_name'    => "cart[{$cart_item_key}][qty]",
                                'input_value'   => $cart_item['quantity'],
                                'max_value'     => $_product->get_max_purchase_quantity(),
                                'min_value'     => '0',
                                'product_name'  => $_product->get_name(),
                            ),
                            $_product,
                            false
                        );
                    }

                    echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
                    ?>
                </div>
            </div>

            <div class="product-subtotal" data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>">
                <div class="price">
                    <small>Pris</small>
                    <?php
                    echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
                    ?>
                </div>
            </div>

            <div class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
                <div class="price">
                    <small>À pris</small>
                    <?php
                    if ( ! empty( $cart_item['rg_bulk_original_price'] ) ) {
                        echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
                    } else {
                        echo $_product->get_price_html();
                    }
                    ?>
                </div>
            </div>

            <div class="product-remove">
                <?php
                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'woocommerce_cart_item_remove_link',
                    sprintf(
                        '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><i class="fas fa-times-circle"></i></a>',
                        esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                        esc_html__( 'Remove this item', 'woocommerce' ),
                        esc_attr( $product_id ),
                        esc_attr( $_product->get_sku() )
                    ),
                    $cart_item_key
                );
                ?>
            </div>
        </li>

        <?php endif; ?>
        <?php endforeach; ?>

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

<div class="fusion-clearfix"></div>

<div class="order-information-container">
    <div class="order-information">
        <div class="order-information-item">
            <i class="fas fa-truck"></i>
            <span>Alltid fri frakt på beställningar från 375.-</span>
        </div>
        <div class="order-information-item">
            <i class="fas fa-retweet"></i>
            <span>Bytesrätt 45 dagar, ångerrätt 14 dagar.</span>
        </div>
    </div>

    <div class="cart_totals <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

        <?php do_action( 'woocommerce_before_cart_totals' ); ?>

        <div class="cart-summary-container cart">
            <div class="cart-summary">
                <div class="number-products">
                    <?php echo WC()->cart->cart_contents_count; ?> <?php echo (WC()->cart->cart_contents_count > 1) ? 'artiklar' : 'artikel'; ?>
                    <span class="delivery-status">Leveranstid: <?php echo ($out_of_stock) ? '7-10 arbetsdagar' : '1-3 arbetsdagar'; ?></span>
                </div>
                <table>
                    <tr>
                        <td>Pris</td>
                        <td><?php echo WC()->cart->get_cart_subtotal(); ?></td>
                    </tr>
                    <!--
                    <tr>
                        <td>Frakt</td>
                        <?php
                            $shipping = WC()->cart->get_cart_shipping_total();
                            if ($shipping == "Free!" || $shipping == "Gratis!"){
                                $shipping = "0 kr";
                            }
                        ?>
                        <td class="shipping-cost"><?php echo $shipping; ?></td>
                    </tr>
                    -->
                    <?php if( !empty( WC()->cart->get_coupons() ) ) : ?>
                        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
                            <tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                                <td><?php wc_cart_totals_coupon_label( $coupon ); ?></td>
                                <td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>">
                                    <?php
                                    $discount = WC()->cart->get_coupon_discount_amount( $code, false );
                                    $total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() + WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total();
                                    $percantage = ($discount / $total) * 100;
                                    echo "(" . number_format((float)$percantage, 2, ',', '') . "%)  ";
                                    ?>
                                    <?php wc_cart_totals_coupon_html( $coupon ); ?>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                    <?php if ( function_exists( 'rg_render_bulk_discount_row' ) ) rg_render_bulk_discount_row(); ?>
                    <tr class="border-on-top totals">
                        <td style="font-weight: 900">Summa SEK:</td>
                        <td class="total-inc-tax" style="font-weight: 900"><?php echo WC()->cart->get_total(); ?></td>
                    </tr>
                    <tr class="tax-row">
                        <td>varav moms:</td>
                        <td class="total-tax"><?php echo wc_price(WC()->cart->get_taxes_total());?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php do_action( 'woocommerce_after_cart_totals' ); ?>

    </div>
</div>

<div class="fusion-clearfix"></div>

<div class="wc-proceed-to-checkout" style="float: right;">
    <a href="<?php echo esc_url( wc_get_checkout_url() );?>" class="checkout-button button alt wc-forward">
        <div class="checkout-btn">
            <?php esc_html_e( 'Proceed to checkout', 'woocommerce' ); ?>
        </div>
    </a>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
