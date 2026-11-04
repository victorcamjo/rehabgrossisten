<?php

require_once __DIR__ . '/vendor/autoload.php';

if (@include get_stylesheet_directory() . '/config/include-config.php') :
    include_once get_stylesheet_directory() . '/config/include-config.php';
endif;

// Disable Checkout WooCommerce
// add_filter( 'woocommerce_is_purchasable', '__return_false' );

// Disable Cart WooCommerce
// function disable_cart() {
//     if ( is_cart() ) {
//       wp_redirect( home_url() );
//       exit;
//     }
// }
// add_action( 'template_redirect', 'disable_cart' );

function theme_enqueue_styles() {

    $cache_buster_css = filemtime(get_stylesheet_directory() . '/assets/css/styles.min.css');
    $cache_buster_js = filemtime(get_stylesheet_directory() . '/assets/js/functions.min.js');

    add_action( 'wp_head', 'insert_avoidance_fouc' );
    add_action( 'wp_footer', 'remove_avoidance_fouc', 900 );

    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/assets/css/styles.min.css', [], $cache_buster_css, 'all' );

    wp_register_script( 'child-script', get_stylesheet_directory_uri() . '/assets/js/functions.min.js', array( 'jquery' ), $cache_buster_js, TRUE);
    wp_enqueue_script( 'child-script' );

    // wp_localize_script Passes Data to JS
    wp_localize_script( 'child-script', 'ajax', array(
        'url' => admin_url( 'admin-ajax.php' )
    ));

    // Remove Contact Form 7 Style and Script on all pages but Contact page
    if( ! is_page(18995) )
    {
        wp_dequeue_script('contact-form-7'); // Dequeue JS Script file.
        wp_dequeue_style('contact-form-7');  // Dequeue CSS file.
    }

    if ( ! is_page(30922) )
    {
        wp_dequeue_style('affwp-forms');
    }

    wp_dequeue_style( 'aws-pro-style' );
    wp_dequeue_style( 'yith-wcan-frontend' );
    wp_dequeue_style( 'yith-infs-style' );

    // WP Blocks
    wp_dequeue_style( 'wp-block-library' ); // Wordpress core
    wp_deregister_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' ); // Wordpress core
    wp_deregister_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' ); // WooCommerce
    wp_dequeue_style( 'storefront-gutenberg-blocks' ); // Storefront theme

    // Lightbox
    wp_deregister_script( "jquery-lightbox-js-extra" );
    wp_deregister_script( "jquery-lightbox" );
    
    // # Used for side cart 
    // wp_dequeue_style( 'lightslider' );

    // Deregister Lightbox if not product page or front page
    // Needed for home page because of "blog" fusion shortcode uses flexslider and lightbox for equal height
    // Also needed for skadeguiden
    if ( is_product() === false && is_front_page() === false ){
        // Fusion_Dynamic_JS::deregister_script('jquery-lightbox');
        // Fusion_Dynamic_JS::deregister_script('jquery-flexslider');
    }
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', PHP_INT_MAX );

function insert_avoidance_fouc(){
    echo '<style>html{visibility: hidden;opacity:0;}</style>';
}

function remove_avoidance_fouc(){
    echo '<style>html{visibility: visible;opacity:1;}</style>';
}

function add_hello_retail_scripts(){ 
    ob_start(); ?>
    
    <script async src="https://helloretailcdn.com/helloretail.js"></script>
    <script id="hr-init">
        window.hrq = window.hrq || [];
        hrq.push(['init', {}]);
    </script>

    <?php echo ob_get_clean();
}
add_action('wp_head', 'add_hello_retail_scripts');

/**
 * Add Google Tag Manager scripts to wp_head
 */

add_action('wp_head', function(){

    echo '<script id="consent-script" class="gcm-script" type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        function gtag() {
        dataLayer.push(arguments);
        }
        gtag("consent", "default", {
        ad_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied",
        analytics_storage: "denied",
        wait_for_update: 500,
        });
        gtag("set", "ads_data_redaction", true);
    </script>';

    echo '<script id="CookieConsent" class="cookie-consent-script" src="https://policy.app.cookieinformation.com/uc.js" data-culture="SV" type="text/javascript" data-gcm-version="2.0" data-cfasync="false" data-pagespeed-no-defer></script>';
    
    echo '<!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
    new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
    \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,\'script\',\'dataLayer\',\'GTM-57HCJTB\');</script>
    <!-- End Google Tag Manager -->';

    if (!is_checkout()) {
        // echo '<script>
        // window.dataLayer = window.dataLayer || [];
        // function gtag(){dataLayer.push(arguments);}
        
        // gtag(\'config\', \'G-L3L0VTLB6Y\');
        // </script>';
    }
}, -10);

