<?php
/**
 * Description tab
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/description.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $post;

?>

<?php the_content(); ?>

<?php
$queried_object = get_queried_object();
$product_information = get_field('product_information', $queried_object);
$additional_text = get_field('product_information_additional_text', $queried_object);

if ( !empty($product_information) ) : ?>

    <div class="rg-row-wrapper">
        <?php foreach($product_information as $column) : ?>

            <div class="rg-column">
                <h3><?php echo $column['title']; ?></h3>
                <ul>
                    <?php if (is_array($column['values']) || is_object($column['values'])) : ?>
                        <?php foreach($column['values'] as $value) : ?>
                                <li>
                                    <?php if (empty($value['link'])) : ?>
                                        <?php echo $value['value']; ?>
                                    <?php else : ?>
                                        <a href="<?php echo $value['link']; ?>"><?php echo $value['value']; ?></a>
                                    <?php endif; ?>
                                </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

        <?php endforeach; ?>
    </div>
    
<?php endif; ?>

<?php if ( !empty($additional_text) ) : ?>
    <p><?php echo $additional_text; ?></p>
<?php endif; ?>