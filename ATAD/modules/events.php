


<?php
$events = [
    1 => [
        'event_name' => 'Tech Workshop',
        'venue' => 'IT Hall',
        'dates' => [
            '2026-01-10' => ['09:00 AM', '01:00 PM'],
            '2026-01-11' => ['10:00 AM', '03:00 PM']
        ]
    ],
    2 => [
        'event_name' => 'Business Seminar',
        'venue' => 'Business Center',
        'dates' => [
            '2026-01-12' => ['10:00 AM', '02:00 PM'],
            '2026-01-13' => ['09:00 AM']
        ]
    ],
    3 => [
        'event_name' => 'Art Exhibition',
        'venue' => 'City Gallery',
        'dates' => [
            '2026-01-15' => ['11:00 AM', '03:00 PM'],
            '2026-01-16' => ['01:00 PM', '06:00 PM']
        ]
    ]
];
?>

<form method="GET" action="index.php">
    <input type="hidden" name="page" value="registration_form">

     <div class="d-flex justify-content-end mb-3">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>


    <!-- EVENT -->
    <select name="event_id" class="form-select mb-2" required>
        <option value="">Select Event</option>
        <?php foreach ($events as $id => $event): ?>
            <option value="<?= $id ?>"><?= $event['event_name'] ?></option>
        <?php endforeach; ?>
    </select>

    <!-- DATE -->
    <select name="event_date" class="form-select mb-2" required>
        <option value="">Select Date</option>
        <?php
        foreach ($events as $event) {
            foreach ($event['dates'] as $date => $times) {
                echo "<option value='$date'>$date</option>";
            }
        }
        ?>
    </select>

    <!-- TIME -->
    <select name="event_time" class="form-select mb-2" required>
        <option value="">Select Time</option>
        <?php
        foreach ($events as $event) {
            foreach ($event['dates'] as $times) {
                foreach ($times as $time) {
                    echo "<option value='$time'>$time</option>";
                }
            }
        }
        ?>
    </select>

    <button class="btn btn-primary">Register</button>
</form>
