<?php
include "includes/connection.php";

// Fetch all participants WITH event info
$result = $conn->query("
    SELECT 
        r.registration_id,
        r.full_name,
        r.gender,
        r.email,
        r.address,
        e.event_name,
        r.event_date,
        r.event_time
    FROM tbl_registration r
    LEFT JOIN tbl_events e ON r.events_id = e.events_id
    ORDER BY r.registration_id DESC
");
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Participant List</h5>

         <div class="d-flex justify-content-end mb-3">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>


        <table class="table datatable">
            <thead>
                <tr>
                    <th>Participant ID</th>
                    <th>Full Name</th>
                    <th>Gender</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Event</th>
                 
                    
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row["registration_id"]) ?></td>
                            <td><?= htmlspecialchars($row["full_name"]) ?></td>
                            <td><?= htmlspecialchars($row["gender"]) ?></td>
                            <td><?= htmlspecialchars($row["email"]) ?></td>
                            <td><?= htmlspecialchars($row["address"]) ?></td>
                            <td><?= htmlspecialchars($row["event_name"] ?? 'N/A') ?></td>
                           
                         >
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No participants found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
?>
