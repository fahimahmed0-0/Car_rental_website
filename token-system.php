<?php
include('config.php');

// Function to award tokens after booking completion
function awardBookingTokens($userEmail, $bookingId)
{
    global $dbh;
    $tokensToAward = 5; // 5 tokens per booking

    try {
        // Start transaction
        $dbh->beginTransaction();

        // Check if tokens were already awarded for this booking
        $stmt = $dbh->prepare("SELECT id FROM tbltokenearnings WHERE bookingId = :bookingId");
        $stmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false; // Tokens already awarded
        }

        // Record token earning
        $stmt = $dbh->prepare("INSERT INTO tbltokenearnings (userEmail, bookingId, tokensEarned) VALUES (:userEmail, :bookingId, :tokens)");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->bindParam(':tokens', $tokensToAward, PDO::PARAM_INT);
        $stmt->execute();

        // Update or create customer token balance
        $stmt = $dbh->prepare("INSERT INTO tblcustomertokens (userEmail, tokenBalance, totalEarnedTokens) 
                              VALUES (:userEmail, :tokens, :tokens)
                              ON DUPLICATE KEY UPDATE 
                              tokenBalance = tokenBalance + :tokens,
                              totalEarnedTokens = totalEarnedTokens + :tokens");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':tokens', $tokensToAward, PDO::PARAM_INT);
        $stmt->execute();

        $dbh->commit();
        return true;
    } catch (PDOException $e) {
        $dbh->rollback();
        error_log("Token Award Error: " . $e->getMessage());
        return false;
    }
}

// Function to get customer's token balance
function getTokenBalance($userEmail)
{
    global $dbh;
    try {
        $stmt = $dbh->prepare("SELECT tokenBalance, totalEarnedTokens FROM tblcustomertokens WHERE userEmail = :userEmail");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }
        return array('tokenBalance' => 0, 'totalEarnedTokens' => 0);
    } catch (PDOException $e) {
        error_log("Get Token Balance Error: " . $e->getMessage());
        return false;
    }
}

// Function to redeem tokens
function redeemTokens($userEmail, $tokensToRedeem, $bookingId)
{
    global $dbh;
    $minTokensRequired = 15; // Minimum tokens required for redemption
    $discountPerToken = 20; // ₹20 per token (₹100 for 5 tokens)

    try {
        // Start transaction
        $dbh->beginTransaction();

        // Get current token balance
        $stmt = $dbh->prepare("SELECT tokenBalance FROM tblcustomertokens WHERE userEmail = :userEmail");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->execute();
        $currentBalance = $stmt->fetchColumn();

        // Check minimum token requirement
        if ($tokensToRedeem < $minTokensRequired) {
            return array('success' => false, 'message' => 'Minimum 15 tokens required for redemption');
        }

        // Check if user has enough tokens
        if ($currentBalance < $tokensToRedeem) {
            return array('success' => false, 'message' => 'Insufficient token balance');
        }

        // Calculate discount amount
        $discountAmount = $tokensToRedeem * $discountPerToken;

        // Record redemption
        $stmt = $dbh->prepare("INSERT INTO tbltokenredemption (userEmail, tokensRedeemed, discountAmount, bookingId) 
                              VALUES (:userEmail, :tokens, :discount, :bookingId)");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':tokens', $tokensToRedeem, PDO::PARAM_INT);
        $stmt->bindParam(':discount', $discountAmount, PDO::PARAM_STR);
        $stmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();

        // Update token balance
        $stmt = $dbh->prepare("UPDATE tblcustomertokens 
                              SET tokenBalance = tokenBalance - :tokens 
                              WHERE userEmail = :userEmail");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':tokens', $tokensToRedeem, PDO::PARAM_INT);
        $stmt->execute();

        $dbh->commit();
        return array('success' => true, 'discountAmount' => $discountAmount);
    } catch (PDOException $e) {
        $dbh->rollback();
        error_log("Token Redemption Error: " . $e->getMessage());
        return array('success' => false, 'message' => 'An error occurred during redemption');
    }
}

// Function to check if booking is eligible for token award
function isBookingEligibleForTokens($bookingId)
{
    global $dbh;
    try {
        $stmt = $dbh->prepare("SELECT Status FROM tblbooking WHERE id = :bookingId");
        $stmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetchColumn();

        // Status 2 means booking is completed
        return ($status == 2);
    } catch (PDOException $e) {
        error_log("Booking Eligibility Check Error: " . $e->getMessage());
        return false;
    }
}

// Function to get token redemption history
function getTokenRedemptionHistory($userEmail)
{
    global $dbh;
    try {
        $stmt = $dbh->prepare("SELECT * FROM tbltokenredemption WHERE userEmail = :userEmail ORDER BY redemptionDate DESC");
        $stmt->bindParam(':userEmail', $userEmail, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get Redemption History Error: " . $e->getMessage());
        return false;
    }
}
