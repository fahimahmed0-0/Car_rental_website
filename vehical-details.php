<?php
session_start();
include('includes/config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(0);
if (isset($_POST['submit'])) {
  $fromdate = $_POST['fromdate'];
  $todate = $_POST['todate'];
  $message = $_POST['message'];
  $useremail = $_SESSION['login'];
  $status = 0;
  $vhid = $_GET['vhid'];
  $bookingno = mt_rand(100000000, 999999999);

  // First check if the car is available for the selected dates
  $ret = "SELECT * FROM tblbooking where (:fromdate BETWEEN date(FromDate) and date(ToDate) || :todate BETWEEN date(FromDate) and date(ToDate) || date(FromDate) BETWEEN :fromdate and :todate) and VehicleId=:vhid";
  $query1 = $dbh->prepare($ret);
  $query1->bindParam(':vhid', $vhid, PDO::PARAM_STR);
  $query1->bindParam(':fromdate', $fromdate, PDO::PARAM_STR);
  $query1->bindParam(':todate', $todate, PDO::PARAM_STR);
  $query1->execute();
  $results1 = $query1->fetchAll(PDO::FETCH_OBJ);

  if ($query1->rowCount() == 0) {
    // Car is available, now process token redemption if requested
    $tokensToRedeem = isset($_POST['redeem_tokens']) ? intval($_POST['redeem_tokens']) : 0;
    $discountAmount = 0;

    if ($tokensToRedeem > 0) {
      // Check if user has enough tokens
      $sql = "SELECT tokenBalance FROM tblcustomertokens WHERE userEmail = :email";
      $query = $dbh->prepare($sql);
      $query->bindParam(':email', $useremail, PDO::PARAM_STR);
      $query->execute();
      $currentTokens = $query->fetchColumn();

      if ($tokensToRedeem < 15) {
        echo "<script>alert('Minimum 15 tokens required for redemption');</script>";
        echo "<script type='text/javascript'> document.location = 'car-listing.php'; </script>";
        exit;
      }

      if ($currentTokens < $tokensToRedeem) {
        echo "<script>alert('Insufficient token balance');</script>";
        echo "<script type='text/javascript'> document.location = 'car-listing.php'; </script>";
        exit;
      }

      // Calculate discount (5 tokens = ₹100)
      $discountAmount = ($tokensToRedeem / 5) * 100;

      // Start transaction for token redemption and booking
      $dbh->beginTransaction();

      try {
        // Deduct tokens and record redemption
        $sql = "UPDATE tblcustomertokens SET tokenBalance = tokenBalance - :tokens WHERE userEmail = :email";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $useremail, PDO::PARAM_STR);
        $query->bindParam(':tokens', $tokensToRedeem, PDO::PARAM_INT);
        $query->execute();

        $sql = "INSERT INTO tbltokenredemption (userEmail, tokensRedeemed, discountAmount, bookingId) VALUES (:email, :tokens, :discount, :booking)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $useremail, PDO::PARAM_STR);
        $query->bindParam(':tokens', $tokensToRedeem, PDO::PARAM_INT);
        $query->bindParam(':discount', $discountAmount, PDO::PARAM_STR);
        $query->bindParam(':booking', $bookingno, PDO::PARAM_STR);
        $query->execute();

        // Insert booking record
        $sql = "INSERT INTO tblbooking(BookingNumber,userEmail,VehicleId,FromDate,ToDate,message,Status) VALUES(:bookingno,:useremail,:vhid,:fromdate,:todate,:message,:status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookingno', $bookingno, PDO::PARAM_STR);
        $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
        $query->bindParam(':vhid', $vhid, PDO::PARAM_STR);
        $query->bindParam(':fromdate', $fromdate, PDO::PARAM_STR);
        $query->bindParam(':todate', $todate, PDO::PARAM_STR);
        $query->bindParam(':message', $message, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();

        // If everything is successful, commit the transaction
        $dbh->commit();

        // Send email notification
        try {
          require 'PHPMailer/Exception.php';
          require 'PHPMailer/PHPMailer.php';
          require 'PHPMailer/SMTP.php';
          $mail = new PHPMailer(true);
          $mail->isSMTP();
          $mail->Host       = 'smtp.gmail.com';
          $mail->SMTPAuth   = true;
          $mail->Username   = 'drivexassam@gmail.com';
          $mail->Password   = 'vobdoprvlxutigyu';
          $mail->SMTPSecure = 'tls';
          $mail->Port       = 587;
          $mail->SMTPOptions = array(
            'ssl' => array(
              'verify_peer' => false,
              'verify_peer_name' => false,
              'allow_self_signed' => true
            )
          );
          $mail->setFrom('drivexassam@gmail.com', 'contact form');
          $mail->addAddress('drivexassam@gmail.com', 'user');
          $mail->isHTML(true);
          $mail->Subject = 'New Car Booking - ' . $bookingno;

          // Get user details from the database
          $sql = "SELECT FullName, ContactNo, EmailId FROM tblusers WHERE EmailId=:useremail";
          $query = $dbh->prepare($sql);
          $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
          $query->execute();
          $user = $query->fetch(PDO::FETCH_OBJ);

          // Get vehicle details
          $sql = "SELECT tblvehicles.VehiclesTitle, tblbrands.BrandName 
                  FROM tblvehicles 
                  JOIN tblbrands ON tblbrands.id=tblvehicles.VehiclesBrand 
                  WHERE tblvehicles.id=:vhid";
          $query = $dbh->prepare($sql);
          $query->bindParam(':vhid', $vhid, PDO::PARAM_STR);
          $query->execute();
          $vehicle = $query->fetch(PDO::FETCH_OBJ);

          $mail->Body    = "Booking Details:<br><br>" .
            "Customer Name: " . $user->FullName . "<br>" .
            "Contact Number: " . $user->ContactNo . "<br>" .
            "Email: " . $user->EmailId . "<br><br>" .
            "Vehicle: " . $vehicle->BrandName . " " . $vehicle->VehiclesTitle . "<br>" .
            "From Date: " . $fromdate . "<br>" .
            "To Date: " . $todate . "<br>" .
            "Message: " . $message . "<br>" .
            "Booking Number: " . $bookingno;

          $mail->send();
        } catch (Exception $e) {
          error_log("Email Error: " . $mail->ErrorInfo);
        }

        echo "<script>alert('Booking successful. Proceed to payment.');</script>";
        echo "<script type='text/javascript'> document.location = 'payment.php?bookingno=" . $bookingno . "'; </script>";
      } catch (Exception $e) {
        // If anything goes wrong, rollback the transaction
        $dbh->rollBack();
        error_log("Booking Error: " . $e->getMessage());
        echo "<script>alert('Something went wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location = 'car-listing.php'; </script>";
      }
    } else {
      // No token redemption, just process the booking
      $sql = "INSERT INTO tblbooking(BookingNumber,userEmail,VehicleId,FromDate,ToDate,message,Status) VALUES(:bookingno,:useremail,:vhid,:fromdate,:todate,:message,:status)";
      $query = $dbh->prepare($sql);
      $query->bindParam(':bookingno', $bookingno, PDO::PARAM_STR);
      $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
      $query->bindParam(':vhid', $vhid, PDO::PARAM_STR);
      $query->bindParam(':fromdate', $fromdate, PDO::PARAM_STR);
      $query->bindParam(':todate', $todate, PDO::PARAM_STR);
      $query->bindParam(':message', $message, PDO::PARAM_STR);
      $query->bindParam(':status', $status, PDO::PARAM_STR);
      $query->execute();
      $lastInsertId = $dbh->lastInsertId();

      // Send email notification
      try {
        require 'PHPMailer/Exception.php';
        require 'PHPMailer/PHPMailer.php';
        require 'PHPMailer/SMTP.php';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'drivexassam@gmail.com';
        $mail->Password   = 'vobdoprvlxutigyu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->SMTPOptions = array(
          'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
          )
        );
        $mail->setFrom('drivexassam@gmail.com', 'contact form');
        $mail->addAddress('drivexassam@gmail.com', 'user');
        $mail->isHTML(true);
        $mail->Subject = 'New Car Booking - ' . $bookingno;

        // Get user details from the database
        $sql = "SELECT FullName, ContactNo, EmailId FROM tblusers WHERE EmailId=:useremail";
        $query = $dbh->prepare($sql);
        $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_OBJ);

        // Get vehicle details
        $sql = "SELECT tblvehicles.VehiclesTitle, tblbrands.BrandName 
                FROM tblvehicles 
                JOIN tblbrands ON tblbrands.id=tblvehicles.VehiclesBrand 
                WHERE tblvehicles.id=:vhid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':vhid', $vhid, PDO::PARAM_STR);
        $query->execute();
        $vehicle = $query->fetch(PDO::FETCH_OBJ);

        $mail->Body    = "Booking Details:<br><br>" .
          "Customer Name: " . $user->FullName . "<br>" .
          "Contact Number: " . $user->ContactNo . "<br>" .
          "Email: " . $user->EmailId . "<br><br>" .
          "Vehicle: " . $vehicle->BrandName . " " . $vehicle->VehiclesTitle . "<br>" .
          "From Date: " . $fromdate . "<br>" .
          "To Date: " . $todate . "<br>" .
          "Message: " . $message . "<br>" .
          "Booking Number: " . $bookingno;

        $mail->send();
      } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
      }

      if ($lastInsertId) {
        echo "<script>alert('Booking successful. Proceed to payment.');</script>";
        echo "<script type='text/javascript'> document.location = 'payment.php?bookingno=" . $bookingno . "'; </script>";
      } else {
        echo "<script>alert('Something went wrong. Please try again');</script>";
        echo "<script type='text/javascript'> document.location = 'car-listing.php'; </script>";
      }
    }
  } else {
    echo "<script>alert('Car already booked for these days');</script>";
    echo "<script type='text/javascript'> document.location = 'car-listing.php'; </script>";
  }
}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
  <title>Car Rental | Vehicle Details</title>
  <!--Bootstrap -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
  <!--Custome Style -->
  <link rel="stylesheet" href="assets/css/style.css" type="text/css">
  <!--OWL Carousel slider-->
  <link rel="stylesheet" href="assets/css/owl.carousel.css" type="text/css">
  <link rel="stylesheet" href="assets/css/owl.transitions.css" type="text/css">
  <!--slick-slider -->
  <link href="assets/css/slick.css" rel="stylesheet">
  <!--bootstrap-slider -->
  <link href="assets/css/bootstrap-slider.min.css" rel="stylesheet">
  <!--FontAwesome Font Style -->
  <link href="assets/css/font-awesome.min.css" rel="stylesheet">

  <!-- SWITCHER -->
  <link rel="stylesheet" id="switcher-css" type="text/css" href="assets/switcher/css/switcher.css" media="all" />
  <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/red.css" title="red" media="all" data-default-color="true" />
  <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/orange.css" title="orange" media="all" />
  <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/blue.css" title="blue" media="all" />
  <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/pink.css" title="pink" media="all" />
  <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/green.css" title="green" media="all" />
  <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/purple.css" title="purple" media="all" />
  <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/favicon-icon/apple-touch-icon-144-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/favicon-icon/apple-touch-icon-114-precomposed.html">
  <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/favicon-icon/apple-touch-icon-72-precomposed.png">
  <link rel="apple-touch-icon-precomposed" href="assets/images/favicon-icon/apple-touch-icon-57-precomposed.png">
  <link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
