'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

jQuery(document).ready(function ($) {

    // Main menu
    $('.fusion-main-menu > ul > .menu-item > a, nav.responsive-header div.menu a').click(function (e) {
        e.preventDefault();
    });

    // Mobile menu
    $('nav.responsive-header').on('click', '.menu a.fusion-icon-bars', function () {

        var mobileNav = $('nav.fusion-mobile-nav-holder');

        // Append Information Section to the End of Mobile Navigation Container
        if (!$('div.shop-information').length) {

            var information = '<div class="shop-information">\n                                    <button>Hitta produkt</button>\n                                    <ul>\n                                        <li><i class="fa fa-credit-card" aria-hidden="true"></i> Betala s\xE4kert med Klarna</li>\n                                        <li><i class="fa fa-truck" aria-hidden="true"></i> Snabb leverans fr\xE5n lager i Sverige</li>\n                                        <li>\n                                            <i class="fa fa-envelope-o" aria-hidden="true"></i>\n                                            <a href="mailto:info@rehabgrossisten.com">info@rehabgrossisten.com</a>\n                                        </li>\n                                        <li class="phone">\n                                            <i class="fa fa-phone" aria-hidden="true"></i>\n                                            <a href="tel:0841024880">08-410 248 80</a>\n                                        </li>\n                                    </ul>\n                                </div>';

            mobileNav.append(information);
        }

        toggleMobileMenu();
    });

    // Mobile Nav Toggle Product Search Guide Button
    $('.fusion-mobile-nav-holder').on('click', 'button', function () {
        toggleMobileMenu();
        $('#slidingbar-area').toggleClass('open');
    });

    // Slider Toggle Product Search Guide Button
    $('.toggle-product-search-guide').on('click', function (e) {
        e.preventDefault();
        $('body').find('#slidingbar-area').toggleClass('open');
    });

    // Alternate Products
    $('div.featured-products').on('click', 'li.product', function () {
        window.location.href = $(this).find('a.product-images').attr('href');
    });

    $('body').on('touchstart mousedown', function (e) {
        var target = $(e.target);
        var mobileNav = $('nav.fusion-mobile-nav-holder');

        if (target.is('a.close-menu')) {
            e.preventDefault();

            setTimeout(function () {
                mobileNav.hide("slide", { direction: "right", distance: mobileNav.width() + "px" }, 500);
            }, 300);
        }

        // Close Slidingbar on click outside of it
        if (!target.parents().andSelf().is($("#slidingbar")) && !target.is('.fusion-sb-toggle') && !target.parents().andSelf().is(".toggle-product-search-guide") && !target.is(".fusion-mobile-nav-holder button")) {

            if ($("#slidingbar-area").hasClass("open")) {
                $("#slidingbar-area").removeClass("open");
            }
        } else {
            $('body #aws-search-result-4').hide();
        }

        if (!target.parents().andSelf().is(mobileNav) && !target.is('a.fusion-icon.fusion-icon-bars') && mobileNav.css('display') == 'block') {
            mobileNav.hide("slide", { direction: "right", distance: mobileNav.width() + "px" }, 500);
        }
    });

    // Document click
    $(document).on('click', function (e) {
        var _this = this;

        e.stopPropagation();
        var target = $(e.target);

        if (target.parents().andSelf().is($('nav.fusion-main-menu')) === false) {
            hideMegaMenu();
        }

        if ($('div.filter-container').length > 0 && !target.is('h4.widget-title') && !target.is('span.widget-dropdown')) {

            // $("div.filter-toggle") are hidden on desktop, prevent filter from hiding on desktop.

            if (target.is(".yith-wcan-list li a") && $("div.filter-toggle").css("display") != "none") {
                toggleVisibilityText($("div.filter-toggle a"), $('.filter-container div.filters'), 'text1');
            }

            $('div.filter-container .yith-woocommerce-ajax-product-filter').each(function () {
                $(_this).find('h4.widget-title').removeClass('open');
                $(_this).find('ul.yith-wcan-list').stop(true, true).fadeOut(100, function () {});
            });
        }
    });

    $('nav.fusion-secondary-menu').hover(function () {
        if (!$(this).hasClass('active-cart-container')) {
            $('nav.fusion-secondary-menu .fusion-custom-menu-item-contents').css(hiddenElem('hide'));
        }
    });

    $('nav.fusion-secondary-menu').on('click', '.fusion-secondary-menu-icon', function (e) {
        e.preventDefault();
        if (!$(this).hasClass('active-cart-container')) {
            $(this).addClass('active-cart-container');
            $('nav.fusion-secondary-menu .fusion-custom-menu-item-contents').css(hiddenElem('show'));
        } else {
            $(this).removeClass('active-cart-container');
            $('nav.fusion-secondary-menu .fusion-custom-menu-item-contents').css(hiddenElem('hide'));
        }
    });

    /*
        === Prevent Default for an Empty Cart ===
    */
    $('.fusion-header').on('click', '.cart-header', function (e) {

        if ($(this).find('.cart-count').text() == '0') {
            e.preventDefault();
        }
    });

    /* Mega menu handler */
    $(".fusion-main-menu > ul > li.menu-item").on('click', function (e) {

        var elem = $(this);
        var active = elem.hasClass('mega-active-link') ? true : false;

        if ($(e.target).is($('div.fusion-megamenu-title'))) {
            return false;
        }

        if (elem.hasClass('search-menuitem')) {
            hideMegaMenu();

            elem.addClass('mega-active-link');
            elem.find('input.aws-search-field').focus();
        } else {
            hideMegaMenu();

            if (!active) {
                elem.addClass('mega-active-link');
            }
        }
    });

    // Trigger Click on Touchstart for iPad
    $(".fusion-main-menu > ul.fusion-menu > li.menu-item > a").on('touchstart', function () {
        $(this).trigger("click");
    });

    // Hide Search Result on Click
    $('body').on('click', '.aws-search-result', function () {
        $('.aws-search-result').hide();
    });

    // Mobile menu
    $("nav.responsive-header").on('click', 'div.search #show-searchbar', function (e) {
        e.preventDefault();

        $('nav.responsive-header .aws-container').toggle();
    });

    /* Blog: Resize number of posts shown in sidebar */
    if ($('body').hasClass('single-post') && $('aside#sidebar').length) {

        // Let page render
        setTimeout(function () {
            var content_height = $('section#content article.post').height() - 40;
            var sidebar_article_height = $('aside#sidebar article.post').height();
            var number_of_articles_to_show = Math.floor(content_height / sidebar_article_height);

            var counter = 1;
            $.each($("aside#sidebar article.post"), function () {

                if (counter > number_of_articles_to_show) {
                    $(this).addClass('hide');
                }
                counter++;
            });
        }, 2000);
    }

    /* Change Endpoint Summary Breadcrumbs */
    if ($('body').hasClass('woocommerce-order-received')) {
        $('section.checkout-breadcrumb li.checkout').removeClass('current');
        $('section.checkout-breadcrumb li.summary').addClass('current');
    }

    /*
     *
     * === HITTA PRODUKT ===
     *
     */

    /* Hide button at initial rendering */
    setTimeout(function () {
        var slidingbar_area = $('#slidingbar-area');
        // console.log("Timeout 2000ms");
        slidingbar_area.css("display", "flex");
    }, 2000);

    /* New Injury Guide */

    $('.injury-guide-wrapper').on('click', '.body-parts > ul > li', function () {
        $('.injury-guide-wrapper .body-parts ul li').removeClass('selected-body-part');
        $(this).addClass('selected-body-part');

        // Hide all injuries and placeholder
        var injuries = $('.injuries');
        injuries.find('.placeholder').hide();
        injuries.find('a').hide();

        // Show injuries
        var body_part = $(this).attr('data-body-part');
        injuries.find('a[data-body-part="' + body_part + '"]').show();

        // Scroll to bottom of container
        $('.fusion-sliding-bar-content-wrapper').scrollTo(injuries, 2000);
    });

    $('.added_to_cart_popup .close, .added_to_cart_popup .continue-shopping').click(function (e) {
        e.preventDefault();

        var css = {
            'display': 'none',
            'opacity': '0'
        };

        $('.added_to_cart_popup').css(css);
        $('.modal-backdrop').remove();
    });

    $('.woocommerce-checkout').on('click', '#print-order', function (e) {
        e.preventDefault();

        var elem1 = $('.klarna_checkout .avada-order-details');
        var elem2 = $('.klarna_checkout .avada-customer-details');

        elem1.clone().appendTo(".hidden-print-div");
        elem2.clone().appendTo(".hidden-print-div");
        window.print();

        $(".hidden-print-div").empty();
    });

    /* Cart */
    $(document).on('click', '#toggle-coupon', function (e) {
        e.preventDefault();
        $('div.coupon').slideToggle(200, function () {
            // Done!
        });
    });

    $('div.woocommerce').on('change', '.qty', function () {
        $("[name='update_cart']").removeAttr('disabled');
        $("[name='update_cart']").trigger("click");
    });

    $(document).on("updated_shipping_method", function () {
        $("[name='update_cart']").removeAttr('disabled');
        $("[name='update_cart']").trigger("click");
    });

    // Update cart with AJAX
    $('body').on('added_to_cart', function () {
        $("[name='update_cart']").removeAttr('disabled');
        $("[name='update_cart']").trigger("click");
    });

    $('.common-injuries').on('click', '.common-injuries-title a', function (e) {
        e.preventDefault();
        toggleVisibilityText($(this).parent(), $('div.common-injuries-content'), 'text1');
    });

    /*
        === Filter ===
    */
    $('.filter-toggle a').click(function (e) {
        e.preventDefault();
        toggleVisibilityText($(this), $('.filter-container div.filters'), 'text1');
    });

    $('.yith-woocommerce-ajax-product-filter').on('click', 'ul.yith-wcan-list a', function () {

        if ($(this).parent().hasClass('chosen')) {
            $(this).parent().removeClass('chosen');
        } else {
            $(this).parent().addClass('chosen');
        }

        product_holder();
    });

    $('.yith-woocommerce-ajax-product-filter').on('click', 'a.yith-wcan-reset-navigation', function () {
        product_holder();
    });

    // Filter Animations
    $('div.filter-container').on('click', '.heading h4.widget-title', function () {

        var dropdown = $(this).parent().nextAll('ul.yith-wcan-list');
        var visible = $(this).hasClass('open');

        // Hide All Filters
        $('div.filter-container .yith-woocommerce-ajax-product-filter').each(function () {
            $(this).find('h4.widget-title').removeClass('open');
            $(this).find('ul.yith-wcan-list').stop(true, true).fadeOut(100, function () {});
        });

        if (visible) {
            $(this).addClass('open');
            dropdown.stop(true, true).fadeIn(100, function () {});
        }
    });

    // Trigger Tab
    $('.woocommerce').on('click', '.anchor-tabs', function () {
        var target = $(this).attr('href');
        $('ul.wc-tabs ' + target + ' > a').trigger('click');
    });

    // Scroll to element with css class
    $('document, body').on('click', '.scroll-to', function (e) {
        e.preventDefault();
        to_position($(this).attr('data-target'));
        if ($(this).hasClass('open-tab')) {
            open_tab($(this).attr('data-target'));
        }
    });

    // Smooth scroll to reviews on click
    $('.woocommerce-review-link').on('click', function () {
        to_position($('.reviews_tab'), 100);
    });

    // Toggle read-more text
    $('a.read-more').click(function (e) {
        e.preventDefault();

        if ($('div.hidden-text').css('display') == 'none') {
            $('div.hidden-text').append($(this));
        } else {
            $('div.hidden-text').prev().append($(this));
        }

        toggleVisibilityText($(this), $('.hidden-text'), 'text2');
    });

    // Remove product--holder when filter is finished
    $(document).on('yith-wcan-ajax-filtered', function () {
        return $('div#products--holder').remove();
    });

    // Hide Mega Menu
    function hideMegaMenu() {
        $('.fusion-main-menu > ul > li').each(function () {
            if ($(this).hasClass('mega-active-link')) {
                $(this).removeClass('mega-active-link');
            }
        });
    }

    // Return correct CSS for hidden element
    var hiddenElem = function hiddenElem(view) {
        if (empty(view)) {
            console.log("Warning: No parameter sent to function hiddenElem");
            return;
        }
        var css = {
            'visibility': view == 'show' ? 'visible' : 'hidden',
            'opacity': view == 'show' ? 1 : 0
        };
        return css;
    };

    var product_holder = function product_holder() {

        var products = $(yith_wcan.container).html();
        var container = '<div id="products--holder"><ul class="products-4">' + products + '</ul></div>';

        // Remove background-spinner
        setTimeout(function () {
            $(yith_wcan.container).css('background-image', 'none');
        }, 0);

        $(container).insertBefore('.yit-wcan-container');
    };

    var toggleVisibilityText = function toggleVisibilityText(elem, toggleElem, text) {
        if (elem.hasClass('active')) {
            text = text == 'text1' ? 'Visa' : 'mer';
            elem.find('span').text(text);
            elem.removeClass('active');
        } else {
            text = text == 'text1' ? 'Dölj' : 'mindre';
            elem.find('span').text(text);
            elem.addClass('active');
        }

        toggleElem.slideToggle(500, function () {});
    };

    // Utility Functions
    var to_position = function to_position(target, position) {
        if (empty(target)) return;

        if (empty(position)) {
            position = 100;
        }
        $('html, body').animate({ scrollTop: $(target).offset().top - position }, 'slow');
    };

    jQuery.fn.scrollTo = function (elem, speed) {
        $(this).animate({
            scrollTop: $(this).scrollTop() - $(this).offset().top + $(elem).offset().top
        }, speed == undefined ? 1000 : speed);
        return this;
    };

    var open_tab = function open_tab(target) {
        $('ul.wc-tabs ' + target + ' > a').trigger('click');
    };

    var toggleMobileMenu = function toggleMobileMenu() {
        var mobileNav = $('nav.fusion-mobile-nav-holder');
        if (mobileNav.is(":visible")) {
            mobileNav.hide("slide", { direction: "right", distance: mobileNav.width() + "px" }, 500);
        } else {
            mobileNav.show("slide", { direction: "right", distance: mobileNav.width() + "px" }, 500);
        }
    };

    /*
    **************
    **************
    **** AJAX ****
    **************
    **************
    */

    // Get Variation Status
    $('form.variations_form').on('change', '.variations select', function () {

        // Timeout in Order to Give WooCommerce Time to Render Variation ID
        setTimeout(function () {
            var variation_id = $('input.variation_id').val();
            var product_id = $('input[name="product_id"]').val();

            if (!empty(variation_id) && !empty(product_id)) {

                var spinner = $('body .spinner').clone().removeClass('hide');

                if (!$('.product-purchase-info .delivery-time .spinner').length) {
                    $('.product-purchase-info .delivery-time').html(spinner);
                }

                $.ajax({
                    type: 'POST',
                    url: ajax.url,
                    data: {
                        action: 'get_variation_status',
                        variation_id: variation_id,
                        product_id: product_id
                    },
                    dataType: 'html',
                    success: function success(response) {
                        response = JSON.parse(response);

                        var _response = response,
                            stock = _response.stock,
                            price = _response.price;


                        $('.product-purchase-info .delivery-time').html(stock);
                        $('.woocommerce-container .summary-container .price').html(price);
                    }
                });
            }
        }, 0);
    });

    $(document).on("update_cart", function (e) {
        console.log("trigger update cart");
        var data = {
            action: 'update_cart_totals',
            security: wc_checkout_params.update_order_review_nonce,
            post_data: $('form.checkout').serialize()
        };

        jQuery.post(ajax.url, data, function (response) {
            $('body').trigger('update_checkout');
        });
    });
});

function createCookie(name, value, days) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length);
        }if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

function empty(val) {

    // test results
    //---------------
    // []        true, empty array
    // {}        true, empty object
    // null      true
    // undefined true
    // ""        true, empty string
    // ''        true, empty string
    // 0         false, number
    // true      false, boolean
    // false     false, boolean
    // Date      false
    // function  false

    if (val === undefined) return true;

    if (typeof val == 'function' || typeof val == 'number' || typeof val == 'boolean' || Object.prototype.toString.call(val) === '[object Date]') return false;

    if (val == null || val.length === 0) // null or 0 length array
        return true;

    if ((typeof val === 'undefined' ? 'undefined' : _typeof(val)) == "object") {
        // empty object

        var r = true;

        for (var f in val) {
            r = false;
        }return r;
    }

    return false;
}
//# sourceMappingURL=functions.js.map