<?php
$submitted = isset($_POST['title']) || isset($_POST['description']) || isset($_POST['genre']) || isset($_POST['subject']) ||
isset($_POST['medium']) || isset($_POST['year']) || isset($_POST['museum']);

$title = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
$description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '';
$genre = isset($_POST['genre']) ? htmlspecialchars($_POST['genre']) : '';
$subject = isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '';
$medium = isset($_POST['medium']) ? htmlspecialchars($_POST['medium']) : '';
$year = isset($_POST['year']) ? htmlspecialchars($_POST['year']) : '';
$museum = isset($_POST['museum']) ? htmlspecialchars($_POST['museum']) : '';
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
    <title>CISC3003 Suggested Exercise 09</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="./js/misc.js"></script>
    <link rel="stylesheet" href="./css/reset.css" />
    <link rel="stylesheet" href="./css/styles.css" />
</head>
<body>
<?php include 'header.inc.php'; ?>
    
<main>
    <section class="results">
    <?php if ($submitted): ?>
    <table>
      <caption class="results__caption">Art Work Saved</caption>
      <tr>
        <td class="results__label">Title</td>    
        <td class="results__value"><?php echo $title; ?></td> 
      </tr>
      <tr>
        <td class="results__label">Description</td>    
        <td class="results__value"><?php echo $description; ?></td> 
      </tr>
      <tr>
        <td class="results__label">Genre</td>    
        <td class="results__value"><?php echo $genre; ?></td> 
      </tr>
      <tr>
        <td class="results__label">Subject</td>    
        <td class="results__value"><?php echo $subject; ?></td> 
      </tr>
      <tr>
        <td class="results__label">Medium</td>    
        <td class="results__value"><?php echo $medium; ?></td> 
      </tr>   
      <tr>
        <td class="results__label">Year</td>    
        <td class="results__value"><?php echo $year; ?></td> 
      </tr>  
      <tr>
        <td class="results__label">Museum</td>    
        <td class="results__value"><?php echo $museum; ?></td> 
      </tr>          
    </table>
    <?php else: ?>
        <p class="results__caption">No data submitted. Please go back to the <a href="./cisc3003-sugex09-after.php">form</a>.</p>
    <?php endif; ?>
    </section>
</main>       
</body>
</html>