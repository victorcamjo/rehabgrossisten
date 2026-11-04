<?php
/**
 * Simple product add to cart
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

do_action( 'woocommerce_before_add_to_cart_form' );

// echo wc_get_stock_html( $product );

if ( $product->is_in_stock() ) : ?>

    <form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
        <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

        <?php
        /*
        do_action( 'woocommerce_before_add_to_cart_quantity' );

        woocommerce_quantity_input( array(
            'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
            'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
            'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
        ) );

        do_action( 'woocommerce_after_add_to_cart_quantity' );
        */


        ?>

        <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

        <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
    </form>

    <div class="product-purchase-info">

        <div class="delivery-time"><b>Leveranstid</b>:
            <?php if ($product->get_stock_quantity() >= 1) : ?>
            <span class="in-stock">
                <span class="time">1-3 arbetsdagar</span> <i class="fa fa-check-circle"></i>
            </span>
            <?php else : ?>
            <span class="out-of-stock">
                <span class="time">7-10 arbetsdagar</span>
                <div class="rg-tooltip">
                    <i class="fa fa-info-circle"></i>
                    <span class="rg-tooltiptext">
                        <h3>Leveranstid</h3>
                        Vald artikel är slut på lagret men vi beräknar att kunna leverera den till dig om ca. 7-10 arbetsdagar.
                        Kontakta vår kundsupport på 08-410 248 80 om du har några frågor.
                    </span>
                </div>
            </span>
            <?php endif; ?>
        </div>

        <div class="payment-methods" style="display:flex;justify-content:center;margin-top:20px;">
            <img style="max-width:300px;" src="/wp-content/themes/Avada-Child-Theme/assets/images/betalmetoder.png" width="300" alt="Betalmetoder" />
        </div>

    </div>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php else : 
        $product_expired = get_field('product_expired');
        $button_text = empty(get_field('button_text'))  ? "Produkt har utgått" : get_field('button_text');
        $button_link = empty(get_field('button_link'))  ? "" : get_field('button_link');
        ?>

    <?php if ($product_expired) : ?>
        <div class="notification-wrapper">
            <a href="<?php echo esc_url($button_link); ?>" class="product-expired-btn <?php echo empty($button_link) ? 'disabled' : ''; ?>"><?php echo esc_html($button_text); ?></a>
        </div>
    <?php else : ?>
        <div class="notification-wrapper">
            <a href="#" class="notification-wrapper-btn">Slut i lager - notifiera mig via e-post</a>
        </div>
        <a style="display:none;" class="product-request-link" href='mailto:info@rehabgrossisten.com?subject=Bevakning av <?php echo $product->get_title(); ?>&Body=Hej, vänligen kontakta mig när produkten "<?php echo $product->get_title(); ?>" finns i lager igen.%0D%0DSKU: <?php echo $product->get_sku(); ?>%0D'>Be om att bli kontaktad när produkten finns i lager.</a>
    <?php endif; ?>
<?php endif; ?>
