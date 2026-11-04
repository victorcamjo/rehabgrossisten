<?php
/**
 * Klarna Checkout page
 *
 * Overrides /checkout/klarna-cart-checkout.php.
 *
 * @package klarna-checkout-for-woocommerce
 */

// wc_print_notices();

// do_action( 'woocommerce_before_checkout_form', WC()->checkout() );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$settings = get_option( 'woocommerce_kco_settings' );
?>

<form class="woocommerce-form-coupon" method="post">

    <?php if ( wc_coupons_enabled() ) { ?>
        <div class="coupon" style="padding: 0; border:0; height: 70px;">
            <div class="coupon-text">
                <p><b>Har du en rabattkod?</b></p>
                <p><small>Rehabgrossistens rabattkoder ger dig som lojal kund förmånliga erbjudanden.</small></p>
            </div>
            <div class="coupon-fields">
                <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
                <input type="submit" class="button" name="apply_coupon" value="Tillämpa" />
            </div>
        </div>
    <?php } ?>

    <div class="clear"></div>
</form>

<form name="checkout" class="checkout woocommerce-checkout">
<?php do_action( 'kco_wc_before_wrapper' ); ?>
	<div id="kco-wrapper">
		<div id="kco-order-review">
			<?php do_action( 'kco_wc_before_order_review' ); ?>
			<?php
			if ( ! isset( $settings['show_subtotal_detail'] ) || in_array( $settings['show_subtotal_detail'], array( 'woo', 'both' ), true ) ) {
				woocommerce_order_review();
			}
			?>
			<?php do_action( 'kco_wc_after_order_review' ); ?>
		</div>
		<div id="kco-iframe">
			<?php do_action( 'kco_wc_before_snippet' ); ?>
			<?php kco_wc_show_snippet(); ?>
			<?php do_action( 'kco_wc_after_snippet' ); ?>
		</div>
	</div>
	<?php do_action( 'kco_wc_after_wrapper' ); ?>
</form>

<?php do_action( 'kco_wc_after_checkout_form' ); ?>

<!--<p class="checkout-additional-information">
	* Adress för upphämtning i lager: Tumstocksvägen 11B, Täby<br>
	** Paket som ej får plats i brevlåda hängs på dörren eller placeras vid ingång.
</p>-->
