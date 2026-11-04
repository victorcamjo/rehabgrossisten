<?php
/**
 * Template for the secondary header.
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
?>
<?php

$content_1 = avada_secondary_header_content( 'header_left_content' );
$content_2 = avada_secondary_header_content( 'header_right_content' );
?>

<div class="fusion-secondary-header">
    <div class="fusion-row">
        <?php if ( $content_1 ) : ?>
            <div class="fusion-alignleft">
                <?php /* echo $content_1; // WPCS: XSS ok. */ ?>
                <ul class="top-bar-checklist">
                    <li>Fri frakt fr. 375.-</li>
                    <li class="hide-mobile">Snabb leverans</li>
                    <li>Hemleverans</li>
                    <li>Fria byten</li>
                    <li>100% Nöjd kundgaranti</li>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ( $content_2 ) : ?>
            <div class="fusion-alignright">

                <ul class="top-bar-checklist">
                    <li class="phone">08-410 248 80</li>
                    <li class="email">info [at] rehabgrossisten.com</li>
                </ul>

            </div>
        <?php endif; ?>
    </div>
</div>
