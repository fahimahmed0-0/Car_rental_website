<?php
if (isset($_POST['submit'])) {
    $userEmail = $_SESSION['login'];
    $vehicleid = $_GET['vid'];
    $fromdate = $_POST['fromdate'];
    $todate = $_POST['todate'];
    $message = $_POST['message'];
    $usertokens = isset($_POST['redeem_tokens']) ? intval($_POST['redeem_tokens']) : 0;
    $status = 0;
    $bookingno = mt_rand(100000000, 999999999);

    // Check if user wants to redeem tokens
    $discountAmount = 0;
    if ($usertokens > 0) {
        $result = redeemTokens($userEmail, $usertokens, $bookingno);
        if ($result['success']) {
            $discountAmount = $result['discountAmount'];
        } else {
            $error = $result['message'];
        }
    }

    if (!isset($error)) {
        $sql = "INSERT INTO tblbooking(BookingNumber,userEmail,VehicleId,FromDate,ToDate,message,Status) VALUES(:bookingno,:userEmail,:vehicleid,:fromdate,:todate,:message,:status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookingno', $bookingno, PDO::PARAM_STR);
        $query->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $query->bindParam(':vehicleid', $vehicleid, PDO::PARAM_STR);
        $query->bindParam(':fromdate', $fromdate, PDO::PARAM_STR);
        $query->bindParam(':todate', $todate, PDO::PARAM_STR);
        $query->bindParam(':message', $message, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();
        $lastInsertId = $dbh->lastInsertId();
        if ($lastInsertId) {
            echo "<script>alert('Booking successful.');</script>";
            echo "<script type='text/javascript'> document.location = 'my-booking.php'; </script>";
        } else {
            echo "<script>alert('Something went wrong. Please try again');</script>";
        }
    } else {
        echo "<script>alert('" . $error . "');</script>";
    }
}
?>

<div class="form-group">
    <h5>Token Redemption</h5>
    <?php
    $tokenInfo = getTokenBalance($_SESSION['login']);
    if ($tokenInfo['tokenBalance'] >= 15) {
    ?>
        <div class="token-redemption">
            <p>Your current token balance: <?php echo $tokenInfo['tokenBalance']; ?> tokens</p>
            <p>• 5 tokens = ₹100 discount</p>
            <p>• Minimum redemption: 15 tokens</p>
            <select name="redeem_tokens" class="form-control">
                <option value="0">Don't use tokens</option>
                <?php
                $maxRedeemable = floor($tokenInfo['tokenBalance'] / 5) * 5;
                for ($i = 15; $i <= $maxRedeemable; $i += 5) {
                    echo "<option value=\"$i\">Redeem $i tokens for ₹" . ($i * 20) . " discount</option>";
                }
                ?>
            </select>
        </div>
    <?php } else { ?>
        <p>You need minimum 15 tokens to get a discount. Current balance: <?php echo $tokenInfo['tokenBalance']; ?> tokens</p>
    <?php } ?>
</div>