<?php
// INCLUDE ON EVERY TOP-LEVEL PAGE!
include("includes/init.php");

$title = "reviews";
$index = "current";
$db = open_or_init_sqlite_db('secure/site.sqlite', 'secure/init.sql');

$errors = array();
$filter = "";
$page = 0;
$messages = array();

function create_select_sql($filter, $page, $search)
{
  if (!empty($filter)) {
    if ($filter == "highest-rating")
      $sql = "SELECT * FROM reviews ORDER BY rating DESC LIMIT 4 OFFSET " . $page;
    else if ($filter == "lowest-rating")
      $sql = "SELECT * FROM reviews ORDER BY rating ASC LIMIT 4 OFFSET " . $page;
    else if ($filter == "most-recent")
      $sql = "SELECT * FROM reviews ORDER BY date DESC LIMIT 4 OFFSET " . $page;
    else if ($filter == "oldest")
      $sql = "SELECT * FROM reviews ORDER BY date ASC LIMIT 4 OFFSET " . $page;
  } else if (isset($search)) {
    $sql = "SELECT * FROM reviews WHERE comment LIKE '%' || :search || '%' OR review_title LIKE '%' || :search || '%' LIMIT 4 OFFSET " . $page;
  } else
    $sql = "SELECT * FROM reviews LIMIT 4 OFFSET " . $page;
  return $sql;
}

function create_search_param($search)
{
  $params = array(
    ':search' => $search
  );
  return $params;
}

//to search for specific term in comment form
if (isset($_POST['submit_search'])) {
  $search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_STRING);
  $search = trim($search);
}

//to filter review by rating or date
if (isset($_POST['submit_filter'])) {
  $filter = $_POST['sorting'];
}

//to view more reviews on page
if (isset($_GET['submit_next'])) {
  $page = $page + 4;
}

if (isset($_GET['submit_back'])) {
  $page = $page - 4;
}

const MAX_FILE_SIZE = 1000000;
//to submit a review
if (isset($_POST['submit_review'])) {
  $db->beginTransaction();

  if (empty($_POST['reviewer'])) {
    array_push($errors, "Reviewer name is required"); //error message for review form
    $reviewer = "";
  } else
    $reviewer = filter_input(INPUT_POST, 'reviewer', FILTER_SANITIZE_STRING);

  $email = filter_input(INPUT_POST, 'reviewer_email', FILTER_SANITIZE_EMAIL);
  $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
  $review_title = filter_input(INPUT_POST, 'review_title', FILTER_SANITIZE_STRING);
  $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
  $date = date("Y-m-d");

  //add review to database
  if (empty($errors)) {
    $sql = "INSERT INTO reviews (reviewer, date, email, rating, review_title, comment)
    VALUES (:reviewer,:date, :email, :rating, :review_title, :comment)";
    $params = array(
      ':reviewer' => $reviewer,
      ':date' => $date,
      ':email' => $email,
      ':rating' => $rating,
      ':review_title' => $review_title,
      ':comment' => $comment
    );
    $result = exec_sql_query($db, $sql, $params);
    if ($result) {
      $review_id = $db->lastInsertId("id");
      array_push($messages, "Review successfully submitted!");
    } else
      array_push($errors, "Add review failed!");

    //get image info
    $upload_info = $_FILES['img_file'];
    if ($_FILES['img_file']['error'] == UPLOAD_ERR_OK) {
      $upload_file = basename($upload_info['name']);
      $upload_ext = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));
      $sql = "INSERT INTO images (review_id, image_name, image_ext) VALUES(:review_id, :image_name, :image_ext)";
      $params = array(
        ':review_id' => $review_id,
        ':image_name' => $upload_file,
        ':image_ext' => $upload_ext
      );
      $result = exec_sql_query($db, $sql, $params);
      if ($result) {
        $doc_id = $db->lastInsertId("id");
        $new_path = "uploads/images/$doc_id.$upload_ext";
        move_uploaded_file($_FILES["img_file"]["tmp_name"], $new_path);
        array_push($messages, "Upload file is success!");
      } else
        array_push($errors, "Upload image file failed!");
    }
    $db->commit();
  }
}

