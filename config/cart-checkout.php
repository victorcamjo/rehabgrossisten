<?php

/**
 * Klarna Checkout Timeout 
 * Default = 20
 * https://docs.krokedil.com/klarna-checkout-for-woocommerce/troubleshooting/faq/
 * */ 
add_filter( 'kco_checkout_timeout_duration', 'kco_set_custom_timeout' );
function kco_set_custom_timeout( $time ) {
    return 60;
}

add_filter( 'woocommerce_cart_shipping_method_full_label', 'change_cart_shipping_method_full_label', 10, 2 );
function change_cart_shipping_method_full_label( $label, $method ) {
    $has_cost  = 0 < $method->cost;
    $hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );

    if ( !$has_cost ) {
        $label  = $method->get_label() . ': 0 kr';
    }
    return $label;
}

// Show Mailchimp checkbox in Klarna iframe
/*
add_filter( 'kco_wc_api_request_args', 'krokedil_add_required_checkbox' );
function krokedil_add_required_checkbox( $create ) {

    $create['options']['additional_checkbox'] = array(
        'id'        => 'newsletter',
        'text'      => 'Ja tack, jag vill gärna ha tips om tränings- och rehabprodukter via e-post.',
        'checked'   => true,
        'required'  => false
    );

    // $create['options']['additional_checkbox']['text']     = 'Ja tack, jag vill gärna ha tips om tränings- och rehabprodukter via e-post.';
    // $create['options']['additional_checkbox']['checked']  = true;
    // $create['options']['additional_checkbox']['required'] = false;
    return $create;
}
*/
add_filter( 'kco_additional_checkboxes', 'add_custom_checkboxes' );
function add_custom_checkboxes( $additional_checkboxes ) {
	$additional_checkboxes[] = array(
		'id'       => 'newsletter', // The ID to be used in the backend to read when we get the order during the checkout process.
		'text'     => 'Ja tack, jag vill gärna ha tips om tränings- och rehabprodukter via e-post.',
		'checked'  => false, // true if it should be automatically checked, false if not.
		'required' => false, // true if its required to be able to place an order, false if not.
	);
    /*
    $additional_checkboxes[] = array(
		'id'       => 'cusrev_checkbox', // The ID to be used in the backend to read when we get the order during the checkout process.
		'text'     => 'Ja, jag vill ha möjlighet att lämna ett produktomdöme efter mitt köp.', // The text that you want to display to the customer.
		'checked'  => false, // true if it should be automatically checked, false if not.
		'required' => false, // true if its required to be able to place an order, false if not.
	);
    */
	return $additional_checkboxes;
}

// Save Mailchimp checkbox choice in metadata
add_action( 'kco_wc_process_payment', 'rehabgrossisten_kco_order_data', 10, 2 );
add_action( 'klarna_before_kco_confirmation', 'rehabgrossisten_kco_order_data', 10, 2 ); // new
function rehabgrossisten_kco_order_data($order_id, $klarna_order ) {
    
    // $klarna_order is the returned object from Klarna
    /*
    if( $klarna_order['merchant_requested']['additional_checkbox'] ) {
        update_post_meta( $order_id, 'mailchimp_woocommerce_is_subscribed', 1 );
    } else {
        update_post_meta( $order_id, 'mailchimp_woocommerce_is_subscribed', 0 );
    }
    */

    if ( isset( $klarna_order['merchant_requested']['additional_checkboxes'] ) ) {
		foreach ( $klarna_order['merchant_requested']['additional_checkboxes'] as $checkbox ) {
			if ( 'newsletter' === $checkbox['id'] ) {
                $checked = $checkbox['checked'] ? 1 : 0;
				update_post_meta( $order_id, 'mailchimp_woocommerce_is_subscribed', $checked );
			}

            if ( 'cusrev_checkbox' === $checkbox['id'] ) {
                $checked = $checkbox['checked'] ? 'yes' : 'no';
                update_post_meta( $order_id, '_ivole_cr_consent', $checked );
            }
		}
	}
}

/* Redirect from cart */
// add_action( 'template_redirect', 'empty_cart_redirection' );
function empty_cart_redirection(){
    if ( is_cart() && ! ( is_front_page() || is_checkout()) ) {
        if ( WC()->cart->is_empty() )
        {
            wp_safe_redirect( esc_url( home_url( '/' ) ) );
            exit;
        }
        else
        {
            wp_safe_redirect( esc_url( wc_get_checkout_url() ) );
            exit;
        }
    }
}

