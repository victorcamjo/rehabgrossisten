<?php
/**
 * Bulk discount feature.
 *
 * - Adds an ACF field group on each product allowing the shop to enable
 *   bulk-discount tiers (minimum quantity + discount percentage).
 * - Applies the tier discount at cart-item level when the cart's quantity
 *   for the product reaches the configured threshold (variations of the
 *   same parent product are summed together).
 * - Renders the tier "buttons" above the add-to-cart button on single
 *   product pages, matching the existing .action-button.light look.
 * - Shows the original price as strike-through next to the discounted
 *   price in cart and checkout, and prints a "Mängdrabatt" summary row
 *   with the total saved.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * 1. ACF field registration
 * ---------------------------------------------------------------------- */

add_action( 'acf/init', 'rg_register_bulk_discount_fields' );
function rg_register_bulk_discount_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key'      => 'group_rg_bulk_discount',
        'title'    => 'Mängdrabatt',
        'fields'   => array(
            array(
                'key'           => 'field_rg_bulk_discount_enabled',
                'label'         => 'Aktivera mängdrabatt',
                'name'          => 'bulk_discount_enabled',
                'type'          => 'true_false',
                'instructions'  => 'Aktivera för att ge rabatt vid köp av flera enheter av denna produkt.',
                'default_value' => 0,
                'ui'            => 1,
                'ui_on_text'    => 'Aktiv',
                'ui_off_text'   => 'Av',
            ),
            array(
                'key'               => 'field_rg_bulk_discount_tiers',
                'label'             => 'Rabattnivåer',
                'name'              => 'bulk_discount_tiers',
                'type'              => 'repeater',
                'instructions'      => 'Lägg till en rad per nivå. Den högsta nivån vars antal uppfylls används.',
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_rg_bulk_discount_enabled',
                            'operator' => '==',
                            'value'    => '1',
                        ),
                    ),
                ),
                'min'          => 0,
                'layout'       => 'table',
                'button_label' => 'Lägg till nivå',
                'sub_fields'   => array(
                    array(
                        'key'           => 'field_rg_bulk_min_quantity',
                        'label'         => 'Antal från',
                        'name'          => 'min_quantity',
                        'type'          => 'number',
                        'min'           => 2,
                        'step'          => 1,
                        'required'      => 1,
                    ),
                    array(
                        'key'           => 'field_rg_bulk_discount_percentage',
                        'label'         => 'Rabatt (%)',
                        'name'          => 'discount_percentage',
                        'type'          => 'number',
                        'min'           => 1,
                        'max'           => 99,
                        'step'          => 1,
                        'required'      => 1,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'product',
                ),
            ),
        ),
        'menu_order'   => 5,
        'position'     => 'normal',
        'style'        => 'default',
        'label_placement' => 'top',
        'active'       => true,
    ) );
}

/* -------------------------------------------------------------------------
 * 2. Helpers
 * ---------------------------------------------------------------------- */

/**
 * Returns the sorted active tiers for a product, or [] when disabled / empty.
 * Each tier: ['min_quantity' => int, 'discount_percentage' => float].
 * Cached per request.
 */
function rg_get_bulk_discount_tiers( $product_id ) {
    static $cache = array();

    if ( isset( $cache[ $product_id ] ) ) {
        return $cache[ $product_id ];
    }

    $cache[ $product_id ] = array();

    if ( ! function_exists( 'get_field' ) ) {
        return $cache[ $product_id ];
    }

    $enabled = (bool) get_field( 'bulk_discount_enabled', $product_id );
    if ( ! $enabled ) {
        return $cache[ $product_id ];
    }

    $rows = get_field( 'bulk_discount_tiers', $product_id );
    if ( empty( $rows ) || ! is_array( $rows ) ) {
        return $cache[ $product_id ];
    }

    $tiers = array();
    foreach ( $rows as $row ) {
        $min     = isset( $row['min_quantity'] ) ? (int) $row['min_quantity'] : 0;
        $percent = isset( $row['discount_percentage'] ) ? (float) $row['discount_percentage'] : 0;

        if ( $min < 2 || $percent <= 0 ) {
            continue;
        }
        $tiers[] = array(
            'min_quantity'        => $min,
            'discount_percentage' => $percent,
        );
    }

    usort( $tiers, function ( $a, $b ) {
        return $a['min_quantity'] <=> $b['min_quantity'];
    } );

    $cache[ $product_id ] = $tiers;
    return $tiers;
}

