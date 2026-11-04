<?php
/**
 * Thankyou page
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.7.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

    <?php if ( $order ) :

        do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

        <?php // ThemeFusion edit for Avada theme: add Avada specific classes avada-thank-you woocommerce-content-box full-width. ?>
        <div class="woocommerce-order avada-thank-you woocommerce-content-box full-width">

            <?php if ( $order->has_status( 'failed' ) ) : ?>

                <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

                <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
                    <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
                    <?php endif; ?>
                </p>

            <?php else : ?>

                <?php $order_details = $order->get_order_item_totals(); ?>

                <div class="order-details-container">
                    <?php // ThemeFusion edit for Avada theme: change to h2 tag ?>

                    <div class="order-details-title">
                        <h2><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
                    </div>

                    <div class="order-details">
                        <div class="woocommerce-order-overview__order order order-detail-item">
                            <i class="fas fa-hashtag"></i>
                            <strong>Order <?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                        </div>

                        <div class="woocommerce-order-overview__date date order-detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                        </div>

                        <div class="woocommerce-order-overview__shipping shipping order-detail-item">
                            <i class="fas fa-truck"></i>
                            <strong><?php echo $order_details['shipping']['value']; ?><?php echo ($order_details['shipping']['value'] == 'Hämta lager') ? ' i Täby' : '';?></strong>
                        </div>

                        <div class="woocommerce-order-overview__total total order-detail-item">
                            <i class="fas fa-receipt"></i>
                            <strong><?php echo $order_details['order_total']['value']; ?></strong>
                        </div>

                        <?php if( $order_details['shipping']['value'] == 'Hämta lager' ) : ?>
                            <div class="shipping-information">
                                <small>Hämta din produkt hos oss på Tumstocksvägen 11 A i Täby, Stockholm. Vi skickar ett SMS till dig när produkterna är
                                    framtagna och redo att hämtas. Varuutlämningen har öppet mån-tors kl. 10-16, fre 10-15.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="clear"></div>

            <?php endif; ?>

            <?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>

        </div>
        <?php 
            do_action( 'woocommerce_thankyou', $order->get_id() );
            $estimated_delivery_date = date('Y-m-d'); // strtotime('+3 days')
        ?>

        <script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>

        <script>
        window.renderOptIn = function() {
            window.gapi.load('surveyoptin', function() {
                window.gapi.surveyoptin.render(
                    {
                    // REQUIRED FIELDS
                    "merchant_id": 134527266,
                    "order_id": "<?php echo $order->get_order_number(); ?>",
                    "email": "<?php echo $order->get_billing_email(); ?>",
                    "delivery_country": "<?php echo $order->get_billing_country(); ?>",
                    "estimated_delivery_date": "<?php echo $estimated_delivery_date; ?>",
                });
            });
        }
        </script>

        <?php
        // Special handling for conversions of products with category "Hallufix"
        $items = $order->get_items(); 

        $hallufix_sum = 0;
        $standard_sum = 0;

        foreach ( $items as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );

            // Hämta produktattributet 'Varumärke' för produkten
            $brand = $product->get_attribute('pa_varumarke');

            // Kontrollera om attributet 'Varumärke' är 'Hallufix'
            if ( $brand == 'Hallufix' ) {
                $hallufix_sum += $product->get_price_excluding_tax() * $item->get_quantity();
            } else {
                $standard_sum += $product->get_price_excluding_tax() * $item->get_quantity();
            }
        }

        // Lägg till fraktpriset exklusive moms till $standard_sum
        $shipping_total = floatval($order->get_shipping_total());
        $standard_sum += $shipping_total;

        $hallufix_sum = number_format($hallufix_sum, 2, ".", "");
        $standard_sum = number_format($standard_sum, 2, ".", "");

        // Skapa dataLayer utdata
        $dataLayer_output = array(
            array(
                'category' => 'standard',
                'sum'      => $standard_sum
            ),
            array(
                'category' => 'hallufix',
                'sum'      => $hallufix_sum
            )
        );

        echo "<script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'orderComment': " . json_encode($dataLayer_output) . "
            });
        </script>";
        ?>

    <?php else : ?>

    <?php // ThemeFusion edit for Avada theme: change to h2 tag ?>
    <h2><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
</div>

<?php endif; ?>

<div class="print-container">
    <div class="print-order">
        <i class="fa fa-print"></i>
        <span>Skriv ut order</span>
    </div>
</div>

</div>