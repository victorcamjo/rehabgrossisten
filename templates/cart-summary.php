<?php
/**
 * Created by PhpStorm.
 * User: Victor El Bishti
 * Date: 2018-03-12
 * Time: 13:08
 */

$discount = 0;
foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
    $discount += WC()->cart->get_coupon_discount_amount( $code, WC()->cart->display_cart_ex_tax );
}

console_log(WC()->cart->get_coupons());

?>

<div class="cart-summary-container">

    <h3>Sammanfattning</h3>

    <div class="cart-summary">

        <div class="number-products"><?php echo WC()->cart->cart_contents_count; ?> <?php echo (WC()->cart->cart_contents_count > 1) ? 'artiklar' : 'artikel'; ?></div>
        <table>
            <tr>
                <td>Pris</td>
                <td><?php echo WC()->cart->get_cart_subtotal(); ?></td>
            </tr>
            <tr>
                <td>Rabatt</td>
                <td>-<?php echo wc_price($discount);?></td>
            </tr>
            <!--
            <tr class="border-on-top">
                <td>Frakt</td>
                <td><?php // echo WC()->cart->get_cart_shipping_total(); ?></td>
            </tr>
            -->
            <tr class="border-on-top totals">
                <td style="font-weight: 900">Summa SEK:</td>
                <td style="font-weight: 900"><?php echo WC()->cart->get_total(); ?></td>
            </tr>
            <tr class="tax-row">
                <td>varav moms:</td>
                <td><?php echo wc_price(WC()->cart->get_taxes_total());?></td>
            </tr>
        </table>

    </div>

    <div class="checkout-details">

        <ul>
            <li><i class="fa fa-credit-card" aria-hidden="true"></i> Betala säkert med Klarna</li>
            <li><i class="fa fa-truck" aria-hidden="true"></i> Snabb leverans från lager i Sverige</li>
            <li><i class="fa fa-envelope-o" aria-hidden="true"></i> Frågor? Maila oss: <a href="mailto:info@rehabgrossisten.com">info@rehabgrossisten.com</a></li>
        </ul>

    </div>

</div>