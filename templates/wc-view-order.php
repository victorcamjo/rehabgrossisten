<?php
/**
 * WooCommere View Order templates.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       http://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1
 */

// The $order_id is inherited from the Avada_Woocommerce::avada_woocommerce_view_order() method.
$order = wc_get_order( $order_id );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
?>

<?php
