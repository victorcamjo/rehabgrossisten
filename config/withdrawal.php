<?php
/**
 * Ångra köp (right of withdrawal / ångerrätt)
 *
 * Self-service flow: customer looks up an order with order number + billing email,
 * selects the items they want to withdraw, optionally states a reason and confirms.
 * The request is recorded on the WooCommerce order (custom status + order note + meta)
 * and confirmation emails are sent to the customer and to staff. No money moves
 * automatically — staff perform the actual Klarna refund once goods are returned.
 *
 * User: Victor Bishti
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Max number of days after the order date that a withdrawal may be requested.
 * Right of withdrawal is 14 days from receipt of the goods; since the delivery
 * date is not reliably stored we measure from the order date and add a generous
 * delivery buffer so customers are never wrongly locked out.
 */
function rg_withdrawal_max_days() {
    $window = (int) apply_filters( 'rg_withdrawal_window_days', 14 );
    $buffer = (int) apply_filters( 'rg_withdrawal_delivery_buffer_days', 14 );
    return $window + $buffer;
}

/**
 * Order statuses from which a withdrawal can no longer be requested.
 */
function rg_withdrawal_blocked_statuses() {
    return apply_filters( 'rg_withdrawal_blocked_statuses', array(
        'cancelled',
        'refunded',
        'failed',
        'angrad',
        'trash',
    ) );
}

/**
 * Look up an order and validate that it is eligible for withdrawal.
 *
 * @param string $order_number Submitted order number.
 * @param string $email        Submitted billing email.
 * @return array { ok: bool, message: string, order: WC_Order|null, items: array }
 */
function rg_withdrawal_lookup_order( $order_number, $email ) {
    $result = array( 'ok' => false, 'message' => '', 'order' => null, 'items' => array() );

    $order_id = absint( preg_replace( '/[^0-9]/', '', $order_number ) );
    $order    = $order_id ? wc_get_order( $order_id ) : false;

    // Generic message so we never reveal whether an order/email exists.
    $not_found = 'Vi hittade ingen order som matchar ordernummer och e-postadress. Kontrollera uppgifterna och försök igen.';

    if ( ! $order ) {
        $result['message'] = $not_found;
        return $result;
    }

    if ( 0 !== strcasecmp( trim( $email ), trim( $order->get_billing_email() ) ) ) {
        $result['message'] = $not_found;
        return $result;
    }

    if ( in_array( $order->get_status(), rg_withdrawal_blocked_statuses(), true ) ) {
        $result['message'] = 'Den här ordern kan inte ångras via sajten. Kontakta vår kundtjänst så hjälper vi dig.';
        return $result;
    }

    $created = $order->get_date_created();
    if ( $created ) {
        $days_since = ( time() - $created->getTimestamp() ) / DAY_IN_SECONDS;
        if ( $days_since > rg_withdrawal_max_days() ) {
            $result['message'] = 'Ångerfristen för den här ordern har tyvärr passerat. Kontakta vår kundtjänst om du har frågor.';
            return $result;
        }
    }

    // Build a normalized item list for the front-end.
    $items = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        $items[] = array(
            'id'    => $item_id,
            'name'  => $item->get_name(),
            'qty'   => $item->get_quantity(),
            'total' => html_entity_decode( wp_strip_all_tags( wc_price( $order->get_line_total( $item ), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ),
        );
    }

    if ( empty( $items ) ) {
        $result['message'] = 'Den här ordern innehåller inga artiklar att ångra.';
        return $result;
    }

    $result['ok']    = true;
    $result['order'] = $order;
    $result['items'] = $items;
    return $result;
}

/* -------------------------------------------------------------------------
 * Custom order status: wc-angrad ("Ångrad – återbetalning begärd")
 * Mirrors the wc-fetched registration in functions.php.
 * ---------------------------------------------------------------------- */

function rg_register_angrad_order_status() {
    register_post_status( 'wc-angrad', array(
        'label'                     => 'Ångrad – återbetalning begärd',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop(
            'Ångrad <span class="count">(%s)</span>',
            'Ångrad <span class="count">(%s)</span>'
        ),
    ) );
}
add_action( 'init', 'rg_register_angrad_order_status' );

function rg_add_angrad_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-angrad'] = 'Ångrad – återbetalning begärd';
        }
    }
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'rg_add_angrad_to_order_statuses' );

