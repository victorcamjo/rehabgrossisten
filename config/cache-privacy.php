<?php

/**
 * Cache safety for WooCommerce/Kustom checkout sessions.
 *
 * A cached HTML response must never carry WooCommerce customer/session cookies.
 * If it does, separate visitors can be assigned the same session before they
 * reach the embedded Kustom checkout.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

if ( ! defined( 'RG_DISABLE_PUBLIC_PAGE_CACHE' ) ) {
    define( 'RG_DISABLE_PUBLIC_PAGE_CACHE', true );
}

function rg_cache_private_cookie_prefixes() {
    return array(
        'wp_woocommerce_session_',
        'woocommerce_cart_hash',
        'woocommerce_items_in_cart',
        'wordpress_logged_in_',
    );
}

function rg_request_has_private_cookie() {
    foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
        foreach ( rg_cache_private_cookie_prefixes() as $prefix ) {
            if ( strpos( $cookie_name, $prefix ) === 0 ) {
                return true;
            }
        }
    }

    return false;
}

function rg_is_private_woocommerce_request() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path        = wp_parse_url( $request_uri, PHP_URL_PATH );
    $path        = is_string( $path ) ? trailingslashit( $path ) : '/';

    $private_paths = array(
        '/kassan/',
        '/varukorg/',
        '/mitt-konto/',
        '/tillagd-produkt/',
    );

    foreach ( $private_paths as $private_path ) {
        if ( strpos( $path, $private_path ) === 0 ) {
            return true;
        }
    }

    if ( isset( $_GET['wc-ajax'] ) || isset( $_GET['add-to-cart'] ) ) {
        return true;
    }

    if ( strpos( $path, '/wp-json/wc/' ) === 0 || strpos( $path, '/wp-json/wc-' ) === 0 ) {
        return true;
    }

    return rg_request_has_private_cookie();
}

function rg_mark_request_uncacheable() {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
}

function rg_should_bypass_page_cache() {
    if ( is_admin() ) {
        return false;
    }

    if ( rg_is_private_woocommerce_request() ) {
        return true;
    }

    if ( RG_DISABLE_PUBLIC_PAGE_CACHE && isset( $_SERVER['REQUEST_METHOD'] ) ) {
        return in_array( strtoupper( $_SERVER['REQUEST_METHOD'] ), array( 'GET', 'HEAD' ), true );
    }

    return false;
}

add_action( 'init', function() {
    if ( rg_should_bypass_page_cache() ) {
        rg_mark_request_uncacheable();
    }
}, 0 );

add_action( 'send_headers', function() {
    if ( rg_should_bypass_page_cache() ) {
        nocache_headers();
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
        header( 'Pragma: no-cache', true );
    }
}, 0 );

add_filter( 'kinsta_cache_bypass', function( $bypass ) {
    return $bypass || rg_should_bypass_page_cache();
} );

add_filter( 'rocket_cache_reject_uri', function( $uris ) {
    $uris = is_array( $uris ) ? $uris : array();

    return array_values( array_unique( array_merge( $uris, array(
        '/kassan/(.*)',
        '/varukorg/(.*)',
        '/mitt-konto/(.*)',
        '/tillagd-produkt/(.*)',
        '/wp-json/wc/(.*)',
        '/wp-json/wc-(.*)',
        '(.*)wc-ajax=(.*)',
        '(.*)add-to-cart=(.*)',
    ) ) ) );
} );

add_filter( 'rocket_cache_reject_cookies', function( $cookies ) {
    $cookies = is_array( $cookies ) ? $cookies : array();

    return array_values( array_unique( array_merge( $cookies, rg_cache_private_cookie_prefixes() ) ) );
} );

function rg_kustom_optimization_exclusions() {
    return array(
        'kustom',
        'klarna',
        'kco',
        'js.klarna.com',
        'js.playground.klarna.com',
        'cdn.klarna.com',
        'x.klarnacdn.net',
        'kustomcheckout.com',
        'krokedil',
    );
}

add_filter( 'rocket_delay_js_exclusions', function( $exclusions ) {
    $exclusions = is_array( $exclusions ) ? $exclusions : array();

    return array_values( array_unique( array_merge( $exclusions, rg_kustom_optimization_exclusions() ) ) );
} );

add_filter( 'rocket_defer_js_exclusions', function( $exclusions ) {
    $exclusions = is_array( $exclusions ) ? $exclusions : array();

    return array_values( array_unique( array_merge( $exclusions, rg_kustom_optimization_exclusions() ) ) );
} );

add_filter( 'rocket_exclude_js', function( $exclusions ) {
    $exclusions = is_array( $exclusions ) ? $exclusions : array();

    return array_values( array_unique( array_merge( $exclusions, rg_kustom_optimization_exclusions() ) ) );
} );