/**
 * Returns the matching tier for a given quantity, or null if none reached.
 */
function rg_get_matching_bulk_tier( $product_id, $quantity ) {
    $tiers   = rg_get_bulk_discount_tiers( $product_id );
    $matched = null;

    foreach ( $tiers as $tier ) {
        if ( $quantity >= $tier['min_quantity'] ) {
            $matched = $tier;
        }
    }
    return $matched;
}

/**
 * Sums cart quantities by parent product id (variations roll up to parent).
 */
function rg_get_cart_quantities_by_product( $cart ) {
    $totals = array();
    foreach ( $cart->get_cart() as $cart_item ) {
        $pid = $cart_item['product_id'];
        $totals[ $pid ] = ( $totals[ $pid ] ?? 0 ) + (int) $cart_item['quantity'];
    }
    return $totals;
}

/**
 * Returns the maximum purchasable quantity when stock limits buying.
 */
function rg_get_stock_limited_quantity( $product ) {
    if ( ! is_a( $product, 'WC_Product' ) || $product->backorders_allowed() ) {
        return null;
    }

    if ( ! $product->managing_stock() ) {
        return null;
    }

    $stock_quantity = $product->get_stock_quantity();
    if ( null !== $stock_quantity ) {
        return max( 0, (int) floor( $stock_quantity ) );
    }

    return null;
}

/**
 * Returns the in-stock count above which an order tips into backorder, or null
 * when there is no such confusing threshold.
 *
 * Only relevant when the product allows backorders AND manages stock AND has at
 * least 1 unit in stock — that is exactly the case where the product page shows
 * "1-3 arbetsdagar" even though larger quantities will actually backorder
 * ("7-10 arbetsdagar"). When stock is already 0 the page shows 7-10 anyway, so
 * there is nothing to warn about.
 */
function rg_get_backorder_stock_threshold( $product ) {
    if ( ! is_a( $product, 'WC_Product' ) ) {
        return null;
    }

    if ( ! $product->backorders_allowed() || ! $product->managing_stock() ) {
        return null;
    }

    $stock_quantity = $product->get_stock_quantity();
    if ( null === $stock_quantity ) {
        return null;
    }

    $stock = (int) floor( $stock_quantity );
    if ( $stock < 1 ) {
        return null;
    }

    return $stock;
}

function rg_get_stock_managed_product_id( $product ) {
    if ( is_a( $product, 'WC_Product' ) && method_exists( $product, 'get_stock_managed_by_id' ) ) {
        return (int) $product->get_stock_managed_by_id();
    }

    return is_a( $product, 'WC_Product' ) ? (int) $product->get_id() : 0;
}

function rg_get_cart_quantity_for_stock_product( $product, $exclude_cart_item_key = '' ) {
    if ( ! function_exists( 'WC' ) || ! WC()->cart || ! is_a( $product, 'WC_Product' ) ) {
        return 0;
    }

    $stock_managed_product_id = rg_get_stock_managed_product_id( $product );
    $quantity                 = 0;

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( $exclude_cart_item_key && $cart_item_key === $exclude_cart_item_key ) {
            continue;
        }

        if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) {
            continue;
        }

        if ( rg_get_stock_managed_product_id( $cart_item['data'] ) === $stock_managed_product_id ) {
            $quantity += (int) $cart_item['quantity'];
        }
    }

    return $quantity;
}

function rg_get_stock_limit_message( $stock_limit ) {
    if ( null === $stock_limit ) {
        return '';
    }

    if ( $stock_limit <= 0 ) {
        return 'Slut i lager';
    }

    return sprintf( 'Endast %d i lager', (int) $stock_limit );
}

