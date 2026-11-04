<?php
/**
 * The product title.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       http://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1.0
 */

global $product;
$id = $product->get_id();
$seo_title = "";
if (function_exists('get_field')) {
    $seo_title = get_field('seo-title', $id);
}
$title = (empty($seo_title)) ? get_the_title() : $seo_title;

?>
<h3 class="product-title">
    <a href="<?php echo esc_url_raw( get_the_permalink() ); ?>">
        <?php echo $title; ?>
    </a>
</h3>
<div class="fusion-price-rating">