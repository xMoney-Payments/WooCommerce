=== xMoney Payments ===
Contributors: xmoneypayments
Tags: payment, gateway, module
Requires at least: 4.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

xMoney enables new and existing store owners to quickly and effortlessly accept online credit card payments over their WooCommerce shop

== Description ==

***Note** :  In case you encounter any difficulties with integration, please contact us at support@xmoney.com and we'll assist you through the process.*

[xMoney Payments](https://www.xmoney.com) is a European certified acquiring bank with a sleek payment gateway optimized for online shops. We process payments from worldwide customers using Mastercard or Visa debit and credit cards. Increase your purchases by using our conversion rate optimized checkout flow and manage your transactions with our dashboard created specifically for online merchants like you. xMoney Payments is the official payment module built for WooCommerce

Our WooCommerce payment extension allows for fast and easy integration with the xMoney Payment Gateway. Quickly start accepting online credit card payments through a secure environment and a fully customizable checkout process. Give your customers the shopping experience they expect, and boost your online sales with our simple and elegant payment plugin.

To use our payment module and start processing you will need a xMoney Payments [merchant account](https://merchants.xmoney.com/sign-up). For any assistance during the on-boarding process, our [sales team](https://www.xmoney.com/contact) is happy to respond to any enquiries you may have.

== Installation ==

The easiest way of installing our module is by visiting the [official module page](https://wordpress.org/plugins/xmoney-payments/).
1. Log into your WordPress site.
2. Go to: Plugins > Add New.
3. Search for "xMoney Payments".
4. Select "Install Now" when you see it’s by xmoney.
5. Select "Activate Now" and you’re ready for customization.
6. Go to: xMoney Payments
7. Select **Yes** under **Live mode**. _(Unless you are testing)_
8. Enter your **Site ID**. _(xMoney Payments Staging Account ID: You can get one from [here](https://merchants.xmoney.com/login))_
9. Enter your **Private Key**. _(xMoney Payments Secret Key: You can get one from [here](https://merchants.xmoney.com/login))_
10. Select the custom page you want to redirect the customer after the payment **Redirect to custom page Thank you page**. _(Leave 'Default' to redirect to order confirmation default page.)_
11. Enter your technical **Contact Email**. _(This will be displayed to customers in case of a payment error)_
12. Save your changes.

You can also get the latest release from [GitHub](https://github.com/xMoney-Payments/WooCommerce/releases).

== Screenshots ==

1. Secure credit card processing for Visa and Mastercard
2. Quick and easy installation
3. Fully customizable checkout experience

== Frequently Asked Questions ==

Find below a list of the most common questions about the xMoney for WooCommerce plugin.

Don't find what you're looking for in this list? Feel free to reach us [by opening an issue on GitHub](https://github.com/xMoney-Payments/WooCommerc/issues/new).

Q: Does this support both live mode and test mode for testing?
A: Yes, it does - choosing between live and test mode is driven by the API keys you use. They are different in both environments. Live API keys won't work for the test environment, and vice-versa.

Q: What happens if I cancel the Order manually?
A:We are working on it. Our API is not ready yet for merchant manual changes. If you need to change the Order status, change it in WooCommerce and then go to our Merchant Dashboard to start a refund.

== Changelog ==

= v1.0.0 =

* Initial release

== Privacy ==

This plugin processes payment information by sending order data to xMoney Payments solely for the purpose of payment authorization, settlement, and fraud prevention. No usage tracking, analytics, or telemetry data is collected.

The plugin does not store personal data outside of WooCommerce. All customer data remains in your WordPress database and is only transmitted securely to xMoney Payments when required to process a payment.

For more information, please review xMoney Payments' legal and privacy documents:

• Terms & Conditions:
https://www.xmoney.com/legal/special-terms-conditions

• Privacy & Cookie Policy:
https://www.xmoney.com/legal/card-privacy-and-cookie-policy
