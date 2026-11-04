<?php
/**
 * Single product title.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       http://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1.0
 */

$seo_title = get_field('seo-title', get_queried_object());

if ( empty($seo_title) ) : ?>

<h1 itemprop="name" class="product_title entry-title"><?php the_title(); ?></h1>

<?php else : ?>

<h1 itemprop="name" class="product_title entry-title"><?php echo $seo_title; ?></h1>

<?php endif; ?>