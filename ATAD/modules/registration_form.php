<?php
include "includes/connection.php";

// Start session only if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$user_id = $_SESSION['user_id']; // Logged-in user ID
$error_msg = "";

// Sample events
$events = [
    1 => ['event_name'=>'Tech Workshop','venue'=>'IT Hall','dates'=>['2026-01-10'=>['09:00 AM','01:00 PM']]],
    2 => ['event_name'=>'Business Seminar','venue'=>'Business Center','dates'=>['2026-01-12'=>['10:00 AM','02:00 PM']]],
    3 => ['event_name'=>'Art Exhibition','venue'=>'City Gallery','dates'=>['2026-01-15'=>['11:00 AM','03:00 PM']]]
];

// Get selected event info from URL
$event_id   = $_GET['event_id'] ?? '';
$event_date = $_GET['event_date'] ?? '';
$event_time = $_GET['event_time'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $event_id   = $_POST['event_id'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $gender     = $_POST['gender'];
    $address    = trim($_POST['address']);

    // --- Insert registration into tbl_registration with user_id ---
    $stmt = $conn->prepare(
        "INSERT INTO tbl_registration 
        (user_id, events_id, event_date, event_time, full_name, email, gender, address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iissssss", $user_id, $event_id, $event_date, $event_time, $fullname, $email, $gender, $address);

    if ($stmt->execute()) {
        $registration_id = $stmt->insert_id;
        $stmt->close();

        // --- Create initial payment record ---
        $amount = 500; // Fixed amount
        $reference_number = "ATAD-" . date("Y") . "-" . str_pad($registration_id, 6, "0", STR_PAD_LEFT);

        $stmt2 = $conn->prepare(
            "INSERT INTO tbl_payments (registration_id, amount, payment_status, reference_number) 
             VALUES (?, ?, 'Pending', ?)"
        );
        $stmt2->bind_param("ids", $registration_id, $amount, $reference_number);
        $stmt2->execute();
        $stmt2->close();

        // --- Add notification for the user ---
        $stmt3 = $conn->prepare("
            INSERT INTO tbl_notification (user_id, title, message)
            VALUES (?, ?, ?)
        ");
        $title = "Registration Successful";
        $message = "Hi $fullname, you have successfully registered for the event: " . $events[$event_id]['event_name'] . ".";
        $stmt3->bind_param("iss", $user_id, $title, $message);
        $stmt3->execute();
        $stmt3->close();

        // Redirect to payment page
        header("Location: index.php?page=payments&registration_id=$registration_id");
        exit;
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
    }
}
?>

<?php if ($event_id && isset($events[$event_id])): ?>
<div class="alert alert-info">
    <b><?= htmlspecialchars($events[$event_id]['event_name']) ?></b><br>
    Date: <b><?= htmlspecialchars($event_date) ?></b><br>
    Time: <b><?= htmlspecialchars($event_time) ?></b><br>
    Venue: <?= htmlspecialchars($events[$event_id]['venue']) ?>
</div>

 <div class="d-flex justify-content-end mb-3">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>


<?php if ($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id) ?>">
    <input type="hidden" name="event_date" value="<?= htmlspecialchars($event_date) ?>">
    <input type="hidden" name="event_time" value="<?= htmlspecialchars($event_time) ?>">

    <input class="form-control mb-2" name="fullname" placeholder="Full Name" required>
    <input class="form-control mb-2" name="email" type="email" placeholder="Email" required>

    <select class="form-select mb-2" name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>

    <input class="form-control mb-2" name="address" placeholder="Address" required>

    <button class="btn btn-success w-100">Submit & Proceed to Payment</button>
</form>

<?php else: ?>
<div class="alert alert-danger">Invalid event selection.</div>
<?php endif; ?>
