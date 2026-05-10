const test = require("node:test");
const assert = require("node:assert/strict");
const OrderStore = require("../assets/js/order-store.js");

function createStore() {
  const storage = OrderStore.createMemoryStorage();
  return OrderStore.createOrderStore(storage, {
    clock: () => new Date("2026-04-20T08:00:00.000Z"),
    randomId: () => "TEAM0501"
  });
}

test("seeded demo cart produces a priced summary", () => {
  const store = createStore();
  const items = store.seedDemoCart();
  const totals = store.calculateTotals(items);

  assert.equal(items.length, 2);
  assert.equal(totals.subtotal, 113);
  assert.equal(totals.serviceFee, 3);
  assert.equal(totals.total, 116);
});

test("checkout creates an order, clears cart, and preserves totals", () => {
  const store = createStore();
  store.seedDemoCart();

  const order = store.createOrder({
    customerName: "Wang Yufeng",
    phone: "+85360001234",
    pickupDate: "2026-04-21",
    pickupSlot: "12:00",
    paymentMethod: "Campus wallet",
    note: "No onion"
  });

  assert.equal(order.code, "ORD-TEAM0501");
  assert.equal(order.total, 116);
  assert.equal(store.getCartItems().length, 0);
  assert.equal(store.getOrders().length, 1);
  assert.equal(store.getOrderById("TEAM0501").customerName, "Wang Yufeng");
});

test("slot capacity blocks overbooking", () => {
  const storage = OrderStore.createMemoryStorage();
  const firstStore = OrderStore.createOrderStore(storage, {
    clock: () => new Date("2026-04-20T08:00:00.000Z"),
    randomId: () => "A0000001"
  });

  for (let count = 0; count < 5; count += 1) {
    firstStore.seedDemoCart();
    firstStore.createOrder({
      customerName: "User " + count,
      phone: "1000" + count,
      pickupDate: "2026-04-22",
      pickupSlot: "17:30"
    });
  }

  firstStore.seedDemoCart();

  assert.throws(() => {
    firstStore.createOrder({
      customerName: "Overflow",
      phone: "9999",
      pickupDate: "2026-04-22",
      pickupSlot: "17:30"
    });
  }, /full/i);
});

test("orders are returned in reverse chronological order", () => {
  const storage = OrderStore.createMemoryStorage();
  const storeA = OrderStore.createOrderStore(storage, {
    clock: () => new Date("2026-04-20T08:00:00.000Z"),
    randomId: () => "OLD00001"
  });
  const storeB = OrderStore.createOrderStore(storage, {
    clock: () => new Date("2026-04-20T09:00:00.000Z"),
    randomId: () => "NEW00001"
  });

  storeA.seedDemoCart();
  storeA.createOrder({
    customerName: "Earlier Order",
    phone: "1111",
    pickupDate: "2026-04-23",
    pickupSlot: "12:30"
  });

  storeB.seedDemoCart();
  storeB.createOrder({
    customerName: "Later Order",
    phone: "2222",
    pickupDate: "2026-04-23",
    pickupSlot: "13:00"
  });

  const orders = storeB.getOrders();
  assert.equal(orders[0].id, "NEW00001");
  assert.equal(orders[1].id, "OLD00001");
});
