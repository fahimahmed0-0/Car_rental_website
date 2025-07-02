<?php
if (isset($_POST['login'])) {
  $login = $_POST['login_id']; // This can be either email or phone
  $password = md5($_POST['password']);
  $sql = "SELECT EmailId,Password,FullName FROM tblusers WHERE (EmailId=:login OR ContactNo=:login) and Password=:password";
  $query = $dbh->prepare($sql);
  $query->bindParam(':login', $login, PDO::PARAM_STR);
  $query->bindParam(':password', $password, PDO::PARAM_STR);
  $query->execute();
  $results = $query->fetchAll(PDO::FETCH_OBJ);

  if ($query->rowCount() > 0) {
    $_SESSION['login'] = $results[0]->EmailId;
    $_SESSION['fname'] = $results[0]->FullName;
    $currentpage = $_SERVER['REQUEST_URI'];
    echo "<script type='text/javascript'> document.location = '$currentpage'; </script>";
  } else {
    echo "<script>alert('Invalid Details');</script>";
  }
}
?>

<!-- Login Modal -->
<div class="modal fade" id="loginform" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content p-3">

      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form method="post">
          <div class="form-group">
            <input type="text" class="form-control" name="login_id" placeholder="Email or Phone Number *" required>
          </div>
          <div class="form-group">
            <input type="password" class="form-control" name="password" placeholder="Password *" required>
          </div>
          <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
          </div>
          <div class="form-group">
            <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
          </div>
        </form>
      </div>

      <div class="modal-footer d-block text-center">
        <p class="mb-1">Don't have an account? <a href="#signupform" data-toggle="modal" data-dismiss="modal">Signup Here</a></p>
        <p><a href="#forgotpassword" data-toggle="modal" data-dismiss="modal">Forgot Password?</a></p>
      </div>

    </div>
  </div>
</div>