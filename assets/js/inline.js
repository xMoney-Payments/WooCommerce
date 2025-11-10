let xmoneyPaymentCompleted = false;
function initXMoneyForm() {
        const container = document.getElementById('xmoney-checkout-container');
        if (!container || typeof XMoneyPaymentForm === 'undefined' || !window.xmoneyData) {
            setTimeout(initXMoneyForm, 150);
            return;
        }

        window.__xmoneyFormInitialized = true;

        const cards = Array.isArray(xmoneyData.savedCards) ? xmoneyData.savedCards : [];

        const customThemeStyles = {
            theme: "light",
        }

        const form = new XMoneyPaymentForm({
            container: 'xmoney-checkout-container',
            payload: xmoneyData.payload,
            checksum: xmoneyData.checksum,
            publicKey: xmoneyData.publicKey,
            sessionToken: xmoneyData.sessionToken,
            userId: xmoneyData.userId,
            options: {
                displayCardHolderName: true, // <-- This must be present
                displaySaveCardOption: xmoneyData.userId > 0,
            },

            onPaymentComplete: function (result) {

                jQuery('.woocommerce-checkout').block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.7
                    }
                });

                if (xmoneyPaymentCompleted) {
                    return;
                }

                xmoneyPaymentCompleted = true;

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
                }).then(r => r.json()).then(resp => {
                    jQuery('.woocommerce-checkout').unblock();

                    if (resp && resp.success) {
                        window.location.href = resp.redirect;
                    } else {
                        alert(resp && resp.message ? resp.message : "Payment failed.");
                    }
                }).catch(function () {
                    jQuery('.woocommerce-checkout').unblock();
                    alert("Network error while confirming payment.");
                });
            },
            onError: function (err) {
                console.error('xMoney error', err);
            }
        });

        window.__xmoneyForm = form;

}

function xmoneyHidePlaceOrderIfSelected() {
    if (jQuery('#payment_method_xmoney-payments').is(':checked')) {
        jQuery('button#place_order, button[name="woocommerce_checkout_place_order"], .place-order button')
            .css({opacity: 0, pointerEvents: 'none', height: 0, padding: 0, margin: 0, overflow: 'hidden'});
    }
}

function xmoneyShowPlaceOrder() {
    jQuery('button#place_order, button[name="woocommerce_checkout_place_order"], .place-order button')
        .css({
            opacity: '',
            pointerEvents: '',
            height: '',
            padding: '',
            margin: '',
            overflow: ''
        });
}

document.addEventListener('DOMContentLoaded', xmoneyHidePlaceOrderIfSelected);
jQuery(document.body).on('payment_method_selected updated_checkout', function () {
    if (jQuery('#payment_method_xmoney-payments').is(':checked')) {
        xmoneyHidePlaceOrderIfSelected();
    } else {
        xmoneyShowPlaceOrder();
    }
});
document.addEventListener('DOMContentLoaded', initXMoneyForm);
jQuery(document.body).on('updated_checkout', initXMoneyForm);






