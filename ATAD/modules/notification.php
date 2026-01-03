<?php
include "includes/connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$user_id = $_SESSION['user_id']; // Logged-in user ID

/* ==============================
   DELETE ALL NOTIFICATIONS
================================= */
if (isset($_POST['delete_all'])) {
    $stmt = $conn->prepare("DELETE FROM tbl_notification WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

/* ==============================
   FETCH NOTIFICATIONS
================================= */
$stmt = $conn->prepare("
    SELECT notification_id, title, message, created_at
    FROM tbl_notification
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="card mx-auto" style="max-width:700px;margin-top:40px;">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">My Notifications</h4>
            <div>
                <?php if ($notifications): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete all notifications?');">
                    <button type="submit" name="delete_all" class="btn btn-danger btn-sm">Delete All</button>
                </form>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>

        <?php if (!$notifications): ?>
            <div class="alert alert-info">You have no notifications yet.</div>
        <?php else: ?>
            <?php foreach ($notifications as $note): ?>
                <div class="mb-3 p-3 border rounded">
                    <h5><?= htmlspecialchars($note['title']) ?></h5>
                    <p><?= htmlspecialchars($note['message']) ?></p>
                    <small class="text-muted">
                        <?= date("M d, Y h:i A", strtotime($note['created_at'])) ?>
                    </small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>
