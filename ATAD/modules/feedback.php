<?php
include "includes/connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// MUST BE LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

/* =================================
   DELETE FEEDBACK (USER ONLY)
================================= */
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Verify ownership
    $stmt = $conn->prepare("
        SELECT feedback_id 
        FROM tbl_feedback 
        WHERE feedback_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("DELETE FROM tbl_feedback WHERE feedback_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php?page=feedback");
        exit;
    } else {
        $error_msg = "You are not allowed to delete this feedback.";
    }
}

/* =================================
   FETCH ALL UNIQUE PAID EVENTS
   (No duplicates in dropdown)
================================= */
$stmt = $conn->prepare("
    SELECT DISTINCT e.events_id, e.event_name
    FROM tbl_registration r
    JOIN tbl_events e ON r.events_id = e.events_id
    JOIN tbl_payments p ON r.registration_id = p.registration_id
    WHERE r.user_id = ? 
      AND p.payment_status = 'Paid'
    ORDER BY e.event_name ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =================================
   SUBMIT FEEDBACK
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)$_POST['event_id'];
    $rating = (int)$_POST['rating'];
    $feedback_text = trim($_POST['feedback_text']);

    if ($event_id <= 0 || $rating < 1 || $rating > 5 || empty($feedback_text)) {
        $error_msg = "Please complete all fields correctly.";
    } else {
        // Check if user has paid for this event
        $stmt = $conn->prepare("
            SELECT r.registration_id
            FROM tbl_registration r
            JOIN tbl_payments p ON r.registration_id = p.registration_id
            WHERE r.user_id = ? AND r.events_id = ? AND p.payment_status = 'Paid'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
        $reg = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reg) {
            $error_msg = "Invalid event selection.";
        } else {
            $registration_id = $reg['registration_id'];

            // Insert feedback
            $stmt = $conn->prepare("
                INSERT INTO tbl_feedback 
                (user_id, registration_id, event_id, feedback_text, rating, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiisi", $user_id, $registration_id, $event_id, $feedback_text, $rating);

            if ($stmt->execute()) {
                $stmt->close();

                // --- CREATE NOTIFICATION ---
                $note_title = "Feedback Submitted";
                $note_message = "You have successfully submitted feedback for your event.";
                $stmt_note = $conn->prepare("
                    INSERT INTO tbl_notification (user_id, title, message)
                    VALUES (?, ?, ?)
                ");
                $stmt_note->bind_param("iss", $user_id, $note_title, $note_message);
                $stmt_note->execute();
                $stmt_note->close();

                $success_msg = "Feedback submitted successfully.";
            } else {
                $error_msg = "Failed to submit feedback.";
                $stmt->close();
            }
        }
    }
}

/* =================================
   FETCH USER FEEDBACKS WITH NAME
================================= */
$stmt = $conn->prepare("
    SELECT f.feedback_id, f.feedback_text, f.rating, f.created_at,
           e.event_name, u.full_name
    FROM tbl_feedback f
    JOIN tbl_events e ON f.event_id = e.events_id
    JOIN tbl_users u ON f.user_id = u.user_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h4 class="mb-3">Your Feedback</h4>

<div class="d-flex justify-content-end mb-3">
    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</div>

<?php if ($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<?php if ($success_msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<?php if ($events): ?>
<form method="POST" class="mb-4">
    <label class="fw-bold">Select Event</label>
    <select name="event_id" class="form-select mb-2" required>
        <option value="">-- Choose Event --</option>
        <?php foreach ($events as $e): ?>
            <option value="<?= $e['events_id'] ?>"><?= htmlspecialchars($e['event_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label class="fw-bold">Rating (1–5)</label>
    <input type="number" name="rating" min="1" max="5" class="form-control mb-2" required>

    <label class="fw-bold">Your Feedback</label>
    <textarea name="feedback_text" class="form-control mb-3" rows="3" required></textarea>

    <button class="btn btn-success w-100">Submit Feedback</button>
</form>
<?php else: ?>
<div class="alert alert-info">You have no paid events yet.</div>
<?php endif; ?>

<h5>Your Submitted Feedback</h5>

<?php if ($feedbacks): ?>
<ul class="list-group">
<?php foreach ($feedbacks as $f): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <strong><?= htmlspecialchars($f['event_name']) ?></strong><br>
            <strong>User:</strong> <?= htmlspecialchars($f['full_name']) ?><br>
            Rating: <?= $f['rating'] ?><br>
            <?= htmlspecialchars($f['feedback_text']) ?><br>
            <small><?= $f['created_at'] ?></small>
        </div>
        <a href="index.php?page=feedback&delete_id=<?= $f['feedback_id'] ?>"
           class="btn btn-sm btn-danger py-0 px-2"
           style="font-size:0.75rem;"
           onclick="return confirm('Delete this feedback?')">
           Delete
        </a>
    </li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No feedback yet.</p>
<?php endif; ?>