/* Insert script into wp_head */
add_action('wp_head', 'insert_head_script');
function insert_head_script(){
        
    if (function_exists('get_field')) {
        $script = get_field('head_script', get_queried_object());
        if ($script) {
            echo $script;
        }
    }
};

/* Remove Inline Styles */
add_action( 'wp_print_styles', function()
{
    // Remove previous inline style
    wp_styles()->add_data( 'yith-wcan-frontend', 'after', '' );
    wp_styles()->add_data( 'woocommerce-inline', 'after', '' );

} );

/*
 * Setup Custom Actions
 */
function setup_custom_theme_actions() {
    global $avada_woocommerce;

    // Remove WP Emoji
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );

    // Remove Product Meta (SKU, Category, Tags)
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

    // Remove Cross Sell Deafult View
    remove_action( 'woocommerce_cart_collaterals', array($avada_woocommerce, 'cross_sell_display'), 5 );

    // Remove Related Products
    remove_action( 'woocommerce_after_single_product_summary', array( $avada_woocommerce, 'output_related_products'), 15 );

    // Remove Out of Stock Flash
    remove_action( 'woocommerce_before_shop_loop_item_title', array( $avada_woocommerce, 'show_product_loop_outofstock_flash' ), 10 );

    remove_action( 'woocommerce_after_single_product_summary', array( $avada_woocommerce, 'upsell_display' ), 10 );
    add_action( 'woocommerce_after_single_product_summary', 'rg_upsell_display', 20 ); // array( $avada_woocommerce, 'rg_upsell_display' )

    // Render Content Before </body> (Produktguiden, Searchbar etc)
    add_action('wp_footer', 'before_body_end');

    // Render Secondary Footer
    add_action( 'avada_after_main_container', 'render_secondary_footer' );

    // Remove Notice Wrapper
    remove_action( 'woocommerce_before_checkout_form_cart_notices', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_before_cart', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_shortcode_before_product_cat_loop', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_cart_is_empty', 'woocommerce_output_all_notices', 5 );
    remove_action( 'before_woocommerce_pay', 'woocommerce_output_all_notices', 10 );
    remove_action( 'kco_wc_before_checkout_form', 'kco_wc_print_notices' );

    // Remove Upsell Display from Single Product Page
    remove_action( 'woocommerce_after_single_product_summary', array( $avada_woocommerce, 'upsell_display' ), 10 );
}
add_action( 'after_setup_theme', 'setup_custom_theme_actions' );


add_action('woocommerce_bis_after_form_fields', 'rg_fill_notification_form');
function rg_fill_notification_form(){
    echo '<p>Vi kontaktar dig när produkten finns åter i lager. Information om hur vi hanterar din personliga data hittar du i vår <a href="https://rehabgrossisten.com/integritetspolicy/">integritetspolicy</a>.</p>';
    echo '<a class="cancel-notification" href="#">x</a>';
}

function before_body_end() {
    get_template_part( 'templates/before-body-end' );
}

function render_secondary_footer() {
    get_template_part( 'templates/secondary-footer' );
}

/* Custom login */
function my_custom_login() {
    echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('stylesheet_directory') . '/login/custom-login-styles.css" />';
}
add_action('login_head', 'my_custom_login');

function my_login_logo_url() {
    return get_bloginfo( 'url' );
}
add_filter( 'login_headerurl', 'my_login_logo_url' );

function my_login_logo_url_title() {
    return 'Rehabgrossisten.com';
}
add_filter( 'login_headertitle', 'my_login_logo_url_title' );

