function calculateTotal(quantity, price) {
    return quantity * price;
}

function outputCartRow(file, title, quantity, price, total) {
    document.write("<tr>");
    document.write('<td><img src="images/' + file + '"></td>');
    document.write('<td>' + title + '</td>');
    document.write('<td>' + quantity + '</td>');
    document.write('<td>$' + price + '</td>');
    document.write('<td>' + outputCurrency(total) + '</td>');
    document.write("</tr>");
}

function calculateTax(subtotal, rate) {
    return subtotal * rate;
}

function calculateShipping(subtotal, threshold) {
    return subtotal > threshold ? 0 : 40;
}

function calculateGrandTotal(subtotal, tax, shipping) {
    return subtotal + tax + shipping;
}

function outputCurrency(num) {
    return "$" + num.toFixed(2);
}