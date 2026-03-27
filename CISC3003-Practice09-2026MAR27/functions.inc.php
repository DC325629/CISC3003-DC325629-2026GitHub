<?php
function outputOrderRow($file, $title, $quantity, $price) {
    $amount = $quantity * $price;
    echo '<tr>';
    echo '<td class="mdl-data-table__cell--non-numeric"><img src="images/books/tinysquare/' . $file . '" style="width: 40px;"></td>';
    echo '<td class="mdl-data-table__cell--non-numeric">' . htmlspecialchars($title) . '</td>';
    echo '<td>' . $quantity . '</td>';
    echo '<td>$' . number_format($price, 2) . '</td>';
    echo '<td>$' . number_format($amount, 2) . '</td>';
    echo '</tr>';
}
?>