function rg_add_stock_limit_notice( $product, $stock_limit ) {
    if ( ! function_exists( 'wc_add_notice' ) ) {
        return;
    }

    $message = rg_get_stock_limit_message( $stock_limit );
    if ( is_a( $product, 'WC_Product' ) && $product->get_name() ) {
        $message = sprintf( '%s av "%s".', $message, $product->get_name() );
    } else {
        $message .= '.';
    }

    wc_add_notice( $message, 'error' );
}

/* -------------------------------------------------------------------------
 * 3. Apply discount on cart calculation
 * ---------------------------------------------------------------------- */

add_action( 'woocommerce_before_calculate_totals', 'rg_apply_bulk_discount_to_cart', 20, 1 );
function rg_apply_bulk_discount_to_cart( $cart ) {

    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    if ( ! $cart || ! is_a( $cart, 'WC_Cart' ) ) {
        return;
    }

    $product_quantities = rg_get_cart_quantities_by_product( $cart );

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $product_id = $cart_item['product_id'];
        $product    = $cart_item['data'];

        if ( ! is_a( $product, 'WC_Product' ) ) {
            continue;
        }

        $qty_for_product = $product_quantities[ $product_id ] ?? 0;
        $tier            = rg_get_matching_bulk_tier( $product_id, $qty_for_product );

        // Use a fresh copy of the product/variation for the original price
        // so we never compound a previously discounted price.
        $variation_or_product_id = ! empty( $cart_item['variation_id'] )
            ? $cart_item['variation_id']
            : $product_id;

        $reference = wc_get_product( $variation_or_product_id );
        if ( ! $reference ) {
            continue;
        }

        $original_price = (float) $reference->get_price();
        if ( $original_price <= 0 ) {
            continue;
        }

        if ( ! $tier ) {
            if ( ! empty( $cart_item['rg_bulk_original_price'] ) ) {
                $product->set_price( $original_price );
            }

            unset(
                $cart->cart_contents[ $cart_item_key ]['rg_bulk_original_price'],
                $cart->cart_contents[ $cart_item_key ]['rg_bulk_discount_percent']
            );
            continue;
        }

        $discounted = $original_price * ( 1 - ( $tier['discount_percentage'] / 100 ) );

        $product->set_price( $discounted );

        // Stash on the cart item for display layers.
        $cart->cart_contents[ $cart_item_key ]['rg_bulk_original_price']   = $original_price;
        $cart->cart_contents[ $cart_item_key ]['rg_bulk_discount_percent'] = (float) $tier['discount_percentage'];
    }
}

/* -------------------------------------------------------------------------
 * 4. Strike-through display in cart / checkout
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_cart_item_price', 'rg_filter_cart_item_price', 10, 3 );
function rg_filter_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
    if ( empty( $cart_item['rg_bulk_original_price'] ) ) {
        return $price_html;
    }
    $original = (float) $cart_item['rg_bulk_original_price'];
    $current  = (float) $cart_item['data']->get_price();

    if ( $original <= $current ) {
        return $price_html;
    }

    return '<span class="rg-bulk-price"><del aria-hidden="true">' . wc_price( $original ) . '</del>'
         . '<ins>' . wc_price( $current ) . '</ins></span>';
}

add_filter( 'woocommerce_cart_item_subtotal', 'rg_filter_cart_item_subtotal', 10, 3 );
function rg_filter_cart_item_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
    if ( empty( $cart_item['rg_bulk_original_price'] ) ) {
        return $subtotal_html;
    }
    $original = (float) $cart_item['rg_bulk_original_price'];
    $current  = (float) $cart_item['data']->get_price();
    $qty      = (int) $cart_item['quantity'];

    if ( $original <= $current || $qty < 1 ) {
        return $subtotal_html;
    }

    $original_subtotal = $original * $qty;
    $current_subtotal  = $current * $qty;

    return '<span class="rg-bulk-price"><del aria-hidden="true">' . wc_price( $original_subtotal ) . '</del>'
         . '<ins>' . wc_price( $current_subtotal ) . '</ins></span>';
}

add_filter( 'woocommerce_cart_item_class', 'rg_add_bulk_discount_cart_item_class', 10, 3 );
function rg_add_bulk_discount_cart_item_class( $class, $cart_item, $cart_item_key ) {
    if ( ! empty( $cart_item['rg_bulk_original_price'] ) ) {
        $class .= ' has-bulk-discount';
    }

    return $class;
}

/* -------------------------------------------------------------------------
 * 5. Total bulk discount summary
 * ---------------------------------------------------------------------- */

