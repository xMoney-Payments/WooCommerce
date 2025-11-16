/* global jQuery, wc_checkout_params, xmoneyConfig, xmoneyData */

(function ($) {
    'use strict';

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------
    let xmoneyPaymentCompleted = false;
    let xmoneyFormInitialized = false;
    let xmoneyFormInitializing = false;

    let draftOrderCreating = false;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    function xmoneyIsSelected() {
        return $('#payment_method_xmoney-payments').is(':checked');
    }

    function safeRedirect(url) {
        if (!url || typeof url !== 'string') {
            return;
        }

        try {
            const safeUrl = new URL(url, window.location.origin);

            if (safeUrl.origin === window.location.origin) {
                window.location.assign(safeUrl.toString());
            } else {
                // eslint-disable-next-line no-console
                console.error('Unsafe redirect blocked:', url);
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Invalid redirect URL:', url);
        }
    }

    function showCheckoutError(message) {
        // Remove existing errors/notices
        $('.woocommerce-error, .woocommerce-message').remove();

        const errorDiv = document.createElement('div');
        errorDiv.className = 'woocommerce-error';
        errorDiv.textContent = message || 'Payment error. Please try again.';

        const formEl = $('form.checkout')[0];
        if (formEl) {
            formEl.prepend(errorDiv);
        }
    }

    function blockCheckout() {
        $('.woocommerce-checkout').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.7
            }
        });
    }

    function unblockCheckout() {
        $('.woocommerce-checkout').unblock();
    }

    function hidePlaceOrderButton() {
        if (!xmoneyIsSelected()) {
            return;
        }

        $('button#place_order, button[name="woocommerce_checkout_place_order"], .place-order button')
            .css({
                opacity: 0,
                pointerEvents: 'none',
                height: 0,
                padding: 0,
                margin: 0,
                overflow: 'hidden'
            });
    }

    function showPlaceOrderButton() {
        $('button#place_order, button[name="woocommerce_checkout_place_order"], .place-order button')
            .css({
                opacity: '',
                pointerEvents: '',
                height: '',
                padding: '',
                margin: '',
                overflow: ''
            });
    }

    // -------------------------------------------------------------------------
    // Payment completion
    // -------------------------------------------------------------------------

    function handlePaymentComplete(result) {
        if (xmoneyPaymentCompleted) {
            return;
        }

        xmoneyPaymentCompleted = true;
        blockCheckout();

        if (typeof window.xmoneyData === 'undefined') {
            unblockCheckout();
            showCheckoutError('Unable to complete payment. Missing payment data.');
            return;
        }

        fetch(window.xmoneyData.confirmUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.xmoneyData.restNonce
            },
            body: JSON.stringify({
                order_id: window.xmoneyData.orderId,
                result: result,
                customer_id: result && result.customerId ? result.customerId : null,
                payment_method_id: result && result.paymentMethodId ? result.paymentMethodId : null
            })
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (resp) {
                unblockCheckout();

                if (resp && resp.success) {
                    safeRedirect(resp.redirect);
                } else {
                    const msg = resp && resp.message ? resp.message : 'Payment failed.';
                    showCheckoutError(msg);
                }
            })
            .catch(function () {
                unblockCheckout();
                showCheckoutError('Network error while confirming payment.');
            });
    }

    // -------------------------------------------------------------------------
    // XMoney Inline Form
    // -------------------------------------------------------------------------

    function initXMoneyForm() {
        const container = document.getElementById('xmoney-checkout-container');
        if (!container) {
            return;
        }

        const selectorExists = $('#payment_method_xmoney-payments').length > 0;
        const selected = xmoneyIsSelected();

        // On checkout page: only init when xMoney is selected
        if (selectorExists && !selected) {
            return;
        }

        // If form already initialized and iframe still present, do nothing
        if (xmoneyFormInitialized && container.children.length > 0) {
            return;
        }

        // If WooCommerce refreshed and wiped iframe, allow re-init
        if (xmoneyFormInitialized && container.children.length === 0) {
            xmoneyFormInitialized = false;
        }

        if (xmoneyFormInitializing) {
            return;
        }

        if (typeof XMoneyPaymentForm === 'undefined') {
            // SDK not ready yet, retry a bit later
            setTimeout(initXMoneyForm, 150);
            return;
        }

        if (typeof window.xmoneyData === 'undefined') {
            // On checkout: wait for draft / real order / inline data from server
            return;
        }

        xmoneyFormInitializing = true;

        const data = window.xmoneyData;
        const options = data.options || {
            displayCardHolderName: false,
            displaySaveCardOption: false,
            enableSavedCards: false
        };

        const formConfig = {
            container: 'xmoney-checkout-container',
            payload: data.payload,
            checksum: data.checksum,
            publicKey: data.publicKey,
            sessionToken: data.sessionToken,
            userId: data.userId,
            options: options,

            onError: function (err) {
                unblockCheckout();

                let errorMsg = 'Payment initialization error.';
                if (err && err.message) {
                    errorMsg += ' ' + err.message;
                } else if (typeof err === 'string') {
                    errorMsg += ' ' + err;
                }

                showCheckoutError(errorMsg);
            },

            onSuccess: function (result) {
                // SDK may call onSuccess for intermediate states (e.g. 3DS)
                if (result && result.needs3dSecureRedirect) {
                    return;
                }

                if (result && typeof result === 'object' && result.id && result.orderStatus) {
                    handlePaymentComplete(result);
                }
            },

            onPaymentComplete: function (result) {
                if (!result) {
                    return;
                }

                // Defensive: normalize both direct and 3DS postMessage calls
                if (result.needs3dSecureRedirect) {
                    return;
                }

                if (result.id && result.orderStatus) {
                    handlePaymentComplete(result);
                }
            }
        };

        const form = new XMoneyPaymentForm(formConfig);

        window.__xmoneyForm = form;
        xmoneyFormInitialized = true;
        xmoneyFormInitializing = false;

        // 3DS / iframe fallback via postMessage
        window.addEventListener('message', function (event) {
            if (!event || !event.origin || typeof event.data !== 'object') {
                return;
            }

            if (!event.origin.includes('xmoney.com')) {
                return;
            }

            const data = event.data;

            // SDK "onSuccess"
            if (data.type === 'onSuccess' && data.data) {
                if (!data.data.needs3dSecureRedirect && data.data.id && data.data.orderStatus) {
                    handlePaymentComplete(data.data);
                }
                return;
            }

            // Custom / legacy "PAYMENT_COMPLETE" or similar
            if (data.type === 'PAYMENT_COMPLETE' || (data.orderStatus && String(data.orderStatus).indexOf('complete') !== -1)) {
                const payload = data.result || data;
                if (payload && payload.id && payload.orderStatus) {
                    handlePaymentComplete(payload);
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Draft order helpers
    // -------------------------------------------------------------------------

    function collectCheckoutPayload(base) {
        const formData = $('form.checkout').serializeArray();
        const payload = base || {};

        formData.forEach(function (item) {
            payload[item.name] = item.value;
        });

        return payload;
    }

    function updateDraftOrderAddress() {
        if (!window.xmoneyData || !window.xmoneyData.orderId) {
            return;
        }

        const payload = collectCheckoutPayload({
            action: 'xmoney_update_draft_order',
            order_id: window.xmoneyData.orderId,
            nonce: $('input[name="woocommerce-process-checkout-nonce"]').val()
        });

        $.ajax({
            url: wc_checkout_params.ajax_url,
            type: 'POST',
            data: payload
        });
    }

    function createDraftOrderAndInitialize() {
        if (!xmoneyIsSelected()) {
            return;
        }

        if (draftOrderCreating) {
            return;
        }

        if (window.xmoneyData && window.xmoneyData.orderId) {
            return;
        }

        const email = $('#billing_email').val();
        const firstName = $('#billing_first_name').val();
        const lastName = $('#billing_last_name').val();

        if (!email || !firstName || !lastName) {
            return;
        }

        draftOrderCreating = true;
        blockCheckout();

        const payload = collectCheckoutPayload({
            action: 'xmoney_create_draft_order',
            nonce: $('input[name="woocommerce-process-checkout-nonce"]').val()
        });

        $.ajax({
            url: wc_checkout_params.ajax_url,
            type: 'POST',
            data: payload,
            success: function (response) {
                draftOrderCreating = false;
                unblockCheckout();

                if (response && response.success && response.data) {
                    $('.xmoney-recreate-notice').remove();
                    window.xmoneyData = response.data;

                    setTimeout(function () {
                        initXMoneyForm();
                    }, 100);
                }
            },
            error: function () {
                draftOrderCreating = false;
                unblockCheckout();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Checkout submit interception (Stripe-like flow)
    // -------------------------------------------------------------------------

    function bindCheckoutSubmitInterceptor() {
        $('form.checkout').on('checkout_place_order_xmoney-payments', function () {
            const $form = $(this);

            $.ajax({
                type: 'POST',
                url: wc_checkout_params.checkout_url,
                data: $form.serialize(),
                dataType: 'json',
                beforeSend: function () {
                    $form.addClass('processing').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                success: function (result) {
                    $form.removeClass('processing').unblock();

                    if (result && result.result === 'success' && result.xmoney_inline_data) {
                        window.xmoneyData = result.xmoney_inline_data;
                        if (!window.xmoneyData.options) {
                            window.xmoneyData.options = {
                                displaySaveCardOption: true,
                                enableSavedCards: false,
                                displayCardHolderName: false
                            };
                        }

                        setTimeout(function () {
                            initXMoneyForm();
                        }, 300);
                        return;
                    }

                    // Handle errors / redirects
                    $('.woocommerce-error, .woocommerce-message').remove();

                    if (result && result.messages) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(result.messages, 'text/html');
                        const nodes = Array.from(doc.body.children).filter(function (node) {
                            return ['DIV', 'P', 'UL', 'LI'].indexOf(node.tagName) !== -1;
                        });

                        const checkoutForm = $('form.checkout')[0];

                        nodes.forEach(function (node) {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'woocommerce-error';
                            wrapper.textContent = node.textContent;
                            checkoutForm.prepend(wrapper);
                        });
                    }

                    if (result && result.redirect) {
                        safeRedirect(result.redirect);
                    }

                    if (result && result.reload) {
                        window.location.reload();
                    }
                },
                error: function () {
                    $form.removeClass('processing').unblock();
                }
            });

            // Prevent default WooCommerce handling
            return false;
        });
    }

    // -------------------------------------------------------------------------
    // Field listeners & Woo events
    // -------------------------------------------------------------------------

    function bindFieldListeners() {
        const keyFields = '#billing_email, #billing_first_name, #billing_last_name';
        const allFields = 'form.checkout input, form.checkout select, form.checkout textarea';

        $('body').on('change blur', keyFields, function () {
            if (!xmoneyIsSelected()) {
                return;
            }

            clearTimeout(window.xmoneyFieldTimeout);
            window.xmoneyFieldTimeout = setTimeout(function () {
                createDraftOrderAndInitialize();
            }, 500);
        });

        $('body').on('change', allFields, function () {
            if (!xmoneyIsSelected() || !window.xmoneyData || !window.xmoneyData.orderId) {
                return;
            }

            clearTimeout(window.xmoneyAddressTimeout);
            window.xmoneyAddressTimeout = setTimeout(function () {
                updateDraftOrderAddress();
            }, 1000);
        });
    }

    function bindWooEvents() {
        $(document.body).on('updated_checkout payment_method_selected', function () {
            if (xmoneyIsSelected()) {
                hidePlaceOrderButton();

                if (!window.xmoneyData || !window.xmoneyData.orderId) {
                    createDraftOrderAndInitialize();
                } else {
                    updateDraftOrderAddress();
                }
            } else {
                showPlaceOrderButton();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Bootstrapping
    // -------------------------------------------------------------------------

    function bootstrapInline() {
        if (xmoneyIsSelected()) {
            hidePlaceOrderButton();
        }

        initXMoneyForm();
    }

    document.addEventListener('DOMContentLoaded', function () {
        bootstrapInline();
    });

    if (typeof jQuery !== 'undefined') {
        $(document).ready(function () {
            setTimeout(initXMoneyForm, 100);
        });

        $(window).on('load', function () {
            setTimeout(initXMoneyForm, 100);
        });
    }

    if (typeof window.xmoneyData !== 'undefined') {
        setTimeout(initXMoneyForm, 50);
    }

    // Attach listeners
    $(function () {
        bindCheckoutSubmitInterceptor();
        bindFieldListeners();
        bindWooEvents();
    });

})(jQuery);