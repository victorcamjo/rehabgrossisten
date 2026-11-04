<?php
/**
 * Purchase dataLayer — hashed customer parameters (Advanced Matching / CAPI)
 *
 * Pushes a `user_data` object into the dataLayer on the order received page so
 * GTM can attach it to the purchase event. Every value except `external_id` is
 * normalised and then SHA-256 hashed (lowercase hex) before it leaves the
 * server, so no readable personal data ends up in the page source.
 *
 * The push happens early in wp_head, i.e. before any purchase event is pushed
 * later in the page. GTM keeps dataLayer values in its model across pushes, so
 * `user_data.em` and friends resolve when the purchase trigger fires — the same
 * mechanism the existing `orderComment` push relies on.
 *
 * Normalisation follows Meta's rules:
 *   em      trimmed, lowercase e-mail
 *   ph      digits only, country code first, no plus sign (46701234567)
 *   fn/ln   lowercase, titles and punctuation removed
 *   ct/st   lowercase, spaces and punctuation removed
 *   zp      first five digits
 *   country two letter ISO code, lowercase
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * Normalisation helpers
 * ---------------------------------------------------------------------- */

/**
 * Multibyte safe lowercase, so Swedish characters survive (Å -> å).
 */
function rg_dl_lower( $value ) {
    $value = (string) $value;

    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
}

/**
 * SHA-256 as lowercase hex. Empty input stays empty — never hash a blank value,
 * the hash of "" is a constant that would match every other customer missing
 * that field.
 */
function rg_dl_hash( $value ) {
    $value = (string) $value;

    if ( '' === $value ) {
        return '';
    }

    return hash( 'sha256', $value );
}

/**
 * Keep letters only (accents included), lowercase. Used for names, city and
 * state, where spaces, hyphens and punctuation must be stripped.
 */
function rg_dl_letters_only( $value ) {
    $value = rg_dl_lower( trim( (string) $value ) );
    $value = preg_replace( '/[^\p{L}\p{M}]/u', '', $value );

    return is_string( $value ) ? $value : '';
}

/**
 * Drop a leading title so "Dr Anna" and "Anna" hash to the same value.
 */
function rg_dl_strip_name_title( $name ) {
    $name = trim( (string) $name );

    if ( '' === $name ) {
        return '';
    }

    $titles = apply_filters( 'rg_datalayer_name_titles', array(
        'herr', 'fru', 'fröken', 'frk', 'dr', 'doktor', 'prof', 'professor',
        'mr', 'mrs', 'ms', 'miss', 'sir', 'ing', 'civ',
    ) );

    $quoted = array_map( function( $title ) {
        return preg_quote( $title, '/' );
    }, (array) $titles );

    $pattern  = '/^(?:' . implode( '|', $quoted ) . ')\.?\s+/iu';
    $stripped = preg_replace( $pattern, '', $name );

    return is_string( $stripped ) ? $stripped : $name;
}

function rg_dl_normalize_name( $name ) {
    return rg_dl_letters_only( rg_dl_strip_name_title( $name ) );
}

function rg_dl_normalize_email( $email ) {
    $email = sanitize_email( rg_dl_lower( trim( (string) $email ) ) );

    return is_email( $email ) ? $email : '';
}

/**
 * Digits of the country calling code for a two letter country code, e.g. 46.
 */
function rg_dl_country_calling_code( $country ) {
    $country = preg_replace( '/[^a-zA-Z]/', '', (string) $country );
    $country = strtoupper( is_string( $country ) ? $country : '' );

    if ( 2 !== strlen( $country ) ) {
        $country = 'SE';
    }

    $code = '';
    $wc   = function_exists( 'WC' ) ? WC() : null;

    if ( $wc && ! empty( $wc->countries ) && method_exists( $wc->countries, 'get_country_calling_code' ) ) {
        $code = $wc->countries->get_country_calling_code( $country );

        if ( is_array( $code ) ) {
            $code = reset( $code );
        }
    }

    $code = preg_replace( '/\D/', '', (string) $code );

    if ( ! is_string( $code ) || '' === $code ) {
        $code = ( 'SE' === $country ) ? '46' : '';
    }

    return $code;
}

/**
 * Digits only, country code first, no plus sign: 46701234567.
 *
 * Handles the formats customers actually type: +46 70-123 45 67,
 * 0046701234567, 070-123 45 67 and 701234567.
 */
function rg_dl_normalize_phone( $phone, $country = '' ) {
    $raw = trim( (string) $phone );

    if ( '' === $raw ) {
        return '';
    }

    $digits = preg_replace( '/\D/', '', $raw );

    if ( ! is_string( $digits ) || '' === $digits ) {
        return '';
    }

    // Already written internationally.
    if ( 0 === strpos( $raw, '+' ) ) {
        return $digits;
    }

    if ( 0 === strpos( $digits, '00' ) ) {
        return substr( $digits, 2 );
    }

    $calling = rg_dl_country_calling_code( $country );

    if ( '' === $calling ) {
        return ltrim( $digits, '0' );
    }

    // National format with trunk prefix: 0701234567 -> 46701234567.
    if ( 0 === strpos( $digits, '0' ) ) {
        return $calling . ltrim( $digits, '0' );
    }

    if ( 0 === strpos( $digits, $calling ) ) {
        return $digits;
    }

    return $calling . $digits;
}

