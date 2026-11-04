<?php
/**
 * RG – Slut i lager sist i produktlistor
 *
 * Lägger produkter med lagerstatus "outofstock" (knappen "Slut i lager")
 * sist i alla produktlistor på frontend (kategorisidor, butik, sök, [products]),
 * oavsett vilken sortering kunden valt. Inom respektive grupp behålls
 * den vanliga ordningen (Standardsortering = menu_order, pris, datum osv).
 *
 * Restorder-produkter (onbackorder) visar "Köp" och räknas som köpbara,
 * så de ligger kvar på sin vanliga plats.
 *
 * Efter driftsättning: rensa Kinsta + WP Rocket, samt WooCommerce → Status →
 * Verktyg → "Rensa transienter" så att cachade [products]-listor sorteras om.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'posts_clauses', 'rg_slut_i_lager_sist', 2000, 2 );

function rg_slut_i_lager_sist( $clauses, $query ) {
    global $wpdb;

    // Rör aldrig admin-listor.
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $clauses;
    }

    // Gäller bara produktfrågor.
    $post_type  = $query->get( 'post_type' );
    $is_product = 'product' === $post_type
        || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
        || $query->is_post_type_archive( 'product' )
        || $query->is_tax( get_object_taxonomies( 'product' ) );

    if ( ! $is_product ) {
        return $clauses;
    }

    // Eget alias så att vi aldrig krockar med WooCommerce (pris-/betygssortering)
    // eller filterpluginens join mot samma tabell. En rad per produkt (PK product_id),
    // så joinen ger inga dubbletter.
    $clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} rg_oos_lookup ON {$wpdb->posts}.ID = rg_oos_lookup.product_id ";

    $oos_last = "CASE WHEN rg_oos_lookup.stock_status = 'outofstock' THEN 1 ELSE 0 END ASC";

    $clauses['orderby'] = ! empty( $clauses['orderby'] )
        ? $oos_last . ', ' . $clauses['orderby']
        : $oos_last;

    return $clauses;
}