/**
 * Calculates the total amount saved across cart items via bulk discount.
 */
function rg_calculate_total_bulk_savings() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return 0.0;
    }

    $total = 0.0;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( empty( $cart_item['rg_bulk_original_price'] ) ) {
            continue;
        }
        $original = (float) $cart_item['rg_bulk_original_price'];
        $current  = (float) $cart_item['data']->get_price();
        $qty      = (int) $cart_item['quantity'];

        if ( $original > $current && $qty > 0 ) {
            $total += ( $original - $current ) * $qty;
        }
    }
    return $total;
}

/**
 * Renders the "Mängdrabatt" row HTML; reused in cart and checkout templates.
 */
function rg_render_bulk_discount_row() {
    $savings = rg_calculate_total_bulk_savings();
    if ( $savings <= 0 ) {
        return;
    }

    $label_cell = is_checkout() && ! is_cart() ? 'th' : 'td';
    ?>
    <tr class="bulk-discount-summary">
        <<?php echo tag_escape( $label_cell ); ?>>Mängdrabatt</<?php echo tag_escape( $label_cell ); ?>>
        <td>-<?php echo wc_price( $savings ); ?></td>
    </tr>
    <?php
}

// Inject the summary row inside the checkout review-order totals.
add_action( 'woocommerce_review_order_before_order_total', 'rg_render_bulk_discount_row' );

/* -------------------------------------------------------------------------
 * 6. Stock validation for quantity jumps
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_add_to_cart_validation', 'rg_validate_stock_limited_add_to_cart_quantity', 20, 5 );
function rg_validate_stock_limited_add_to_cart_quantity( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
    if ( ! $passed ) {
        return false;
    }

    $product = wc_get_product( $variation_id ? $variation_id : $product_id );
    if ( ! $product ) {
        return $passed;
    }

    $stock_limit = rg_get_stock_limited_quantity( $product );
    if ( null === $stock_limit ) {
        return $passed;
    }

    $requested_quantity = (int) $quantity + rg_get_cart_quantity_for_stock_product( $product );
    if ( $requested_quantity > $stock_limit ) {
        rg_add_stock_limit_notice( $product, $stock_limit );
        return false;
    }

    return $passed;
}

add_filter( 'woocommerce_update_cart_validation', 'rg_validate_stock_limited_cart_quantity', 20, 4 );
function rg_validate_stock_limited_cart_quantity( $passed, $cart_item_key, $cart_item, $quantity ) {
    if ( ! $passed || empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) {
        return $passed;
    }

    $product     = $cart_item['data'];
    $stock_limit = rg_get_stock_limited_quantity( $product );
    if ( null === $stock_limit ) {
        return $passed;
    }

    $requested_quantity = (int) $quantity + rg_get_cart_quantity_for_stock_product( $product, $cart_item_key );
    if ( $requested_quantity > $stock_limit ) {
        rg_add_stock_limit_notice( $product, $stock_limit );
        return false;
    }

    return $passed;
}

/* -------------------------------------------------------------------------
 * 7. Single product page: tier buttons above add-to-cart
 * ---------------------------------------------------------------------- */

