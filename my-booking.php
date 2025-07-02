<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['login']) == 0) {
  header('location:index.php');
} else {
?>
  <!DOCTYPE HTML>
  <html lang="en">

  <head>

    <title>Car Rental Portal - My Booking</title>
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

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/favicon-icon/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/favicon-icon/apple-touch-icon-114-precomposed.html">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/favicon-icon/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="assets/images/favicon-icon/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
    <!-- Google-Font-->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
  </head>

  <body>

    <!-- Start Switcher -->
    <?php include('includes/colorswitcher.php'); ?>
    <!-- /Switcher -->

    <!--Header-->
    <?php include('includes/header.php'); ?>
    <!--Page Header-->
    <!-- /Header -->

    <!--Page Header-->
    <section class="page-header profile_page">
      <div class="container">
        <div class="page-header_wrap">
          <div class="page-heading">
            <h1>My Booking</h1>
          </div>
          <ul class="coustom-breadcrumb">
            <li><a href="#">Home</a></li>
            <li>My Booking</li>
          </ul>
        </div>
      </div>
      <!-- Dark Overlay-->
      <div class="dark-overlay"></div>
    </section>
    <!-- /Page Header-->

    <?php
    $useremail = $_SESSION['login'];
    $sql = "SELECT * from tblusers where EmailId=:useremail ";
    $query = $dbh->prepare($sql);
    $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    $cnt = 1;
    if ($query->rowCount() > 0) {
      foreach ($results as $result) { ?>
        <section class="user_profile inner_pages">
          <div class="container">
            <div class="user_profile_info gray-bg padding_4x4_40">
              <div class="upload_user_logo"> <img src="assets/images/profiledp.png" alt="image">
              </div>

              <div class="dealer_info">
                <h5><?php echo htmlentities($result->FullName); ?></h5>
                <p><?php echo htmlentities($result->Address); ?><br>
                  <?php echo htmlentities($result->City); ?>&nbsp;<?php echo htmlentities($result->Country);
                                                                }
                                                              } ?></p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3 col-sm-3">
                <?php include('includes/sidebar.php'); ?>

                <div class="col-md-8 col-sm-8">
                  <div class="profile_wrap">
                    <h5 class="uppercase underline">My Bookings</h5>
                    <div class="my_vehicles_list">
                      <?php
                      $useremail = $_SESSION['login'];
                      $sql = "SELECT tblvehicles.Vimage1 as Vimage1,tblvehicles.VehiclesTitle,tblvehicles.id as vid,tblbrands.BrandName,tblbooking.FromDate,tblbooking.ToDate,tblbooking.message,tblbooking.Status,tblvehicles.PricePerDay,DATEDIFF(tblbooking.ToDate,tblbooking.FromDate) + 1 as totaldays,tblbooking.BookingNumber,tblbooking.PostingDate  from tblbooking join tblvehicles on tblbooking.VehicleId=tblvehicles.id join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand where tblbooking.userEmail=:useremail order by tblbooking.id desc";
                      $query = $dbh->prepare($sql);
                      $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
                      $query->execute();
                      $results = $query->fetchAll(PDO::FETCH_OBJ);
                      $cnt = 1;
                      if ($query->rowCount() > 0) {
                        foreach ($results as $result) { ?>
                          <div class="booking-entry mb-4">
                            <div class="vehicle_status text-right mb-3">
                              <?php if ($result->Status == 1) { ?>
                                <span class="badge badge-success">Confirmed</span>
                              <?php } else if ($result->Status == 2) { ?>
                                <span class="badge badge-danger">Cancelled</span>
                              <?php } else if ($result->Status == 3) { ?>
                                <span class="badge badge-info">Completed</span>
                              <?php } else { ?>
                                <span class="badge badge-warning">Not Confirmed yet</span>
                              <?php } ?>
                            </div>

                            <div class="booking-header mb-3">
                              <h4 class="booking-number" style="color:red">Booking No #<?php echo htmlentities($result->BookingNumber); ?></h4>
                              <small class="text-muted">Booked on: <?php echo date('d M Y h:i A', strtotime($result->PostingDate)); ?></small>
                            </div>

                            <div class="row">
                              <div class="col-md-4">
                                <div class="vehicle_img">
                                  <a href="vehical-details.php?vhid=<?php echo htmlentities($result->vid); ?>">
                                    <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1); ?>" alt="image" class="img-fluid">
                                  </a>
                                </div>
                              </div>
                              <div class="col-md-8">
                                <div class="vehicle_title">
                                  <h6>
                                    <a href="vehical-details.php?vhid=<?php echo htmlentities($result->vid); ?>">
                                      <?php echo htmlentities($result->BrandName); ?> , <?php echo htmlentities($result->VehiclesTitle); ?>
                                    </a>
                                  </h6>
                                  <div class="booking-dates">
                                    <p><strong>From:</strong> <?php echo htmlentities($result->FromDate); ?> <strong>To:</strong> <?php echo htmlentities($result->ToDate); ?></p>
                                  </div>
                                  <?php if ($result->message) { ?>
                                    <div class="booking-message">
                                      <p><strong>Message:</strong> <?php echo htmlentities($result->message); ?></p>
                                    </div>
                                  <?php } ?>
                                </div>
                              </div>
                            </div>

                            <div class="invoice-section mt-4">
                              <h5 style="color:blue" class="mb-3">Invoice</h5>
                              <div class="car-invoice">
                                <table class="table table-striped table-bordered">
                                  <tr>
                                    <th colspan="2" style="text-align:center;background-color:#f5f5f5">Booking Details</th>
                                  </tr>
                                  <tr>
                                    <td><strong>Car Rental Per Day</strong></td>
                                    <td>₹<?php echo htmlentities($result->PricePerDay); ?></td>
                                  </tr>
                                  <tr>
                                    <td><strong>Number of Days</strong></td>
                                    <td><?php echo htmlentities($result->totaldays); ?></td>
                                  </tr>
                                  <tr>
                                    <td><strong>Total Rental Amount</strong></td>
                                    <td>₹<?php
                                          $total_rental = $result->PricePerDay * $result->totaldays;
                                          echo htmlentities($total_rental);
                                          ?></td>
                                  </tr>
                                  <?php
                                  // Get token redemption details for this booking
                                  $bookingNumber = $result->BookingNumber;
                                  $sql = "SELECT tokensRedeemed, discountAmount FROM tbltokenredemption WHERE bookingId=:bookingNumber";
                                  $query = $dbh->prepare($sql);
                                  $query->bindParam(':bookingNumber', $bookingNumber, PDO::PARAM_STR);
                                  $query->execute();
                                  $tokenData = $query->fetch(PDO::FETCH_OBJ);

                                  if ($tokenData) { ?>
                                    <tr>
                                      <td>
                                        <strong>Token Discount</strong><br>
                                        <small class="text-muted">(<?php echo htmlentities($tokenData->tokensRedeemed); ?> tokens redeemed)</small>
                                      </td>
                                      <td class="text-success">- ₹<?php echo htmlentities($tokenData->discountAmount); ?></td>
                                    </tr>
                                    <tr class="table-info">
                                      <td><strong>Final Amount After Discount</strong></td>
                                      <td><strong>₹<?php
                                                    $final_amount = $total_rental - $tokenData->discountAmount;
                                                    echo htmlentities($final_amount);
                                                    ?></strong></td>
                                    </tr>
                                  <?php } ?>
                                </table>
                              </div>
                            </div>
                            <hr style="border: 0; height: 2px; background: #ddd; margin: 30px 0; background-color: #c6c6c6;">
                          </div>
                        <?php }
                      } else { ?>
                        <div class="alert alert-info">
                          <h5 class="text-center">No bookings found</h5>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </section>
        <!--/my-vehicles-->
        <?php include('includes/footer.php'); ?>

        <!-- Scripts -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/interface.js"></script>
        <!--Switcher-->
        <script src="assets/switcher/js/switcher.js"></script>
        <!--bootstrap-slider-JS-->
        <script src="assets/js/bootstrap-slider.min.js"></script>
        <!--Slider-JS-->
        <script src="assets/js/slick.min.js"></script>
        <script src="assets/js/owl.carousel.min.js"></script>
  </body>

  </html>
<?php } ?>