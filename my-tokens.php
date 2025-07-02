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
        <title>Car Rental Portal - My Tokens</title>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="assets/css/style.css" type="text/css">
        <link rel="stylesheet" href="assets/css/owl.carousel.css" type="text/css">
        <link rel="stylesheet" href="assets/css/owl.transitions.css" type="text/css">
        <link href="assets/css/slick.css" rel="stylesheet">
        <link href="assets/css/bootstrap-slider.min.css" rel="stylesheet">
        <link href="assets/css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" id="switcher-css" type="text/css" href="assets/switcher/css/switcher.css" media="all" />
        <link rel="alternate stylesheet" type="text/css" href="assets/switcher/css/red.css" title="red" media="all" data-default-color="true" />
        <link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
        <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
    </head>

    <body>
        <?php include('includes/colorswitcher.php'); ?>
        <?php include('includes/header.php'); ?>
        <section class="page-header profile_page">
            <div class="container">
                <div class="page-header_wrap">
                    <div class="page-heading">
                        <h1>My Tokens</h1>
                    </div>
                    <ul class="coustom-breadcrumb">
                        <li><a href="#">Home</a></li>
                        <li>My Tokens</li>
                    </ul>
                </div>
            </div>
            <div class="dark-overlay"></div>
        </section>
        <?php
        $useremail = $_SESSION['login'];
        $sql = "SELECT * from tblusers where EmailId=:useremail ";
        $query = $dbh->prepare($sql);
        $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        if ($query->rowCount() > 0) {
            foreach ($results as $result) { ?>
                <section class="user_profile inner_pages">
                    <div class="container">
                        <div class="user_profile_info gray-bg padding_4x4_40">
                            <div class="upload_user_logo"> <img src="assets/images/profiledp.png" alt="image"> </div>
                            <div class="dealer_info">
                                <h5><?php echo htmlentities($result->FullName); ?></h5>
                                <p><?php echo htmlentities($result->Address); ?><br>
                                    <?php echo htmlentities($result->City); ?>&nbsp;<?php echo htmlentities($result->Country); ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 col-sm-3">

                                <?php include('includes/sidebar.php'); ?>
                                <div class="col-md-8 col-sm-8">
                                    <div class="profile_wrap">
                                        <h5 class="uppercase underline">My Tokens</h5>
                                        <div class="my_token_summary">
                                            <?php
                                            // Fetch token balance and total earned tokens
                                            $sql = "SELECT tokenBalance, totalEarnedTokens FROM tblcustomertokens WHERE userEmail=:useremail";
                                            $query = $dbh->prepare($sql);
                                            $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
                                            $query->execute();
                                            $tokens = $query->fetch(PDO::FETCH_OBJ);
                                            $current_tokens = $tokens ? $tokens->tokenBalance : 0;
                                            $total_earned = $tokens ? $tokens->totalEarnedTokens : 0;
                                            ?>

                                            <div class="token-stats mb-4">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="card bg-light mb-3">
                                                            <div class="card-body">
                                                                <h6 class="card-subtitle mb-2 text-muted">Current Balance</h6>
                                                                <h4 class="card-title text-primary"><?php echo $current_tokens; ?> Tokens</h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card bg-light mb-3">
                                                            <div class="card-body">
                                                                <h6 class="card-subtitle mb-2 text-muted">Total Earned</h6>
                                                                <h4 class="card-title text-success"><?php echo $total_earned; ?> Tokens</h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="token-rules mb-4">
                                                <h5 class="text-dark mb-3">Token Rules</h5>
                                                <ul class="list-unstyled">
                                                    <li class="mb-2"><i class="fa fa-circle text-primary mr-2"></i> Earn 5 tokens for each completed booking</li>
                                                    <li class="mb-2"><i class="fa fa-circle text-primary mr-2"></i> Redeem 5 tokens for ₹100 discount</li>
                                                    <li class="mb-2"><i class="fa fa-circle text-primary mr-2"></i> Minimum 15 tokens required for redemption</li>
                                                </ul>
                                            </div>

                                            <hr class="my-4">

                                            <div class="redemption-history">
                                                <h5 class="text-dark mb-3">Redemption History</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Tokens Redeemed</th>
                                                                <th>Discount Amount</th>
                                                                <th>Booking ID</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $sql = "SELECT * FROM tbltokenredemption WHERE userEmail=:useremail ORDER BY redemptionDate DESC";
                                                            $query = $dbh->prepare($sql);
                                                            $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
                                                            $query->execute();
                                                            $history = $query->fetchAll(PDO::FETCH_OBJ);
                                                            if ($query->rowCount() > 0) {
                                                                foreach ($history as $row) {
                                                            ?>
                                                                    <tr>
                                                                        <td><?php echo htmlentities(date('d M Y', strtotime($row->redemptionDate))); ?></td>
                                                                        <td><?php echo htmlentities($row->tokensRedeemed); ?></td>
                                                                        <td>₹<?php echo htmlentities($row->discountAmount); ?></td>
                                                                        <td>#<?php echo htmlentities($row->bookingId); ?></td>
                                                                    </tr>
                                                                <?php }
                                                            } else { ?>
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted">No redemption history found</td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </section>
                </div>

        <?php }
        } ?>
        <?php include('includes/footer.php'); ?>
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/interface.js"></script>
        <script src="assets/switcher/js/switcher.js"></script>
        <script src="assets/js/bootstrap-slider.min.js"></script>
        <script src="assets/js/slick.min.js"></script>
        <script src="assets/js/owl.carousel.min.js"></script>
    </body>

    </html>
<?php } ?>