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
    {
      id: "hamburger",
      name: "Hamburger",
      price: 25,
      category: "Burger",
      image: "./assets/images/menu-1.png"
    },
    {
      id: "pizza",
      name: "Pizza",
      price: 63,
      category: "Pizza",
      image: "./assets/images/menu-2.png"
    },
    {
      id: "chicken-wings",
      name: "Baked Chicken Wings",
      price: 199,
      category: "Chicken",
      image: "./assets/images/menu-3.png"
    },
    {
      id: "seafood-pizza",
      name: "Seafood Pizza",
      price: 352,
      category: "Pizza",
      image: "./assets/images/menu-4.png"
    }
  ];
  var DEFAULT_SLOTS = [
    { id: "11:30", label: "11:30 AM", capacity: 6 },
    { id: "12:00", label: "12:00 PM", capacity: 8 },
    { id: "12:30", label: "12:30 PM", capacity: 8 },
    { id: "13:00", label: "1:00 PM", capacity: 6 },
    { id: "17:30", label: "5:30 PM", capacity: 5 },
    { id: "18:00", label: "6:00 PM", capacity: 5 }
  ];

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function createMemoryStorage(initialState) {
    var map = new Map(Object.entries(initialState || {}));
    return {
      getItem: function (key) {
        return map.has(key) ? map.get(key) : null;
      },
      setItem: function (key, value) {
        map.set(key, String(value));
      },
      removeItem: function (key) {
        map.delete(key);
      }
    };
  }

  function getDefaultStorage() {
    if (typeof window !== "undefined" && window.localStorage) {
      return window.localStorage;
    }

    return createMemoryStorage();
  }

  function createDefaultState() {
    return {
      meals: clone(DEFAULT_MEALS),
      cart: [],
      orders: [],
      bookings: {}
    };
  }

  function safeParse(rawValue) {
    if (!rawValue) {
      return createDefaultState();
    }

    try {
      var parsed = JSON.parse(rawValue);
      return {
        meals: Array.isArray(parsed.meals) && parsed.meals.length ? parsed.meals : clone(DEFAULT_MEALS),
        cart: Array.isArray(parsed.cart) ? parsed.cart : [],
        orders: Array.isArray(parsed.orders) ? parsed.orders : [],
        bookings: parsed.bookings && typeof parsed.bookings === "object" ? parsed.bookings : {}
      };
    } catch (error) {
      return createDefaultState();
    }
  }

  function cartLookup(cartItems) {
    return cartItems.reduce(function (lookup, item) {
      lookup[item.mealId] = item;
      return lookup;
    }, {});
  }

  function createOrderStore(storage, options) {
    var targetStorage = storage || getDefaultStorage();
    var settings = options || {};
    var clock = typeof settings.clock === "function" ? settings.clock : function () { return new Date(); };
    var randomId = typeof settings.randomId === "function"
      ? settings.randomId
      : function () {
          return Math.random().toString(36).slice(2, 10).toUpperCase();
        };

    function loadState() {
      return safeParse(targetStorage.getItem(STORAGE_KEY));
    }

    function saveState(state) {
      targetStorage.setItem(STORAGE_KEY, JSON.stringify(state));
      return state;
    }

    function getMeals() {
      return clone(loadState().meals);
    }

    function mealById(id) {
      return loadState().meals.find(function (meal) {
        return meal.id === id;
      }) || null;
    }

    function getCartItems() {
      var state = loadState();
      return state.cart.map(function (entry) {
        var meal = state.meals.find(function (candidate) {
          return candidate.id === entry.mealId;
        });

        if (!meal) {
          return null;
        }

        return {
          mealId: meal.id,
          name: meal.name,
          category: meal.category,
          image: meal.image,
          price: meal.price,
          quantity: entry.quantity,
          lineTotal: meal.price * entry.quantity
        };
      }).filter(Boolean);
    }

    function calculateTotals(items) {
      var subtotal = items.reduce(function (sum, item) {
        return sum + item.lineTotal;
      }, 0);
      var serviceFee = items.length ? DEFAULT_SERVICE_FEE : 0;
      return {
        subtotal: subtotal,
        serviceFee: serviceFee,
        total: subtotal + serviceFee
      };
    }

    function setCartItem(mealId, quantity) {
      var meal = mealById(mealId);
      if (!meal) {
        throw new Error("Unknown meal: " + mealId);
      }

      var nextQuantity = Number(quantity);
      if (!Number.isFinite(nextQuantity)) {
        throw new Error("Quantity must be numeric.");
      }

      var state = loadState();
      var nextCart = cartLookup(state.cart);

      if (nextQuantity <= 0) {
        delete nextCart[mealId];
      } else {
        nextCart[mealId] = {
          mealId: mealId,
          quantity: Math.min(Math.max(Math.round(nextQuantity), 1), 20)
        };
      }

      state.cart = Object.keys(nextCart).map(function (key) {
        return nextCart[key];
      });
      saveState(state);
      return getCartItems();
    }

    function removeCartItem(mealId) {
      return setCartItem(mealId, 0);
    }

    function clearCart() {
      var state = loadState();
      state.cart = [];
      saveState(state);
      return [];
    }

    function seedDemoCart() {
      var state = loadState();
      if (state.cart.length) {
        return getCartItems();
      }

      state.cart = [
        { mealId: "hamburger", quantity: 2 },
        { mealId: "pizza", quantity: 1 }
      ];
      saveState(state);
      return getCartItems();
    }

    function getAvailableSlots(dateValue) {
      var state = loadState();
      var normalizedDate = dateValue || "";

      return DEFAULT_SLOTS.map(function (slot) {
        var bookingKey = normalizedDate + "|" + slot.id;
        var booked = Number(state.bookings[bookingKey] || 0);
        var remaining = Math.max(slot.capacity - booked, 0);

        return {
          id: slot.id,
          label: slot.label,
          capacity: slot.capacity,
          booked: booked,
          remaining: remaining,
          available: remaining > 0
        };
      });
    }

    function getSlotLabel(slotId) {
      var slot = DEFAULT_SLOTS.find(function (item) {
        return item.id === slotId;
      });

      return slot ? slot.label : slotId;
    }

    function createOrder(payload) {
      var state = loadState();
      var cartItems = getCartItems();
      var customerName = String(payload.customerName || "").trim();
      var phone = String(payload.phone || "").trim();
      var pickupDate = String(payload.pickupDate || "").trim();
      var pickupSlot = String(payload.pickupSlot || "").trim();

      if (!cartItems.length) {
        throw new Error("Your cart is empty.");
      }

      if (!customerName || !phone || !pickupDate || !pickupSlot) {
        throw new Error("Please complete the checkout form.");
      }

      var slot = getAvailableSlots(pickupDate).find(function (item) {
        return item.id === pickupSlot;
      });

      if (!slot || !slot.available) {
        throw new Error("The selected pickup slot is full.");
      }

      var totals = calculateTotals(cartItems);
      var createdAt = clock();
      var orderId = randomId();
      var order = {
        id: orderId,
        code: "ORD-" + orderId,
        status: "Confirmed",
        customerName: customerName,
        phone: phone,
        pickupDate: pickupDate,
        pickupSlot: pickupSlot,
        pickupSlotLabel: getSlotLabel(pickupSlot),
        paymentMethod: String(payload.paymentMethod || "Pay at pickup"),
        note: String(payload.note || "").trim(),
        createdAt: typeof createdAt.toISOString === "function" ? createdAt.toISOString() : String(createdAt),
        items: cartItems,
        subtotal: totals.subtotal,
        serviceFee: totals.serviceFee,
        total: totals.total
      };

      state.orders.unshift(order);
      state.cart = [];
      state.bookings[pickupDate + "|" + pickupSlot] = (state.bookings[pickupDate + "|" + pickupSlot] || 0) + 1;
      saveState(state);
      return clone(order);
    }

    function getOrders() {
      return clone(loadState().orders).sort(function (left, right) {
        return new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime();
      });
    }

    function getOrderById(orderId) {
      return getOrders().find(function (order) {
        return order.id === orderId || order.code === orderId;
      }) || null;
    }

    function getOrderStats() {
      var orders = getOrders();
      return {
        count: orders.length,
        revenue: orders.reduce(function (sum, order) {
          return sum + order.total;
        }, 0)
      };
    }

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