/* Send Fail and Cancel emails to customer */
add_action('woocommerce_order_status_changed', 'send_custom_email_notifications', 10, 4 );
function send_custom_email_notifications( $order_id, $old_status, $new_status, $order ){
    if ( $new_status == 'cancelled' || $new_status == 'failed' ){
        $wc_emails = WC()->mailer()->get_emails(); // Get all WC_emails objects instances
        $customer_email = $order->get_billing_email(); // The customer email
    }

    if ( $new_status == 'cancelled' ) {
        // change the recipient of this instance
        $wc_emails['WC_Email_Cancelled_Order']->recipient .= ',' . $customer_email;
        // Sending the email from this instance
        $wc_emails['WC_Email_Cancelled_Order']->trigger( $order_id );
    }
    elseif ( $new_status == 'failed' ) {
        // change the recipient of this instance
        $wc_emails['WC_Email_Failed_Order']->recipient .= ',' . $customer_email;
        // Sending the email from this instance
        $wc_emails['WC_Email_Failed_Order']->trigger( $order_id );
    }
}

/**
 * @snippet       Removes shipping method labels @ WooCommerce Cart / Checkout
 */
add_filter( 'woocommerce_cart_shipping_method_full_label', 'remove_shipping_label_order_review', 9999, 2 );
function remove_shipping_label_order_review( $label, $method ) {
    $new_label = preg_replace( '/^.+:/', '', $label );
    return $new_label;
}

add_action('woocommerce_checkout_update_order_review', 'checkout_update_refresh_shipping_methods', 10, 1);
function checkout_update_refresh_shipping_methods( $post_data ) {
    $packages = WC()->cart->get_shipping_packages();
    foreach ($packages as $package_key => $package ) {
         WC()->session->set( 'shipping_for_package_' . $package_key, false );
    }
}

function initilize_shipping_rates_private_org($rates, $package) 
{
    $rates = getShippingMethods($rates);

    return $rates;
}
add_filter( 'woocommerce_package_rates', 'initilize_shipping_rates_private_org', 10, 2);

function getShippingMethods($rates){
    $klarna_order_id = WC()->session->get( 'kco_wc_order_id' );

    if ( empty($klarna_order_id) ) 
    {
        return $rates;
    }

    $klarna_order = KCO_WC()->api->get_klarna_order( $klarna_order_id );

    if ( is_wp_error( $klarna_order ) )
    {
        return $rates;
    }

    if ( isOrganizationalCustomer($klarna_order) )
    {
        if (isset($rates['flat_rate:34']))      unset($rates['flat_rate:34']);
        if (isset($rates['flat_rate:40']))      unset($rates['flat_rate:40']);
        if (isset($rates['flat_rate:44']))      unset($rates['flat_rate:44']);
        if (isset($rates['flat_rate:46']))      unset($rates['flat_rate:46']);
        if (isset($rates['local_pickup:38']))   unset($rates['local_pickup:38']);
        if (isset($rates['free_shipping:33']))  unset($rates['free_shipping:33']);
        if (isset($rates['free_shipping:43']))  unset($rates['free_shipping:43']);
    }
    else 
    {
        if ( isset($rates['free_shipping:41']) ) unset($rates['free_shipping:41']);
        if ( isset($rates['flat_rate:45']) ) unset($rates['flat_rate:45']);
        if ( isset($rates['free_shipping:48']) ) unset($rates['free_shipping:48']);
        if ( isset($rates['flat_rate:49']) ) unset($rates['flat_rate:49']);
    }

    return $rates;
}

function isOrganizationalCustomer( $klarna_order ){
    return isset( $klarna_order['customer']['type'] ) && $klarna_order['customer']['type'] == 'organization';
}

/**
 * Update order metadata after order placement
 * Set organizational/business customer
 */
add_action( 'woocommerce_thankyou', 'get_order_meta_at_thankyoupage', 20, 1);
function get_order_meta_at_thankyoupage( $order_id ){
    $order = wc_get_order( $order_id );
    
    $klarna_order_id = get_post_meta( $order_id, '_wc_klarna_order_id', true );
	$klarna_order    = KCO_WC()->api->get_klarna_order( $klarna_order_id );

    $isBusinessCustomer = isOrganizationalCustomer($klarna_order);

    // Update meta data for order
    $order->update_meta_data( 'is_company_customer', $isBusinessCustomer ? 'true' : 'false' );
    $order->save();
}


/**
 * Notice of amount before free shipping
 */
