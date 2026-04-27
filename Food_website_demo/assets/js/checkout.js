"use strict";

(function () {
  var store = window.OrderStore.createOrderStore(window.localStorage);
  var currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });

  var cartItems = document.querySelector("[data-cart-items]");
  var summarySubtotal = document.querySelector("[data-summary-subtotal]");
  var summaryFee = document.querySelector("[data-summary-fee]");
  var summaryTotal = document.querySelector("[data-summary-total]");
  var emptyState = document.querySelector("[data-empty-state]");
  var submitButton = document.querySelector("[data-submit-order]");
  var seedCartButtons = document.querySelectorAll("[data-seed-cart]");

  function renderCart() {
    var items = store.getCartItems();
    var totals = store.calculateTotals(items);
    cartItems.innerHTML = items.map(function (item) {
      return [
        "<article class='summary-item'>",
        "  <div class='summary-item__body'>",
        "    <p class='summary-item__category'>" + item.category + "</p>",
        "    <h3 class='summary-item__title'>" + item.name + "</h3>",
        "    <p class='summary-item__price'>" + currency.format(item.price) + " each</p>",
        "  </div>",
        "  <div class='summary-item__controls'>",
        "    <button class='quantity-btn' type='button' data-action='decrease' data-meal-id='" + item.mealId + "'>-</button>",
        "    <span class='quantity-value'>" + item.quantity + "</span>",
        "    <button class='quantity-btn' type='button' data-action='increase' data-meal-id='" + item.mealId + "'>+</button>",
        "    <button class='text-link' type='button' data-action='remove' data-meal-id='" + item.mealId + "'>Remove</button>",
        "  </div>",
        "  <p class='summary-item__total'>" + currency.format(item.lineTotal) + "</p>",
        "</article>"
      ].join("");
    }).join("");
    summarySubtotal.textContent = currency.format(totals.subtotal);
    summaryFee.textContent = currency.format(totals.serviceFee);
    summaryTotal.textContent = currency.format(totals.total);
    emptyState.hidden = items.length !== 0;
    submitButton.disabled = items.length === 0;
  }

  cartItems.addEventListener("click", function (event) {
    var button = event.target.closest("[data-action]");
    if (!button) return;
    var mealId = button.getAttribute("data-meal-id");
    var action = button.getAttribute("data-action");
    var item = store.getCartItems().find(function (entry) { return entry.mealId === mealId; });
    if (!item) return;
    if (action === "increase") store.setCartItem(mealId, item.quantity + 1);
    if (action === "decrease") store.setCartItem(mealId, item.quantity - 1);
    if (action === "remove") store.removeCartItem(mealId);
    renderCart();
  });

  seedCartButtons.forEach(function (button) {
    button.addEventListener("click", function () { store.seedDemoCart(); renderCart(); });
  });

  if (!store.getCartItems().length && !store.getOrders().length) store.seedDemoCart();
  renderCart();
})();