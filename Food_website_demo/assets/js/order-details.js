"use strict";

(function () {
  var store = window.OrderStore.createOrderStore(window.localStorage);
  var currency = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD"
  });

  var content = document.querySelector("[data-order-details]");
  var params = new URLSearchParams(window.location.search);
  var targetId = params.get("id");
  var order = targetId ? store.getOrderById(targetId) : null;

  if (!order) {
    content.innerHTML = [
      "<div class='message-card'>",
      "  <p class='eyebrow'>Order details</p>",
      "  <h1>No order selected</h1>",
      "  <p>Place an order from checkout, then open its details page.</p>",
      "  <div class='message-card__actions'>",
      "    <a class='btn' href='./checkout.html'>Go to checkout</a>",
      "    <a class='btn btn--secondary' href='./orders.html'>Open history</a>",
      "  </div>",
      "</div>"
    ].join("");
    return;
  }

  content.innerHTML = [
    "<div class='detail-grid'>",
    "  <section class='detail-card'>",
    "    <p class='eyebrow'>Order summary</p>",
    "    <h1>" + order.code + "</h1>",
    "    <p class='detail-card__lead'>Status: <strong>" + order.status + "</strong></p>",
    "    <dl class='detail-list'>",
    "      <div><dt>Customer</dt><dd>" + order.customerName + "</dd></div>",
    "      <div><dt>Phone</dt><dd>" + order.phone + "</dd></div>",
    "      <div><dt>Pickup</dt><dd>" + order.pickupDate + " at " + (order.pickupSlotLabel || order.pickupSlot) + "</dd></div>",
    "      <div><dt>Payment</dt><dd>" + order.paymentMethod + "</dd></div>",
    "      <div><dt>Note</dt><dd>" + (order.note || "No special request") + "</dd></div>",
    "    </dl>",
    "  </section>",
    "  <section class='detail-card'>",
    "    <p class='eyebrow'>Cost breakdown</p>",
    "    <div class='price-line'><span>Subtotal</span><strong>" + currency.format(order.subtotal) + "</strong></div>",
    "    <div class='price-line'><span>Service fee</span><strong>" + currency.format(order.serviceFee) + "</strong></div>",
    "    <div class='price-line price-line--total'><span>Total</span><strong>" + currency.format(order.total) + "</strong></div>",
    "  </section>",
    "</div>",
    "<section class='detail-card'>",
    "  <p class='eyebrow'>Order items</p>",
    "  <div class='detail-items'>",
    order.items.map(function (item) {
      return [
        "<article class='summary-item summary-item--detail'>",
        "  <div class='summary-item__body'>",
        "    <p class='summary-item__category'>" + item.category + "</p>",
        "    <h3 class='summary-item__title'>" + item.name + "</h3>",
        "  </div>",
        "  <div class='summary-item__controls'>Qty " + item.quantity + "</div>",
        "  <p class='summary-item__total'>" + currency.format(item.lineTotal) + "</p>",
        "</article>"
      ].join("");
    }).join(""),
    "  </div>",
    "</section>"
  ].join("");
})();