function free_shipping_threshold_notice()
{
    if (is_admin()) return false;
	
    $total = WC()->cart->get_cart_contents_total();
    $free_shipping = get_free_shipping_minimum_zone('Sweden');

    // Restrict to specific countries
    // $country_restrictions = array('SE'); // Only show this notice for these countries
    // if (!in_array(WC()->customer->get_shipping_country(), $country_restrictions)) return false;

    if ($total < $free_shipping) {
        $remaining_for_free_shipping = $free_shipping - $total;
        wc_print_notice(sprintf(__('Du har %s kvar till fri frakt.', 'rehabgrossisten'), wc_price($remaining_for_free_shipping)), 'notice');
    }
}
// add_action('woocommerce_before_checkout_form', 'free_shipping_threshold_notice');
// add_action('woocommerce_before_cart', 'free_shipping_threshold_notice');

function get_free_shipping_minimum_zone($zone_name)
{
    if (!isset($zone_name)) return null;

    $result = null;
    $zone = null;

    $zones = WC_Shipping_Zones::get_zones();
    foreach ($zones as $z) {
        if ($z['zone_name'] == $zone_name) {
            $zone = $z;
        }
    }

    if ($zone) {
        $shipping_methods_nl = $zone['shipping_methods'];
        $free_shipping_method = null;
        foreach ($shipping_methods_nl as $method) {
            if ($method->id == 'free_shipping') {
                $free_shipping_method = $method;
                break;
            }
        }

        if ($free_shipping_method) {
            $result = $free_shipping_method->min_amount;
        }
    }

    return $result;
}
 
function checkout_add_upsell() {

    PostHog\PostHog::init('phc_fqkWdy2uPDf6BmdyEE7oVLJ8BIxg8g6JrzYBiVBLIhN',
        array('host' => 'https://eu.i.posthog.com')
    );

    // A-B test conversions
    if (PostHog\PostHog::getFeatureFlag('checkout-upsales-conversions', 'user distinct id') == 'test') {
        return;
    } else {
        // Default behaviour
        echo show_items_cross_sell();
    }
}
add_action( 'kco_wc_after_order_review', 'checkout_add_upsell', 10 );

function show_items_cross_sell( $columns = 8 ) {
    $cross_sell_products = array();
    $number_of_products = 0;
    $category = 55; // Rehab och träning
    $exclude_category = 551; // Okategoriserad
    $exclude = array();

    if ( !empty(WC()->cart->get_cross_sells()) ) {
        foreach(WC()->cart->get_cross_sells() as $item) {
            $_cross_sell_product = wc_get_product( $item );
            $is_in_stock = $_cross_sell_product && $_cross_sell_product->is_in_stock();
            $exclude_from_cross_sell = false;
            if (is_object($_cross_sell_product)) {
                $exclude_from_cross_sell = get_field('exclude_from_cross_sell', $_cross_sell_product->get_id()) ?? false;
            }
            

            if ($exclude_from_cross_sell === true) {
                array_push($exclude, $_cross_sell_product->get_id());
                continue;
            }

            if ($is_in_stock && $number_of_products < $columns) {
                array_push($cross_sell_products, $item);
                $number_of_products++;
            }
        }
    }

    if ($number_of_products < $columns) {

        array_push($exclude, 23432, 23433);
        foreach( WC()->cart->get_cart() as $item ) {
            array_push($exclude, $item['product_id'] );
        }

        $products = new WP_Query( array(
            'post_type'         => array('product', 'product_simple'),
            'post__not_in'      => $exclude,
            'post_status'       => 'publish',
            'orderby'           => 'rand',
            'order'             => 'ASC',
            'posts_per_page'    =>  $columns - $number_of_products,
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
                'relation'  => 'AND',
                array(
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '=',
                ),                
                array(
                    'key'       => '_price',
                    'value'     => 200,
                    'compare'   => '<=',
                    'type'      => 'NUMERIC'
                )
            )
        ) );

        $cross_sell_products = array_merge($cross_sell_products, $products->posts);
    }
    
    $products_shortcode = '[products orderby="post__in" class="product-slider" ids="' . implode(",", $cross_sell_products) . '"]';
    
    ob_start();
    ?>

    <div class="cross-sell-products">
        <h3 class="cross-sell-title"><span>Behöver du något mer?</span></h3>
        <?php echo do_shortcode($products_shortcode); ?>
    </div>
    
    <?php

    return ob_get_clean();
}

// Add the product name as data argument to Ajax add to cart buttons
add_filter( "woocommerce_loop_add_to_cart_args", "filter_wc_loop_add_to_cart_args", 20, 2 );
function filter_wc_loop_add_to_cart_args( $args, $product ) {
    if ( $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ) {
        $args['attributes']['data-product_name'] = $product->get_name();
    }
    return $args;
}