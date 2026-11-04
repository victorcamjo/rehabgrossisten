<?php
/**
 * Single variation cart button
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>

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
    
    $product_expired = get_field('product_expired');
    $button_text = empty(get_field('button_text'))  ? "Produkt har utgått" : get_field('button_text');
    $button_link = empty(get_field('button_link'))  ? "" : get_field('button_link');
?>

<?php if ($product_expired) : ?>
    <div>
        <a href="<?php echo esc_url($button_link); ?>" class="product-expired-btn <?php echo empty($button_link) ? 'disabled' : ''; ?>"><?php echo esc_html($button_text); ?></a>
    </div>
<?php else : ?>

    <div class="woocommerce-variation-add-to-cart variations_button">
        <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

        <div class="notification-wrapper" style="display:none;">
            <a href="#" class="notification-wrapper-btn">Slut i lager - notifiera mig via e-post</a>
        </div>

        <?php if ($out_of_stock) : ?>

            <!--
            <a class="rg-add-to-cart button" style="display: none; width: 100%;" 
                href='mailto:info@rehabgrossisten.com?subject=Bevakning av <?php echo $product->get_title(); ?>&Body=Hej, vänligen kontakta mig när produkten "<?php echo $product->get_title(); ?>" finns i lager igen.%0D%0DAnge variant här (storlek, färg etc.)%0D'>
                Be om att bli kontaktad när produkten finns i lager.
            </a>

            <div class="notification-wrapper">
                <a href="#" class="notification-wrapper-btn">Slut i lager - notifiera mig via e-post</a>
            </div>
            -->

            <button disabled class="rg-add-to-cart disabled-button button">Välj variant - Notifiera mig via e-post</button>

        <?php else : ?>

            <button type="submit" class="single_add_to_cart_button button alt">
                <?php echo esc_html( $product->single_add_to_cart_text() ); ?>
            </button>

        <?php endif; ?>

        <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

        <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
        <input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
        <input type="hidden" name="variation_id" class="variation_id" value="0" />
    </div>
<?php endif; ?>