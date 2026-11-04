<?php


// Enables debugging in browser
function console_log( $data = null ){
    if (empty($data)) {
       return;
    }
    echo '<script>';
    echo 'console.log('. json_encode( $data ) .')';
    echo '</script>';
}

/**
 * Clear Cache from WP Rocket on proudct update
 * Used to clear cache when product is updated through REST API
 */
add_action('woocommerce_update_product', 'clear_cache_product_update', 10, 2);
function clear_cache_product_update($product_id, $product){
    clear_cache_wp_rocket($product_id);
}

add_action('woocommerce_product_set_stock', 'clear_cache_stock_update', 1);
add_action('woocommerce_variation_set_stock', 'clear_cache_stock_update', 1);
function clear_cache_stock_update( $product ){
    $product_id = $product->get_id();
    clear_cache_wp_rocket($product_id);
}

function clear_cache_wp_rocket( $product_id ){
    if ( !empty($product_id) && function_exists( 'rocket_clean_post' ) ) {
        rocket_clean_post( $product_id );
    }
} 

// add the action
// add_action( 'woocommerce_cart_updated', 'action_woocommerce_cart_updated', 10, 0 );

add_filter( 'woocommerce_get_order_item_totals', 'reordering_order_item_totals', 10, 3 );
function reordering_order_item_totals( $total_rows, $order, $tax_display ){
    
    // 1. saving the values of items totals to be reordered
    $cart_subtotal = array(
        'label' => __( 'Delsumma:' ),
        'value' => wc_price($order->get_total() - $order->get_shipping_total()  - $order->get_shipping_tax())
    );
    // $total_rows['cart_subtotal'];
    $payment_method = $total_rows['payment_method'];
    $shipping = $total_rows['shipping'];
    $order_total = $total_rows['order_total'];

    $out_of_stock = false;
    foreach ($order->get_items() as $item_id => $item_data) {
        $product = $item_data->get_product();
        $qty = $item_data->get_quantity();
        $stock_qty = $product->get_stock_quantity();

        if (($stock_qty + $qty) < $qty) {
            $out_of_stock = true;
            break;
        }
    }

    $delivery = array(
        'label' => __( 'Beräknad leveranstid:', 'woocommerce' ),
        'value' => ($out_of_stock) ? '7-10 arbetsdagar från orderdatum' : '1-3 arbetsdagar från orderdatum',
    );

    // 2. remove items totals to be reordered
    unset($total_rows['cart_subtotal']);
    unset($total_rows['payment_method']);
    unset($total_rows['shipping']);
    unset($total_rows['order_total']);

    // 3 Reinsert removed items totals in the right order
    $total_rows['cart_subtotal'] = $cart_subtotal;
    $total_rows['payment_method'] = $payment_method;
    $total_rows['shipping'] = $shipping;
    $total_rows['delivery'] = $delivery;
    $total_rows['order_total'] = $order_total;

    return $total_rows;
}

/* Exclude uncategorized products from shop and archive */
add_filter('woocommerce_product_query_tax_query', 'exclude_product_category_in_tax_query', 10, 2 );
function exclude_product_category_in_tax_query( $tax_query, $query ) {
    if( is_admin() ) return $tax_query;

    // Uncategorized term ID
    $terms = array( 551 ); // Term ID

    // The taxonomy for Product Categories custom taxonomy
    $taxonomy = 'product_cat';

    $tax_query[] = array(
        'taxonomy' => $taxonomy,
        'field'    => 'term_id', // Or 'slug' or 'name'
        'terms'    => $terms,
        'operator' => 'NOT IN', // Excluded
        'include_children' => true // (default is true)
    );

    return $tax_query;
}

// Remove URL field from comments form
add_filter('comment_form_default_fields', 'website_remove');
function website_remove($fields)
{
    if (isset($fields['url']))
        unset($fields['url']);
    return $fields;
}

/* Change Size of WooCommerce Gallery Thumbnails */
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', function( $size ) {
    return array(
        'width' => 150,
        'height' => 150,
        'crop' => 0,
    );
} );


