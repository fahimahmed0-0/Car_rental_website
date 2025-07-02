<?php
session_start();
include('includes/config.php');
require('vendor/autoload.php'); // Our custom autoloader

// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys
\Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY');

if (isset($_POST['stripeToken'])) {
    $token = $_POST['stripeToken'];
    $bookingno = $_SESSION['bookingno'];
    $amount = $_SESSION['amount']; // Amount in cents

    try {
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'inr',
            'description' => 'Car Booking Payment for booking ' . $bookingno,
            'source' => $token,
        ]);

        // Payment successful, update booking status
        $sql = "UPDATE tblbooking SET Status=1, payment_status='Paid', transaction_id=:transaction_id WHERE BookingNumber=:bookingno";
        $query = $dbh->prepare($sql);
        $query->bindParam(':transaction_id', $charge->id, PDO::PARAM_STR);
        $query->bindParam(':bookingno', $bookingno, PDO::PARAM_STR);
        $query->execute();

        echo "<script>alert('Payment successful.');</script>";
        echo "<script type='text/javascript'> document.location = 'my-booking.php'; </script>";
    } catch (\Stripe\Exception\CardException $e) {
        // Since it's a decline, \Stripe\Exception\CardException will be caught
        echo "<script>alert('Payment failed: " . $e->getError()->message . "');</script>";
        echo "<script type='text/javascript'> document.location = 'my-booking.php'; </script>";
    } catch (\Stripe\Exception\RateLimitException $e) {
        // Too many requests made to the API too quickly
        echo "<script>alert('Payment failed due to rate limiting. Please try again later.');</script>";
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Invalid parameters were supplied to Stripe's API
        echo "<script>alert('Payment failed due to an invalid request. Please contact support.');</script>";
    } catch (\Stripe\Exception\AuthenticationException $e) {
        // Authentication with Stripe's API failed
        echo "<script>alert('Payment failed due to an authentication error. Please contact support.');</script>";
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        // Network communication with Stripe failed
        echo "<script>alert('Payment failed due to a network error. Please try again later.');</script>";
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Display a very generic error to the user, and maybe send
        // yourself an email
        echo "<script>alert('Something went wrong. Please try again');</script>";
    } catch (Exception $e) {
        // Something else happened, completely unrelated to Stripe
        echo "<script>alert('An unexpected error occurred. Please try again');</script>";
    }
}

if (!isset($_GET['bookingno'])) {
    header('Location: index.php');
    exit;
}

$bookingno = $_GET['bookingno'];
$_SESSION['bookingno'] = $bookingno;

// Fetch booking details to calculate the amount
$sql = "SELECT tblvehicles.PricePerDay, DATEDIFF(tblbooking.ToDate, tblbooking.FromDate) as totaldays 
        FROM tblbooking 
        JOIN tblvehicles ON tblbooking.VehicleId = tblvehicles.id 
        WHERE tblbooking.BookingNumber = :bookingno";
$query = $dbh->prepare($sql);
$query->bindParam(':bookingno', $bookingno, PDO::PARAM_STR);
$query->execute();
$bookingDetails = $query->fetch(PDO::FETCH_OBJ);

if (!$bookingDetails) {
    echo "Invalid booking number.";
    exit;
}

$totaldays = $bookingDetails->totaldays > 0 ? $bookingDetails->totaldays : 1;
$pricePerDay = $bookingDetails->PricePerDay;
$totalAmount = $totaldays * $pricePerDay;

// Check for token discount
$sql = "SELECT discountAmount FROM tbltokenredemption WHERE bookingId = :bookingno";
$query = $dbh->prepare($sql);
$query->bindParam(':bookingno', $bookingno, PDO::PARAM_STR);
$query->execute();
$discountResult = $query->fetch(PDO::FETCH_OBJ);
if ($discountResult) {
    $totalAmount -= $discountResult->discountAmount;
}

