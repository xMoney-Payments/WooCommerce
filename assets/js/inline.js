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
    // AJAX URL helper - fallback if wc_checkout_params not available
    // -------------------------------------------------------------------------
    function getAjaxUrl() {
        if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.ajax_url) {
            return wc_checkout_params.ajax_url;
        }
        if (typeof xmoneyConfig !== 'undefined' && xmoneyConfig.ajaxUrl) {
            return xmoneyConfig.ajaxUrl;
        }
        // Last resort fallback
        return '/wp-admin/admin-ajax.php';
    }

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
        errorDiv.setAttribute('role', 'alert');
        errorDiv.textContent = message || 'Payment error. Please try again.';

        const formEl = $('form.checkout')[0];
        if (formEl) {
            formEl.insertBefore(errorDiv, formEl.firstChild);
        }
        
        // Prevent error from triggering checkout refresh
        return false;
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

    function validateRequiredFields() {
        const missingFields = [];

        // Check WooCommerce required billing fields
        $('.woocommerce-billing-fields .validate-required').each(function() {
            const $wrapper = $(this);
            const $field = $wrapper.find('input, select, textarea').not('[type="hidden"]');
            
            if ($field.length > 0 && $field.is(':visible')) {
                const val = $field.val();
                
                if (!val || (typeof val === 'string' && val.trim() === '')) {
                    const label = $wrapper.find('label').first().clone().children().remove().end().text().replace('*', '').trim();
                    if (label) {
                        missingFields.push(label);
                    }
                }
            }
        });

        // Validate email format if email field exists and has value
        const $emailField = $('#billing_email');
        if ($emailField.length > 0) {
            const email = $emailField.val();
            if (email && email.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                const emailLabel = $emailField.closest('.form-row').find('label').first().clone().children().remove().end().text().replace('*', '').trim();
                missingFields.push((emailLabel || 'Email') + ' (invalid format)');
            }
        }

        return missingFields;
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
                if (resp && resp.success) {
                    unblockCheckout();
                    safeRedirect(resp.redirect);
                } else {
                    // Validation error - reset state and keep iframe
                    xmoneyPaymentCompleted = false;
                    unblockCheckout();
                    const msg = resp && resp.message ? resp.message : 'Payment failed.';
                    showCheckoutError(msg);
                    $('html, body').animate({
                        scrollTop: $('form.checkout').offset().top - 100
                    }, 500);
                }
            })
            .catch(function () {
                xmoneyPaymentCompleted = false;
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
        
        // Hide SDK's submit button - we'll use Place Order button with submit()
        options.displaySubmitButton = false;

        const formConfig = {
            container: 'xmoney-checkout-container',
            orderPayload: data.payload,
            orderChecksum: data.checksum,
            publicKey: data.publicKey,
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
            url: getAjaxUrl(),
            type: 'POST',
            data: payload,
            success: function(response) {
                // If server returns new payload/checksum, update the SDK iframe
                if (response && response.success && response.data && response.data.payload && response.data.checksum) {
                    // Update stored data
                    window.xmoneyData.payload = response.data.payload;
                    window.xmoneyData.checksum = response.data.checksum;
                    
                    // Update the SDK iframe with new order data
                    if (window.__xmoneyForm && typeof window.__xmoneyForm.updateOrder === 'function') {
                        window.__xmoneyForm.updateOrder(response.data.payload, response.data.checksum);
                    }
                }
            },
            error: function(xhr, status, error) {
                // Silently fail - don't disrupt user experience
                // The order will still have the data from when it was created
            }
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

        draftOrderCreating = true;
        blockCheckout();

        const payload = collectCheckoutPayload({
            action: 'xmoney_create_draft_order',
            nonce: $('input[name="woocommerce-process-checkout-nonce"]').val()
        });

        $.ajax({
            url: getAjaxUrl(),
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
        $('form.checkout').on('checkout_place_order', function () {
            if (!xmoneyIsSelected()) {
                return true; // Let other payment methods work normally
            }
            
            // Check if required fields are filled
            let hasErrors = false;
            $('.woocommerce-billing-fields .validate-required').each(function() {
                const $field = $(this).find('input, select, textarea').not('[type="hidden"]');
                if ($field.length > 0 && $field.is(':visible')) {
                    const val = $field.val();
                    if (!val || (typeof val === 'string' && val.trim() === '')) {
                        hasErrors = true;
                        return false; // Break loop
                    }
                }
            });
            
            if (hasErrors) {
                // Let WooCommerce show its validation errors
                return true;
            }
            
            // All required fields filled - update draft order with final billing info, then call SDK submit()
            if (!window.__xmoneyForm || !xmoneyFormInitialized) {
                showCheckoutError('Payment form is not ready. Please wait a moment and try again.');
                return false;
            }
            
            if (typeof window.__xmoneyForm.submit !== 'function') {
                showCheckoutError('Payment SDK submit() method is not available. Please update the SDK.');
                return false;
            }
            
            // Update draft order with current billing info before submitting payment
            blockCheckout();
            
            const payload = collectCheckoutPayload({
                action: 'xmoney_update_draft_order',
                order_id: window.xmoneyData.orderId,
                nonce: $('input[name="woocommerce-process-checkout-nonce"]').val()
            });
            
            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: payload,
                success: function(response) {
                    // Order updated, now call SDK submit()
                    window.__xmoneyForm.submit();
                },
                error: function() {
                    unblockCheckout();
                    showCheckoutError('Failed to update order. Please try again.');
                }
            });
            
            return false;
        });
    }

    // -------------------------------------------------------------------------
    // Field listeners & Woo events
    // -------------------------------------------------------------------------

    function bindFieldListeners() {
        const keyFields = '#billing_email, #billing_first_name, #billing_last_name';

        $('body').on('change blur', keyFields, function () {
            if (!xmoneyIsSelected()) {
                return;
            }

            clearTimeout(window.xmoneyFieldTimeout);
            window.xmoneyFieldTimeout = setTimeout(function () {
                createDraftOrderAndInitialize();
            }, 500);
        });
    }

    function bindWooEvents() {
        $(document.body).on('updated_checkout payment_method_selected', function () {
            if (xmoneyIsSelected()) {
                // Always try to create draft order when payment method selected
                createDraftOrderAndInitialize();

                // Try to init iframe
                setTimeout(function() {
                    initXMoneyForm();
                }, 300);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Bootstrapping
    // -------------------------------------------------------------------------

    function bootstrapInline() {
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