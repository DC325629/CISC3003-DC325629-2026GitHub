"use strict";

(function () {
  var store = window.OrderStore.createOrderStore(window.localStorage);
  var currency = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD"
  });

  var ordersList = document.querySelector("[data-orders-list]");
  var orderCount = document.querySelector("[data-order-count]");
  var revenueTotal = document.querySelector("[data-revenue-total]");
  var searchInput = document.querySelector("[data-order-search]");
  var statusFilter = document.querySelector("[data-status-filter]");
  var emptyState = document.querySelector("[data-orders-empty]");

  function filteredOrders() {
    var term = searchInput.value.trim().toLowerCase();
    var status = statusFilter.value;

    return store.getOrders().filter(function (order) {
      var matchesStatus = status === "all" || order.status.toLowerCase() === status;
      var matchesTerm = !term || [
        order.code,
        order.customerName,
        order.pickupDate,
        order.items.map(function (item) { return item.name; }).join(" ")
      ].join(" ").toLowerCase().includes(term);

      return matchesStatus && matchesTerm;
    });
  }

  function renderOrders() {
    var orders = filteredOrders();
    var stats = store.getOrderStats();

    orderCount.textContent = String(stats.count);
    revenueTotal.textContent = currency.format(stats.revenue);
    emptyState.hidden = orders.length !== 0;

    ordersList.innerHTML = orders.map(function (order) {
      return [
        "<article class='history-card'>",
        "  <div class='history-card__header'>",
        "    <div>",
        "      <p class='eyebrow'>Order code</p>",
        "      <h2>" + order.code + "</h2>",
        "    </div>",
        "    <span class='status-pill'>" + order.status + "</span>",
        "  </div>",
        "  <div class='history-card__meta'>",
        "    <p><strong>Pickup:</strong> " + order.pickupDate + " at " + (order.pickupSlotLabel || order.pickupSlot) + "</p>",
        "    <p><strong>Placed:</strong> " + new Date(order.createdAt).toLocaleString() + "</p>",
        "    <p><strong>Items:</strong> " + order.items.length + "</p>",
        "  </div>",
        "  <div class='history-card__footer'>",
        "    <p class='history-card__amount'>" + currency.format(order.total) + "</p>",
        "    <a class='btn' href='./order-details.html?id=" + encodeURIComponent(order.id) + "'>View details</a>",
        "  </div>",
        "</article>"
      ].join("");
    }).join("");
  }

  searchInput.addEventListener("input", renderOrders);
  statusFilter.addEventListener("change", renderOrders);
  renderOrders();
})();