/* -------------------------------------------------------------------------
 * Front-end: assets + shortcode
 * ---------------------------------------------------------------------- */

function rg_withdrawal_register_assets() {
    $path = get_stylesheet_directory() . '/assets/js/withdrawal.js';
    $ver  = file_exists( $path ) ? filemtime( $path ) : '1.0';

    wp_register_script(
        'rg-withdrawal',
        get_stylesheet_directory_uri() . '/assets/js/withdrawal.js',
        array( 'jquery' ),
        $ver,
        true
    );

    wp_localize_script( 'rg-withdrawal', 'rgWithdrawal', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'rg_withdrawal' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'rg_withdrawal_register_assets' );

/**
 * [angra_kop] — renders the lookup form. The item list is rendered by JS after
 * a successful lookup. Uses WooCommerce form markup (form-row / input-text /
 * button) so it inherits the site's existing styling.
 */
function rg_withdrawal_shortcode() {
    wp_enqueue_script( 'rg-withdrawal' );

    ob_start(); ?>
    <div class="rg-withdrawal woocommerce">

        <div class="rg-withdrawal__notice woocommerce-info" style="display:none;"></div>

        <form class="rg-withdrawal__lookup" novalidate>
            <p class="form-row form-row-wide">
                <label for="rg-wd-order">Ordernummer <span class="required">*</span></label>
                <input type="text" class="input-text" id="rg-wd-order" name="order_number" autocomplete="off" required>
            </p>
            <p class="form-row form-row-wide">
                <label for="rg-wd-email">E-postadress (samma som vid köpet) <span class="required">*</span></label>
                <input type="email" class="input-text" id="rg-wd-email" name="email" autocomplete="email" required>
            </p>
            <p class="form-row">
                <button type="submit" class="button">Hämta min order</button>
            </p>
        </form>

        <form class="rg-withdrawal__items" style="display:none;">
            <h3>Välj vad du vill ångra</h3>
            <div class="rg-withdrawal__items-list"></div>

            <p class="form-row form-row-wide">
                <label for="rg-wd-reason">Anledning (frivilligt)</label>
                <textarea class="input-text" id="rg-wd-reason" name="reason" rows="3"></textarea>
            </p>

            <div class="rg-withdrawal__legal">
                <p>Du har enligt lag (distansavtalslagen) rätt att ångra ditt köp inom 14 dagar
                från det att du tagit emot varan. När vi tagit emot din ångeranmälan får du
                instruktioner för returen. Återbetalning sker när vi mottagit varan.</p>
                <p>Du kan även använda Konsumentverkets standardblankett för ångerrätt:
                <a href="https://publikationer.konsumentverket.se/kontrakt-och-mallar/angerblankett"
                   target="_blank" rel="noopener noreferrer">Ångerblankett (Konsumentverket)</a>.</p>
            </div>

            <p class="form-row">
                <button type="submit" class="button alt">Skicka ångeranmälan</button>
                <a href="#" class="rg-withdrawal__back" style="margin-left:1em;">Avbryt</a>
            </p>
        </form>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'angra_kop', 'rg_withdrawal_shortcode' );

/* -------------------------------------------------------------------------
 * AJAX endpoints (both nonce-verified)
 * ---------------------------------------------------------------------- */

function rg_withdrawal_ajax_lookup() {
    check_ajax_referer( 'rg_withdrawal', 'nonce' );

    $order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
    $email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

    if ( '' === $order_number || ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Fyll i både ordernummer och en giltig e-postadress.' ) );
    }

    $lookup = rg_withdrawal_lookup_order( $order_number, $email );

    if ( ! $lookup['ok'] ) {
        wp_send_json_error( array( 'message' => $lookup['message'] ) );
    }

    wp_send_json_success( array(
        'order_number' => $lookup['order']->get_order_number(),
        'items'        => $lookup['items'],
    ) );
}
add_action( 'wp_ajax_rg_withdrawal_lookup', 'rg_withdrawal_ajax_lookup' );
add_action( 'wp_ajax_nopriv_rg_withdrawal_lookup', 'rg_withdrawal_ajax_lookup' );

function rg_withdrawal_ajax_submit() {
    check_ajax_referer( 'rg_withdrawal', 'nonce' );

    $order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
    $email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $reason       = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
    $raw_items    = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();

    // Re-run eligibility server-side — never trust the client.
    $lookup = rg_withdrawal_lookup_order( $order_number, $email );
    if ( ! $lookup['ok'] ) {
        wp_send_json_error( array( 'message' => $lookup['message'] ) );
    }

    $order = $lookup['order'];

    // Index allowed items (id => max qty) from the authoritative order.
    $allowed = array();
    foreach ( $lookup['items'] as $item ) {
        $allowed[ (int) $item['id'] ] = $item;
    }

    // Validate the customer's selection against the order.
    $selected = array();
    foreach ( $raw_items as $row ) {
        $id  = isset( $row['id'] ) ? (int) $row['id'] : 0;
        $qty = isset( $row['qty'] ) ? (int) $row['qty'] : 0;
        if ( $id && isset( $allowed[ $id ] ) && $qty > 0 ) {
            $qty          = min( $qty, (int) $allowed[ $id ]['qty'] );
            $selected[]   = array(
                'id'   => $id,
                'name' => $allowed[ $id ]['name'],
                'qty'  => $qty,
            );
        }
    }

    if ( empty( $selected ) ) {
        wp_send_json_error( array( 'message' => 'Välj minst en artikel att ångra.' ) );
    }

    // Persist the request on the order.
    $order->update_meta_data( '_rg_withdrawal_requested', current_time( 'mysql' ) );
    $order->update_meta_data( '_rg_withdrawal_items', $selected );
    $order->update_meta_data( '_rg_withdrawal_reason', $reason );
    $order->save();

    // Order note for staff.
    $note_lines = array( 'Kund ångrade köpet via sajten. Begärda artiklar:' );
    foreach ( $selected as $s ) {
        $note_lines[] = sprintf( '– %d × %s', $s['qty'], $s['name'] );
    }
    if ( '' !== $reason ) {
        $note_lines[] = 'Anledning: ' . $reason;
    }
    $order->add_order_note( implode( "\n", $note_lines ) );

    $order->update_status( 'angrad', 'Kund ångrade köpet via sajten. ' );

    // Notify customer and staff.
    rg_withdrawal_send_customer_email( $order->get_id(), $selected, $reason );
    rg_withdrawal_send_staff_email( $order, $selected, $reason );

    wp_send_json_success( array(
        'message' => 'Tack! Vi har tagit emot din ångeranmälan och skickat en bekräftelse till din e-post. Du får returinstruktioner inom kort.',
    ) );
}
add_action( 'wp_ajax_rg_withdrawal_submit', 'rg_withdrawal_ajax_submit' );
add_action( 'wp_ajax_nopriv_rg_withdrawal_submit', 'rg_withdrawal_ajax_submit' );

/* -------------------------------------------------------------------------
 * Emails
 * ---------------------------------------------------------------------- */

/**
 * Customer confirmation — sent through the WC_Email system so it inherits the
 * site's email header/footer/styling and is manageable under WooCommerce → Emails.
 */
function rg_withdrawal_send_customer_email( $order_id, $items, $reason ) {
    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();
    if ( isset( $emails['RG_Email_Withdrawal_Confirmation'] ) ) {
        $emails['RG_Email_Withdrawal_Confirmation']->trigger( $order_id, $items, $reason );
    }
}

/**
 * Internal notification to staff so they can start the return/refund handling.
 */
function rg_withdrawal_send_staff_email( $order, $items, $reason ) {
    $recipient = apply_filters( 'rg_withdrawal_staff_recipient', get_option( 'admin_email' ) );

    $lines   = array();
    $lines[] = sprintf( 'Order #%s har ångrats av kunden via sajten.', $order->get_order_number() );
    $lines[] = '';
    $lines[] = 'Kund: ' . $order->get_formatted_billing_full_name();
    $lines[] = 'E-post: ' . $order->get_billing_email();
    $lines[] = '';
    $lines[] = 'Begärda artiklar:';
    foreach ( $items as $s ) {
        $lines[] = sprintf( ' - %d x %s', $s['qty'], $s['name'] );
    }
    if ( '' !== $reason ) {
        $lines[] = '';
        $lines[] = 'Anledning: ' . $reason;
    }
    $lines[] = '';
    $lines[] = 'Hantera ordern: ' . $order->get_edit_order_url();

    wp_mail(
        $recipient,
        sprintf( 'Ångrat köp – order #%s', $order->get_order_number() ),
        implode( "\n", $lines )
    );
}

/**
 * Register the custom WC_Email class.
 */
add_filter( 'woocommerce_email_classes', 'rg_register_withdrawal_email_class' );
function rg_register_withdrawal_email_class( $email_classes ) {

    if ( ! class_exists( 'RG_Email_Withdrawal_Confirmation' ) && class_exists( 'WC_Email' ) ) {

        class RG_Email_Withdrawal_Confirmation extends WC_Email {

            public $withdrawal_items  = array();
            public $withdrawal_reason = '';

            public function __construct() {
                $this->id             = 'rg_withdrawal_confirmation';
                $this->title          = 'Ångra köp – bekräftelse';
                $this->description    = 'Skickas till kunden när de ångrar sitt köp via sajten.';
                $this->customer_email = true;
                $this->template_html  = 'emails/customer-withdrawal-confirmation.php';
                $this->template_base  = get_stylesheet_directory() . '/woocommerce/';
                parent::__construct();
            }

            public function get_default_subject() {
                return 'Vi har tagit emot din ångeranmälan – order {order_number}';
            }

            public function get_default_heading() {
                return 'Din ångeranmälan är mottagen';
            }

            public function trigger( $order_id, $withdrawal_items = array(), $reason = '' ) {
                $this->setup_locale();

                $order = $order_id ? wc_get_order( $order_id ) : false;
                if ( $order ) {
                    $this->object                         = $order;
                    $this->recipient                      = $order->get_billing_email();
                    $this->withdrawal_items               = $withdrawal_items;
                    $this->withdrawal_reason              = $reason;
                    $this->placeholders['{order_number}'] = $order->get_order_number();
                }

                if ( $this->is_enabled() && $this->get_recipient() ) {
                    $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
                }

                $this->restore_locale();
            }

            public function get_content_html() {
                return wc_get_template_html(
                    $this->template_html,
                    array(
                        'order'             => $this->object,
                        'withdrawal_items'  => $this->withdrawal_items,
                        'withdrawal_reason' => $this->withdrawal_reason,
                        'email_heading'     => $this->get_heading(),
                        'additional_content'=> '',
                        'sent_to_admin'     => false,
                        'plain_text'        => false,
                        'email'             => $this,
                    ),
                    '',
                    $this->template_base
                );
            }
        }
    }

    $email_classes['RG_Email_Withdrawal_Confirmation'] = new RG_Email_Withdrawal_Confirmation();
    return $email_classes;
}