if (isset($_POST['cancel_review'])) {
  array_push($messages, "Review successfully cancelled!");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
  <link href="styles/all.css" media="all" rel="stylesheet" type="text/css">
  <title>Reviews</title>
</head>

<body>
  <!-- TODO: This should be your main page for your site. -->
  <?php include("includes/header.php"); ?>

  <!--display error message-->
  <?php foreach ($errors as $error) {
    echo "<p class=message><strong>" . htmlspecialchars($error) . "</strong></p>\n";
  } ?>

  <?php foreach ($messages as $message) {
    echo "<p class=message><strong>" . htmlspecialchars($message) . "</strong></p>\n";
  } ?>

  <main id="reviews_main">
    <?php if (isset($_POST["write_review"]) || !empty($errors)) { ?>
      <h2>Write a Review</h2>
      <form id="review_form" action="reviews.php" method="post" enctype="multipart/form-data">
        <fieldset class="review">
          <p>
            <label>Name:</label>
            <input class="review" type="text" name="reviewer" value="<?php echo htmlspecialchars($reviewer); ?>" />
          </p>
          <p>
            <label>Email:</label>
            <input class="review" type="email" name="reviewer_email" value="<?php echo htmlspecialchars($email); ?>" />
          </p>
          <p>
            <label>Rating:</label>
            <input class="review" type="radio" name="rating" value="5" checked />5
            <input class="review" type="radio" name="rating" value="4" <?php if (isset($rating) && $rating == "4") echo "checked"; ?> />4
            <input class="review" type="radio" name="rating" value="3" <?php if (isset($rating) && $rating == "3") echo "checked"; ?> />3
            <input class="review" type="radio" name="rating" value="2" <?php if (isset($rating) && $rating == "2") echo "checked"; ?> />2
            <input class="review" type="radio" name="rating" value="1" <?php if (isset($rating) && $rating == "1") echo "checked"; ?> />1
          </p>

          <p>
            <label>Review Title:</label>
            <input class="review" type="text" name="review_title" value="<?php echo htmlspecialchars($review_title); ?>" />
          </p>
          <p>
            <label>Comment:</label>
            <textarea class="review" name="comment"><?php echo htmlspecialchars($comment); ?></textarea>
          </p>

          <p>
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>" />
            Images to Share? Upload it!
            <label>Image:</label>
            <input class="review" type="file" name="img_file" />
          </p>
          <p>
            <input class="review" name="submit_review" type="submit" value="Add Review">
            <input class="review" name="cancel_review" type="submit" value="Cancel Review">
          </p>
        </fieldset>
      </form>
    <?php
  } else { ?>
      <h2>Reviews</h2>
      <!-- main review page display -->
      <form id="review_filter_forms" class="form-inline" action="reviews.php" method="post">
        <!-- filter form -->
        <select id="review_sort" name="sorting">
          <option value="" selected>Sort By ...</option>
          <option value="highest-rating">Sort by rating: high to low</option>
          <option value="lowest-rating">Sort by rating: low to high</option>
          <option value="most-recent">Sort by date: Most Recent</option>
          <option value="oldest">Sort by date: Oldest</option>
        </select>
        <input class="review_button" id="review_sort_button" type="submit" name="submit_filter" value="Filter">
        <!-- search form -->
        <label>Show reviews that mention:</label>
        <input id="review_search_field" class="review" type="text" name="search" placeholder="Search:" value="<?php echo htmlspecialchars($search_tag); ?>" />
        <input id="review_search_button" class="review_button" type="submit" name="submit_search" value="Search">
        <!-- write a review button -->
        <input id="write_review_button" class="review_button" type="submit" name="write_review" value="Write a review">
      </form>



      <!-- SHOW Reviews -->
      <?php
      $sql = create_select_sql($filter, $page, $search);
      if (isset($search))
        $params = create_search_param($search);
      else
        $params = array();
      $result = exec_sql_query($db, $sql, $params);
      $records = $result->fetchAll();
      foreach ($records as $record) { ?>
        <div class="display_review">
          <!--Each table column is echoed into a td cell-->
          <div id="left" class="inner_display_review">
            <p><?php echo htmlspecialchars($record['reviewer']); ?></p>
            <p><?php echo htmlspecialchars($record['date']); ?></p>
          </div>
          <div id="right" class="inner_display_review">
            <p>
              <?php $stars = intval($record["rating"]);
              for ($i = 1; $i <= 5; $i++) {
                if ($i <= $stars) {
                  echo "★";
                } else {
                  echo "☆";
                }
              }
              ?></p>
            <p id="display_review_title"><?php echo htmlspecialchars($record['review_title']); ?></p>
            <p><?php echo htmlspecialchars($record['comment']); ?></p>
          </div>
        </div>
      <?php
    } ?>

      <div>
        <!-- Show More reviews -->
        <form id="review_more" action="reviews.php" method="get">
          <?php if($page != 0 && isset($search)){ ?>
          <button id="review_back_button" class="review_button" type="submit" name="submit_back">
            < Back </button> <?php }
                              if ($page != 0) { ?> <button id="review_next_button" class="review_button" type="submit" name="submit_next"> Next >
              <?php } ?>
          </button>
        </form>
      </div>
    <?php
  } ?>


  </main>

  <?php include("includes/footer.php"); ?>

</body>

</html>
