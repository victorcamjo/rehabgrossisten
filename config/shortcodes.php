<?php
/**
 * Created by PhpStorm.
 * User: Victor Bishti
 * Date: 2018-10-18
 * Time: 17:08
 */


/*
 * Additional recommended products
 * [additional_recommended_products]
*/

function additional_recommended_products() {

    $products = get_field('additional_recommended_products', get_queried_object());
    $products_array = get_product_string_from_array($products);

    if (empty($products_array)) {
        return false;
    }

    ob_start(); ?>
    <div>
        <h3 class="archive-subtitle"><span>Fler rekommenderade produkter</span></h3>
    </div>

    <?php echo do_shortcode( '[products ids="' . $products_array . '" columns="4" per_page="4"]', false ); ?>

    <?php
    $product_cat = get_field('additional_recommended_products_category', get_queried_object());
    
    if($product_cat != null && !empty($product_cat) && ! is_wp_error( $product_cat )) : 
    
        $excluded_product_ids = explode(',', $products_array);
        $exclude_recommended_product = get_field('recommended_product', get_queried_object());
        
        if (!empty($exclude_recommended_product)) {
            array_push($excluded_product_ids, strval($exclude_recommended_product));
        }

         $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 12,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $product_cat->slug,
                ),
            ),
            'post__not_in' => $excluded_product_ids,
        );
    
        $products_in_category = new WP_Query($args);
        $products_in_category_array = get_product_string_from_array($products_in_category->posts);

        $product_cat_link = rg_get_term_link_from_field( $product_cat, 'product_cat' );

        if($products_in_category_array && !empty($products_in_category_array) && ! empty( $product_cat_link )) : ?>
            <div class="portfolio-additional-products-category">
                <h3 class="archive-subtitle">
                    <span>Produkter i kategorin <a href="<?php echo esc_url( $product_cat_link ); ?>"><?php echo esc_html( $product_cat->name ); ?></a></span>
                </h3>
            </div>
    
            <?php echo do_shortcode( '[products ids="' . $products_in_category_array . '" columns="4" per_page="12"]', false ); ?>
        <?php endif;
    endif;

    return ob_get_clean();
}
add_shortcode('additional_recommended_products', 'additional_recommended_products');

function output_attribute_products( $atts )
{    
    $input = (shortcode_atts(array(
        'attribute' => 'varumarke',
    ), $atts));

    $queried_object = get_queried_object();

    $shortcode = '[products attribute="' . $input['attribute'] . '" filter="' . $queried_object->slug . '" limit="12" paginate="true"]';
    return do_shortcode($shortcode);
}

add_shortcode('show_attribute_products', 'output_attribute_products');

function get_product_string_from_array($products){
    $products_array = '';

    if (!empty($products)) {
        foreach ($products as $product_id) {
            if ($product_id) {
                $products_array .= $product_id . ',';
            }
        }
        $products_array = rtrim($products_array, ',');
    }

    return $products_array;
}

function rg_get_term_link_from_field( $term, $taxonomy = '' ) {
    if ( empty( $term ) || is_wp_error( $term ) ) {
        return '';
    }

    if ( is_a( $term, 'WP_Term' ) ) {
        $link = get_term_link( $term );
        return is_wp_error( $link ) ? '' : $link;
    }

    $term_id = 0;
    if ( is_object( $term ) && isset( $term->term_id ) ) {
        $term_id = (int) $term->term_id;
        $taxonomy = $taxonomy ?: ( $term->taxonomy ?? '' );
    } elseif ( is_array( $term ) && isset( $term['term_id'] ) ) {
        $term_id = (int) $term['term_id'];
        $taxonomy = $taxonomy ?: ( $term['taxonomy'] ?? '' );
    } elseif ( is_numeric( $term ) ) {
        $term_id = (int) $term;
    }

    if ( $term_id <= 0 ) {
        return '';
    }

    $link = $taxonomy ? get_term_link( $term_id, $taxonomy ) : get_term_link( $term_id );
    return is_wp_error( $link ) ? '' : $link;
}

