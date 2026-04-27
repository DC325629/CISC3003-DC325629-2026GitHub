"use strict";

(function () {
  if (!window.OrderStore) return;

  var store = window.OrderStore.createOrderStore(window.localStorage);
  var buttons = document.querySelectorAll("[data-add-cart]");
  var liveRegion = document.querySelector("[data-cart-live]");

  function currentQuantity(mealId) {
    var item = store.getCartItems().find(function (entry) { return entry.mealId === mealId; });
    return item ? item.quantity : 0;
  }

  function renderButtons() {
    buttons.forEach(function (button) {
      var mealId = button.getAttribute("data-meal-id");
      var quantity = currentQuantity(mealId);
      var status = button.parentElement.querySelector("[data-cart-status]");
      button.textContent = quantity > 0 ? "Add one more" : "Add to cart";
      if (status) status.textContent = quantity > 0 ? "In cart: " + quantity : "Not in cart yet";
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener("click", function () {
      var mealId = button.getAttribute("data-meal-id");
      store.setCartItem(mealId, currentQuantity(mealId) + 1);
      renderButtons();
      if (liveRegion) {
        var cardTitle = button.closest(".item-content").querySelector(".item-title");
        liveRegion.textContent = (cardTitle ? cardTitle.textContent.trim() : mealId) + " added to cart.";
      }
    });
  });

  renderButtons();
})();