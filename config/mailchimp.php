<?php

/*
 * Send the product category to Mailchimp as vendor, used for purchase segments.
 *
 * Report: wp rg-mailchimp-vendors > vendors.csv
 * Send:   wp rg-mailchimp-vendors --queue
 *
 * Run --queue again after renaming or moving a category. Do not use Resync in the
 * Mailchimp plugin for this, it also resyncs all orders.
 */

// The plugin ignores mailchimp_sync_product_vendor unless WC Product Vendors is active
add_filter( 'mailchimp_sync_product', 'rg_mailchimp_set_product_vendor', 10, 2 );
function rg_mailchimp_set_product_vendor( $product, $woo ) {
    if ( $woo instanceof WC_Product ) {
        $product->setVendor( rg_mailchimp_get_product_vendor( $woo->get_parent_id() ?: $woo->get_id() ) );
    }

    return $product;
}

// Get "Main category > Subcategory" for a product, empty if it has no assortment category
function rg_mailchimp_get_product_vendor( $product_id ) {
    $terms = get_the_terms( $product_id, 'product_cat' );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    $paths = array();
    foreach ( $terms as $term ) {
        $path = rg_mailchimp_get_category_path( $term );

        if ( ! empty( $path ) ) {
            $paths[ $term->term_id ] = $path;
        }
    }

    if ( empty( $paths ) ) {
        return '';
    }

    $primary_id = rg_mailchimp_get_primary_category_id( $product_id );

    if ( isset( $paths[ $primary_id ] ) ) {
        $path = $paths[ $primary_id ];
    } else {
        usort( $paths, 'rg_mailchimp_compare_category_paths' );
        $path = $paths[0];
    }

    $names = array();
    foreach ( array_slice( $path, 0, 2 ) as $term ) {
        $names[] = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
    }

    return implode( ' > ', $names );
}

// Get a category with its ancestors, top level first. Empty if it is not an assortment category.
function rg_mailchimp_get_category_path( $term ) {
    $excluded_slugs = array( 'okategoriserad', 'nyheter', 'rea' );

    $path = array( $term );
    foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
        array_unshift( $path, get_term( $ancestor_id, 'product_cat' ) );
    }

    return array_intersect( wp_list_pluck( $path, 'slug' ), $excluded_slugs ) ? array() : $path;
}

// Get primary category set in Yoast
function rg_mailchimp_get_primary_category_id( $product_id ) {
    if ( ! class_exists( 'WPSEO_Primary_Term' ) ) {
        return 0;
    }

    $wpseo_primary_term = new WPSEO_Primary_Term( 'product_cat', $product_id );

    return (int) $wpseo_primary_term->get_primary_term();
}

// Without a primary category: pick by branch, then the deepest category, then the lowest term ID
function rg_mailchimp_compare_category_paths( $a, $b ) {
    return rg_mailchimp_get_category_path_rank( $a ) <=> rg_mailchimp_get_category_path_rank( $b );
}

function rg_mailchimp_get_category_path_rank( $path ) {
    $branch_order = array( 'skydd-bandage', 'rehab-traning', 'ergonomi-halsa', 'vardag-hjalpmedel', 'tillbehor' );
    $branch       = array_search( $path[0]->slug, $branch_order, true );

    return array(
        false === $branch ? count( $branch_order ) : $branch,
        -count( $path ),
        end( $path )->term_id,
    );
}

// The plugin does not sync the product when only its categories change
add_action( 'set_object_terms', 'rg_mailchimp_sync_on_category_change', 10, 6 );
function rg_mailchimp_sync_on_category_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
    if ( 'product_cat' === $taxonomy && ( array_diff( $tt_ids, $old_tt_ids ) || array_diff( $old_tt_ids, $tt_ids ) ) ) {
        rg_mailchimp_queue_product_sync( $object_id );
    }
}

add_action( 'added_post_meta', 'rg_mailchimp_sync_on_primary_category_change', 10, 3 );
add_action( 'updated_post_meta', 'rg_mailchimp_sync_on_primary_category_change', 10, 3 );
add_action( 'deleted_post_meta', 'rg_mailchimp_sync_on_primary_category_change', 10, 3 );
function rg_mailchimp_sync_on_primary_category_change( $meta_id, $object_id, $meta_key ) {
    if ( '_yoast_wpseo_primary_product_cat' === $meta_key ) {
        rg_mailchimp_queue_product_sync( $object_id );
    }
}

