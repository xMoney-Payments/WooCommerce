let xmoneyPaymentCompleted = false;
let xmoneyFormInitialized = false;
let xmoneyFormInitializing = false;

/**
 * Detect if XMoney should be active
 */
function xmoneyIsSelected() {
    return jQuery('#payment_method_xmoney-payments').is(':checked');
}

/**
 * Initialize the XMoney inline form (Stripe-style)
 */
function initXMoneyForm() {

    const container = document.getElementById('xmoney-checkout-container');
    if (!container) {
        return;
    }

    const isSelected = jQuery('#payment_method_xmoney-payments').is(':checked');
    if (!isSelected) {
        return;
    }

    // NEW FIX: check if WC wiped the iframe
    if (xmoneyFormInitialized) {
        if (container.children.length === 0) {
            xmoneyFormInitialized = false;
        } else {
            return;
        }
    }

    if (xmoneyFormInitializing) {
        return;
    }

    if (typeof XMoneyPaymentForm === 'undefined' || !window.xmoneyData) {
        setTimeout(initXMoneyForm, 150);
        return;
    }

    xmoneyFormInitializing = true;

    const form = new XMoneyPaymentForm({
        container: 'xmoney-checkout-container',
        payload: xmoneyData.payload,
        checksum: xmoneyData.checksum,
        publicKey: xmoneyData.publicKey,
        sessionToken: xmoneyData.sessionToken,
        userId: xmoneyData.userId,
        options: {
            displayCardHolderName: true,
            displaySaveCardOption: xmoneyData.options.displaySaveCardOption,
        },

        onError: function (err) {
            console.error('[XMoney Error]', err);
        },

        onPaymentComplete: function (result) {
            if (xmoneyPaymentCompleted) {
                return;
            }
            xmoneyPaymentCompleted = true;

            jQuery('.woocommerce-checkout').block({
                message: null,
                overlayCSS: {background: '#fff', opacity: 0.7}
            });


            fetch(xmoneyData.confirmUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": xmoneyData.restNonce,
                },
                body: JSON.stringify({
                    order_id: xmoneyData.orderId,
                    result: result,
                    customer_id: result.customerId || null,
                    payment_method_id: result.paymentMethodId || null
                })
            })
                .then(r => r.json())
                .then(resp => {
                    jQuery('.woocommerce-checkout').unblock();

                    if (resp && resp.success) {
                        window.location.href = resp.redirect;
                    } else {
                        alert(resp && resp.message ? resp.message : "Payment failed.");
                    }
                })
                .catch(() => {
                    jQuery('.woocommerce-checkout').unblock();
                    alert("Network error while confirming payment.");
                });
        }
    });

    window.__xmoneyForm = form;
    xmoneyFormInitialized = true;
    xmoneyFormInitializing = false;
}

/**
 * Hide Woo's default button (Stripe style)
 */
function xmoneyHidePlaceOrderButton() {
    if (xmoneyIsSelected()) {
        jQuery('button#place_order, button[name="woocommerce_checkout_place_order"], .place-order button')
            .css({opacity: 0, pointerEvents: 'none', height: 0, padding: 0, margin: 0, overflow: 'hidden'});
    }
}

function xmoneyShowPlaceOrderButton() {
    jQuery('button#place_order, button[name="woocommerce_checkout_place_order"], .place-order button')
        .css({opacity: '', pointerEvents: '', height: '', padding: '', margin: '', overflow: ''});
}

/**
 * Re-init when WC updates checkout (WooCommerce AJAX refresh)
 */
jQuery(document.body).on('updated_checkout payment_method_selected', function () {
    if (xmoneyIsSelected()) {
        xmoneyHidePlaceOrderButton();
        setTimeout(initXMoneyForm, 50); // ensure DOM replaced fully
    } else {
        xmoneyShowPlaceOrderButton();
    }
});

/**
 * DOM Ready: first attempt
 */
document.addEventListener('DOMContentLoaded', function () {
    if (xmoneyIsSelected()) {
        xmoneyHidePlaceOrderButton();
    }
    initXMoneyForm();
});