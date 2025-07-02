<?php
function addTokensOnBookingComplete($dbh, $userEmail, $bookingId)
{
    try {
        // Add 5 tokens for completed booking
        $tokens_to_add = 5;

        // Start transaction
        $dbh->beginTransaction();

        // Update user's tokens and total_tokens_earned
        $sql = "UPDATE tblusers SET 
                tokens = tokens + :tokens,
                total_tokens_earned = total_tokens_earned + :tokens 
                WHERE EmailId = :email";
        $query = $dbh->prepare($sql);
        $query->bindParam(':tokens', $tokens_to_add, PDO::PARAM_INT);
        $query->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $query->execute();

        // Commit transaction
        $dbh->commit();
        return true;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $dbh->rollBack();
        error_log("Error adding tokens: " . $e->getMessage());
        return false;
    }
}

function redeemTokens($dbh, $userEmail, $bookingId, $tokensToRedeem)
{
    try {
        // Calculate discount amount (5 tokens = ₹100)
        $discountAmount = ($tokensToRedeem / 5) * 100;

        // Start transaction
        $dbh->beginTransaction();

        // Check if user has enough tokens
        $sql = "SELECT tokens FROM tblusers WHERE EmailId = :email";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $query->execute();
        $currentTokens = $query->fetchColumn();

        if ($currentTokens < $tokensToRedeem) {
            throw new Exception("Insufficient tokens");
        }

        // Deduct tokens from user's account
        $sql = "UPDATE tblusers SET tokens = tokens - :tokens WHERE EmailId = :email";
        $query = $dbh->prepare($sql);
        $query->bindParam(':tokens', $tokensToRedeem, PDO::PARAM_INT);
        $query->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $query->execute();

        // Record redemption
        $sql = "INSERT INTO tblredemptions (userEmail, TokensRedeemed, DiscountAmount, BookingID) 
                VALUES (:email, :tokens, :discount, :booking)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $query->bindParam(':tokens', $tokensToRedeem, PDO::PARAM_INT);
        $query->bindParam(':discount', $discountAmount, PDO::PARAM_STR);
        $query->bindParam(':booking', $bookingId, PDO::PARAM_INT);
        $query->execute();

        // Commit transaction
        $dbh->commit();
        return $discountAmount;
    } catch (Exception $e) {
        // Rollback transaction on error
        $dbh->rollBack();
        error_log("Error redeeming tokens: " . $e->getMessage());
        throw $e;
    }
}

function getUserTokens($dbh, $userEmail)
{
    try {
        $sql = "SELECT tokens FROM tblusers WHERE EmailId = :email";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $query->execute();
        return $query->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting user tokens: " . $e->getMessage());
        return false;
    }
}