function rg_mailchimp_queue_product_sync( $product_id ) {
    if ( function_exists( 'mailchimp_is_configured' ) && mailchimp_is_configured() && 'product' === get_post_type( $product_id ) ) {
        mailchimp_handle_or_queue( new MailChimp_WooCommerce_Single_Product( $product_id ), 5 );
    }
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'rg-mailchimp-vendors', 'rg_mailchimp_vendors_command' );
}

/**
 * Lists the vendor value of each product as CSV, and optionally queues a sync.
 *
 * Covers all products in Mailchimp and all published products, or only --ids.
 * Nothing is sent without --queue. Totals are written to STDERR.
 *
 * ## OPTIONS
 *
 * [--ids=<ids>]
 * : Comma separated product IDs.
 *
 * [--queue]
 * : Queue a Mailchimp product sync for each listed product. Orders are not synced.
 */
function rg_mailchimp_vendors_command( $args, $assoc_args ) {
    if ( ! function_exists( 'mailchimp_is_configured' ) || ! mailchimp_is_configured() ) {
        WP_CLI::error( 'Mailchimp for WooCommerce is not configured.' );
    }

    $queue  = ! empty( $assoc_args['queue'] );
    $totals = array( 'vendor' => 0, 'no_category' => 0, 'not_in_woo' => 0, 'skipped' => 0, 'primary' => 0, 'queued' => 0 );
    $rows   = array();

    if ( isset( $assoc_args['ids'] ) ) {
        $ids = array_filter( array_map( 'trim', explode( ',', $assoc_args['ids'] ) ) );
    } else {
        $ids = array_unique( array_merge( rg_mailchimp_get_synced_product_ids(), get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) ) ) );
    }

    foreach ( $ids as $id ) {
        // Mailchimp also holds deleted_{id} placeholders for products removed from Woo
        $woo = is_numeric( $id ) ? wc_get_product( (int) $id ) : false;

        if ( ! $woo ) {
            $totals['not_in_woo']++;
            $rows[] = array( 'id' => $id, 'status' => '', 'type' => '', 'primary_category' => '', 'vendor' => '' );
            continue;
        }

        $product_id = $woo->get_parent_id() ?: $woo->get_id();
        $vendor     = rg_mailchimp_get_product_vendor( $product_id );
        $primary_id = rg_mailchimp_get_primary_category_id( $product_id );

        $rows[] = array(
            'id'               => $id,
            'status'           => $woo->get_status(),
            'type'             => $woo->get_type(),
            'primary_category' => $primary_id ?: '',
            'vendor'           => $vendor,
        );

        $totals['primary'] += $primary_id ? 1 : 0;

        // The plugin does not sync these statuses
        if ( in_array( $woo->get_status(), array( 'trash', 'auto-draft' ), true ) ) {
            $totals['skipped']++;
            continue;
        }

        $totals[ '' === $vendor ? 'no_category' : 'vendor' ]++;

        if ( $queue ) {
            mailchimp_handle_or_queue( new MailChimp_WooCommerce_Single_Product( $woo->get_id() ) );
            $totals['queued']++;
        }
    }

    WP_CLI\Utils\format_items( 'csv', $rows, array( 'id', 'status', 'type', 'primary_category', 'vendor' ) );

    fwrite( STDERR, sprintf(
        "Total: %d | With vendor: %d | No eligible category: %d | Not in Woo: %d | Trash or auto-draft: %d | Yoast primary set: %d | Queued: %d\n",
        count( $ids ),
        $totals['vendor'],
        $totals['no_category'],
        $totals['not_in_woo'],
        $totals['skipped'],
        $totals['primary'],
        $totals['queued']
    ) );
}

function rg_mailchimp_get_synced_product_ids() {
    $api      = mailchimp_get_api();
    $store_id = mailchimp_get_store_id();
    $count    = 200;
    $page     = 0;
    $ids      = array();

    try {
        do {
            $response = $api->products( $store_id, $page, $count );
            $products = $response['products'] ?? array();
            $ids      = array_merge( $ids, wp_list_pluck( $products, 'id' ) );
            $page++;
        } while ( count( $products ) === $count );
    } catch ( Exception $e ) {
        WP_CLI::error( 'Could not list Mailchimp products: ' . $e->getMessage() );
    }

    return $ids;
}
