<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="shop_table woocommerce-checkout-review-order-table" style="visibility: hidden;">
    
<?php 
$total = WC()->cart->subtotal;
$free_shipping = 375;

if ($total < $free_shipping) :
    $remaining_for_free_shipping = $free_shipping - $total;
    $remaining_percentage = ($total / $free_shipping) * 100;
    $message = 'Du har <span class="free-shipping-amount">' . wc_price($remaining_for_free_shipping) . '</span> kvar till <b>fri frakt</b>.';
?>
<div class="free-shipping-notice">
    <div class="percentage-bar-wrapper">
        <div class="percentage-bar" style="width: <?php echo $remaining_percentage; ?>%"></div>
    </div>
    <p><?php echo $message; ?></p>
</div>
<?php endif; ?>

    <ul>
    <?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
    <?php // do_action( 'woocommerce_before_cart_contents' ); ?>

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
            {
                $size = $_product->get_attribute( $key );
            }
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
    </ul>

    <?php do_action( 'woocommerce_after_cart_contents' ); ?>
    
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

        <div class="cart_totals">
            <div class="cart-summary-container">
                <div class="cart-summary">
                    <div class="number-products">
                        <?php echo WC()->cart->cart_contents_count; ?> <?php echo (WC()->cart->cart_contents_count > 1) ? 'artiklar' : 'artikel'; ?>
                        <span class="delivery-status">Leveranstid: <?php echo ($out_of_stock) ? '7-10 arbetsdagar' : '1-3 arbetsdagar'; ?></span>
                    </div>
                    <table>
                        <tr class="cart-subtotal">
                            <th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
                            <td><?php wc_cart_totals_subtotal_html(); ?></td>
                        </tr>

                        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
                            <tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                                <th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
                                <td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

                            <?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

                            <?php wc_cart_totals_shipping_html(); ?>

                            <?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

                        <?php endif; ?>

                        <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
                            <tr class="fee">
                                <th><?php echo esc_html( $fee->name ); ?></th>
                                <td><?php wc_cart_totals_fee_html( $fee ); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
                            <?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
                                <?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
                                    <tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                                        <th><?php echo esc_html( $tax->label ); ?></th>
                                        <td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="tax-total">
                                    <th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
                                    <td><?php wc_cart_totals_taxes_total_html(); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

                        <tr class="order-total">
                            <th><?php esc_html_e( 'Total', 'woocommerce' ); ?> inkl. moms</th>
                            <td><?php wc_cart_totals_order_total_html(); ?></td>
                        </tr>

                        <?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>