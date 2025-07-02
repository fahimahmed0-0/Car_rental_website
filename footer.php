<?php
if (isset($_POST['emailsubscibe'])) {
  $subscriberemail = $_POST['subscriberemail'];
  $sql = "SELECT SubscriberEmail FROM tblsubscribers WHERE SubscriberEmail=:subscriberemail";
  $query = $dbh->prepare($sql);
  $query->bindParam(':subscriberemail', $subscriberemail, PDO::PARAM_STR);
  $query->execute();
  $results = $query->fetchAll(PDO::FETCH_OBJ);

  if ($query->rowCount() > 0) {
    echo "<script>alert('Already Subscribed.');</script>";
  } else {
    $sql = "INSERT INTO tblsubscribers(SubscriberEmail) VALUES(:subscriberemail)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':subscriberemail', $subscriberemail, PDO::PARAM_STR);
    $query->execute();
    $lastInsertId = $dbh->lastInsertId();

    if ($lastInsertId) {
      echo "<script>alert('Subscribed successfully.');</script>";
    } else {
      echo "<script>alert('Something went wrong. Please try again');</script>";
    }
  }
}
?>

<footer>
  <div class="footer-top">
    <div class="container">
      <div class="row">

        <div class="col-md-6 col-sm-6">
          <h6>About Us</h6>
          <ul>
            <li><a href="page.php?type=aboutus">About Us</a></li>
            <li><a href="page.php?type=privacy">Privacy and Policy</a></li>
            <li><a href="page.php?type=terms">Terms and Conditions</a></li>
            <li><a href="admin/">Admin Login</a></li>
          </ul>
        </div>

        <div class="col-md-6 col-sm-6">
          <h6>Subscribe Newsletter</h6>
          <div class="newsletter-form">
            <form method="post">
              <div class="form-group">
                <input type="email" name="subscriberemail" class="form-control newsletter-input" required placeholder="Enter Email Address" />
              </div>
              <button type="submit" name="emailsubscibe" class="btn btn-block">
                Subscribe <span class="angle_arrow"><i class="fa fa-angle-right" aria-hidden="true"></i></span>
              </button>
            </form>
            <p class="subscribed-text">*We are the best in our fields brought great deals and latest offers to our subscribed users.</p>
          </div>

          <div class="footer_widget" style="margin: 20px 0 0 0;">
            <p>Connect Us:</p>
            <ul class="social-icons">
              <li><a href="https://www.facebook.com/p/Drivex-61575801312906/" target="_blank"><i class="fa fa-facebook-square" aria-hidden="true" style="font-size: 24px;"></i></a></li>
              <li><a href="https://drivexassam.com/" target="_blank"><i class="fa fa-google-plus-square" aria-hidden="true" style="font-size: 24px;"></i></a></li>
              <li><a href="https://www.instagram.com/drivex_assam/?locale=en-GB&hl=ar" target="_blank"><i class="fa fa-instagram" aria-hidden="true" style="font-size: 24px;"></i></a></li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <div class="row">

        <div class="col-md-6 text-left">
          <p class="copy-right">© DriveXAssam.</p>
        </div>

        <div class="col-md-6 text-right">
        </div>

      </div>
    </div>
  </div>
</footer>