function rgstyle_menu_item_count( $output, $item, $depth, $args ) {

    // Check if the item is a Category or Custom Taxonomy
    if( $item->type == 'taxonomy' ) {
        $object = get_term($item->object_id, $item->object);

        $child = true;
        if ($object->taxonomy == 'product_cat') {

            if($object->parent == 0 || $object->parent == 55)
                $child = false;
        }

        // Dont show titles (parent categories) or Rehab & Träning
        if ($object->taxonomy == 'product_tag' || $child) {

            $pos = 11;
            $str = "<span class='menu-item-product-count'> (" . $object->count.")</span>";
            $output = substr($output, 0, strlen($output) - $pos) . $str . substr($output, strlen($output) - $pos);
        }
    } else if($item->object == 'product') {
        $pos = 11;
        $str = "<span class='menu-item-product-count'> (1)</span>";
        $output = substr($output, 0, strlen($output) - $pos) . $str . substr($output, strlen($output) - $pos);
    }

    return $output;
}
// add_action( 'walker_nav_menu_start_el', 'rgstyle_menu_item_count', 10, 4 );

/* Logo */
add_action("avada_logo_append", "header_search_form", 10);
function header_search_form(){

    $html = '<div class="search">';
    $html .= '<div class="search-wrapper aws-search-form""><input type="search" name="s" placeholder="Sök produkt eller skada"></div>';
    // $html .= do_shortcode('[aws_search_form id="1"]');
    $html .= '<div class="find-products-injury-guide"><span>Hitta produkt via <a href="' . get_site_url() . '/skadeguiden">Skadeguiden</a></span></div></div>';
    echo $html;
}

/* Meta Description Injection */
add_action("wp_head", 'meta_injection');
function meta_injection(){
    global $wp;
    $page_title = $wp->request;

    // Skadeguiden
    if ($page_title && $page_title == "skadeguiden") {
        echo '<meta name="description" content="Skadeguiden hjälper dig med tips på rehabilitering, produkter för egenvård i hemmet, tränings- och rehabprodukter - Välkommen!" />';
    }
}

/* Update Meta Title */
add_filter('pre_get_document_title', 'change_title', 50);
function change_title($title) {
    global $wp;
    $page_title = $wp->request;

    // Skadeguiden
    if ($page_title && $page_title == "skadeguiden") {
        $title = 'Allt för egenvård hemma - Skadeguiden | REHABgrossisten';
    }
    
    return $title;
}

/* Register order status - Fetched */
function register_fetched_order_status() {
    register_post_status( 'wc-fetched', array(
        'label'                     => 'Fetched',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'Fetched <span class="count">(%s)</span>', 'Fetched <span class="count">(%s)</span>' )
    ) );
 }
 add_action( 'init', 'register_fetched_order_status' );

 function add_fetched_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-fetched'] = 'Fetched';
        }
    }
    return $new_order_statuses;
 }
 add_filter( 'wc_order_statuses', 'add_fetched_to_order_statuses' );

//  add_filter( 'rocket_defer_inline_exclusions', function( $inline_exclusions_list ) {
//     if ( ! is_array( $inline_exclusions_list ) ) {
//       $inline_exclusions_list = array();
//     }
  
//     // Duplicate this line if you need to exclude more
//     $inline_exclusions_list[] = 'key_from_target_script';
  
//     return $inline_exclusions_list;
//   } );


function product_shop_message(){

    $product_message = get_field('product_message');
    $background_color = get_field('background_color');
    $text_color = get_field('text_color');

    if ( empty($product_message) ) return;

    ob_start(); ?>

    <div style="
        background-color: <?php echo esc_html($background_color); ?>;
        color: <?php echo esc_html($text_color); ?>;
        border: 1px solid #ebeaea;
        text-align: center;
        margin-bottom: 20px;
        padding: 5px;
        margin-top: -30px;
    ">
    <?php echo $product_message; ?>
    </div>

    <?php echo ob_get_clean();
}
add_action('woocommerce_before_single_product', 'product_shop_message', 20);

add_filter( 'wpseo_robots_array', function ( $robots ) {
      foreach ( array_keys( $_GET ) as $key ) {
          if ( strpos( $key, 'filter_' ) === 0 ) {
              $robots['index']  = 'noindex';
              $robots['follow'] = 'follow';
              break;
          }
      }
      return $robots;
}, 999 );