$_SESSION['amount'] = $totalAmount * 100; // Stripe expects amount in cents
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <title>Car Rental | Payment</title>
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
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .payment-method.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }

        .payment-method img {
            height: 30px;
            margin-right: 10px;
        }

        .payment-section {
            display: none;
        }

        .payment-section.active {
            display: block;
        }

        .upi-input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            width: 100%;
            margin: 10px 0;
        }

        .upi-input:focus {
            border-color: #007bff;
            outline: none;
        }

        .payment-amount {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-amount h2 {
            margin: 0;
            font-size: 2.5em;
        }

        .payment-amount p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <section class="user_profile inner_pages">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-sm-10 col-md-offset-2 col-sm-offset-1">
                    <div class="payment-form">
                        <div class="payment-amount">
                            <h2>₹<?php echo number_format($totalAmount, 2); ?></h2>
                            <p>Total Amount to Pay</p>
                        </div>

                        <h3><i class="fa fa-credit-card"></i> Choose Payment Method</h3>

                        <!-- Payment Method Selection -->
                        <div class="payment-methods">
                            <div class="payment-method" data-method="upi">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI">
                                <strong>UPI Payment</strong> (GPay, PhonePe, Paytm, Bharat Pay)
                            </div>

                            <div class="payment-method" data-method="card">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Card">
                                <strong>Credit/Debit Card</strong> (Visa, MasterCard, RuPay)
                            </div>

                            <div class="payment-method" data-method="netbanking">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/NetBanking_logo.svg" alt="Net Banking">
                                <strong>Net Banking</strong> (All Major Banks)
                            </div>
                        </div>

                        <!-- UPI Payment Section -->
                        <div id="upi-section" class="payment-section">
                            <h4><i class="fa fa-mobile"></i> UPI Payment</h4>
                            <p>Pay using any UPI app like GPay, PhonePe, Paytm, or Bharat Pay</p>

                            <form action="payment.php" method="post" id="upi-form">
                                <div class="form-group">
                                    <label for="upi-id">UPI ID (e.g., username@bank)</label>
                                    <input type="text" id="upi-id" name="upi_id" class="upi-input"
                                        placeholder="Enter your UPI ID" required>
                                </div>

                                <div class="form-group">
                                    <label for="mobile">Mobile Number</label>
                                    <input type="tel" id="mobile" name="mobile" class="upi-input"
                                        placeholder="Enter your mobile number" required>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="fa fa-mobile"></i> Pay ₹<?php echo number_format($totalAmount, 2); ?> via UPI
                                </button>
                            </form>
                        </div>

                        <!-- Card Payment Section -->
                        <div id="card-section" class="payment-section">
                            <h4><i class="fa fa-credit-card"></i> Card Payment</h4>
                            <form action="payment.php" method="post" id="card-form">
                                <div class="form-row">
                                    <label for="card-element">
                                        Credit or debit card details
                                    </label>
                                    <div id="card-element" class="upi-input">
                                        <!-- A Stripe Element will be inserted here. -->
                                    </div>
                                    <!-- Used to display form errors. -->
                                    <div id="card-errors" role="alert" style="color: #dc3545; margin-top: 10px;"></div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fa fa-credit-card"></i> Pay ₹<?php echo number_format($totalAmount, 2); ?> via Card
                                </button>
                            </form>
                        </div>

                        <!-- Net Banking Section -->
                        <div id="netbanking-section" class="payment-section">
                            <h4><i class="fa fa-university"></i> Net Banking</h4>
                            <p>Select your bank to proceed with payment</p>

                            <form action="payment.php" method="post" id="netbanking-form">
                                <div class="form-group">
                                    <label for="bank-select">Select Your Bank</label>
                                    <select id="bank-select" name="bank" class="upi-input" required>
                                        <option value="">Choose your bank</option>
                                        <option value="sbi">State Bank of India</option>
                                        <option value="hdfc">HDFC Bank</option>
                                        <option value="icici">ICICI Bank</option>
                                        <option value="axis">Axis Bank</option>
                                        <option value="kotak">Kotak Mahindra Bank</option>
                                        <option value="yes">Yes Bank</option>
                                        <option value="pnb">Punjab National Bank</option>
                                        <option value="canara">Canara Bank</option>
                                        <option value="union">Union Bank of India</option>
                                        <option value="bankofbaroda">Bank of Baroda</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-info btn-lg btn-block">
                                    <i class="fa fa-university"></i> Pay ₹<?php echo number_format($totalAmount, 2); ?> via Net Banking
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('includes/footer.php'); ?>

    <script>
        var stripe = Stripe('YOUR_STRIPE_PUBLISHABLE_KEY');
        var elements = stripe.elements();
        var style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
        var card = elements.create('card', {
            style: style
        });
        card.mount('#card-element');

        card.addEventListener('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(function(method) {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(function(m) {
                    m.classList.remove('selected');
                });

                // Add selected class to clicked method
                this.classList.add('selected');

                // Hide all payment sections
                document.querySelectorAll('.payment-section').forEach(function(section) {
                    section.classList.remove('active');
                });

                // Show selected payment section
                var methodType = this.getAttribute('data-method');
                document.getElementById(methodType + '-section').classList.add('active');
            });
        });

        // Card form submission
        var cardForm = document.getElementById('card-form');
        cardForm.addEventListener('submit', function(event) {
            event.preventDefault();

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    stripeTokenHandler(result.token);
                }
            });
        });

        function stripeTokenHandler(token) {
            var form = document.getElementById('card-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);
            form.submit();
        }

        // UPI form submission
        document.getElementById('upi-form').addEventListener('submit', function(event) {
            event.preventDefault();
            var upiId = document.getElementById('upi-id').value;
            var mobile = document.getElementById('mobile').value;

            if (!upiId || !mobile) {
                alert('Please fill in all required fields');
                return;
            }

            // For demo purposes, show success message
            // In production, you would integrate with actual UPI payment gateway
            alert('UPI payment initiated! Please complete the payment in your UPI app.');

            // Simulate successful payment
            setTimeout(function() {
                alert('Payment successful! Redirecting to bookings page...');
                window.location.href = 'my-booking.php';
            }, 2000);
        });

        // Net Banking form submission
        document.getElementById('netbanking-form').addEventListener('submit', function(event) {
            event.preventDefault();
            var bank = document.getElementById('bank-select').value;

            if (!bank) {
                alert('Please select a bank');
                return;
            }

            // For demo purposes, show success message
            // In production, you would redirect to bank's payment gateway
            alert('Redirecting to ' + bank.toUpperCase() + ' Net Banking...');

            // Simulate successful payment
            setTimeout(function() {
                alert('Payment successful! Redirecting to bookings page...');
                window.location.href = 'my-booking.php';
            }, 2000);
        });

        // Auto-select UPI as default
        document.querySelector('[data-method="upi"]').click();
    </script>

</body>

</html>