/**
 * Five digit postcode. "123 45" -> "12345".
 */
function rg_dl_normalize_postcode( $postcode ) {
    $digits = preg_replace( '/\D/', '', (string) $postcode );

    if ( ! is_string( $digits ) || '' === $digits ) {
        return '';
    }

    return substr( $digits, 0, 5 );
}

function rg_dl_normalize_country( $country ) {
    $country = preg_replace( '/[^a-zA-Z]/', '', (string) $country );
    $country = rg_dl_lower( is_string( $country ) ? $country : '' );

    return 2 === strlen( $country ) ? $country : '';
}

/* -------------------------------------------------------------------------
 * Building the user_data object
 * ---------------------------------------------------------------------- */

/**
 * Stable identifier for the buyer. Registered customers get their WooCommerce
 * user id, guests get the order number.
 *
 * The values are prefixed so a customer id and an order number that happen to
 * be the same number are never treated as the same person. Return the bare id
 * through the filter if a server side integration needs the raw value.
 */
function rg_dl_external_id( $order ) {
    $customer_id = (int) $order->get_customer_id();

    if ( $customer_id > 0 ) {
        $external_id = 'cust-' . $customer_id;
    } else {
        $external_id = 'order-' . $order->get_order_number();
    }

    return (string) apply_filters( 'rg_datalayer_external_id', $external_id, $order );
}

/**
 * Normalised + hashed customer parameters for an order.
 * Empty fields are left out entirely rather than sent as empty strings.
 *
 * @param WC_Order $order
 * @return array
 */
function rg_datalayer_user_data( $order ) {
    if ( ! $order instanceof WC_Order ) {
        return array();
    }

    $email = $order->get_billing_email();

    if ( '' === trim( (string) $email ) && $order->get_customer_id() ) {
        $user = get_userdata( $order->get_customer_id() );

        if ( $user ) {
            $email = $user->user_email;
        }
    }

    $phone = $order->get_billing_phone();

    if ( '' === trim( (string) $phone ) && method_exists( $order, 'get_shipping_phone' ) ) {
        $phone = $order->get_shipping_phone();
    }

    $first_name = $order->get_billing_first_name() ? $order->get_billing_first_name() : $order->get_shipping_first_name();
    $last_name  = $order->get_billing_last_name() ? $order->get_billing_last_name() : $order->get_shipping_last_name();
    $city       = $order->get_billing_city() ? $order->get_billing_city() : $order->get_shipping_city();
    $state      = $order->get_billing_state() ? $order->get_billing_state() : $order->get_shipping_state();
    $postcode   = $order->get_billing_postcode() ? $order->get_billing_postcode() : $order->get_shipping_postcode();
    $country    = $order->get_billing_country() ? $order->get_billing_country() : $order->get_shipping_country();

    $normalized = array(
        'em'      => rg_dl_normalize_email( $email ),
        'ph'      => rg_dl_normalize_phone( $phone, $country ),
        'fn'      => rg_dl_normalize_name( $first_name ),
        'ln'      => rg_dl_normalize_name( $last_name ),
        'ct'      => rg_dl_letters_only( $city ),
        'st'      => rg_dl_letters_only( $state ),
        'zp'      => rg_dl_normalize_postcode( $postcode ),
        'country' => rg_dl_normalize_country( $country ),
    );

    $user_data = array();

    foreach ( $normalized as $key => $value ) {
        if ( '' !== $value ) {
            $user_data[ $key ] = rg_dl_hash( $value );
        }
    }

    $external_id = rg_dl_external_id( $order );

    if ( '' !== $external_id ) {
        $user_data['external_id'] = $external_id;
    }

    return apply_filters( 'rg_datalayer_user_data', $user_data, $order );
}

/* -------------------------------------------------------------------------
 * Output
 * ---------------------------------------------------------------------- */

/**
 * The order being shown on the order received page, or false.
 *
 * The order key is verified here because this runs on wp_head, outside the
 * WooCommerce thankyou template — without the check the order id in the URL
 * could be swapped to read another customer's hashed data.
 *
 * @return WC_Order|false
 */
function rg_datalayer_get_thankyou_order() {
    if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
        return false;
    }

    global $wp;

    $order_id = 0;

    if ( isset( $wp->query_vars['order-received'] ) ) {
        $order_id = absint( $wp->query_vars['order-received'] );
    }

    if ( ! $order_id && isset( $_GET['order-received'] ) ) {
        $order_id = absint( wp_unslash( $_GET['order-received'] ) );
    }

    if ( ! $order_id ) {
        return false;
    }

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return false;
    }

    $order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';

    if ( '' !== $order_key && hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
        return $order;
    }

    return current_user_can( 'view_order', $order_id ) ? $order : false;
}