</head>

<body>

  <!-- Start Switcher -->
  <?php include('includes/colorswitcher.php'); ?>
  <!-- /Switcher -->

  <!--Header-->
  <?php include('includes/header.php'); ?>
  <!-- /Header -->

  <!--Listing-Image-Slider-->

  <?php
  $vhid = intval($_GET['vhid']);
  $sql = "SELECT tblvehicles.*,tblbrands.BrandName,tblbrands.id as bid  from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand where tblvehicles.id=:vhid";
  $query = $dbh->prepare($sql);
  $query->bindParam(':vhid', $vhid, PDO::PARAM_STR);
  $query->execute();
  $results = $query->fetchAll(PDO::FETCH_OBJ);
  $cnt = 1;
  if ($query->rowCount() > 0) {
    foreach ($results as $result) {
      $_SESSION['brndid'] = $result->bid;
  ?>

      <section id="listing_img_slider">
        <div><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1); ?>" class="img-responsive" alt="image" width="900" height="560"></div>
        <div><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage2); ?>" class="img-responsive" alt="image" width="900" height="560"></div>
        <div><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage3); ?>" class="img-responsive" alt="image" width="900" height="560"></div>
        <div><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage4); ?>" class="img-responsive" alt="image" width="900" height="560"></div>
        <?php if ($result->Vimage5 == "") {
        } else {
        ?>
          <div><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage5); ?>" class="img-responsive" alt="image" width="900" height="560"></div>
        <?php } ?>
      </section>
      <!--/Listing-Image-Slider-->


      <!--Listing-detail-->
      <section class="listing-detail">
        <div class="container">
          <div class="listing_detail_head row">
            <div class="col-md-9">
              <h2><?php echo htmlentities($result->BrandName); ?> , <?php echo htmlentities($result->VehiclesTitle); ?></h2>
            </div>
            <div class="col-md-3">
              <div class="price_info">
                <p>₹<?php echo htmlentities($result->PricePerDay); ?> </p>Per Day
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-9">
              <div class="main_features">
                <ul>
                  <li> <i class="fa fa-calendar" aria-hidden="true"></i>
                    <h5><?php echo htmlentities($result->ModelYear); ?></h5>
                    <p>Reg.Year</p>
                  </li>
                  <li> <i class="fa fa-cogs" aria-hidden="true"></i>
                    <h5><?php echo htmlentities($result->FuelType); ?></h5>
                    <p>Fuel Type</p>
                  </li>
                  <li> <i class="fa fa-user-plus" aria-hidden="true"></i>
                    <h5><?php echo htmlentities($result->SeatingCapacity); ?></h5>
                    <p>Seats</p>
                  </li>
                </ul>
              </div>
              <div class="listing_more_info">
                <div class="listing_detail_wrap">
                  <!-- Nav tabs -->
                  <ul class="nav nav-tabs gray-bg" role="tablist">
                    <li role="presentation" class="active"><a href="#vehicle-overview " aria-controls="vehicle-overview" role="tab" data-toggle="tab">Vehicle Overview </a></li>
                    <li role="presentation"><a href="#accessories" aria-controls="accessories" role="tab" data-toggle="tab">Accessories</a></li>
                  </ul>

                  <!-- Tab panes -->
                  <div class="tab-content">
                    <!-- vehicle-overview -->
                    <div role="tabpanel" class="tab-pane active" id="vehicle-overview">
                      <p><?php echo htmlentities($result->VehiclesOverview); ?></p>
                    </div>

                    <!-- Accessories -->
                    <div role="tabpanel" class="tab-pane" id="accessories">
                      <!--Accessories-->
                      <table>
                        <thead>
                          <tr>
                            <th colspan="2">Accessories</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>Air Conditioner</td>
                            <?php if ($result->AirConditioner == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>AntiLock Braking System</td>
                            <?php if ($result->AntiLockBrakingSystem == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Power Steering</td>
                            <?php if ($result->PowerSteering == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Power Windows</td>
                            <?php if ($result->PowerWindows == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>CD Player</td>
                            <?php if ($result->CDPlayer == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Leather Seats</td>
                            <?php if ($result->LeatherSeats == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Central Locking</td>
                            <?php if ($result->CentralLocking == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Power Door Locks</td>
                            <?php if ($result->PowerDoorLocks == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>
                          <tr>
                            <td>Brake Assist</td>
                            <?php if ($result->BrakeAssist == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php  } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Driver Airbag</td>
                            <?php if ($result->DriverAirbag == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Passenger Airbag</td>
                            <?php if ($result->PassengerAirbag == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                          <tr>
                            <td>Crash Sensor</td>
                            <?php if ($result->CrashSensor == 1) {
                            ?>
                              <td><i class="fa fa-check" aria-hidden="true"></i></td>
                            <?php } else { ?>
                              <td><i class="fa fa-close" aria-hidden="true"></i></td>
                            <?php } ?>
                          </tr>

                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
          <?php }
      } ?>

            </div>

            <!--Side-Bar-->
            <aside class="col-md-3">
              <div class="share_vehicle">
                <p>Share: <a href="https://www.facebook.com/p/Drivex-61575801312906/">
                    <i class="fa fa-facebook-square" aria-hidden="true"></i>
                  </a>
                  <a href="https://www.instagram.com/drivex_assam/?locale=en-GB&hl=ar">
                    <i class="fa fa-instagram" aria-hidden="true"></i>
                  </a>
                </p>
              </div>
              <div class="sidebar_widget">
                <div class="widget_heading">
                  <h5><i class="fa fa-envelope" aria-hidden="true"></i>Book Now</h5>
                </div>
                <form method="post">
                  <div class="form-group">
                    <label>From Date:</label>
                    <input type="date" class="form-control" name="fromdate" id="fromdate" placeholder="From Date" min="<?php echo date('Y-m-d'); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>To Date:</label>
                    <input type="date" class="form-control" name="todate" id="todate" placeholder="To Date" min="<?php echo date('Y-m-d'); ?>" required>
                  </div>
                  <div class="form-group">
                    <textarea rows="4" class="form-control" name="message" placeholder="Message" required></textarea>
                  </div>
                  <?php if ($_SESSION['login']) { ?>
                    <div class="form-group">
                      <input type="submit" class="btn" name="submit" value="Book Now">
                    </div>
                  <?php } else { ?>
                    <a href="#loginform" class="btn btn-xs uppercase" data-toggle="modal" data-dismiss="modal">Login For Book</a>
                  <?php } ?>
                  <div class="form-group">
                    <?php if (strlen($_SESSION['login']) == 0) { ?>
                      <a href="#loginform" class="btn btn-xs uppercase" data-toggle="modal" data-dismiss="modal"></a>
                    <?php } else { ?>
                      <!-- Token Redemption Section -->
                      <div class="token-redemption-section p-3 mb-4 bg-light rounded">
                        <?php
                        // Get user's token balance
                        $sql = "SELECT tokenBalance FROM tblcustomertokens WHERE userEmail = :email";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':email', $_SESSION['login'], PDO::PARAM_STR);
                        $query->execute();
                        $tokenBalance = $query->fetchColumn();
                        if (!$tokenBalance) $tokenBalance = 0;
                        ?>

                        <h4 class="text-primary mb-3">Use Your Tokens for Discount!</h4>
                        <div class="token-info mb-3">
                          <div class="row">
                            <div class="col-md-6">
                              <div class="current-balance">
                                <h5>Your Token Balance</h5>
                                <h3 class="text-success"><?php echo $tokenBalance; ?> tokens</h3>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="redemption-rules">
                                <ul class="list-unstyled">
                                  <li><i class="fa fa-check text-success"></i> 5 tokens = ₹100 discount</li>
                                  <li><i class="fa fa-check text-success"></i> Minimum 15 tokens needed</li>
                                  <li><i class="fa fa-check text-success"></i> Redeem in multiples of 5</li>
                                </ul>
                              </div>
                            </div>
                          </div>
                        </div>

                        <?php if ($tokenBalance >= 15) { ?>
                          <div class="redemption-options">
                            <label for="redeem_tokens"><strong>Select Tokens to Redeem:</strong></label>
                            <select name="redeem_tokens" id="redeem_tokens" class="form-control" onchange="updateDiscountDisplay()">
                              <option value="0">Select Tokens Here</option>
                              <?php
                              // Show redemption options in multiples of 5, up to user's balance
                              for ($i = 15; $i <= $tokenBalance && $i <= 50; $i += 5) {
                                $discount = ($i / 5) * 100;
                                echo "<option value=\"$i\">Redeem $i tokens for ₹$discount discount</option>";
                              }
                              ?>
                            </select>
                            <div id="selected-discount" class="mt-2 text-success" style="display: none;">
                              <strong>Selected Discount: ₹<span id="discount-amount">0</span></strong>
                            </div>
                          </div>
                        <?php } else { ?>
                          <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> You need at least 15 tokens to get a discount.
                            Complete more bookings to earn tokens!
                          </div>
                        <?php } ?>
                      </div>
                      <!-- End Token Redemption Section -->
                    <?php } ?>
                  </div>
                </form>
              </div>
            </aside>
            <!--/Side-Bar-->
          </div>

          <div class="space-20"></div>
          <div class="divider"></div>

          <!--Similar-Cars-->
          <div class="similar_cars">
            <h3>Similar Cars</h3>
            <div class="row">
              <?php
              $bid = $_SESSION['brndid'];
              $sql = "SELECT tblvehicles.VehiclesTitle,tblbrands.BrandName,tblvehicles.PricePerDay,tblvehicles.FuelType,tblvehicles.ModelYear,tblvehicles.id,tblvehicles.SeatingCapacity,tblvehicles.VehiclesOverview,tblvehicles.Vimage1 from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand where tblvehicles.VehiclesBrand=:bid";
              $query = $dbh->prepare($sql);
              $query->bindParam(':bid', $bid, PDO::PARAM_STR);
              $query->execute();
              $results = $query->fetchAll(PDO::FETCH_OBJ);
              $cnt = 1;
              if ($query->rowCount() > 0) {
                foreach ($results as $result) { ?>
                  <div class="col-md-3 grid_listing">
                    <div class="product-listing-m gray-bg">
                      <div class="product-listing-img"> <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id); ?>"><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1); ?>" class="img-responsive" alt="image" /> </a>
                      </div>
                      <div class="product-listing-content">
                        <h5><a href="vehical-details.php?vhid=<?php echo htmlentities($result->id); ?>"><?php echo htmlentities($result->BrandName); ?> , <?php echo htmlentities($result->VehiclesTitle); ?></a></h5>
                        <p class="list-price">₹<?php echo htmlentities($result->PricePerDay); ?></p>
                        <ul class="features_list">
                          <li><i class="fa fa-user" aria-hidden="true"></i><?php echo htmlentities($result->SeatingCapacity); ?> seats</li>
                          <li><i class="fa fa-calendar" aria-hidden="true"></i><?php echo htmlentities($result->ModelYear); ?> model</li>
                          <li><i class="fa fa-car" aria-hidden="true"></i><?php echo htmlentities($result->FuelType); ?></li>
                        </ul>
                      </div>
                    </div>
                  </div>
              <?php }
              } ?>
            </div>
          </div>
          <!--/Similar-Cars-->

        </div>
      </section>
      <!--/Listing-detail-->

      <!--Footer -->
      <?php include('includes/footer.php'); ?>
      <!-- /Footer-->

      <!--Back to top-->
      <div id="back-top" class="back-top"> <a href="#top"><i class="fa fa-angle-up" aria-hidden="true"></i> </a> </div>
      <!--/Back to top-->

      <!--Login-Form -->
      <?php include('includes/login.php'); ?>
      <!--/Login-Form -->

      <!--Register-Form -->
      <?php include('includes/registration.php'); ?>

      <!--/Register-Form -->

      <!--Forgot-password-Form -->
      <?php include('includes/forgotpassword.php'); ?>

      <script src="assets/js/jquery.min.js"></script>
      <script src="assets/js/bootstrap.min.js"></script>
      <script src="assets/js/interface.js"></script>
      <script src="assets/switcher/js/switcher.js"></script>
      <script src="assets/js/bootstrap-slider.min.js"></script>
      <script src="assets/js/slick.min.js"></script>
      <script src="assets/js/owl.carousel.min.js"></script>

      <script>
        // Date validation
        document.getElementById('fromdate').addEventListener('change', function() {
          document.getElementById('todate').min = this.value;
        });

        document.getElementById('todate').addEventListener('change', function() {
          if (this.value < document.getElementById('fromdate').value) {
            alert('To Date cannot be before From Date');
            this.value = document.getElementById('fromdate').value;
          }
        });

        function updateDiscountDisplay() {
          var tokens = document.getElementById('redeem_tokens').value;
          var discountDiv = document.getElementById('selected-discount');
          var discountAmount = document.getElementById('discount-amount');

          if (tokens > 0) {
            var discount = (tokens / 5) * 100;
            discountAmount.textContent = discount;
            discountDiv.style.display = 'block';
          } else {
            discountDiv.style.display = 'none';
          }
        }
      </script>

</body>

</html>