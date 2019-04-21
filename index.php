<?php
// INCLUDE ON EVERY TOP-LEVEL PAGE!
include("includes/init.php");

$title = "index";
$index = "current";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="styles/all.css" media="all" rel="stylesheet" type="text/css">
  <title>Home</title>
</head>

<body>
  <header>
    <h1>Welcome to the home page</h1>
  </header>
  <div class="pagecontent">

    <!-- TODO: This should be your main page for your site. -->
    <?php include("includes/header.php"); ?>

    <main>
      <h2>Home</h2>


    </main>

    <?php include("includes/footer.php"); ?>

</body>

</html>
