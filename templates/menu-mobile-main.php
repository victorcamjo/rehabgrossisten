<?php
/**
 * Mobile main menu template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       http://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

if ( 'flyout' === Avada()->settings->get( 'mobile_menu_design' ) && ( 'Top' !== Avada()->settings->get( 'header_position' ) || ! in_array( Avada()->settings->get( 'header_layout' ), array( 'v4', 'v5' ) ) ) ) :

    get_template_part( 'templates/menu-mobile-flyout' );

else :
    if ( 'Top' !== Avada()->settings->get( 'header_position' ) || ( ! in_array( Avada()->settings->get( 'header_layout' ), array( 'v4', 'v5' ) ) ) ) {
        get_template_part( 'templates/menu-mobile-modern' );
    }

endif; ?>

<?php
$mobile_menu_css_classes = 'flyout' === Avada()->settings->get( 'mobile_menu_design' )
    ? ' fusion-flyout-menu fusion-flyout-mobile-menu'
    : ' fusion-mobile-menu-text-align-' . Avada()->settings->get( 'mobile_menu_text_align' );
?>

    <nav class="fusion-mobile-nav-holder<?php echo esc_attr( $mobile_menu_css_classes ); ?>">
        <div class="mobile-top-content">
            <a href="#" class="close-menu" aria-label="Toggle mobile menu"></a>
            <img src="<?php echo get_site_url(); ?>/wp-content/uploads/2018/02/Rehabgrossisten.mobil_.600-1.png"
                 srcset="<?php echo get_site_url(); ?>/wp-content/uploads/2018/02/Rehabgrossisten.mobil_.600-1.png 1x, <?php echo get_site_url(); ?>/wp-content/uploads/2018/02/Rehabgrossisten.mobil_.1200.png 2x"
                 width="601" height="75" style="max-height:75px;height:auto;" alt="Rehabgrossisten Logotyp"
                 retina_logo_url="<?php echo get_site_url(); ?>/wp-content/uploads/2018/02/Rehabgrossisten.mobil_.1200.png" class="fusion-mobile-logo">
        </div>
    </nav>

<?php
/* Omit closing PHP tag to avoid "Headers already sent" issues. */

