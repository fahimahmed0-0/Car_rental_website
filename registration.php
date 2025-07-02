<?php
if (isset($_POST['signup'])) {
  $fname = $_POST['fullname'];
  $email = $_POST['emailid'];
  $mobile = $_POST['mobileno'];
  $password = md5($_POST['password']);
  $sql = "INSERT INTO tblusers(FullName,EmailId,ContactNo,Password) VALUES(:fname,:email,:mobile,:password)";
  $query = $dbh->prepare($sql);
  $query->bindParam(':fname', $fname, PDO::PARAM_STR);
  $query->bindParam(':email', $email, PDO::PARAM_STR);
  $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
  $query->bindParam(':password', $password, PDO::PARAM_STR);
  $query->execute();
  $lastInsertId = $dbh->lastInsertId();
  if ($lastInsertId) {
    echo "<script>alert('Registration successful. Now you can login');</script>";
  } else {
    echo "<script>alert('Something went wrong. Please try again');</script>";
  }
}
?>

<script>
function checkAvailability() {
  $("#loaderIcon").show();
  jQuery.ajax({
    url: "check_availability.php",
    data: 'emailid=' + $("#emailid").val(),
    type: "POST",
    success: function(data) {
      $("#user-availability-status").html(data);
      $("#loaderIcon").hide();
    },
    error: function() {}
  });
}
</script>

<!-- Signup Modal -->
<div class="modal fade" id="signupform" tabindex="-1" role="dialog" aria-labelledby="signupformLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
    <div class="modal-content p-3">

      <div class="modal-header">
        <h5 class="modal-title">Sign Up</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form method="post" name="signup">
          <div class="form-group">
            <input type="text" class="form-control" name="fullname" placeholder="Full Name" required>
          </div>
          <div class="form-group">
            <input type="text" class="form-control" name="mobileno" placeholder="Mobile Number" maxlength="10" required>
          </div>
          <div class="form-group">
            <input type="email" class="form-control" name="emailid" id="emailid" onBlur="checkAvailability()" placeholder="Email Address" required>
            <span id="user-availability-status" style="font-size:12px;"></span>
          </div>
          <div class="form-group">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
          </div>
          <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="terms_agree" required checked>
            <label class="form-check-label" for="terms_agree">
              I agree with <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a>
            </label>
          </div>
          <div class="form-group">
            <button type="submit" name="signup" class="btn btn-primary btn-block">Sign Up</button>
          </div>
        </form>
      </div>

      <div class="modal-footer text-center d-block">
        <p class="mb-0">Already have an account? <a href="#loginform" data-toggle="modal" data-dismiss="modal">Login Here</a></p>
      </div>

    </div>
  </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
        <p>By creating an account, you agree to our terms and conditions including responsible usage, data privacy, and adherence to applicable laws.</p>
        <p>Your personal data will be handled securely in accordance with our privacy policy. You may not use the service for illegal or harmful purposes.</p>
        <p>Any violation of these terms may result in termination of your account.</p>
        <!-- <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin sed arcu vel neque cursus feugiat. Curabitur vitae nisl eget sapien accumsan dignissim.</p> -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">I Agree</button>
      </div>

    </div>
  </div>
</div>