add_action( 'woocommerce_before_add_to_cart_button', 'rg_render_bulk_discount_buttons', 5 );
function rg_render_bulk_discount_buttons() {
    global $product;
    if ( ! $product ) {
        return;
    }

    $tiers = rg_get_bulk_discount_tiers( $product->get_id() );
    if ( empty( $tiers ) ) {
        return;
    }

    $stock_limit     = rg_get_stock_limited_quantity( $product );
    $backorder_stock = rg_get_backorder_stock_threshold( $product );
    ?>
    <div class="bulk-discount-tiers"
         role="group"
         aria-label="Mängdrabatt"
         data-stock-limit="<?php echo null === $stock_limit ? '' : esc_attr( $stock_limit ); ?>"
         data-backorder-stock="<?php echo null === $backorder_stock ? '' : esc_attr( $backorder_stock ); ?>">
        <?php foreach ( $tiers as $tier ) :
            $min_qty = (int) $tier['min_quantity'];
            $percent = (float) $tier['discount_percentage'];
            $percent_label = ( floor( $percent ) == $percent )
                ? number_format( $percent, 0 )
                : number_format( $percent, 2, ',', '' );
            $is_unavailable = null !== $stock_limit && $min_qty > $stock_limit;
            ?>
            <button type="button"
                    class="bulk-discount-tier action-button light<?php echo $is_unavailable ? ' is-unavailable' : ''; ?>"
                    data-quantity="<?php echo esc_attr( $min_qty ); ?>"
                    data-discount="<?php echo esc_attr( $percent ); ?>"
                    <?php disabled( $is_unavailable ); ?>
                    aria-disabled="<?php echo $is_unavailable ? 'true' : 'false'; ?>"
                    aria-pressed="false">
                Köp <?php echo esc_html( $min_qty ); ?>+ st &bull; -<?php echo esc_html( $percent_label ); ?>%
            </button>
        <?php endforeach; ?>
        <input type="hidden" name="quantity" value="1" class="bulk-discount-quantity-input" />
        <p class="bulk-discount-backorder-notice" role="alert" hidden></p>
    </div>
    <?php
}

/* -------------------------------------------------------------------------
 * 7b. Expose per-variation backorder stock threshold to the front-end
 *     so variable products can warn on the correct selected variation.
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_available_variation', 'rg_add_backorder_stock_to_variation', 10, 3 );
function rg_add_backorder_stock_to_variation( $data, $product, $variation ) {
    $threshold = rg_get_backorder_stock_threshold( $variation );
    $data['rg_backorder_stock'] = ( null === $threshold ) ? '' : $threshold;

    return $data;
}

/* -------------------------------------------------------------------------
 * 8. Inline assets (CSS + tiny JS) — keeps feature self-contained.
 * ---------------------------------------------------------------------- */

add_action( 'wp_head', 'rg_bulk_discount_inline_styles', 100 );
function rg_bulk_discount_inline_styles() {
    ?>
    <style id="rg-bulk-discount-styles">
        .bulk-discount-tiers {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 0 12px 0;
            align-items: center;
        }
        .bulk-discount-tiers__label {
            width: 100%;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #494949;
            margin-bottom: 2px;
        }
        .bulk-discount-tier {
            flex: 1 1 auto;
            min-width: 130px;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            padding: 0 10px;
            white-space: nowrap;
        }
        .bulk-discount-tier.is-active {
            border-color: #A0CE4E;
            color: #2c4500;
            background: #f1f9e2;
            font-weight: 800;
        }
        .bulk-discount-tier.is-unavailable,
        .bulk-discount-tier:disabled {
            border-color: #ddd;
            color: #777;
            background: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.65;
        }
        .woocommerce-cart-form__cart-item .product-price del,
        .woocommerce-cart-form__cart-item .product-subtotal del {
            color: #999;
            margin-right: 4px;
            font-weight: 400;
        }
        .woocommerce-cart-form__cart-item .product-price ins,
        .woocommerce-cart-form__cart-item .product-subtotal ins {
            text-decoration: none;
            color: #A0CE4E;
            font-weight: 700;
        }
        .woocommerce-cart-form__cart-item .rg-bulk-price {
            display: block;
            line-height: 1.2;
            white-space: nowrap;
        }
        .woocommerce-cart-form__cart-item .rg-bulk-price del,
        .woocommerce-cart-form__cart-item .rg-bulk-price ins {
            display: block !important;
            padding-left: 0 !important;
            text-align: right;
        }
        .woocommerce-cart-form__cart-item .rg-bulk-price del span.amount,
        .woocommerce-cart-form__cart-item .rg-bulk-price ins span.amount {
            display: inline !important;
            vertical-align: baseline !important;
            line-height: 1.2 !important;
        }
        tr.bulk-discount-summary td:last-child {
            color: #A0CE4E;
            font-weight: 700;
        }
        .bulk-discount-backorder-notice {
            width: 100%;
            margin: 0;
            padding: 8px 12px;
            border: 1px solid #f0c36d;
            border-left-width: 4px;
            border-radius: 4px;
            background: #fdf6e3;
            color: #7a5b14;
            font-size: 13px;
            line-height: 1.4;
        }
        .bulk-discount-backorder-notice[hidden] {
            display: none;
        }
        .product-purchase-info .delivery-time.rg-backorder-active .in-stock .fa-check-circle {
            display: none;
        }
        .product-purchase-info .delivery-time.rg-backorder-active .in-stock .time {
            color: #7a5b14;
        }
        @media all and (max-width: 800px) {
            .shop_table li.woocommerce-cart-form__cart-item.has-bulk-discount {
                min-height: 150px;
            }
            .shop_table li.woocommerce-cart-form__cart-item.has-bulk-discount .product-price,
            .shop_table li.woocommerce-cart-form__cart-item.has-bulk-discount .product-subtotal {
                bottom: 8px !important;
            }
            .shop_table li.woocommerce-cart-form__cart-item.has-bulk-discount .rg-bulk-price del span.amount {
                font-size: 12px !important;
            }
            .shop_table li.woocommerce-cart-form__cart-item.has-bulk-discount .rg-bulk-price ins span.amount {
                font-size: 14px !important;
            }
        }
    </style>
    <?php
}