// Provide archive portfolio templates via filter.
function portfolio_archive_template( $archive_post_template ) {
    $archive_portfolio_template = dirname( __FILE__ ) . '/templates/archive-avada_portfolio.php';

    // Checks if the archive is portfolio.
    if ( is_post_type_archive( 'avada_portfolio' )
        || is_tax( 'portfolio_category' )
        || is_tax( 'portfolio_skills' )
        || is_tax( 'portfolio_tags' ) ) {
        if ( file_exists( $archive_portfolio_template ) ) {
            if ( function_exists( 'fusion_portfolio_scripts' ) ) {
                fusion_portfolio_scripts();
            }
            return $archive_portfolio_template;
        }
    }
    return $archive_post_template;
}
remove_filter( 'archive_template', 'fusion_portfolio_archive_template', 10 );
add_filter( 'archive_template', 'portfolio_archive_template', 20 );

/* Show Product Excrept */
function woocommerce_after_shop_loop_item_title_short_description() {
    global $product;
    if ( ! $product->post->post_excerpt ) return;

    $excerpt = apply_filters( 'woocommerce_short_description', $product->post->post_excerpt ); // Get full excerpt
    $excerpt = strip_tags(strip_shortcodes($excerpt)); // Strip excerpt from tags and shortcodes
    $excerpt = wp_trim_words($excerpt, 20, "..."); // Trim excerpt length
    $excerpt = preg_replace('/(.*?[?!.](?=\s|$)).*/', '\\1', $excerpt ); // Filter first sentence
    ?>
    <div itemprop="description" class="description-excerpt">
        <?php echo $excerpt; ?>
    </div>
    <?php
}
add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_after_shop_loop_item_title_short_description', 5);

function rg_add_features(){
    $queried_object = get_queried_object();
    $features = get_field('features', $queried_object);
    $feature_image = get_field('feature_image', $queried_object);

    ob_start();
    if ($features || $feature_image) : ?>
        <div class="feature-container">
            <?php if($features && is_array($features)) : ?>
                <ul class="feature-list">
                    <?php foreach($features as $item) : ?>
                        <li><?php echo $item['feature']; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if($feature_image && is_array($feature_image)) : ?>
                <div class="feature-image">
                    <img src="<?php echo (!empty($feature_image['sizes']['fusion-400'])) ? $feature_image['sizes']['fusion-400'] : $feature_image['url']; ?>" 
                        alt="<?php echo $feature_image['name']; ?>" />
                </div>
            <?php endif; ?>
        </div>
    <?php endif;
    echo ob_get_clean();
}
add_action('woocommerce_before_add_to_cart_form', 'rg_add_features');

if ( ! function_exists( 'woocommerce_default_product_tabs' ) ) {

    /**
     * Add default product tabs to product pages.
     *
     * @param array $tabs
     * @return array
     */
    function woocommerce_default_product_tabs( $tabs = array() ) {
        global $product, $post;

        // Product Video tab - shows Youtube or Vimeo video of product
        if ( !empty( get_field('product_video', get_queried_object()) ) ) {
            $tabs['produktvideo'] = array(
                'title'    => 'Produktvideo',
                'priority' => 10,
                'callback' => 'video_template',
            );
        }

        // Description tab - shows product content
        if ( $post->post_content ) {
            $tabs['description'] = array(
                'title'    => __( 'Description', 'woocommerce' ),
                'priority' => 20,
                'callback' => 'woocommerce_product_description_tab',
            );
        }

        // Reviews tab - shows comments
        if ( comments_open() ) {
            $tabs['reviews'] = array(
                'title'    => sprintf( __( 'Reviews (%d)', 'woocommerce' ), $product->get_review_count() ),
                'priority' => 30,
                'callback' => 'comments_template',
            );
        }

        // Additional information tab - shows attributes
        $tabs['additional_information'] = array(
            'title'    => 'Vanliga frågor',
            'priority' => 40,
            'callback' => 'woocommerce_product_additional_information_tab',
        );

        return $tabs;
    }
}

