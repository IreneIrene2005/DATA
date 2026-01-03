<?php
include __DIR__ . "/../includes/connection.php";

$error = "";
$success = "";

/* -------------------------------------------------
   AUTO CREATE 10 USERS (ONLY ONCE)
-------------------------------------------------- */
$check = $conn->query("SELECT COUNT(*) AS total FROM tbl_users");
$row = $check->fetch_assoc();

if ($row['total'] == 0) {
    $users = [
        ['Alice', 'alice@gmail.com', '123'],
        ['Bob', 'bob@gmail.com', '123'],
        ['Charlie', 'charlie@gmail.com', '123'],
        ['David', 'david@gmail.com', '123'],
        ['Eve', 'eve@gmail.com', '123'],
        ['Frank', 'frank@gmail.com', '123'],
        ['Grace', 'grace@gmail.com', '123'],
        ['Heidi', 'heidi@gmail.com', '123'],
        ['Ivan', 'ivan@gmail.com', '123'],
        ['Judy', 'judy@gmail.com', '123']
    ];

    $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, email, password) VALUES (?, ?, ?)");
    foreach ($users as $u) {
        $stmt->bind_param("sss", $u[0], $u[1], $u[2]);
        $stmt->execute();
    }
    $stmt->close();
}

/* -------------------------------------------------
   LOGIN LOGIC
-------------------------------------------------- */
if (isset($_POST['btn-login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT user_id, full_name FROM tbl_users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];

        header("Location: index.php?page=events");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}

/* -------------------------------------------------
   REGISTER LOGIC
-------------------------------------------------- */
if (isset($_POST['btn-register'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $password);
            if ($stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Failed to create account: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login / Register - NiceAdmin</title>
<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
/* Simple toggle form */
#registerForm { display: none; }
.toggle-link { cursor: pointer; color: blue; text-decoration: underline; }
</style>
</head>
<body>
<main>
<div class="container">
<section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-6 col-md-8 d-flex flex-column align-items-center justify-content-center">

<div class="d-flex justify-content-center py-4">
<a href="index.php" class="logo d-flex align-items-center w-auto">
<img src="assets/img/logo.png" alt="">
<span class="d-none d-lg-block">NiceAdmin</span>
</a>
</div>

<div class="card mb-3">
<div class="card-body">

<div class="pt-4 pb-2 text-center">
<h5 class="card-title pb-0 fs-4">Login</h5>
</div>

<?php if ($error): ?>
<div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- LOGIN FORM -->
<form method="POST" class="row g-3 mb-3">
<div class="col-12">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>
<div class="col-12">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" required>
</div>
<div class="col-12">
<button class="btn btn-primary w-100" type="submit" name="btn-login">Login</button>
</div>
</form>

<p class="text-center mt-2">Don't have an account yet? <span class="toggle-link" onclick="showRegister()">Create account</span></p>

<!-- REGISTER FORM -->
<form method="POST" class="row g-3" id="registerForm">
<div class="col-12">
<label class="form-label">Full Name</label>
<input type="text" name="full_name" class="form-control" required>
</div>
<div class="col-12">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>
<div class="col-12">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" required>
</div>
<div class="col-12">
<label class="form-label">Confirm Password</label>
<input type="password" name="confirm_password" class="form-control" required>
</div>
<div class="col-12">
<button class="btn btn-success w-100" type="submit" name="btn-register">Create Account</button>
</div>
</form>

</div>
</div>

<div class="credits text-center">
Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
</div>

</div>
</div>
</div>
</section>
</div>
</main>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function showRegister() {
    document.getElementById('registerForm').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</body>
</html>
