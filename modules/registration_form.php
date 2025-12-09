<?php
include "includes/connection.php";
if (isset($_POST['btn-submit'])) {

$fullname = $_POST['registration_fullname'];
$email = $_POST['registration_email'];
$password = $_POST['registration_password'];

$sql = "INSERT INTO tbl_registration (full_name, email, password)
VALUES ('$fullname', '$email', '$password')";

if ($conn->query($sql) === TRUE) {
      echo '<script>alert("new form added");</script>';
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
} 


  
}
$conn->close();
?>
 
 
 <div class="col-lg-6">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Registration Form</h5>

              
              <form class="row g-3" method="POST" action="index.php?page=registration_form">
                <div class="col-12">
                  <label for="inputNanme4" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="inputNanme4" name="registration_fullname">
                </div>
                <div class="col-12">
                  <label for="inputEmail4" class="form-label">Email</label>
                  <input type="email" class="form-control" id="inputEmail4" name="registration_email">
                </div>
                <div class="col-12">
                  <label for="inputPassword4" class="form-label">Password</label>
                  <input type="password" class="form-control" id="inputPassword4" name="registration_password">
                </div>
                <div class="text-center">
                  <button type="submit" class="btn btn-primary" name="btn-submit">Submit</button>
                </div>
              </form>

            </div>
          </div>