function video_template() {

    $queried_object = get_queried_object();
    $video_obj = get_field('product_video', $queried_object);

    if (empty($video_obj)) {
        return false;
    }

    foreach ($video_obj as $video) : ?>

        <div class="product-video-container">

        <?php if ($video['video_service'] == 'youtube') : ?>

                <iframe class="product-video-frame" width="1018" height="599" src="https://www.youtube.com/embed/<?php echo $video['video_src']; ?>?rel=0" frameborder="0" allowfullscreen></iframe>

            <?php elseif($video['video_service'] == 'vimeo') : ?>

                <iframe class="product-video-frame" src="https://player.vimeo.com/video/<?php echo $video['video_src']; ?>?byline=0&portrait=0" width="1018" height="608" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>

        <?php endif; ?>

        </div>

    <?php endforeach;
}

// add_filter( 'woocommerce_variation_is_active', 'bbloomer_grey_out_variations_out_of_stock', 10, 2 );
 
function bbloomer_grey_out_variations_out_of_stock( $is_active, $variation ) {

    if ( ! $variation->is_in_stock() ) return false;
    return $is_active;
}

if ( ! function_exists( 'wc_dropdown_variation_attribute_options' ) ) {

    /**
     * Output a list of variation attributes for use in the cart forms.
     *
     * @param array $args
     * @since 2.4.0
     */
    function wc_dropdown_variation_attribute_options( $args = array() ) {
        $args = wp_parse_args( apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ), array(
            'options'          => false,
            'attribute'        => false,
            'product'          => false,
            'selected' 	       => false,
            'name'             => '',
            'id'               => '',
            'class'            => '',
            'show_option_none' => __( 'Choose an option', 'woocommerce' ),
        ) );

        $options               = $args['options'];
        $product               = $args['product'];
        $attribute             = $args['attribute'];
        $name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
        $id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
        $class                 = $args['class'];
        $show_option_none      = $args['show_option_none'] ? true : false;
        $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

        if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
            $attributes = $product->get_variation_attributes();
            $options    = $attributes[ $attribute ];
        }

        $html = '<select style="width: 100%; padding: 9px 15px; font-family: Karla,sans-serif; color: #494949; font-size: 15px;" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
        $html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

        if ( ! empty( $options ) ) {
            if ( $product && taxonomy_exists( $attribute ) ) 
            {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

                // Get all product variations
                $variations = $product->get_available_variations();

                foreach ( $terms as $term ) {

                    $stock_content = '';

                    // Can't trust the option value if more than one attribute is used
                    // (terms not the same amount as variations)
                    if (count($terms) == count($variations))
                    {
                        foreach ($variations as $variation) {
                            if( $variation['attributes'][$name] == $term->slug ) {
                                $stock = $variation['is_in_stock'];
                                $backorders_allowed = $variation['backorders_allowed'];
    
                                if ($stock) break;
                            }
                        }
                        if (isset($stock) && isset($backorders_allowed)) {
                            if( $stock == false && $backorders_allowed == false) {
                                $stock_content = ' - Slut i lager (Notifiera mig)';
                            }
                        }                        
                    }
                    

                    if ( in_array( $term->slug, $options ) ) {
                        $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) . $stock_content ) . '</option>';
                    }
                }
            } 
            else 
            {
                foreach ( $options as $option ) {

                    // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                    $selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );

                    $html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
                }
            }
        }

        $html .= '</select>';

        echo apply_filters( 'woocommerce_dropdown_variation_attribute_options_html', $html, $args );
    }
}

/*
 * === Return Related Portfolios (skador) ===
 */
function get_related_portfolios($post){
    $custom_taxterms = wp_get_object_terms( $post->ID, 'portfolio_category', array('fields' => 'ids') );

    $args = array(
        'post_type' => 'avada_portfolio',
        'post_status' => 'publish',
        'posts_per_page' => -1, // you may edit this number
        'orderby' => 'DESC',
        'post__not_in' => array ( $post->ID ),
        'tax_query' => array(
            array(
                'taxonomy' => 'portfolio_category',
                'field' => 'id',
                'terms' => $custom_taxterms[0]
            )
        )
    );

    $related_items = new WP_Query( $args );
    return $related_items;
}

