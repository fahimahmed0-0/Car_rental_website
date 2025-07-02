<?php
session_start();
error_reporting(0);
include('includes/config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['send'])) {
  $name = $_POST['fullname'];
  $email = $_POST['email'];
  $contactno = $_POST['contactno'];
  $message = $_POST['message'];



  //Load Composer's autoloader (created by composer, not included with PHPMailer)
  require 'PHPMailer/Exception.php';
  require 'PHPMailer/PHPMailer.php';
  require 'PHPMailer/SMTP.php';

  //Create an instance; passing `true` enables exceptions
  $mail = new PHPMailer(true);

  try {
    //Server settings                   //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'drivexassam@gmail.com';                     //SMTP username
    $mail->Password   = 'vobdoprvlxutigyu';                               //SMTP password
    $mail->SMTPSecure = 'tls';                                //Enable TLS encryption
    $mail->Port       = 587;                                    //TCP port to connect to

    // Additional settings to help prevent errors
    $mail->SMTPOptions = array(
      'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
      )
    );                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('drivexassam@gmail.com', 'contact form');
    $mail->addAddress('drivexassam@gmail.com', 'user');

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Contact Form';
    $mail->Body    = "sender name - $name <br> sender email - $email <br>contact no - $contactno <br> message - $message";

    $mail->send();
    echo ' ';
  } catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
  }
  $sql = "INSERT INTO tblcontactusquery(name,EmailId,ContactNumber,Message) VALUES(:name,:email,:contactno,:message)";
  $query = $dbh->prepare($sql);
  $query->bindParam(':name', $name, PDO::PARAM_STR);
  $query->bindParam(':email', $email, PDO::PARAM_STR);
  $query->bindParam(':contactno', $contactno, PDO::PARAM_STR);
  $query->bindParam(':message', $message, PDO::PARAM_STR);
  $query->execute();
  $lastInsertId = $dbh->lastInsertId();
  if ($lastInsertId) {
    $msg = "Query Sent. We will contact you shortly";
  } else {
    $error = "Something went wrong. Please try again";
  }
}
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
  <title>Car Rental | Contact Us Page</title>
  <meta charset="utf-8">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/owl.carousel.css">
  <link rel="stylesheet" href="assets/css/owl.transitions.css">
  <link href="assets/css/slick.css" rel="stylesheet">
  <link href="assets/css/bootstrap-slider.min.css" rel="stylesheet">
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
  <link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">

  <style>
    .errorWrap {
      padding: 10px;
      margin: 0 0 20px 0;
      background: #fff;
      border-left: 4px solid #dd3d36;
      box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
    }

    .succWrap {
      padding: 10px;
      margin: 0 0 20px 0;
      background: #fff;
      border-left: 4px solid #5cb85c;
      box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
    }

    .map-heading {
      margin: 40px 0 20px;
      font-size: 24px;
      font-weight: 600;
      color: #333;
      text-align: center;
    }

    .map-wrapper {
      position: relative;
      padding-top: 56.25%;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      background-color: #f4f4f4;
    }

    .map-wrapper iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border: none;
      border-radius: 12px;
    }
  </style>
</head>

<body>

  <?php include('includes/colorswitcher.php'); ?>
  <?php include('includes/header.php'); ?>

  <section class="page-header contactus_page">
    <div class="container">
      <div class="page-header_wrap">
        <div class="page-heading">
          <h1>Contact Us</h1>
        </div>
        <ul class="coustom-breadcrumb">
          <li><a href="#">Home</a></li>
          <li>Contact Us</li>
        </ul>
      </div>
    </div>
    <div class="dark-overlay"></div>
  </section>

  <section class="contact_us section-padding">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <h3>Get in touch using the form below</h3>
          <?php if ($error) { ?>
            <div class="errorWrap"><strong>ERROR</strong>:<?php echo htmlentities($error); ?> </div>
          <?php } else if ($msg) { ?>
            <div class="succWrap"><strong>SUCCESS</strong>:<?php echo htmlentities($msg); ?> </div>
          <?php } ?>
          <div class="contact_form gray-bg">
            <form method="post">
              <div class="form-group">
                <label class="control-label">Full Name <span>*</span></label>
                <input type="text" name="fullname" class="form-control white_bg" required>
              </div>
              <div class="form-group">
                <label class="control-label">Email Address <span>*</span></label>
                <input type="email" name="email" class="form-control white_bg" required>
              </div>
              <div class="form-group">
                <label class="control-label">Phone Number <span>*</span></label>
                <input type="text" name="contactno" class="form-control white_bg" required maxlength="10" pattern="[0-9]+">
              </div>
              <div class="form-group">
                <label class="control-label">Message <span>*</span></label>
                <textarea class="form-control white_bg" name="message" rows="4" required></textarea>
              </div>
              <div class="form-group">
                <button class="btn" type="submit" name="send">Send Message <span class="angle_arrow"><i class="fa fa-angle-right" aria-hidden="true"></i></span></button>
              </div>
            </form>
          </div>
        </div>
        <div class="col-md-6">
          <h3>Contact Info</h3>
          <div class="contact_detail">
            <?php
            $sql = "SELECT Address,EmailId,ContactNo FROM tblcontactusinfo";
            $query = $dbh->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);
            if ($query->rowCount() > 0) {
              foreach ($results as $result) { ?>
                <ul>
                  <li>
                    <div class="icon_wrap"><i class="fa fa-map-marker" aria-hidden="true"></i></div>
                    <div class="contact_info_m"><?php echo htmlentities($result->Address); ?></div>
                  </li>
                  <li>
                    <div class="icon_wrap"><i class="fa fa-phone" aria-hidden="true"></i></div>
                    <div class="contact_info_m"><a href="tel:<?php echo htmlentities($result->ContactNo); ?>"><?php echo htmlentities($result->ContactNo); ?></a></div>
                  </li>
                  <li>
                    <div class="icon_wrap"><i class="fa fa-envelope-o" aria-hidden="true"></i></div>
                    <div class="contact_info_m"><a href="mailto:<?php echo htmlentities($result->EmailId); ?>"><?php echo htmlentities($result->EmailId); ?></a></div>
                  </li>
                </ul>
            <?php }
            } ?>

            <!-- Google Map - Stylish Embed -->
            <h3 class="map-heading">Find Us on Map</h3>
            <div class="map-wrapper">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3564.5028488546827!2d92.8324141!3d26.6963781!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3744ebbb5c5463f7%3A0x418fff7737cff274!2sDriveX!5e0!3m2!1sen!2sin!4v1748452177173!5m2!1sen!2sin"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include('includes/footer.php'); ?>
  <div id="back-top" class="back-top"><a href="#top"><i class="fa fa-angle-up" aria-hidden="true"></i></a></div>

  <?php include('includes/login.php'); ?>
  <?php include('includes/registration.php'); ?>
  <?php include('includes/forgotpassword.php'); ?>

  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
  <script src="assets/js/interface.js"></script>
  <script src="assets/switcher/js/switcher.js"></script>
  <script src="assets/js/bootstrap-slider.min.js"></script>
  <script src="assets/js/slick.min.js"></script>
  <script src="assets/js/owl.carousel.min.js"></script>

</body>

</html>