<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This templates can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update templates files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the templates file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

/* Queried object */
$queried_object = get_queried_object();

$category_title = get_field('title', $queried_object);
$subtitle = get_field('subtitle', $queried_object);
?>
    <header class="woocommerce-products-header">

        <h1 class="woocommerce-products-header__title page-title"><?php echo (empty($category_title)) ? woocommerce_page_title() : $category_title; ?></h1>
        
        <?php if (!is_paged()) : ?>
        
            <?php if ($subtitle) : ?>
                <h2 class="archive-subtitle"><span><?php echo $subtitle; ?></span></h2>
            <?php endif; ?>

            <?php
            /**
             * Hook: woocommerce_archive_description.
             *
             * @hooked woocommerce_taxonomy_archive_description - 10
             * @hooked woocommerce_product_archive_description - 10
             */
            do_action( 'woocommerce_archive_description' ); ?>
        
        <?php endif; ?>

    </header>

    <!-- Category page - Top products in category - Box 1 -->
    <div id="hr-recom-k6655dfb8f72036773fb2514d"></div>
<?php

if ( woocommerce_product_loop() ) {

    $complementary_subcategories = get_field('sub-categories', get_queried_object());

    // Get subcategories of the current category
    $subcategories = get_terms([
        'taxonomy'    => 'product_cat',
        'hide_empty'  => true,
        'parent'      => get_queried_object_id()
    ]);

    $contains_subcategories = (is_countable($subcategories) && count($subcategories) > 0) || (is_countable($complementary_subcategories) && count($complementary_subcategories) > 0);
    $current_category_id = get_queried_object_id();
    ?>

    <div class="sub-categories-wrapper <?php echo $contains_subcategories ? 'border-top border-bottom' : 'border-top'; ?>">
        <div class="sub-categories">
        
        <?php 
        if ($subcategories) : 
            foreach ($subcategories as $category) : ?>

                <div class="sub-category <?php echo ($category->term_id == $current_category_id) ? 'active' : ''; ?>">
                    <a href="<?php echo get_term_link($category, 'product_cat'); ?>">
                        <?php echo $category->name; ?>
                    </a>
                </div>

        <?php endforeach;
        endif;

        if ($complementary_subcategories) :  
            foreach ($complementary_subcategories as $category) : 
                $title = ($category['text']) ? $category['text'] : $category['category']->name; ?>

                <?php if (isset($category['category']->term_id)) : ?>
                    <div class="sub-category <?php echo ($category['category']->term_id == $current_category_id) ? ' active' : ''; ?>">
                        <a href="<?php echo get_term_link($category['category']->term_id); ?>"><?php echo $title; ?></a>
                    </div>
                <?php endif; ?>

        <?php endforeach;
        endif; ?>
        
        </div>
    </div>

    <?php
    /**
     * Hook: woocommerce_before_shop_loop.
     *
     * @hooked wc_print_notices - 10
     * @hooked woocommerce_result_count - 20
     * @hooked woocommerce_catalog_ordering - 30
     */

    do_action( 'woocommerce_before_shop_loop' );

    woocommerce_product_loop_start();

    if ( wc_get_loop_prop( 'total' ) ) {
        while ( have_posts() ) {
            the_post();

            /**
             * Hook: woocommerce_shop_loop.
             *
             * @hooked WC_Structured_Data::generate_product_data() - 10
             */
            do_action( 'woocommerce_shop_loop' );

            wc_get_template_part( 'content', 'product' );
        }
    }

    woocommerce_product_loop_end();

    /**
     * Hook: woocommerce_after_shop_loop.
     *
     * @hooked woocommerce_pagination - 10
     */
    do_action( 'woocommerce_after_shop_loop' );
} else {
    /**
     * Hook: woocommerce_no_products_found.
     *
     * @hooked wc_no_products_found - 10
     */
    do_action( 'woocommerce_no_products_found' );
}

if (!is_paged()) :
    $description = get_field('full-description', $queried_object);
    if(!empty($description)) : ?>

        <div class="archive-description">
            <div class="description-content">
                <h3 class="archive-subtitle">
                    <span>Mer information <?php echo ($category_title) ? $category_title : woocommerce_page_title(); ?></span>
                </h3>
                <?php echo $description; ?>
            </div>
        </div>

    <?php endif;
endif;

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );

/* Omit closing PHP tag to avoid "Headers already sent" issues. */