/*
 *
 * Return HTML-list of related portfolios
 * Shortcode
 *
 */
add_shortcode( 'related_portfolio', 'render_related_portfolios' );
function render_related_portfolios( $atts ) {
    global $post;

    extract(shortcode_atts(array(
        'sidebar'   => 'yes',
        'class'     => '' 
    ), $atts));

    $view = (wp_specialchars($sidebar) == 'yes') ? true : false;
    $class = wp_specialchars($class);

    $related_items = get_related_portfolios($post);
    $custom_taxterms = wp_get_object_terms( $post->ID, 'portfolio_category', array('fields' => 'ids') );
    $category = get_term($custom_taxterms[0]);
    $category_link = get_term_link($custom_taxterms[0]);

    if ($related_items->have_posts()) :

        ob_start(); ?>

        <div class="relaterade-skador <?php echo $class; ?> <?php echo ($view) ? 'y-view' : 'x-view'; ?>">
            <h4 class="title-heading-left">
                Andra skador 
                <a class="dark-link" href="<?php echo $category_link; ?>"><?php echo $category->name; ?></a>
            </h4>
            <ul class="fusion-checklist fusion-checklist-1">

    <?php foreach($related_items->posts as $current_post) : ?>

                <li class="fusion-li-item">
                    <span class="icon-wrapper circle-no">
                        <i class="fusion-li-icon fa fa-chevron-right"></i>
                    </span>
                    <div class="fusion-li-item-content">
                        <a class="link-underline" href="<?php echo get_permalink($current_post->ID); ?>">
                            <?php echo $current_post->post_title; ?>
                        </a>
                    </div>
                 </li>

    <?php endforeach; ?>

            </ul>
        </div>

    <?php endif;

    return ob_get_clean();
}

/*
 * Loop through all products with the given criteria
 * Return title and name if product are found
 */

add_action( 'wp_ajax_nopriv_get_matched_product', 'get_matched_product' );
add_action( 'wp_ajax_get_matched_product', 'get_matched_product' );

function get_matched_product() {
    $values = $_POST['values'];

    $chosen_active_attribute = $values['pa_aktiv'];
    $product_tag = get_term_by('slug', $values['product_tag'], 'product_tag');
    $active_items = get_field('active_items', $product_tag);

    // Initilize variables
    $is_active = false;
    $product_title = $product_link = '';
    $item2 = '';

    if (!empty($active_items)) {
        foreach($active_items as $item) {

            if ( $item['active'] != 'n/a' ) {
                $is_active = true;
            }

            // If chosen attribute equals product tag attribute or if it's not applicable
            if ($item['active'] == $chosen_active_attribute || $item['active'] == 'n/a') {

                $is_active = ($item['active'] == 'n/a') ? false : true;
                $product = $item['product'];
                $active_attribute = $item['active'];

                if(!empty($product)) {
                    $product_link = get_permalink( $product->get_id() );
                    $product_title = $product->post_title;
                }

                // Break loop if item is found
                break;
            }
        }
    }

    $response = (object) [
        'product_title' => $product_title,
        'product_link' => $product_link,
        'active_attribute' => $active_attribute,
        'is_active' => $is_active,
        'active_items' => $active_items
    ];

    wp_send_json($response);
    wp_die();
}

/*
 * Update Cart Items Count
 *
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'iconic_cart_count_fragments', 10, 1 );
function iconic_cart_count_fragments( $fragments ) {
    $fragments['div.cart-header span.cart-count'] = '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
    $fragments['div.cart-wrapper span.cart-count'] = '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
    return $fragments;
}

/*
 * Set Initial Cart Items Count
 * Important When Refresing page
 */
