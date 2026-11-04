<?php

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

$product_id = $_POST['add-to-cart'];
$_product = wc_get_product( $product_id );
$stock = 0;

if ( !empty($_POST['variation_id']) ) {
    // Get variation id
    $variation_id = $_POST['variation_id'];

    // Get variation object
    $variation_obj = new WC_Product_variation($variation_id);

    // Get product object
    $_product = wc_get_product( $variation_id );

    // Get product title
    $product_title = $_product->get_name();

    // If variation object's not empty - get stock
    if ($variation_obj) {
        $stock = $variation_obj->get_stock_quantity();
    }

    // Get variant price
    $price = $_product->get_price_html();
} else {
    if (!empty($_product)) {
        $stock = $_product->get_stock_quantity();
        $product_title = $_product->get_title();
        $price = $_product->get_price_html();
    }
}

$thumbnail_id = ( $_POST['variation_id'] && has_post_thumbnail( $_POST['variation_id'] ) ) ? $_POST['variation_id'] : $product_id;

if ($stock >= 1) {
    $duration = "1-3";
} else {
    $duration = "7-10";
}

$item = null;
foreach ( WC()->cart->get_cart() as $cart_item ) {
    if ($cart_item['data']->get_id() == $product_id || $cart_item['data']->get_id() == $variation_id) {
        $item = $cart_item;
        break;
    }
}
?>

<div class="fusion-modal modal fade modal-1 added_to_cart_popup" tabindex="-1" role="dialog" aria-labelledby="modal-heading-1" aria-hidden="false">
    <style type="text/css">.modal-1 .modal-header, .modal-1 .modal-footer{border-color:#ebebeb;}</style>
    <div class="modal-dialog modal-md">
        <div class="modal-content fusion-modal-content" style="background-color:#f6f6f6">
            <div class="modal-header">
                <button class="close" type="button" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 class="modal-title" id="modal-heading-1" data-dismiss="modal" aria-hidden="true">Produkt tillagd i varukorgen</h3>
            </div>
            <div class="modal-body">
                <div class="product-container">
                    <div class="product-image">
                        <?php echo get_the_post_thumbnail( $thumbnail_id, 'thumbnail' ); ?>
                    </div>
                    <div class="popup-product-information">
                        <h3><?php echo $product_title; ?></h3>
                        <div class="details">
                            <p><b>Pris: </b><?php echo $price; ?></p>
                            <p><b>Leveranstid:</b> <?php echo $duration; ?> dagar</p>
                        </div>

                        <div class="quantity">
                            <form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
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

                            echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $item['key'], $item );
                            ?>

                                <button style="display: none;" type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="button-container">
                    <a class="continue-shopping" href="#">
                        <div class="action-button light">Fortsätt handla</div>
                    </a>
                    <a class="cart-link" href="<?php echo wc_get_page_permalink( 'checkout' ); ?>">
                        <div class="action-button main alt-button">Till kassan</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop fade in"></div>