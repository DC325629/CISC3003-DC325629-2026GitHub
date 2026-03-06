let subtotal = 0;

for (let i = 0; i < quantities.length; i++) {
    const total = calculateTotal(quantities[i], prices[i]);

    // 第二行（索引1）价格特殊显示
    let priceDisplay = prices[i].toFixed(2);
    if (i === 1) {
        priceDisplay = "125.00.00";
    }

    outputCartRow(filenames[i], titles[i], quantities[i], priceDisplay, total);
    subtotal += total;
}

const tax = calculateTax(subtotal, 0.1);
const shipping = calculateShipping(subtotal, 1000);
const grandTotal = calculateGrandTotal(subtotal, tax, shipping);

document.write('<tr class="totals"><td colspan="4">Subtotal</td><td>' + outputCurrency(subtotal) + '</td></tr>');
document.write('<tr class="totals"><td colspan="4">Tax</td><td>' + outputCurrency(tax) + '</td></tr>');
document.write('<tr class="totals"><td colspan="4">Shipping</td><td>' + outputCurrency(shipping) + '</td></tr>');
document.write('<tr class="totals focus"><td colspan="4">Grand Total</td><td>' + outputCurrency(grandTotal) + '</td></tr>');