add_shortcode( 'cart_count', 'get_cart_count' );
function get_cart_count() {
    $cart_count = 0;

    if (
        class_exists( 'WooCommerce' ) &&
        function_exists( 'rg_request_has_private_cookie' ) &&
        rg_request_has_private_cookie() &&
        WC()->cart
    ) {
        $cart_count = WC()->cart->get_cart_contents_count();
    }

    return $cart_count;
}

// Always display rating stars
function filter_woocommerce_product_get_rating_html( $rating_html, $rating, $count ) {
    $rating_html  = '<div class="star-rating">';
    $rating_html .= wc_get_star_rating_html( $rating, $count );
    $rating_html .= '</div>';

    return $rating_html;
};
add_filter( 'woocommerce_product_get_rating_html', 'filter_woocommerce_product_get_rating_html', 10, 3 );

/**
 * Trim zeros in price decimals
 **/
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

/**
 * Show a given widget based on it's id and it's sidebar index
 *
 * Example: wpse_show_widget( 'sidebar-1', 'calendar-2' )
 *
 * @param string $index. Index of the sidebar where the widget is placed in.
 * @param string $id. Id of the widget.
 * @return boolean. TRUE if the widget was found and called, else FALSE.
 */
function wpse_show_widget( $sidebar_name, $widget_name )
{
    global $wp_registered_widgets, $wp_registered_sidebars;
    $did_one = FALSE;

    // Check if $id is a registered widget
    if( ! isset( $wp_registered_widgets[$widget_name] )
        || ! isset( $wp_registered_widgets[$widget_name]['params'][0] ) )
    {
        return FALSE;
    }

    // Check if $sidebar_name is a registered sidebar
    $sidebars_widgets = wp_get_sidebars_widgets();
    if ( empty( $wp_registered_sidebars[ $sidebar_name ] )
        || empty( $sidebars_widgets[ $sidebar_name ] )
        || ! is_array( $sidebars_widgets[ $sidebar_name ] ) )
    {
        return FALSE;
    }

    // Construct $params
    $sidebar = $wp_registered_sidebars[$sidebar_name];
    $params = array_merge(
        array( array_merge( $sidebar, array('widget_id' => $widget_name, 'widget_name' => $wp_registered_widgets[$widget_name]['name']) ) ),
        (array) $wp_registered_widgets[$widget_name]['params']
    );

    // Substitute HTML id and class attributes into before_widget
    $classname_ = '';
    foreach ( (array) $wp_registered_widgets[$widget_name]['classname'] as $cn )
    {
        if ( is_string($cn) )
            $classname_ .= '_' . $cn;
        elseif ( is_object($cn) )
            $classname_ .= '_' . get_class($cn);
    }
    $classname_ = ltrim($classname_, '_');
    $params[0]['before_widget'] = sprintf($params[0]['before_widget'], $widget_name, $classname_);
    $params = apply_filters( 'dynamic_sidebar_params', $params );

    // Run the callback
    $callback = $wp_registered_widgets[$widget_name]['callback'];
    if ( is_callable( $callback ) )
    {
        call_user_func_array( $callback, $params );
        $did_one = TRUE;
    }

    return $did_one;
}

function widget($atts) {

    extract(shortcode_atts(array(
        'sidebar_name' => FALSE,
        'widget_name' => FALSE
    ), $atts));

    $sidebar_name = wp_specialchars($sidebar_name);
    $widget_name = wp_specialchars($widget_name);

    wpse_show_widget($sidebar_name, $widget_name);
}
add_shortcode('widget','widget');

/**
 * Call a shortcode function by tag name.
 *
 * @author J.D. Grimes
 * @link https://codesymphony.co/dont-do_shortcode/
 *
 * @param string $tag     The shortcode whose function to call.
 * @param array  $atts    The attributes to pass to the shortcode function. Optional.
 * @param array  $content The shortcode's content. Default is null (none).
 *
 * @return string|bool False on failure, the result of the shortcode on success.
 */
