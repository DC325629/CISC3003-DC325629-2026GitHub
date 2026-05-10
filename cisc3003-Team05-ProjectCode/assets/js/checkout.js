"use strict";

(function () {
  var store = window.OrderStore.createOrderStore(window.localStorage);
  var currency = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD"
  });

  var cartItems = document.querySelector("[data-cart-items]");
  var slotGrid = document.querySelector("[data-slot-grid]");
  var checkoutForm = document.querySelector("[data-checkout-form]");
  var pickupDateInput = document.querySelector("#pickupDate");
  var summarySubtotal = document.querySelector("[data-summary-subtotal]");
  var summaryFee = document.querySelector("[data-summary-fee]");
  var summaryTotal = document.querySelector("[data-summary-total]");
  var successPanel = document.querySelector("[data-order-success]");
  var emptyState = document.querySelector("[data-empty-state]");
  var submitButton = document.querySelector("[data-submit-order]");
  var slotError = document.querySelector("[data-slot-error]");
  var seedCartButtons = document.querySelectorAll("[data-seed-cart]");

  function tomorrowString() {
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().slice(0, 10);
  }

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
        "    <button class='quantity-btn' type='button' data-action='decrease' data-meal-id='" + item.mealId + "' aria-label='Decrease quantity'>-</button>",
        "    <span class='quantity-value'>" + item.quantity + "</span>",
        "    <button class='quantity-btn' type='button' data-action='increase' data-meal-id='" + item.mealId + "' aria-label='Increase quantity'>+</button>",
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

  function renderSlots() {
    var pickupDate = pickupDateInput.value;
    var slots = store.getAvailableSlots(pickupDate);

    slotGrid.innerHTML = slots.map(function (slot) {
      var disabled = slot.available ? "" : "disabled";
      var status = slot.available ? slot.remaining + " left" : "Full";
      return [
        "<label class='slot-card " + (slot.available ? "" : "slot-card--disabled") + "'>",
        "  <input type='radio' name='pickupSlot' value='" + slot.id + "' " + disabled + ">",
        "  <span class='slot-card__time'>" + slot.label + "</span>",
        "  <span class='slot-card__meta'>Capacity " + slot.capacity + " | " + status + "</span>",
        "</label>"
      ].join("");
    }).join("");
  }

  function renderAll() {
    renderCart();
    renderSlots();
  }

  cartItems.addEventListener("click", function (event) {
    var button = event.target.closest("[data-action]");
    if (!button) {
      return;
    }

    var mealId = button.getAttribute("data-meal-id");
    var action = button.getAttribute("data-action");
    var item = store.getCartItems().find(function (entry) {
      return entry.mealId === mealId;
    });

    if (!item) {
      return;
    }

    if (action === "increase") {
      store.setCartItem(mealId, item.quantity + 1);
    }

    if (action === "decrease") {
      store.setCartItem(mealId, item.quantity - 1);
    }

    if (action === "remove") {
      store.removeCartItem(mealId);
    }

    renderCart();
  });

  checkoutForm.addEventListener("submit", function (event) {
    event.preventDefault();
    slotError.textContent = "";

    try {
      var formData = new FormData(checkoutForm);
      var order = store.createOrder({
        customerName: formData.get("customerName"),
        phone: formData.get("phone"),
        pickupDate: formData.get("pickupDate"),
        pickupSlot: formData.get("pickupSlot"),
        paymentMethod: formData.get("paymentMethod"),
        note: formData.get("note")
      });

      successPanel.hidden = false;
      successPanel.innerHTML = [
        "<div class='message-card message-card--success'>",
        "  <p class='eyebrow'>Order confirmed</p>",
        "  <h2>" + order.code + "</h2>",
        "  <p>Your pickup slot is " + order.pickupDate + " at " + order.pickupSlotLabel + ".</p>",
        "  <div class='message-card__actions'>",
        "    <a class='btn' href='./orders.html'>View order history</a>",
        "    <a class='btn btn--secondary' href='./order-details.html?id=" + encodeURIComponent(order.id) + "'>Open details</a>",
        "  </div>",
        "</div>"
      ].join("");

      checkoutForm.reset();
      pickupDateInput.value = tomorrowString();
      renderAll();
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
      slotError.textContent = error.message;
    }
  });

  pickupDateInput.value = tomorrowString();
  pickupDateInput.addEventListener("change", renderSlots);

  seedCartButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      store.seedDemoCart();
      renderCart();
    });
  });

  if (!store.getCartItems().length && !store.getOrders().length) {
    store.seedDemoCart();
  }

  renderAll();
})();
