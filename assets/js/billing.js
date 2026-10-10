/**
 * Plans & Billing: opens Razorpay Checkout for the pending order rendered in #razorpay-order and,
 * on success, posts the payment id + signature to billing.verify (#razorpay-verify) for server-side checking.
 */
(function () {
  'use strict';

  var data = document.getElementById('razorpay-order');
  var form = document.getElementById('razorpay-verify');
  if (!data || !form) return;

  var options = JSON.parse(data.textContent);

  function open() {
    if (typeof window.Razorpay !== 'function') return;
    var rzp = new window.Razorpay(Object.assign({}, options, {
      handler: function (res) {
        form.elements.razorpay_order_id.value = res.razorpay_order_id;
        form.elements.razorpay_payment_id.value = res.razorpay_payment_id;
        form.elements.razorpay_signature.value = res.razorpay_signature;
        form.submit();
      }
    }));
    rzp.open();
  }

  document.querySelectorAll('[data-razorpay-open]').forEach(function (btn) {
    btn.addEventListener('click', open);
  });
  // checkout.js is deferred too; window "load" fires after every deferred script has run
  window.addEventListener('load', open);
})();