function do_shortcode_func( $tag, array $atts = array(), $content = null ) {

    global $shortcode_tags;

    if ( ! isset( $shortcode_tags[ $tag ] ) )
        return false;

    return call_user_func( $shortcode_tags[ $tag ], $atts, $content, $tag );
}

if ( ! function_exists( 'woocommerce_get_product_thumbnail' ) ) {
    /**
     * Get the product thumbnail, or the placeholder if not set.
     *
     * @subpackage Loop
     * @param string $size (default: 'shop_catalog')
     * @param int $deprecated1 Deprecated since WooCommerce 2.0 (default: 0)
     * @param int $deprecated2 Deprecated since WooCommerce 2.0 (default: 0)
     * @return string
     */
    function woocommerce_get_product_thumbnail( $size = 'shop_catalog', $deprecated1 = 0, $deprecated2 = 0 ) {
        global $post;
        if ( has_post_thumbnail() ) {
            return '<a href="' . get_permalink( $post->ID ) . '">' . get_the_post_thumbnail( $post->ID, $size ) . '</a>';
        } elseif ( wc_placeholder_img_src() ) {
            return wc_placeholder_img( $size );
        }
    }
}

/* Change to SEO title if exist and send to Google Product Feed */
function lw_woocommerce_gpf_title( $title, $product_id ) {

    $seo_title = get_post_meta( $product_id, 'seo-title', true );

    if (!empty($seo_title)) {
        $title = $seo_title;
    }

    return $title;
}
add_filter( 'woocommerce_gpf_title', 'lw_woocommerce_gpf_title', 10, 2 );

/**
 * Fall back to WooCommerce's own GTIN field in the Google Product Feed.
 *
 * Google Product Feed only reads its own per-product field
 * (_woocommerce_gpf_data['gtin']). The EAN that the Business Central sync
 * writes lands in WooCommerce's standard "GTIN, UPC, EAN or ISBN" field
 * (global_unique_id, WC 9.2+), which GPF never looks at — so products that
 * only have the BC value end up with no <g:gtin> at all.
 *
 * This fills the gap: GPF's own value always wins, and only when it is empty
 * do we use global_unique_id (variation first, then parent). Values that are
 * not a valid GTIN-8/12/13/14 are skipped rather than sent — Google prefers an
 * empty field over an invalid identifier.
 */
function lw_woocommerce_gpf_gtin_fallback( $elements, $product_id, $variation_id = null ) {

    if ( ! empty( $elements['gtin'] ) ) {
        return $elements;
    }

    foreach ( array( $variation_id, $product_id ) as $id ) {

        if ( empty( $id ) ) {
            continue;
        }

        $product = wc_get_product( $id );

        if ( ! $product || ! is_callable( array( $product, 'get_global_unique_id' ) ) ) {
            continue;
        }

        $gtin = lw_normalise_gtin( $product->get_global_unique_id() );

        if ( $gtin ) {
            $elements['gtin'] = array( $gtin );
            break;
        }
    }

    return $elements;
}
add_filter( 'woocommerce_gpf_elements', 'lw_woocommerce_gpf_gtin_fallback', 20, 3 );

/**
 * Validate a GTIN and return it in the form Google expects.
 *
 * Accepts GTIN-8, UPC-A (12), EAN-13 and GTIN-14 and verifies the GS1
 * mod-10 check digit. Returns an empty string for anything else, so a bad
 * value in the ERP never reaches Merchant Center.
 *
 * @param  string $value Raw value from the product.
 * @return string        A valid GTIN, or '' when the value is not one.
 */
function lw_normalise_gtin( $value ) {

    $digits = preg_replace( '/[^0-9]/', '', (string) $value );

    if ( ! in_array( strlen( $digits ), array( 8, 12, 13, 14 ), true ) ) {
        return '';
    }

    $reversed = strrev( $digits );
    $sum      = 0;

    for ( $i = 1, $length = strlen( $reversed ); $i < $length; $i++ ) {
        $sum += (int) $reversed[ $i ] * ( $i % 2 ? 3 : 1 );
    }

    if ( ( 10 - ( $sum % 10 ) ) % 10 !== (int) $reversed[0] ) {
        return '';
    }

    return $digits;
}