function rg_get_term_name_from_field( $term, $taxonomy = '' ) {
    if ( empty( $term ) || is_wp_error( $term ) ) {
        return '';
    }

    if ( is_a( $term, 'WP_Term' ) || ( is_object( $term ) && isset( $term->name ) ) ) {
        return (string) $term->name;
    }

    if ( is_array( $term ) && isset( $term['name'] ) ) {
        return (string) $term['name'];
    }

    if ( is_numeric( $term ) ) {
        $term_obj = $taxonomy ? get_term( (int) $term, $taxonomy ) : get_term( (int) $term );
        return is_wp_error( $term_obj ) || ! $term_obj ? '' : $term_obj->name;
    }

    return '';
}

/*
 * Recently Purchased Products
 * [recently_purcased_products]
 *
*/
function recently_purchased_products_func( $atts ) {

    $a = (shortcode_atts(array(
        'number_of_products' => 4,
    ), $atts));

    // Convert to int
    $a['number_of_products'] = intval($a['number_of_products']);

    $args = apply_filters( 'rg_args', array(
        'numberposts'   => 10,
        'orderby'       => 'date',
        'post_status'   => 'wc-completed'
    ) );

    $orders = wc_get_orders( $args );
    $products = [];

    foreach ( $orders as $order ) {
        $items = $order->get_items();

        foreach ( $items as $item ) {

            if (count($products) == $a['number_of_products']) break;

            $product = $item->get_product();
            $is_purchable = $product->is_purchasable() && $product->is_in_stock();

            // if ( !in_array($item->get_product_id(), $products) && $is_purchable ) {
            if ( !in_array($item->get_product_id(), $products)) {
                array_push( $products, $item->get_product_id() );
            }
        }

        if (count($products) == $a['number_of_products']) {
            break;
        }
    }

    $products = count($products) ? $products : [0];
    $products_ids = implode (", ", $products);
    $shortcode = '[products ids="' . $products_ids . '" skus=""]';

    return do_shortcode($shortcode);
}

add_shortcode('recently_purchased_products', 'recently_purchased_products_func');

