<?php
include "includes/connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: index.php?page=login");
    exit;
}

$reference_number = trim($_GET['reference_number'] ?? '');
$schedule = null;
$error_msg = '';

if ($reference_number !== '') {

    // Use JOIN to get registration + payment + event info, only for logged-in user
    $stmt = $conn->prepare("
        SELECT
            r.registration_id,
            r.full_name,
            r.gender,
            r.email,
            r.address,
            r.events_id,
            r.event_date,
            r.event_time,
            e.event_name,
            e.venue,
            p.payment_status,
            p.reference_number
        FROM tbl_registration r
        JOIN tbl_payments p ON r.registration_id = p.registration_id
        LEFT JOIN tbl_events e ON r.events_id = e.events_id
        WHERE TRIM(p.reference_number) = ? AND r.user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("si", $reference_number, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $schedule = $res->fetch_assoc();
    $stmt->close();

    if (!$schedule) {
        $error_msg = "No record found for this reference number or it does not belong to you.";
    }
}
?>

<div class="card mx-auto" style="max-width:700px;margin-top:40px;">
    <div class="card-body">

        <!-- LOGOUT BUTTON -->
        <div class="d-flex justify-content-end mb-3">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>

        <h4 class="card-title mb-3">Check Your Schedule</h4>

        <!-- Search form -->
        <form method="GET" action="index.php" class="mb-3">
            <input type="hidden" name="page" value="schedule">
            <input type="text" name="reference_number" class="form-control" placeholder="Enter your Reference Number" required
                   value="<?= htmlspecialchars($reference_number) ?>">
            <button type="submit" class="btn btn-primary mt-2 w-100">Search</button>
        </form>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <?php if ($schedule): ?>
            <h5>Schedule Details</h5>
            <div class="mb-3 p-2 border rounded">
                <p><strong>Name:</strong> <?= htmlspecialchars($schedule['full_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($schedule['email']) ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($schedule['gender']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($schedule['address']) ?></p>
                <p><strong>Event:</strong> <?= htmlspecialchars($schedule['event_name'] ?? 'Event not found') ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($schedule['event_date']) ?></p>
                <p><strong>Time:</strong> <?= htmlspecialchars($schedule['event_time']) ?></p>
                <p><strong>Venue:</strong> <?= htmlspecialchars($schedule['venue'] ?? 'Venue not found') ?></p>
                <p><strong>Payment Status:</strong> 
                    <span class="badge <?= $schedule['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                        <?= htmlspecialchars($schedule['payment_status']) ?>
                    </span>
                </p>
                <p><strong>Reference Number:</strong> <?= htmlspecialchars($schedule['reference_number']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
