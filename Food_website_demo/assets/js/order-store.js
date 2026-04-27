(function (root, factory) {
  if (typeof module === "object" && module.exports) {
    module.exports = factory();
    return;
  }
  root.OrderStore = factory();
})(typeof self !== "undefined" ? self : this, function () {
  "use strict";

  var STORAGE_KEY = "crispy-order-state-v1";
  var DEFAULT_SERVICE_FEE = 3;
  var DEFAULT_MEALS = [
    { id: "hamburger", name: "Hamburger", price: 25, category: "Burger", image: "./assets/images/menu-1.png" },
    { id: "pizza", name: "Pizza", price: 63, category: "Pizza", image: "./assets/images/menu-2.png" },
    { id: "chicken-wings", name: "Baked Chicken Wings", price: 199, category: "Chicken", image: "./assets/images/menu-3.png" },
    { id: "seafood-pizza", name: "Seafood Pizza", price: 352, category: "Pizza", image: "./assets/images/menu-4.png" }
  ];
  var DEFAULT_SLOTS = [
    { id: "11:30", label: "11:30 AM", capacity: 6 },
    { id: "12:00", label: "12:00 PM", capacity: 8 },
    { id: "12:30", label: "12:30 PM", capacity: 8 },
    { id: "13:00", label: "1:00 PM", capacity: 6 },
    { id: "17:30", label: "5:30 PM", capacity: 5 },
    { id: "18:00", label: "6:00 PM", capacity: 5 }
  ];

  function clone(value) { return JSON.parse(JSON.stringify(value)); }

  function createMemoryStorage(initialState) {
    var map = new Map(Object.entries(initialState || {}));
    return {
      getItem: function (key) { return map.has(key) ? map.get(key) : null; },
      setItem: function (key, value) { map.set(key, String(value)); },
      removeItem: function (key) { map.delete(key); }
    };
  }

  function getDefaultStorage() {
    return (typeof window !== "undefined" && window.localStorage) ? window.localStorage : createMemoryStorage();
  }

  function createDefaultState() {
    return { meals: clone(DEFAULT_MEALS), cart: [], orders: [], bookings: {} };
  }

  function safeParse(rawValue) {
    if (!rawValue) return createDefaultState();
    try {
      var parsed = JSON.parse(rawValue);
      return {
        meals: Array.isArray(parsed.meals) && parsed.meals.length ? parsed.meals : clone(DEFAULT_MEALS),
        cart: Array.isArray(parsed.cart) ? parsed.cart : [],
        orders: Array.isArray(parsed.orders) ? parsed.orders : [],
        bookings: parsed.bookings && typeof parsed.bookings === "object" ? parsed.bookings : {}
      };
    } catch (e) { return createDefaultState(); }
  }

  function cartLookup(cartItems) {
    return cartItems.reduce(function (lookup, item) { lookup[item.mealId] = item; return lookup; }, {});
  }

  function createOrderStore(storage, options) {
    var targetStorage = storage || getDefaultStorage();
    var settings = options || {};
    var clock = settings.clock || function () { return new Date(); };
    var randomId = settings.randomId || function () { return Math.random().toString(36).slice(2, 10).toUpperCase(); };

    function loadState() { return safeParse(targetStorage.getItem(STORAGE_KEY)); }
    function saveState(state) { targetStorage.setItem(STORAGE_KEY, JSON.stringify(state)); return state; }

    function getMeals() { return clone(loadState().meals); }
    function mealById(id) { return loadState().meals.find(function (meal) { return meal.id === id; }) || null; }

    function getCartItems() {
      var state = loadState();
      return state.cart.map(function (entry) {
        var meal = state.meals.find(function (c) { return c.id === entry.mealId; });
        if (!meal) return null;
        return {
          mealId: meal.id, name: meal.name, category: meal.category, image: meal.image, price: meal.price,
          quantity: entry.quantity, lineTotal: meal.price * entry.quantity
        };
      }).filter(Boolean);
    }

    function calculateTotals(items) {
      var subtotal = items.reduce(function (sum, item) { return sum + item.lineTotal; }, 0);
      var serviceFee = items.length ? DEFAULT_SERVICE_FEE : 0;
      return { subtotal: subtotal, serviceFee: serviceFee, total: subtotal + serviceFee };
    }

    function setCartItem(mealId, quantity) {
      var meal = mealById(mealId);
      if (!meal) throw new Error("Unknown meal: " + mealId);
      var nextQuantity = Number(quantity);
      if (!Number.isFinite(nextQuantity)) throw new Error("Quantity must be numeric.");
      var state = loadState();
      var nextCart = cartLookup(state.cart);
      if (nextQuantity <= 0) delete nextCart[mealId];
      else nextCart[mealId] = { mealId: mealId, quantity: Math.min(Math.max(Math.round(nextQuantity), 1), 20) };
      state.cart = Object.keys(nextCart).map(function (key) { return nextCart[key]; });
      saveState(state);
      return getCartItems();
    }

    function removeCartItem(mealId) { return setCartItem(mealId, 0); }
    function clearCart() { var state = loadState(); state.cart = []; saveState(state); return []; }

    function seedDemoCart() {
      var state = loadState();
      if (state.cart.length) return getCartItems();
      state.cart = [{ mealId: "hamburger", quantity: 2 }, { mealId: "pizza", quantity: 1 }];
      saveState(state);
      return getCartItems();
    }

    function getAvailableSlots(dateValue) { /* 略，不影响购物车 */ return []; }
    function getSlotLabel(slotId) { return slotId; }
    function createOrder(payload) { /* 略 */ return {}; }
    function getOrders() { return []; }
    function getOrderById(id) { return null; }
    function getOrderStats() { return { count: 0, revenue: 0 }; }

    return {
      storageKey: STORAGE_KEY,
      getMeals: getMeals,
      getCartItems: getCartItems,
      calculateTotals: calculateTotals,
      setCartItem: setCartItem,
      removeCartItem: removeCartItem,
      clearCart: clearCart,
      seedDemoCart: seedDemoCart,
      getAvailableSlots: getAvailableSlots,
      getSlotLabel: getSlotLabel,
      createOrder: createOrder,
      getOrders: getOrders,
      getOrderById: getOrderById,
      getOrderStats: getOrderStats
    };
  }

  return {
    STORAGE_KEY: STORAGE_KEY,
    DEFAULT_MEALS: clone(DEFAULT_MEALS),
    DEFAULT_SLOTS: clone(DEFAULT_SLOTS),
    createMemoryStorage: createMemoryStorage,
    createOrderStore: createOrderStore
  };
});