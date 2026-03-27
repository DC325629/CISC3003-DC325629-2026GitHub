<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CISC3003 Practice 09</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.blue_grey-orange.min.css">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
<div class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer mdl-layout--fixed-header">
            
  <?php include __DIR__ . '/header.inc.php'; ?>
  <?php include __DIR__ . '/left.inc.php'; ?>

  <main class="mdl-layout__content">
    <div class="page-content">
      <header class="mdl-color--blue-grey-100">
        <h2>Order Summaries</h2>
        <p>Examine your customer orders</p>
      </header>   
      
      <section class="mdl-grid">
      
        <div class="mdl-cell mdl-cell--4-col">
          <div class="mdl-card mdl-shadow--2dp" style="width: 100%;">
            <div class="mdl-card__title mdl-color--purple mdl-color-text--white">
              <h2 class="mdl-card__title-text">My Orders</h2>
            </div>
            <div class="mdl-card__supporting-text">            
                <ul style="list-style-type: none; padding: 0;">
                    <?php
                    for ($i = 500; $i <= 540; $i += 10) {
                        echo "<li style=\"margin-bottom: 10px;\"><a href=\"#\" class=\"mdl-color-text--orange\" style=\"text-decoration: underline;\">Order #{$i}</a></li>\n";
                    }
                    ?>
                </ul>   
            </div>    
          </div> 
        </div>

        <div class="mdl-cell mdl-cell--8-col">
          <div class="mdl-card mdl-shadow--2dp" style="width: 100%;">
            <div class="mdl-card__title mdl-color--orange">
              <h2 class="mdl-card__title-text">Selected Order: #520</h2>
            </div>
            <div class="mdl-card__supporting-text" style="width: 100%; box-sizing: border-box; overflow-x: auto;">
                <?php
                    include __DIR__ . '/data.inc.php';
                    include __DIR__ . '/functions.inc.php';
                    
                    $subtotal = ($quantity1 * $price1) + ($quantity2 * $price2) + ($quantity3 * $price3) + ($quantity4 * $price4);
                    
                    $shipping = ($subtotal > 10000) ? 100.00 : 200.00;
                   
                    $grandTotal = $subtotal + $shipping;
                ?>
                <table class="mdl-data-table mdl-js-data-table" style="width: 100%; border: none;">
                  <caption style="text-align: center; padding: 10px; color: #757575;">Customer: <strong>Mount Royal University</strong></caption>
                  <thead>
                    <tr>
                      <th class="mdl-data-table__cell--non-numeric">Cover</th>
                      <th class="mdl-data-table__cell--non-numeric">Title</th>
                      <th>Quantity</th>
                      <th>Price</th>
                      <th>Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                        outputOrderRow($file1, $title1, $quantity1, $price1);
                        outputOrderRow($file2, $title2, $quantity2, $price2);
                        outputOrderRow($file3, $title3, $quantity3, $price3);
                        outputOrderRow($file4, $title4, $quantity4, $price4);
                    ?>
                  </tbody>
                  <tfoot>
                    <tr class="totals">
                      <td colspan="4" class="mdl-data-table__cell--non-numeric" style="text-align: right;">Subtotal</td>
                      <td>$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr class="totals">
                      <td colspan="4" class="mdl-data-table__cell--non-numeric" style="text-align: right;">Shipping</td>
                      <td>$<?php echo number_format($shipping, 2); ?></td>
                    </tr>
                    <tr class="grandtotals">
                      <td colspan="4" class="mdl-data-table__cell--non-numeric" style="text-align: right;">Grand Total</td>
                      <td style="color: #751FEC; font-weight: 700;">$<?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                  </tfoot>
                </table>
            </div>
          </div> 
        </div>
      
      </section>
    </div>
  </main>
  
</div>
          
</body>
</html>