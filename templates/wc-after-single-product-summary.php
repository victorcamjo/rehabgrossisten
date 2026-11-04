<?php
/**
 * Adds HTML after the product summary.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1.0
 */

?>
<div class="fusion-clearfix"></div>

<?php $nofollow = ( Avada()->settings->get( 'nofollow_social_links' ) ) ? ' rel="noopener noreferrer nofollow"' : ' rel="noopener noreferrer"'; ?>

<?php if ( Avada()->settings->get( 'woocommerce_social_links' ) ) : ?>

    <?php $facebook_url = 'https://m.facebook.com/sharer.php?u=' . rawurlencode( get_permalink() ); ?>
    <?php if ( ! avada_jetpack_is_mobile() ) : ?>
        <?php $facebook_url = 'http://www.facebook.com/sharer.php?u=' . rawurlencode( get_permalink() ) . '&t=' . rawurlencode( the_title_attribute( [ 'echo' => false ] ) ); ?>
    <?php endif; ?>

    <div class="social-share-container">

        <ul class="social-share clearfix">
            <li class="facebook">
                <a href="<?php echo esc_url_raw( $facebook_url ); ?>" target="_blank"<?php echo $nofollow; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                    <div class="rg-tooltip top">
                        <span class="rg-tooltiptext">Dela på Facebook</span>
                        <i class="fab fa-facebook-f fontawesome-icon medium circle-yes fusion-icon-facebook"></i>
                    </div>
                </a>
            </li>
            <li class="twitter">
                <a href="https://twitter.com/share?text=<?php the_title_attribute(); ?>&amp;url=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank"<?php echo $nofollow; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                    <div class="rg-tooltip top">
                        <span class="rg-tooltiptext">Dela på Twitter</span>
                        <i class="fab fa-twitter fontawesome-icon medium circle-yes fusion-icon-twitter"></i>
                    </div>
                </a>
            </li>
            <li class="linkedin">
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo rawurlencode( get_permalink() ); ?>&amp;title=<?php echo rawurlencode( the_title_attribute( [ 'echo' => false ] ) ); ?>" target="_blank"<?php echo $nofollow; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                    <div class="rg-tooltip top">
                        <span class="rg-tooltiptext">Dela på Linkedin</span>
                        <i class="fab fa-linkedin-in fontawesome-icon medium circle-yes fusion-icon-linkedin"></i>
                    </div>
                </a>
            </li>
            <li class="email">
                <a href="mailto:?subject=<?php echo rawurlencode( html_entity_decode( the_title_attribute( [ 'echo' => false ] ), ENT_COMPAT, 'UTF-8' ) ); ?>&body=<?php echo esc_url_raw( get_permalink() ); ?>" target="_blank"<?php echo $nofollow; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                    <div class="rg-tooltip top">
                        <span class="rg-tooltiptext">Skicka med e-post</span>
                        <i class="fas fa-envelope fontawesome-icon medium circle-yes fusion-icon-mail"></i>
                    </div>
                </a>
            </li>
        </ul>
    </div>
<?php endif; ?>
