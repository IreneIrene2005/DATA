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

$error_msg = "";
$success_msg = "";

// Static events info
$events = [
    1 => ['event_name' => 'Tech Workshop', 'venue' => 'IT Hall'],
    2 => ['event_name' => 'Business Seminar', 'venue' => 'Business Center'],
    3 => ['event_name' => 'Art Exhibition', 'venue' => 'City Gallery']
];

// ------------------- FUNCTIONS -------------------

// Fetch a single registration with payment info
function fetch_registration($conn, $registration_id, $user_id) {
    $stmt = $conn->prepare("
        SELECT r.registration_id, r.full_name, r.gender, r.email, r.address,
               r.events_id, r.event_date, r.event_time,
               p.payment_id, p.amount, p.payment_status, p.payment_method, p.reference_number
        FROM tbl_registration r
        LEFT JOIN tbl_payments p ON p.registration_id = r.registration_id
        WHERE r.registration_id = ? AND r.user_id = ?
    ");
    $stmt->bind_param("ii", $registration_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    return $data;
}

// Fetch all registrations for the user
function fetch_all_registrations($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT r.registration_id, r.full_name, r.events_id, r.event_date, r.event_time,
               p.payment_status, p.amount, p.reference_number
        FROM tbl_registration r
        LEFT JOIN tbl_payments p ON p.registration_id = r.registration_id
        WHERE r.user_id = ?
        ORDER BY r.registration_id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $res;
}

// ------------------- HANDLE PAYMENT -------------------

$registration_id = $_GET['registration_id'] ?? 0;
$data = $registration_id ? fetch_registration($conn, $registration_id, $user_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_submit']) && $data) {
    $card_number = trim($_POST['card_number'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (empty($card_number) || empty($payment_method)) {
        $error_msg = "Please complete payment details.";
    } else {
        $reference_number = "ATAD-" . date("Y") . "-" . str_pad($data['payment_id'] ?? 0, 6, "0", STR_PAD_LEFT);

        if ($data['payment_id']) {
            $stmt = $conn->prepare("
                UPDATE tbl_payments 
                SET payment_status='Paid', payment_method=?, reference_number=?
                WHERE payment_id=?
            ");
            $stmt->bind_param("ssi", $payment_method, $reference_number, $data['payment_id']);
            if ($stmt->execute()) {
                $success_msg = "Payment successful!";

                // --- CREATE NOTIFICATION ---
                $note_title = "Payment Successful";
                $note_message = "You have successfully paid for " . ($events[$data['events_id']]['event_name'] ?? 'your event') . ".";
                $stmt_note = $conn->prepare("
                    INSERT INTO tbl_notification (user_id, title, message)
                    VALUES (?, ?, ?)
                ");
                $stmt_note->bind_param("iss", $user_id, $note_title, $note_message);
                $stmt_note->execute();
                $stmt_note->close();
            } else {
                $error_msg = "Payment failed: " . $stmt->error;
            }
            $stmt->close();
        }

        // Refresh registration data
        $data = fetch_registration($conn, $registration_id, $user_id);
    }
}

// Fetch all registrations for this user
$registrations = fetch_all_registrations($conn, $user_id);
?>

<div class="container mt-4">
    <h3>Your Payments</h3>

     <div class="d-flex justify-content-end mb-3">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>


    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <?php if ($registrations): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reference No</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($events[$r['events_id']]['event_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['event_date']) ?></td>
                        <td><?= htmlspecialchars($r['event_time']) ?></td>
                        <td>₱<?= number_format($r['amount'] ?? 0, 2) ?></td>
                        <td>
                            <?php if (($r['payment_status'] ?? '') === 'Paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['reference_number'] ?? '-') ?></td>
                        <td>
                            <?php if (($r['payment_status'] ?? '') !== 'Paid'): ?>
                                <a href="index.php?page=payments&registration_id=<?= $r['registration_id'] ?>" class="btn btn-sm btn-primary">Pay Now</a>
                            <?php else: ?>
                                <span class="text-success">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No payment records yet. Please register for an event first.</p>
    <?php endif; ?>

    <?php if ($data && ($data['payment_status'] ?? '') !== 'Paid'): ?>
        <hr>
        <h5>Make Payment for <?= htmlspecialchars($events[$data['events_id']]['event_name'] ?? '') ?></h5>
        <form method="POST">
            <div class="mb-2">
                <label>Card Number</label>
                <input type="text" name="card_number" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Payment Method</label>
                <select name="payment_method" class="form-select" required>
                    <option value="">Select</option>
                    <option>GCash</option>
                    <option>Credit Card</option>
                    <option>PayPal</option>
                </select>
            </div>
            <button name="pay_submit" class="btn btn-success">Submit Payment</button>
        </form>
    <?php endif; ?>
</div>