/**
 * Push the hashed parameters before anything else on the page so they are in
 * the dataLayer by the time the purchase event fires.
 */
add_action( 'wp_head', 'rg_datalayer_print_user_data', 1 );
function rg_datalayer_print_user_data() {
    static $printed = false;

    if ( $printed ) {
        return;
    }

    $order = rg_datalayer_get_thankyou_order();

    if ( ! $order ) {
        return;
    }

    $user_data = rg_datalayer_user_data( $order );

    if ( empty( $user_data ) ) {
        return;
    }

    $key     = (string) apply_filters( 'rg_datalayer_user_data_key', 'user_data' );
    $payload = wp_json_encode(
        array( $key => $user_data ),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    if ( ! $payload ) {
        return;
    }

    $printed = true;

    echo '<script id="rg-user-data">window.dataLayer = window.dataLayer || [];window.dataLayer.push(' . $payload . ');</script>' . "\n";
}

/* -------------------------------------------------------------------------
 * GTM4WP in-app browser workaround
 * ---------------------------------------------------------------------- */

/**
 * GTM4WP abandons its entire WooCommerce data layer when the request carries an
 * X-Requested-With header — PageDataLayer::add_datalayer_data(), line 86 in the
 * current release, integration/woocommerce.php line 378 before the refactor:
 *
 *     if ( array_key_exists( 'HTTP_X_REQUESTED_WITH', $_SERVER ) ) {
 *         return $data_layer;
 *     }
 *
 * The check tests for presence, not value. It exists for a good reason: the
 * method has side effects beyond its return value — purchase flags the order
 * _ga_tracked, and the one-shot cart events consume session state. Letting it
 * run during a background request would burn that state into a response the
 * browser never evaluates as a data layer, and the event would be lost.
 *
 * Android WebViews break the assumption behind it. The Facebook, Instagram and
 * Klarna in-app browsers stamp X-Requested-With with their own package name
 * (com.facebook.katana and friends) on ordinary page loads, not just on XHR.
 * Every order placed inside those apps is therefore invisible to both GA4 and
 * Meta — roughly 27% of Meta orders on this store. GTM4WP's own "reliable
 * purchase tracking" fallback is called from inside the same method, so it is
 * behind the same early return and provides no safety net.
 *
 * The header is removed for the duration of GTM4WP's data layer build only,
 * and only when this is plainly a normal page load: not AJAX, not REST, and
 * the header says something other than XMLHttpRequest. A genuine background
 * request keeps its header and keeps GTM4WP's protection intact.
 *
 * Patching the plugin was rejected — the last update moved the line from 378
 * to 86 and would have silently eaten the patch.
 *
 * Kill switch: add_filter( 'rg_gtm4wp_webview_fix_enabled', '__return_false' );
 */
function rg_gtm4wp_should_strip_requested_with() {
    if ( ! apply_filters( 'rg_gtm4wp_webview_fix_enabled', true ) ) {
        return false;
    }

    if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) {
        return false;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return false;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return false;
    }

    return 'xmlhttprequest' !== strtolower( (string) $_SERVER['HTTP_X_REQUESTED_WITH'] );
}

/**
 * Runs before GTM4WP's own callback on the filter.
 *
 * PHP_INT_MIN rather than a low-but-ordinary number on purpose: the plugin's
 * registration priority is not something this theme can see, and a bracket that
 * loses the race would fail silently — the fix would look installed and do
 * nothing.
 */
add_filter( 'gtm4wp_compile_datalayer', 'rg_gtm4wp_remove_requested_with', PHP_INT_MIN );
function rg_gtm4wp_remove_requested_with( $data_layer ) {
    if ( rg_gtm4wp_should_strip_requested_with() ) {
        $GLOBALS['rg_gtm4wp_requested_with'] = $_SERVER['HTTP_X_REQUESTED_WITH'];
        unset( $_SERVER['HTTP_X_REQUESTED_WITH'] );
    }

    return $data_layer;
}

/**
 * Puts the header back as soon as GTM4WP is done with it, so nothing later in
 * the request sees a modified $_SERVER. Klarna and security plugins legitimately
 * detect in-app browsers through this header.
 */
add_filter( 'gtm4wp_compile_datalayer', 'rg_gtm4wp_restore_requested_with', PHP_INT_MAX );
function rg_gtm4wp_restore_requested_with( $data_layer ) {
    if ( isset( $GLOBALS['rg_gtm4wp_requested_with'] ) ) {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $GLOBALS['rg_gtm4wp_requested_with'];
        unset( $GLOBALS['rg_gtm4wp_requested_with'] );
    }

    return $data_layer;
}
