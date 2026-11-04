/**
 * Ångra köp (right of withdrawal) — front-end flow.
 *
 * Two steps over admin-ajax:
 *   1. lookup  — verify order number + email, render the order's items.
 *   2. submit  — send the selected items + reason, show a confirmation.
 */
(function ($) {
    'use strict';

    if (typeof rgWithdrawal === 'undefined') {
        return;
    }

    var $root = $('.rg-withdrawal');
    if (!$root.length) {
        return;
    }

    var $notice = $root.find('.rg-withdrawal__notice');
    var $lookup = $root.find('.rg-withdrawal__lookup');
    var $items = $root.find('.rg-withdrawal__items');
    var $list = $root.find('.rg-withdrawal__items-list');

    function showNotice(message, type) {
        $notice
            .removeClass('woocommerce-info woocommerce-error woocommerce-message')
            .addClass(type || 'woocommerce-info')
            .html(message)
            .show();
    }

    function clearNotice() {
        $notice.hide().empty();
    }

    function lockButton($form, locked) {
        $form.find('button[type="submit"]').prop('disabled', locked);
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    function renderItems(items) {
        $list.empty();

        items.forEach(function (item) {
            var max = parseInt(item.qty, 10) || 1;
            var row = '' +
                '<div class="form-row rg-withdrawal__item" style="display:flex;align-items:center;gap:.75em;border-bottom:1px solid #eee;padding:.5em 0;">' +
                '<label style="display:flex;align-items:center;gap:.5em;flex:1;margin:0;">' +
                '<input type="checkbox" class="rg-wd-item-check" value="' + parseInt(item.id, 10) + '">' +
                '<span>' + escapeHtml(item.name) + '</span>' +
                '</label>' +
                '<span class="rg-wd-item-total" style="white-space:nowrap;">' + escapeHtml(item.total) + '</span>' +
                '<input type="number" class="rg-wd-item-qty input-text" min="1" max="' + max + '" value="' + max + '" ' +
                'style="width:4.5em;" disabled aria-label="Antal">' +
                '</div>';
            $list.append(row);
        });
    }

    // Enable/disable the qty field with its checkbox.
    $root.on('change', '.rg-wd-item-check', function () {
        $(this).closest('.rg-withdrawal__item').find('.rg-wd-item-qty').prop('disabled', !this.checked);
    });

    // Step 1 — lookup.
    $lookup.on('submit', function (e) {
        e.preventDefault();
        clearNotice();
        lockButton($lookup, true);

        $.post(rgWithdrawal.ajaxUrl, {
            action: 'rg_withdrawal_lookup',
            nonce: rgWithdrawal.nonce,
            order_number: $('#rg-wd-order').val(),
            email: $('#rg-wd-email').val()
        }).done(function (response) {
            if (response && response.success) {
                renderItems(response.data.items);
                $lookup.hide();
                $items.show();
            } else {
                showNotice(response.data.message, 'woocommerce-error');
            }
        }).fail(function () {
            showNotice('Något gick fel. Försök igen senare.', 'woocommerce-error');
        }).always(function () {
            lockButton($lookup, false);
        });
    });

    // Back / cancel.
    $root.on('click', '.rg-withdrawal__back', function (e) {
        e.preventDefault();
        clearNotice();
        $items.hide();
        $lookup.show();
    });

    // Step 2 — submit.
    $items.on('submit', function (e) {
        e.preventDefault();
        clearNotice();

        var selected = [];
        $list.find('.rg-wd-item-check:checked').each(function () {
            var $row = $(this).closest('.rg-withdrawal__item');
            selected.push({
                id: $(this).val(),
                qty: $row.find('.rg-wd-item-qty').val()
            });
        });

        if (!selected.length) {
            showNotice('Välj minst en artikel att ångra.', 'woocommerce-error');
            return;
        }

        lockButton($items, true);

        $.post(rgWithdrawal.ajaxUrl, {
            action: 'rg_withdrawal_submit',
            nonce: rgWithdrawal.nonce,
            order_number: $('#rg-wd-order').val(),
            email: $('#rg-wd-email').val(),
            reason: $('#rg-wd-reason').val(),
            items: selected
        }).done(function (response) {
            if (response && response.success) {
                $lookup.hide();
                $items.hide();
                showNotice(response.data.message, 'woocommerce-message');
            } else {
                showNotice(response.data.message, 'woocommerce-error');
                lockButton($items, false);
            }
        }).fail(function () {
            showNotice('Något gick fel. Försök igen senare.', 'woocommerce-error');
            lockButton($items, false);
        });
    });

})(jQuery);