function render_injury_guide() {

    ob_start(); ?>

    <div class="fusion-title">
        <h3 class="title-heading-left">Välj skada/skydd</h3>
        <div class="title-sep-container">
            <div class="title-sep sep-single sep-solid" style="border-color: #fff;"></div>
        </div>
    </div>

    <div class="injuries">

        <small class="placeholder">Välj kroppsdel först</small>

        <?php

        $posts = get_posts(array(
            'posts_per_page'	=> -1,
            'post_type'			=> 'avada_portfolio',
            'status'            => 'published'
        ));

        if ( $posts ) :
            foreach ( $posts as $post ) :

                // Get slug and title
                $slug = $post->post_name;
                $title = $post->post_title;

                // Get first category of portfolio
                $terms = get_the_terms( $post->ID , 'portfolio_category' );
                $cat = '';
                if ($terms) {
                    $cat = $terms[0]->slug;
                }

                // Get product tag
                $product_tag = get_term_by('slug', $slug, 'product_tag');

                if ($product_tag) :

                    // Get recommended products
                    $recommended_products = get_field('injury_products', $product_tag);

                    if ($recommended_products) :

                        foreach ($recommended_products as $item) :

                            $label = $item['label'];
                            $product = $item['product'];
                            ?>

                            <a href="<?php echo get_permalink( $product->ID ); ?>"
                               class="injury" data-body-part="<?php echo $cat; ?>" style="display: none;">
                            <span class="injury-title">
                                <?php echo $label; ?>
                            </span>
                            </a>

                        <?php endforeach;
                    endif;
                endif;

                // Show default injury page (skadeguiden)
                ?>

                <a href="<?php echo get_site_url() . '/skadeguiden/' . $slug; ?>"
                   class="injury" data-body-part="<?php echo $cat; ?>" style="display: none;">
                    <span class="injury-title" >
                        <?php echo $title; ?>
                    </span>
                </a>

            <?php
            endforeach;
        endif;
        ?>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('injury_guide', 'render_injury_guide');


/*
 * Product Category Card
 * [cat_card category="nacke" title="Nacke & Axel" term="produktkategori"]
 */
function product_cat_card($atts) {

    extract(shortcode_atts(array(
        'category'    => 'nacke-axel',
        'title'       => 'Nacke & Axel',
        'term'        => 'produktkategori',
    ), $atts));

    $category = wp_specialchars($category);
    $title = wp_specialchars($title);
    $term = wp_specialchars($term);

    ob_start();
    ?>

    <div class="category-item liftup-shadow" id="item-<?php echo $category; ?>">
        <a class="category-link" href="<?php echo get_site_url(); ?>/<?php echo $term; ?>/<?php echo $category; ?>/"></a>
        <div class="category-title">
            <h4><?php echo $title; ?></h4>
        </div>

        <picture>
            <source srcset="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/body-parts/<?php echo $category; ?>.webp" type="image/webp">
            <source srcset="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/body-parts/<?php echo $category; ?>.png" type="image/png">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/body-parts/<?php echo $category; ?>.png" width="150" height="150"
                 alt="<?php echo $title; ?>" class="img-responsive wp-image-18793" />
        </picture>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('cat_card', 'product_cat_card');

// Shortcode to display cross-sell
add_shortcode( 'woocommerce-cross-sell', 'cross_sell_shortcode' );
function cross_sell_shortcode(){
    global $avada_woocommerce;
    $avada_woocommerce->cross_sell_display();
}

// [product_carousel categories="241, 2322, 2349, 3630, 249, 563" number_of_products="20"]
function product_carousel( $atts ) {

    $input = (shortcode_atts(array(
        'categories' => 249,
        'number_of_products' => 16
    ), $atts));
    
    $args = array(
        'post_type'         => 'product',
        'post_status'       => 'publish',
        'posts_per_page'    => $input["number_of_products"],
        'orderby'           => 'title',
        'tax_query' => array(
            array(
                'taxonomy'  => 'product_cat',
                'field'     => 'term_id',
                'terms'     => explode(',', $input["categories"]),
                'operator'  => 'IN'
            )
        )
    );
    
    $products = new WP_Query( $args );
    ob_start(); ?>

    <div class="fusion-woo-product-slider fusion-woo-slider">
        <div class="fusion-carousel fusion-carousel-title-below-image" data-metacontent="yes" data-autoplay="no" data-columns="4" data-itemmargin="13" data-itemwidth="180" data-touchscroll="yes" data-imagesize="auto">
            <div class="fusion-carousel-positioner" style="margin-left: -13px;">
                <div class="fusion-carousel-wrapper fusion-carousel-swipe" style="text-align: start; float: none; position: relative; inset: auto; z-index: auto; width: 1112px; height: 390px; padding:0; margin: 0px;">
                    <ul class="fusion-carousel-holder" style="text-align: left; float: none; position: absolute; inset: auto auto auto 0px; margin: 0px; width: 10008px; height: 363.667px; z-index: auto;">
    
    <?php foreach ($products->posts as $product) : 
        $product_object = wc_get_product( $product->get_id() );
        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );
        $title = get_post_meta( $product->get_id(), 'seo-title', true );
        if (empty($title)) {
            $title = $product->post_title;
        }
        ?>
        <li class="fusion-carousel-item" style="margin-left: 13px; width: 265px; overflow: visible;">
            <div class="fusion-classic-product-image-wrapper">
                <div class="fusion-carousel-item-wrapper" style="visibility: inherit;">
                    <div class="fusion-image-wrapper" aria-haspopup="true">
                        <a href="<?php echo get_permalink($product); ?>" aria-label="<?php echo $title; ?>">
                            <img width="850" height="850" src="<?php echo $image[0]; ?>" class="attachment-full size-full wp-post-image lazyautosizes lazyloaded" alt="<?php echo $title; ?>" data-sizes="auto" sizes="263px">
                        </a>
                    </div>
                    <h4 class="fusion-carousel-title product-title"><a href="<?php echo get_permalink($product); ?>" target="_self"><?php echo $title; ?></a></h4>
                    <div class="fusion-carousel-meta">
                        <div class="fusion-carousel-price">
                            <span class="price">
                                <span class="woocommerce-Price-amount amount">
                                    <bdi><?php echo $product_object->get_price(); ?>&nbsp;<span class="woocommerce-Price-currencySymbol">kr</span></bdi>
                                </span>
                                <small class="woocommerce-price-suffix">inkl. moms</small>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
                    </ul>
                </div>
                <div class="fusion-carousel-nav" style="visibility: inherit;">
                    <span class="fusion-nav-prev" style="margin-left: 13px;"></span>
                    <span class="fusion-nav-next" style="left: 1082px;"></span>
                </div>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('product_carousel', 'product_carousel');

function portfolio_featured() {
    $featured_image = get_field('skadeguiden_category_featured_image', get_queried_object());
	$featured_text = get_field('skadeguiden_category_text_input', get_queried_object());

    ob_start();
	if ( $featured_text && $featured_text ) : ?>
		
		<div class="featured-portfolio-wrapper">
			<div class="featured-portfolio">
				<div class="featured-portfolio-image">
					<img src="<?php echo $featured_image['sizes']['fusion-400']; ?>" alt="<?php echo $featured_image['title']; ?>" />
				</div>
				<div class="featured-portfolio-text">
					<?php echo $featured_text; ?>
				</div>
			</div>
		</div>

	<?php endif;
    return ob_get_clean();
}
add_shortcode('portfolio_featured', 'portfolio_featured');


add_shortcode('skadeguiden_verified', 'skadeguiden_get_verified_by');
function skadeguiden_get_verified_by(){

    $verified_by_picture = get_field('verified_by_picture', get_queried_object());
    $verified_by_text = get_field('verified_by_text', get_queried_object());

    if (!$verified_by_text)
    {
        return;
    }

    ob_start(); ?>

    <div class="portfolio-verified-by">
        
        <?php if ($verified_by_picture) : ?>
            <div class="verified-by-image">
                <img src="<?php echo $verified_by_picture['sizes']['medium']; ?>" 
                    alt="<?php echo $verified_by_picture['alt']; ?>" />
            </div>
        <?php endif; ?>

        <div class="verified-by-text" style="<?php echo (!$verified_by_picture) ? 'padding-left: 0;' : ''; ?>">
            <?php echo $verified_by_text; ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('portfolio_meta', 'get_portfolio_meta');
function get_portfolio_meta(){
    global $post;
    
    $last_updated = date('j F, Y', get_the_modified_time('U'));
    $categories = get_the_terms( $post->ID, 'portfolio_category' );
    $category = ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0] : null;
    $category_link = $category ? rg_get_term_link_from_field( $category, 'portfolio_category' ) : '';

    ob_start(); ?>

    <div class="portfolio-meta">
        <small class="last-updated"><?php echo $last_updated; ?></small>
        <?php if ($category_link) : ?>
            <small class="separator">|</small>
            <small>
                <a href="<?php echo esc_url( $category_link ); ?>">
                    Skadeguiden <?php echo esc_html( $category->name ); ?>
                </a>
            </small>
        <?php endif; ?>
    </div>

    <?php 
    return ob_get_clean();
}


add_shortcode('main_category_text', 'get_main_category_text');
function get_main_category_text(){
    $visible_text = get_field('full-description', get_queried_object());
    $hidden_text = get_field('main_category_hidden_text', get_queried_object());

    ob_start(); ?>

    <div class="main-category-text">
        <?php echo $visible_text; ?>

        <?php if ($hidden_text) : ?>
            <a class="read-more" href="#">Läs <span>mer</span>...</a>
            <div class="hidden-text" style="display: none; text-align:center;">
                <?php echo $hidden_text; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('product_overview', 'get_product_overview');
function get_product_overview(){
    $product_categories = get_field('main_category_product_overview', get_queried_object());

    if ( empty( $product_categories ) || ! is_array( $product_categories ) ) {
        return '';
    }

    ob_start(); ?>

    <div class="product-overview">

    <?php foreach($product_categories as $category) : ?>

        <div class="product-overview-category">
            <h3>
                <?php if ( ! empty( $category['subcategory_link'] ) ) : ?>
                    <a href="<?php echo esc_url( $category['subcategory_link'] ); ?>">
                        <?php echo esc_html( $category['subcategory_title'] ?? '' ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( $category['subcategory_title'] ?? '' ); ?>
                <?php endif; ?>
            </h3>
            
            <?php if ( ! empty( $category['product_subcategories'] ) && is_array( $category['product_subcategories'] ) ) : ?>
                <?php
                foreach($category['product_subcategories'] as $subcategory) :
                    $subcategory_obj = $subcategory['product_subcategory'] ?? null;
                    $subcategory_title = ! empty( $subcategory['subcategory_text'] )
                        ? $subcategory['subcategory_text']
                        : rg_get_term_name_from_field( $subcategory_obj, 'product_cat' );
                    $product_link = ! empty( $subcategory['overwrite_link'] )
                        ? $subcategory['overwrite_link']
                        : rg_get_term_link_from_field( $subcategory_obj, 'product_cat' );

                    if ( empty( $subcategory_title ) || empty( $product_link ) ) {
                        continue;
                    }
                    ?>

                    <a class="dark-link alt" href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $subcategory_title ); ?></a>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
        <?php
    endforeach;
    ?>

    </div>
    <?php

    return ob_get_clean();
}


add_shortcode('main_category_products', 'get_main_category_products');
function get_main_category_products(){
    $queried_object = get_queried_object();

    $slug = $queried_object->slug;

    ob_start();
    echo do_shortcode('[product_category category="' . $slug . '" limit="12" columns="4" orderby="menu_order" order="asc" paginate="true"]');
    return ob_get_clean();
}

add_shortcode('review_stars', 'review_stars');
function review_stars( $atts ) {

    $a = (shortcode_atts(array(
        'stars' => 5,
    ), $atts));

    $stars = intval($a['stars']);
    
    if (!$stars || $stars == 0) return;

    ob_start(); ?>

    <div class="review-stars">

    <?php
    for($i = 0; $i < $stars; $i++) : ?>
        <i class="fas fa-star"></i>
    <?php endfor; ?>

    </div>

    <?php
    return ob_get_clean();
}


add_shortcode('product_subcategories', 'product_subcategories');

function product_subcategories(){
    $subcategories = get_field('sub-categories', get_queried_object()); 

    ob_start();
    ?>

    <div class="sub-categories-wrapper <?php echo is_countable($subcategories) && count($subcategories) > 0 ? 'border-top border-bottom' : 'border-top'; ?>">

    <?php if ($subcategories) : ?>

        <div class="sub-categories">

        <?php
        foreach ($subcategories as $category) : 
            $category_obj = $category['category'] ?? null;
            $title = ! empty( $category['text'] ) ? $category['text'] : rg_get_term_name_from_field( $category_obj, 'product_cat' );
            $category_link = rg_get_term_link_from_field( $category_obj, 'product_cat' );

            if ( empty( $title ) || empty( $category_link ) ) {
                continue;
            }
            ?>

            <div class="sub-category">
                <a href="<?php echo esc_url( $category_link ); ?>"><?php echo esc_html( $title ); ?></a>
            </div> 

    <?php endforeach; ?>

        </div>

    <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
}


add_shortcode('show_extra_information_skadeguiden', 'render_extra_information_skadeguiden');

function render_extra_information_skadeguiden(){

    $queried_object = get_queried_object();
    $show = get_field('show_extra_information', $queried_object);

    if ($show != 'show') {
        return;
    }

    $title = get_field('skadeguiden_extra_information_title', $queried_object);    
    $text = get_field('skadeguiden_extra_information_text', $queried_object);
    ob_start(); ?>
    <div class="skadeguiden-extra-information-wrapper">
        <h2 class="archive-subtitle">
            <span><?php echo $title; ?></span>
        </h2>
        <?php echo $text; ?>
    </div>
    <?php

    return ob_get_clean();
}

function custom_aspect_ratio_box_shortcode($atts) {

    $atts = shortcode_atts(array(
        'id' => 0
    ), $atts, 'aspect_ratio_box');

    if ($atts['id'] == 0) return;

    $aspect_ratio_boxes = get_field('aspect_ratio_boxes');

    $found = false;
    $index = 0;
    foreach($aspect_ratio_boxes as $aspect_ratio_box) {
        if ($aspect_ratio_box['id'] == $atts['id']) {
            $found = true;
            break;
        }

        $index++;
    }

    if ($found === false) return;

    $title = $aspect_ratio_boxes[$index]['title'];
    $description = $aspect_ratio_boxes[$index]['description'];
    $background_image = $aspect_ratio_boxes[$index]['background_image'];
    $aspect_ratio = $aspect_ratio_boxes[$index]['aspect_ratio'];
    $link = $aspect_ratio_boxes[$index]['link'];
    $link_text = $aspect_ratio_boxes[$index]['link_text'];
    $overlay_opacity = $aspect_ratio_boxes[$index]['overlay_opacity'];
    $overlay_opacity = ($overlay_opacity > 0) ? $overlay_opacity / 100 : 0;

    // TODO: Move CSS

    ob_start(); ?>
    <style>
        /* Aspect-ratio Image Box */
        .aspect-ratio-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 0;
        }

        .aspect-ratio-container .image-container {
            position: relative;
            width: 100%;
            padding-top: 71.4%;
            background-size: cover;
            background-position: center;
        }

        .aspect-ratio-container .aspect-ratio-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .aspect-ratio-container .text-content {
            position: absolute;
            top: 50%;
            left: 5%;
            transform: translateY(-50%);
            color: white;
            text-align: left;
            padding: 20px 0;
            width: 90%;
            z-index: 2;
        }
        .aspect-ratio-container .text-content h3 {
            color: #fff;
            font-size: 28px;
            margin-bottom: 10px;
            margin-top: 0;
        }

        @media (max-width: 767px) {
            .aspect-ratio-container .image-container {
                height: auto !important;
            }
        }
        @media (min-width: 768px) {
            .aspect-ratio-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                max-width: 1400px;
            }

            .aspect-ratio-container .image-container {
                width: 50%;
                padding-top: 0 !important;
                position: relative;
                flex-basis: 700px;
                height: calc(700px * 0.714);
            }

            .aspect-ratio-container .text-content {
                position: relative;
                width: 50%;
                top: auto;
                left: auto;
                padding: 40px 20px 20px;
                text-align: left;
                transform: none;
                float: right;
                z-index: 2; 
            }
        }
    </style>
    <div class="aspect-ratio-container">
        <div class="image-container" style="background-image: url('<?php echo esc_url($background_image); ?>'); padding-top: <?php echo esc_html($aspect_ratio); ?>%; height: calc(700px * <?php echo esc_html(($aspect_ratio / 100)); ?>)">
            <div class="aspect-ratio-overlay" style="background-color: rgba(0, 0, 0, <?php echo esc_attr($overlay_opacity); ?>);"></div>
            <div class="text-content">
                <h3><?php echo esc_html($title); ?></h3>
                <p><?php echo esc_html($description); ?></p>
                <a class="fusion-button button-flat fusion-button-default-size button-default fusion-button-default button-1 fusion-button-default-span fusion-button-default-type" target="_self" href="<?php echo esc_url($link); ?>">
                    <span class="fusion-button-text"><?php echo esc_html($link_text); ?></span>
                </a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('aspect_ratio_box', 'custom_aspect_ratio_box_shortcode');
