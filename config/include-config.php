<?php

if (@include get_stylesheet_directory() . '/config/cache-privacy.php') :
    include_once get_stylesheet_directory() . '/config/cache-privacy.php';
endif;
if (@include get_stylesheet_directory() . '/config/custom.php') :
    include_once get_stylesheet_directory() . '/config/custom.php';
endif;
if (@include get_stylesheet_directory() . '/config/shortcodes.php') :
    include_once get_stylesheet_directory() . '/config/shortcodes.php';
endif;
if (@include get_stylesheet_directory() . '/config/single-products.php') :
    include_once get_stylesheet_directory() . '/config/single-products.php';
endif;
if (@include get_stylesheet_directory() . '/config/cart-checkout.php') :
    include_once get_stylesheet_directory() . '/config/cart-checkout.php';
endif;
if (@include get_stylesheet_directory() . '/config/bulk-discount.php') :
    include_once get_stylesheet_directory() . '/config/bulk-discount.php';
endif;
if (@include get_stylesheet_directory() . '/config/withdrawal.php') :
    include_once get_stylesheet_directory() . '/config/withdrawal.php';
endif;
if (@include get_stylesheet_directory() . '/config/datalayer.php') :
    include_once get_stylesheet_directory() . '/config/datalayer.php';
endif;
if (@include get_stylesheet_directory() . '/config/product-sorting.php') :
    include_once get_stylesheet_directory() . '/config/product-sorting.php';
endif;
/*
if (@include get_stylesheet_directory() . '/config/product-category.php') :
    include_once get_stylesheet_directory() . '/config/product-category.php';
endif;
*/
