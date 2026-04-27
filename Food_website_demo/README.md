# Food Website Demo Change Log

This document records the main changes made to the project from the first edit in this workspace through the current PHP/MySQL version.

## 1. Initial Review And Scope Alignment

- Read the assignment proposal file `cisc3003-TeamAssgnProposal-Team05.docx`.
- Mapped the assigned responsibility for Wang Yufeng to:
  - checkout
  - order history
  - order details
- Audited the original static site files:
  - `cisc3003-PairAssgn.html`
  - `assets/css/style.css`
  - `assets/js/script.js`

## 2. First Prototype: Frontend-Only Order Flow

The project originally contained only a static single-page website. The first implementation phase added a working frontend prototype for the assigned module without PHP/MySQL yet.

### Files added in the first prototype

- `checkout.html`
- `orders.html`
- `order-details.html`
- `assets/js/order-store.js`
- `assets/js/checkout.js`
- `assets/js/orders.js`
- `assets/js/order-details.js`
- `assets/css/order-pages.css`
- `tests/order-store.test.js`

### What was implemented

- Local cart and order persistence with `localStorage`
- Checkout page with:
  - cart summary
  - pickup slot selection
  - order confirmation
- Order history page with:
  - order list
  - search and filtering
- Order details page with:
  - itemized order breakdown
- JavaScript unit tests for order creation and slot capacity rules

## 3. Static Site Integration And Cleanup

The next phase connected the new flow to the original single-page UI and fixed obvious issues in the original template.

### Files updated

- `cisc3003-PairAssgn.html`
- `assets/css/style.css`
- `assets/js/script.js`

### Main changes

- Added links from the original landing page to:
  - checkout
  - orders
- Added real add-to-cart controls to menu items
- Fixed multiple HTML/CSS/JS issues from the template, including:
  - broken navigation anchors
  - incorrect CSS property names
  - mobile nav null-safety in JavaScript
  - layout and footer selector typos

## 4. Migration To Real PHP/MySQL Backend For XAMPP

The frontend-only prototype was then replaced with a true PHP/MySQL implementation for XAMPP.

### Backend foundation files added

- `config/app.php`
- `includes/helpers.php`
- `includes/database.php`
- `includes/schema.php`
- `includes/repositories.php`
- `includes/bootstrap.php`
- `includes/layout.php`
- `database/schema.sql`
- `database/seed.sql`
- `setup.php`
- `tests/backend_flow.php`

### PHP application pages added

- `index.php`
- `checkout.php`
- `orders.php`
- `order-details.php`
- `delete-order.php`

### Backend features implemented

- PDO-based MySQL connection
- Schema creation and seed installation
- Session-based cart
- Real order insertion into MySQL
- Real order items insertion into MySQL
- Pickup slot capacity checks in transaction flow
- Order history retrieval from MySQL
- Order details retrieval from MySQL
- Delete order functionality with cascading delete of order items

## 5. UI Realignment To Match The Original Template

After the backend migration, the PHP pages were visually aligned back toward the original `Crispy` UI so that the site looked like a real ordering website instead of a backend demo.

### Files updated for UI realignment

- `index.php`
- `checkout.php`
- `orders.php`
- `order-details.php`
- `includes/layout.php`
- `assets/css/php-app.css`

### Main UI changes

- Restored the original-style top navigation:
  - Home
  - Menu
  - Hours
  - Checkout
  - Orders
- Rebuilt the `index.php` hero section to match the original visual structure
- Reworked the menu section styling to better match the original template
- Removed user-facing technical wording such as:
  - PHP
  - MySQL
  - XAMPP
  - backend
  - session/cart implementation details
- Removed personal text such as:
  - Wang Yufeng
- Changed checkout defaults so:
  - student name is blank by default
  - contact number is blank by default

## 6. Restoring Missing Bottom Sections On `index.php`

After UI review, the original landing page was found to be missing its last two sections in the PHP version.

### Restored sections

- Reservation section
- Footer section

### Files updated

- `index.php`
- `includes/layout.php`

### What was restored

- Reservation card and image block below the working hours section
- Footer with:
  - brand block
  - quick links
  - products list
  - contact information
  - footer bottom links

## 7. Testing Performed During The Work

### JavaScript prototype phase

- Ran Node-based tests for `order-store.js`
- Verified:
  - seeded demo cart totals
  - order creation
  - slot capacity blocking
  - order sorting

### PHP/MySQL backend phase

- Ran PHP syntax checks on all PHP entry files and included PHP files
- Ran `tests/backend_flow.php`

### Backend flow covered by tests

- schema installation
- meal seed loading
- add meals to session cart
- build cart snapshot
- read pickup slots
- create order in database
- read order details from database
- search order history
- delete order record

## 8. Current Important Files

### Main pages

- `index.php`
- `checkout.php`
- `orders.php`
- `order-details.php`
- `delete-order.php`

### Backend support

- `includes/helpers.php`
- `includes/database.php`
- `includes/repositories.php`
- `includes/layout.php`
- `includes/schema.php`

### Database files

- `database/schema.sql`
- `database/seed.sql`
- `setup.php`

### Tests

- `tests/order-store.test.js`
- `tests/backend_flow.php`

## 9. Current Status

The project now includes:

- a PHP/XAMPP-compatible application
- MySQL-backed checkout
- MySQL-backed order history
- MySQL-backed order details
- delete-order functionality
- a landing page visually aligned with the original theme
- restored reservation and footer sections
- documented backend setup and tested flow