add_action( 'wp_footer', 'rg_bulk_discount_inline_script', 100 );
function rg_bulk_discount_inline_script() {
    if ( ! is_product() ) {
        return;
    }
    ?>
    <script id="rg-bulk-discount-script">
    (function(){
        function parseStockLimit(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            var parsed = parseInt(value, 10);
            return isNaN(parsed) ? null : parsed;
        }

        function getQuantityInput(form) {
            return form.querySelector('input.qty[name="quantity"]:not(.bulk-discount-quantity-input)')
                || form.querySelector('input[name="quantity"]:not(.bulk-discount-quantity-input)')
                || form.querySelector('.bulk-discount-quantity-input');
        }

        function setQuantity(form, quantity) {
            var input = getQuantityInput(form);
            if (input) {
                input.value = quantity;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function setTierActive(button, isActive) {
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        }

        // Backorder delivery-time warning ------------------------------------
        var deliveryObserver = null;

        function getDeliveryTimeEl() {
            return document.querySelector('.product-purchase-info .delivery-time');
        }

        function getBulkForm() {
            var group = document.querySelector('form.cart .bulk-discount-tiers');
            return group ? group.closest('form.cart') : null;
        }

        // Swap the rendered "Leveranstid" line between 1-3 and 7-10 days.
        // Disconnect the observer around our own edits so they don't re-trigger it.
        function setDeliveryTimeOverride(warn) {
            var dt = getDeliveryTimeEl();
            if (!dt) return;

            var timeEl = dt.querySelector('.time');

            if (deliveryObserver) {
                deliveryObserver.disconnect();
            }

            if (warn) {
                if (timeEl) {
                    if (!timeEl.hasAttribute('data-rg-instock-text')) {
                        timeEl.setAttribute('data-rg-instock-text', (timeEl.textContent || '').trim());
                    }
                    timeEl.textContent = '7-10 arbetsdagar';
                }
                dt.classList.add('rg-backorder-active');
            } else {
                if (timeEl && timeEl.hasAttribute('data-rg-instock-text')) {
                    timeEl.textContent = timeEl.getAttribute('data-rg-instock-text');
                }
                dt.classList.remove('rg-backorder-active');
            }

            if (deliveryObserver) {
                deliveryObserver.observe(dt, { childList: true, subtree: true });
            }
        }

        function applyBackorderWarning(form) {
            if (!form) return;

            var group = form.querySelector('.bulk-discount-tiers');
            if (!group) return;

            var notice    = group.querySelector('.bulk-discount-backorder-notice');
            var threshold = parseStockLimit(group.getAttribute('data-backorder-stock'));
            var activeBtn = group.querySelector('.bulk-discount-tier.is-active');
            var activeQty = activeBtn ? (parseInt(activeBtn.getAttribute('data-quantity'), 10) || 0) : 0;
            var warn      = threshold !== null && activeQty > threshold;

            if (notice) {
                if (warn) {
                    notice.textContent = 'Obs! Endast ' + threshold + ' st finns i lager. '
                        + 'Vi levererar hela beställningen så fort vi har fått hem produkterna på lager.';
                    notice.hidden = false;
                } else {
                    notice.textContent = '';
                    notice.hidden = true;
                }
            }

            setDeliveryTimeOverride(warn);
        }

        function updateTierAvailability(form, limit) {
            var group = form.querySelector('.bulk-discount-tiers');
            if (!group) return;

            group.querySelectorAll('.bulk-discount-tier').forEach(function(button){
                var tierQuantity = parseInt(button.getAttribute('data-quantity'), 10) || 1;
                var isUnavailable = limit !== null && tierQuantity > limit;

                button.disabled = isUnavailable;
                button.setAttribute('aria-disabled', isUnavailable ? 'true' : 'false');
                button.classList.toggle('is-unavailable', isUnavailable);

                if (isUnavailable && button.classList.contains('is-active')) {
                    setTierActive(button, false);
                    setQuantity(form, 1);
                }
            });

            if (limit !== null) {
                group.setAttribute('data-stock-limit', limit);
            } else {
                group.removeAttribute('data-stock-limit');
            }

            applyBackorderWarning(form);
        }

        document.querySelectorAll('form.cart').forEach(function(form){
            var group = form.querySelector('.bulk-discount-tiers');
            if (group) {
                updateTierAvailability(form, parseStockLimit(group.getAttribute('data-stock-limit')));
            }
        });

        // Re-apply the warning whenever the delivery-time line is re-rendered
        // externally (variable products re-render it via AJAX on variation change).
        (function(){
            var deliveryEl = getDeliveryTimeEl();
            if (deliveryEl && window.MutationObserver) {
                deliveryObserver = new MutationObserver(function(){
                    applyBackorderWarning(getBulkForm());
                });
                deliveryObserver.observe(deliveryEl, { childList: true, subtree: true });
            }
        })();

        document.addEventListener('click', function(e){
            var btn = e.target.closest('.bulk-discount-tier');
            if (!btn) return;
            e.preventDefault();

            var form = btn.closest('form.cart');
            if (!form) return;

            if (btn.disabled) {
                return;
            }

            var qty = parseInt(btn.getAttribute('data-quantity'), 10) || 1;
            var wasActive = btn.classList.contains('is-active');

            form.querySelectorAll('.bulk-discount-tier').forEach(function(b){
                setTierActive(b, false);
            });

            if (wasActive) {
                setQuantity(form, 1);
                applyBackorderWarning(form);
                return;
            }

            setQuantity(form, qty);
            setTierActive(btn, true);
            applyBackorderWarning(form);
        });

        if (window.jQuery) {
            window.jQuery(function($){
                $('form.variations_form').on('found_variation', function(event, variation){
                    var form  = this;
                    var group = form.querySelector('.bulk-discount-tiers');
                    if (group) {
                        var backorderStock = (variation && variation.rg_backorder_stock !== undefined && variation.rg_backorder_stock !== '')
                            ? variation.rg_backorder_stock
                            : '';
                        if (backorderStock === '') {
                            group.removeAttribute('data-backorder-stock');
                        } else {
                            group.setAttribute('data-backorder-stock', backorderStock);
                        }
                    }

                    if (variation && variation.backorders_allowed) {
                        updateTierAvailability(form, null);
                    } else if (variation && variation.is_in_stock === false) {
                        updateTierAvailability(form, 0);
                    } else {
                        updateTierAvailability(form, variation ? parseStockLimit(variation.max_qty) : null);
                    }
                });

                $('form.variations_form').on('reset_data hide_variation', function(){
                    var group = this.querySelector('.bulk-discount-tiers');
                    if (group) {
                        group.removeAttribute('data-backorder-stock');
                    }
                    updateTierAvailability(this, null);
                });
            });
        }
    })();
    </script>
    <?php
}
