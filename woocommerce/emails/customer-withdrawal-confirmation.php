<?php
/**
 * Customer withdrawal confirmation email (Ångra köp)
 *
 * Override this template by copying it to yourtheme/woocommerce/emails/customer-withdrawal-confirmation.php
 *
 * @package WooCommerce\Templates\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( 'Hej %s,', esc_html( $order->get_billing_first_name() ) ); ?></p>

<p><?php printf(
    'Vi har tagit emot din ångeranmälan för order <strong>#%s</strong>. Nedan ser du vad du valt att ångra.',
    esc_html( $order->get_order_number() )
); ?></p>

<h2><?php esc_html_e( 'Artiklar du har ångrat', 'woocommerce' ); ?></h2>

<ul>
    <?php foreach ( $withdrawal_items as $item ) : ?>
        <li><?php echo esc_html( $item['qty'] . ' × ' . $item['name'] ); ?></li>
    <?php endforeach; ?>
</ul>

<?php if ( ! empty( $withdrawal_reason ) ) : ?>
    <p><strong>Din kommentar:</strong> <?php echo esc_html( $withdrawal_reason ); ?></p>
<?php endif; ?>

<h2>Nästa steg</h2>

<p>Vi återkommer inom kort med instruktioner för hur du returnerar varan/varorna.
Återbetalning sker så snart vi har tagit emot returen, till samma betalsätt som du
använde vid köpet.</p>

<p>Har du frågor är du välkommen att svara på detta mejl eller kontakta vår kundtjänst.</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
