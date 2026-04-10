<?php
include './includes/book-utilities.inc.php';

$customers = readCustomers('./data/customers.txt');

$selectedId = isset($_GET['id']) ? trim($_GET['id']) : '';
$selectedCustomer = null;
$orders = [];

if ($selectedId !== '' && isset($customers[$selectedId])) {
    $selectedCustomer = $customers[$selectedId];
    $orders = readOrders($selectedId, './data/orders.txt');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>CISC3003 Suggested Exercise 10</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.1.3/material.blue_grey-orange.min.css">
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/demo-styles.css">
    <link rel="stylesheet" href="./css/material.min.css">
    
    <script src="https://code.jquery.com/jquery-1.7.2.min.js"></script>
    <script src="https://code.getmdl.io/1.1.3/material.min.js"></script>
    <script src="./js/jquery.sparkline.2.1.2.js"></script>
    <script src="./js/material.min.js"></script>
</head>

<body>
    
<div class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer
            mdl-layout--fixed-header">
            
    <?php include './includes/header.inc.php'; ?>
    <?php include './includes/left-nav.inc.php'; ?>
    
    <main class="mdl-layout__content mdl-color--grey-50">
        <section class="page-content">

            <div class="mdl-grid">

              <div class="mdl-cell mdl-cell--7-col card-lesson mdl-card  mdl-shadow--2dp">
                <div class="mdl-card__title mdl-color--orange">
                  <h2 class="mdl-card__title-text">Customers</h2>
                </div>
                <div class="mdl-card__supporting-text">
                    <table class="mdl-data-table  mdl-shadow--2dp">
                      <thead>
                        <tr>
                          <th class="mdl-data-table__cell--non-numeric">Name</th>
                          <th class="mdl-data-table__cell--non-numeric">University</th>
                          <th class="mdl-data-table__cell--non-numeric">City</th>
                          <th>Sales</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($customers as $cust): ?>
                        <tr>
                          <td class="mdl-data-table__cell--non-numeric">
                            <a href="?id=<?= htmlspecialchars($cust['id']) ?>">
                              <?= htmlspecialchars($cust['firstname'] . ' ' . $cust['lastname']) ?>
                            </a>
                           </td>
                          <td class="mdl-data-table__cell--non-numeric"><?= htmlspecialchars($cust['university']) ?></td>
                          <td class="mdl-data-table__cell--non-numeric"><?= htmlspecialchars($cust['city']) ?></td>
                          <td>
                            <span class="sales-sparkline"><?= htmlspecialchars($cust['sales']) ?></span>
                           </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                </div>
              </div>  <!-- / mdl-cell + mdl-card -->
              
              
            <div class="mdl-grid mdl-cell--5-col">
    
                  <div class="mdl-cell mdl-cell--12-col card-lesson mdl-card  mdl-shadow--2dp">
                    <div class="mdl-card__title mdl-color--deep-purple mdl-color-text--white">
                      <h2 class="mdl-card__title-text">Customer Details</h2>
                    </div>
                    <div class="mdl-card__supporting-text">
                        <?php if ($selectedCustomer): ?>
                            <!-- 修正：從 h4 改為 h3，避免跳過 heading 層級 -->
                            <h3><?= htmlspecialchars($selectedCustomer['firstname'] . ' ' . $selectedCustomer['lastname']) ?></h3>
                            <p><strong>Email:</strong> <?= htmlspecialchars($selectedCustomer['email']) ?></p>
                            <p><strong>University:</strong> <?= htmlspecialchars($selectedCustomer['university']) ?></p>
                            <?php
                            $addressParts = [
                                $selectedCustomer['address'],
                                $selectedCustomer['city'],
                                $selectedCustomer['state'],
                                $selectedCustomer['country'],
                                $selectedCustomer['postal']
                            ];
                            $fullAddress = implode(', ', $addressParts);
                            ?>
                            <p><strong>Address:</strong> <?= htmlspecialchars($fullAddress) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($selectedCustomer['phone']) ?></p>
                            
                        <?php else: ?>
                            <p>Select a customer to view details.</p>
                        <?php endif; ?>
                    </div>    
                  </div>  <!-- / mdl-cell + mdl-card -->   

                  
<div class="mdl-cell mdl-cell--12-col card-lesson mdl-card  mdl-shadow--2dp">
    <div class="mdl-card__title mdl-color--deep-purple mdl-color-text--white">
        <h2 class="mdl-card__title-text">Order Details</h2>
    </div>
    <div class="mdl-card__supporting-text">       
        <table class="mdl-data-table mdl-shadow--2dp" style="width:100%;">
            <thead>
                <tr>
                    <th class="mdl-data-table__cell--non-numeric">Cover</th>
                    <th class="mdl-data-table__cell--non-numeric">ISBN</th>
                    <th class="mdl-data-table__cell--non-numeric">Title</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$selectedCustomer): ?>
                   
                <?php elseif (empty($orders)): ?>
                    <tr>
                        <td class="mdl-data-table__cell--non-numeric" colspan="3" style="text-align:center; color:#757575;">
                            No orders for this customer
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="mdl-data-table__cell--non-numeric">
                            <img src="./images/tinysquare/<?= htmlspecialchars($order['isbn']) ?>.jpg" alt="Book Cover" style="width:40px; height:auto;">
                        </td>
                        <td class="mdl-data-table__cell--non-numeric">
                            <?= htmlspecialchars($order['isbn']) ?>
                        </td>
                        <td class="mdl-data-table__cell--non-numeric">
                            <?= htmlspecialchars($order['title']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>    
</div>  <!-- / mdl-cell + mdl-card -->             
               </div>   
            </div>  <!-- / mdl-grid -->    
        </section>
    </main>    
</div>    <!-- / mdl-layout --> 

<script>
$(document).ready(function(){
    $('.sales-sparkline').sparkline('html', {
        type: 'bar',
        barColor: '#651FFF',
        height: '20px',
        barWidth: 4,
        barSpacing: 1
    });
});
</script>
          
</body>
</html>