/* Affiliate WP */
add_action( 'affwp_affiliate_login_form_top', 'add_affilite_register_button', 10 );
function add_affilite_register_button(){
    $html = '<div class="join-affiliate-link"><a href="' . get_home_url() . '/bonusprogram/">Anslut till vårt Bonusprogram</a></div>';
    echo $html;
}

add_action( 'wp_ajax_nopriv_get_injury_guide', 'get_injury_guide' );
add_action( 'wp_ajax_get_injury_guide', 'get_injury_guide' );

function get_injury_guide(){
    ob_start(); ?>

    <div></div>

    <?php
}

add_action( 'woocommerce_shop_loop_item_title', 'custom_loop_product_color_alternatives', 10 );
function custom_loop_product_color_alternatives() {
    global $product;

    $colors = wc_get_product_terms( $product->get_id(), 'pa_farg', array( 'fields' => 'all' ) );

    if ( !empty($colors) )
    {
        $html = '<div class="color-preview">';
        foreach ($colors as $color) {
            $color_hex = get_term_meta( $color->term_id, 'color_preview', true );
            $color_hex_2 = get_term_meta( $color->term_id, 'color_preview_2', true );

            if (!empty($color_hex_2)) {
                $html .= '<span class="color" style="background: linear-gradient(135deg, ' . $color_hex . ' 50%, ' . $color_hex_2 . ' 50%);"></span>';
            } elseif(!empty($color_hex)) {
                $html .= '<span class="color" style="background-color:' . $color_hex . ';"></span>';
            } else {
                $html .= '<span class="color color__' . $color->slug  . '"></span>';
            }
        }
        $html .= '</div>';
        echo $html;
    }
}

/**
 * Add badge to product cards for onsale and featured products
 */
add_filter( 'woocommerce_sale_flash', 'woocommerce_custom_badge', 9999, 3 );
function woocommerce_custom_badge( $output_html, $post, $product ) {

    if ($product->is_on_sale() && $product->is_featured())
    {
        $output_html = '<span class="onsale">Erbjudande!</span>';
        $output_html .= '<span class="featured">Ny!</span>';
    } 
    elseif ($product->is_on_sale())
    {
        $output_html = '<span class="onsale">Erbjudande!</span>';
    }
    elseif ($product->is_featured())
    {
        $output_html = '<span class="featured">Ny!</span>';
    }

    return $output_html;
} 

function yoast_seo_canonical_change_woocom_shop( $canonical ) {
	
    if (is_paged()) {
        $canonical = get_pagenum_link(1);
    }
    
    return $canonical;
}
add_filter( 'wpseo_canonical', 'yoast_seo_canonical_change_woocom_shop', 20, 1 );


/* Work in progress 
add_filter( 'woocommerce_loop_add_to_cart_link', 'ts_replace_add_to_cart_button', 10, 2 );
function ts_replace_add_to_cart_button( $button, $product ) {
    
    if( $product->is_type( 'variable' ) ){
        $attributes = $product->get_variation_attributes();
        $attribute_value = 'alternativ';

        if (count($attributes) == 1)
        {
            $attribute_value = substr(array_key_first($attributes), 3);
            if ($attribute_value == 'farg')
            {
                $attribute_value = 'färg';
            }
        }

        $button = sprintf(
            '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="add_to_cart %s product_type_%s">%s</a>',
            esc_url( $product->add_to_cart_url() ),
            esc_attr( $product->get_id() ),
            esc_attr( $product->get_sku() ),
            $product -> is_purchasable() ? 'add_to_cart_button' : '',
            esc_attr( $product->get_type() ),
            esc_html( 'Välj ' . $attribute_value )
        );
    }

    return $button;
}
*/


/*
add_filter('woocommerce_show_product_sale_flash', 'single_product_sales_badge', 10);
function single_product_sales_badge(){
    // console_log("single product sale badge");
}
*/
