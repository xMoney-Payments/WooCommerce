/* global jQuery, wc_checkout_params, xmoneyConfig, xmoneyData */

(function ($) {
    'use strict';

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------
    let xmoneyPaymentCompleted = false;
    let xmoneyFormInitialized = false;
    let xmoneyFormInitializing = false;

    let paymentPreparing = false;

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

    /**
     * Normalize an xMoney SDK transaction object into a flat result for the server.
     *
     * The SDK transaction object may contain nested fields. This function extracts
     * all relevant IDs so the PHP backend can find the correct transaction for refunds.
     *
     * @param {object} tx - The transaction object from the SDK (result.transaction).
     * @returns {object} Normalized result with all available ID fields preserved.
     */
    function normalizeTransactionResult(tx) {
        if (!tx) {
            return {};
        }

        const status = tx.transactionStatus || tx.status || tx.orderStatus || '';

        return {
            // Preserve ALL ID fields so the server can pick the right one.
            // The SDK uses 'id' on the transaction object as the transaction ID.
            id: tx.id,
            transactionId: tx.transactionId || tx.transactionID || tx.id || null,
            orderId: tx.orderId || tx.orderID || null,
            externalOrderId: tx.externalOrderId,
            orderStatus: status,
            transactionStatus: status,
            customerId: tx.customerData ? tx.customerData.id : (tx.customerId || null),
            customerData: tx.customerData || null,
            cardId: tx.cardId || tx.transactionMethodId || null,
            identifier: tx.customerData ? tx.customerData.identifier : (tx.identifier || null),
            // Pass the full raw transaction object so the server has everything
            _rawTransaction: tx
        };
    }

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
            console.error('[xMoney] Missing xmoneyData');
            xmoneyPaymentCompleted = false;
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
                    // Clear WC cart fragments from browser storage so mini-cart
                    // and cart page don't show stale items after redirect.
                    try {
                        if (typeof sessionStorage !== 'undefined') {
                            for (var i = sessionStorage.length - 1; i >= 0; i--) {
                                var key = sessionStorage.key(i);
                                if (key && (key.indexOf('wc_cart') === 0 || key.indexOf('wc_fragments') === 0)) {
                                    sessionStorage.removeItem(key);
                                }
                            }
                        }
                    } catch (e) { /* ignore storage errors */ }

                    // Fire-and-forget AJAX call to empty the server-side cart.
                    // AJAX context has proper WC session access (unlike REST API).
                    $.post(getAjaxUrl(), {
                        action: 'xmoney_empty_cart',
                        nonce: window.xmoneyData.cartNonce || ''
                    });

                    unblockCheckout();
                    safeRedirect(resp.redirect);
                } else {
                    // Validation error - reset state and keep iframe
                    xmoneyPaymentCompleted = false;
                    unblockCheckout();
                    const msg = resp && resp.message ? resp.message : 'Payment failed.';
                    console.error('[xMoney] Payment failed:', msg);
                    showCheckoutError(msg);
                    $('html, body').animate({
                        scrollTop: $('form.checkout').offset().top - 100
                    }, 500);
                }
            })
            .catch(function (err) {
                console.error('[xMoney] Network error:', err);
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
        options.enableBackgroundRefresh = true;
        const formConfig = {
            container: 'xmoney-checkout-container',
            orderPayload: data.payload,
            orderChecksum: data.checksum,
            publicKey: data.publicKey,
            userId: data.userId,
            options: options,

            onError: function (err) {
                console.error('[xMoney SDK] onError:', err);
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
                console.log('[xMoney SDK] onSuccess called with result:', result);
                // SDK may call onSuccess for intermediate states (e.g. 3DS)
                if (result && result.needs3dSecureRedirect) {
                    return;
                }

                // Handle transaction object structure
                if (result && result.transaction) {
                    handlePaymentComplete(normalizeTransactionResult(result.transaction));
                    return;
                }

                if (result && typeof result === 'object' && (result.orderStatus || result.transactionStatus)) {
                    handlePaymentComplete(result);
                } else {
                    console.log('[xMoney SDK] onSuccess called but missing id/orderStatus:', result);
                }
            },

            onPaymentComplete: function (result) {
                console.log('[xMoney SDK] onPaymentComplete called with result:', result);
                if (!result) {
                    console.log('[xMoney SDK] onPaymentComplete called with no result');
                    return;
                }

                // Defensive: normalize both direct and 3DS postMessage calls
                if (result.needs3dSecureRedirect) {
                    return;
                }

                // Handle transaction object structure
                if (result.transaction) {
                    handlePaymentComplete(normalizeTransactionResult(result.transaction));
                    return;
                }

                if ((result.orderStatus || result.transactionStatus)) {
                    handlePaymentComplete(result);
                } else {
                    console.log('[xMoney SDK] onPaymentComplete missing id/orderStatus:', result);
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
            
            // Handle onPaymentComplete with transaction data
            if (data.type === 'onPaymentComplete' && data.data) {
                
                // Check for transaction object structure
                if (data.data.transaction) {
                    handlePaymentComplete(normalizeTransactionResult(data.data.transaction));
                    return;
                }
                
                // Fallback to direct data structure
                if (data.data.id && (data.data.orderStatus || data.data.transactionStatus)) {
                    handlePaymentComplete(data.data);
                    return;
                }
            }

            // SDK "onSuccess"
            if (data.type === 'onSuccess' && data.data) {
                if (!data.data.needs3dSecureRedirect) {
                    // Check for transaction object
                    if (data.data.transaction) {
                        handlePaymentComplete(normalizeTransactionResult(data.data.transaction));
                        return;
                    }
                    // Fallback
                    if (data.data.id && (data.data.orderStatus || data.data.transactionStatus)) {
                        handlePaymentComplete(data.data);
                    }
                }
                return;
            }

            // Custom / legacy "PAYMENT_COMPLETE" or similar
            if (data.type === 'PAYMENT_COMPLETE' || (data.orderStatus && String(data.orderStatus).indexOf('complete') !== -1)) {
                const payload = data.result || data;
                if (payload && payload.id && (payload.orderStatus || payload.transactionStatus)) {
                    handlePaymentComplete(payload);
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Payment data helpers (no WP order created until Place Order)
    // -------------------------------------------------------------------------

    function collectCheckoutPayload(base) {
        const formData = $('form.checkout').serializeArray();
        const payload = base || {};

        formData.forEach(function (item) {
            payload[item.name] = item.value;
        });

        return payload;
    }

    function refreshPaymentData() {
        if (!window.xmoneyData) {
            return;
        }

        const payload = collectCheckoutPayload({
            action: 'xmoney_prepare_payment',
            nonce: typeof xmoneyConfig !== 'undefined' ? xmoneyConfig.nonce : ''
        });

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: payload,
            success: function(response) {
                if (response && response.success && response.data && response.data.payload && response.data.checksum) {
                    window.xmoneyData.payload = response.data.payload;
                    window.xmoneyData.checksum = response.data.checksum;
                    if (response.data.customer) {
                        window.xmoneyData.customer = response.data.customer;
                    }
                    if (window.__xmoneyForm && typeof window.__xmoneyForm.updateOrder === 'function') {
                        window.__xmoneyForm.updateOrder({ orderPayload: response.data.payload, orderChecksum: response.data.checksum });
                    }
                }
            },
            error: function() {
                // Silently fail - payment data will be refreshed when order is created
            }
        });
    }

    function preparePaymentAndInitialize() {
        if (!xmoneyIsSelected()) {
            return;
        }

        if (paymentPreparing) {
            return;
        }

        // If SDK is already initialized, don't reinitialize
        if (window.xmoneyData && xmoneyFormInitialized) {
            return;
        }

        paymentPreparing = true;
        blockCheckout();

        const payload = collectCheckoutPayload({
            action: 'xmoney_prepare_payment',
            nonce: typeof xmoneyConfig !== 'undefined' ? xmoneyConfig.nonce : ''
        });

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: payload,
            success: function (response) {
                paymentPreparing = false;
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
                paymentPreparing = false;
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
            
            if (!window.__xmoneyForm || !xmoneyFormInitialized) {
                showCheckoutError('Payment form is not ready. Please wait a moment and try again.');
                return false;
            }
            
            if (typeof window.__xmoneyForm.submit !== 'function') {
                showCheckoutError('Payment SDK submit() method is not available. Please update the SDK.');
                return false;
            }
            
            blockCheckout();
            
            // Create the WP order now (deferred until the user actually submits payment)
            const orderPayload = collectCheckoutPayload({
                action: 'xmoney_create_draft_order',
                nonce: $('input[name="woocommerce-process-checkout-nonce"]').val()
            });
            
            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: orderPayload,
                success: function(response) {
                    if (response && response.success && response.data) {
                        // Merge real order data into xmoneyData (orderId, confirmUrl, restNonce, etc.)
                        window.xmoneyData = Object.assign(window.xmoneyData || {}, response.data);
                        
                        // Update SDK with payload containing the real orderId
                        if (window.__xmoneyForm && typeof window.__xmoneyForm.updateOrder === 'function') {
                            window.__xmoneyForm.updateOrder({
                                orderPayload: response.data.payload,
                                orderChecksum: response.data.checksum
                            });
                        }
                        
                        // Allow a short delay for updateOrder to take effect, then submit payment
                        setTimeout(function() {
                            window.__xmoneyForm.submit();
                        }, 150);
                    } else {
                        unblockCheckout();
                        var msg = response && response.data && response.data.message ? response.data.message : 'Failed to create order.';
                        showCheckoutError(msg);
                    }
                },
                error: function() {
                    unblockCheckout();
                    showCheckoutError('Failed to create order. Please try again.');
                }
            });
            
            return false;
        });
    }

    // -------------------------------------------------------------------------
    // Field listeners & Woo events
    // -------------------------------------------------------------------------

    function bindFieldListeners() {
        const keyFields = '#billing_email, #billing_first_name, #billing_last_name, #billing_address_1, #billing_city, #billing_postcode, #billing_country, #billing_phone';

        $('body').on('change blur', keyFields, function () {
            if (!xmoneyIsSelected()) {
                return;
            }

            clearTimeout(window.xmoneyFieldTimeout);
            window.xmoneyFieldTimeout = setTimeout(function () {
                if (window.xmoneyData && xmoneyFormInitialized) {
                    // SDK already initialized - refresh payment data with updated billing info
                    refreshPaymentData();
                } else {
                    // No payment data yet - prepare and initialize from cart
                    preparePaymentAndInitialize();
                }
            }, 500);
        });
    }

    function bindWooEvents() {
        $(document.body).on('updated_checkout payment_method_selected', function () {
            if (xmoneyIsSelected()) {
                // Prepare payment data from cart and initialize SDK (no WP order created)
                preparePaymentAndInitialize();

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