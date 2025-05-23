<?php
session_start();
include('db_connection.php'); // Ensure the database connection is included


// Default department and category filters
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch all events for display on page load (without filtering)
$eventsQuery = "SELECT * FROM events ORDER BY event_date ASC";
$eventsResult = $conn->query($eventsQuery);

// Query for upcoming events with department and category filters
$upcomingEventsQuery = "SELECT e.id, e.event_name, e.resource_person, e.event_details, e.event_date, e.department, e.category, ec.category_name, e.hod_status, e.dean_status, e.principal_status, e.added_by, u.role as added_by_role
                        FROM events e
                        LEFT JOIN event_categories ec ON e.category = ec.id
                        JOIN users u ON e.added_by = u.id
                        WHERE e.event_date >= CURDATE() 
                        -- In your WHERE clause:
AND (
    (u.role IN ('faculty', 'hod') AND e.dean_status = 'Approved') 
    OR u.role IN ('dean', 'principal')
)




                        AND (e.department = '$departmentFilter' OR '$departmentFilter' = '') 
                        AND (e.category = '$categoryFilter' OR '$categoryFilter' = '') 
                        AND (e.event_date BETWEEN '$startDate' AND '$endDate' OR '$startDate' = '' OR '$endDate' = '') 
                        ORDER BY e.event_date ASC";
$upcomingEventsResult = $conn->query($upcomingEventsQuery);

// Query for past events with department and category filters
$pastEventsQuery = "SELECT e.id, e.event_name, e.resource_person, e.event_details, e.event_date, e.department, e.category, ec.category_name,  e.hod_status, e.dean_status, e.principal_status,  e.added_by, u.role as added_by_role
                    FROM events e
                    LEFT JOIN event_categories ec ON e.category = ec.id
                    JOIN users u ON e.added_by = u.id
                    WHERE e.event_date < CURDATE() 
                    -- In your WHERE clause:
AND (
    (u.role IN ('faculty', 'hod') AND e.dean_status = 'Approved') 
    OR u.role IN ('dean', 'principal')
)



                    AND (e.department = '$departmentFilter' OR '$departmentFilter' = '') 
                    AND (e.category = '$categoryFilter' OR '$categoryFilter' = '') 
                    AND (e.event_date BETWEEN '$startDate' AND '$endDate' OR '$startDate' = '' OR '$endDate' = '') 
                    ORDER BY e.event_date DESC";
$pastEventsResult = $conn->query($pastEventsQuery);


// Function to get status badge
function getStatusBadge($status) {
    if ($status === 'Approved') {
        return '<span class="badge bg-success">✅ Approved</span>';
    } elseif ($status === 'Rejected') {
        return '<span class="badge bg-danger">❌ Rejected</span>';
    } else {
        return '<span class="badge bg-warning">⏳ Pending</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
      

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 80px;

      background-color: #f5f5f5; /* light gray background */

      display: flex;
      align-items: center;
      padding: 0 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      z-index: 999;
    }

    .logo {
      height: 90px;
      margin-right: 20px;
    }

    .heading {
      font-size: 28px;
      font-weight: bold;
      color: #333;
    }

    
   
    /* Heading style */
.container h1 {
    color: #2dd62d;
    font-size: 2.5rem;
    margin-bottom: 30px;
    text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7);
}


.centered-heading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: clamp(20px, 2.5vw, 32px);

  font-weight: 900;
  background: linear-gradient(90deg, #ff6ec4, #7873f5, #4ade80, #facc15, #f87171);
  background-size: 300%;
  animation: gradientShift 6s infinite linear;
  background-clip: text;
-webkit-background-clip: text;

  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.5), 2px 2px 8px rgba(0, 0, 0, 0.3);
  letter-spacing: 2px;
  white-space: nowrap;
  font-family: 'Segoe UI', 'Poppins', sans-serif;
  text-align: center;
  z-index: 10;
}

@keyframes gradientShift {
  0% { background-position: 0% }
  100% { background-position: 100% }
}
.space {
    padding-top: 80px; /* same or more than header height */
}


    </style>
</head>

<body>
<header>
        <img src="logo.png" alt="College Logo" class="logo" />
        <div class="centered-heading">SITAMS ACTIVITY MANAGEMENT SYSTEM</div>
      </header>

<div class="space">
</div>
<div class="text-end text-muted mb-3">
    <span class="badge bg-light text-dark">
        Welcome, <?= htmlspecialchars($_SESSION['role']) ?> 
        (ID: <?= $_SESSION['user_id'] ?>)
    </span>
</div>


    <div class="container mt-5">
        <h1 class="text-center text-primary"></h1>
        <a href="login.php" class="btn btn-danger float-end">Logout</a>
          <!-- Add Event Button -->
 <button class="btn btn-success mt-4" id="toggle-event-form">Add Event</button>

 <a href="principal_filter.php" class="btn btn-primary">filter events</a>



<!-- Add Event Form Container (Initially Hidden) -->
<div id="event-form-container" style="display: none;">

<h2 class="mt-4" >Add New Event</h2>
<form id="add-event-form" method="POST" enctype="multipart/form-data">
   <div class="mb-3">
       <label for="event_name" class="form-label">Event Name</label>
       <input type="text" class="form-control" id="event_name" name="event_name" required>
   </div>
   <div class="mb-3">
       <label for="event_details" class="form-label">Event Details</label>
       <textarea class="form-control" id="event_details" name="event_details" required></textarea>
   </div>
   <div class="mb-3">
       <label for="event_date" class="form-label">Event Date</label>
       <input type="date" class="form-control" id="event_date" name="event_date" required>
   </div>
   <div class="mb-3">
       <label for="resource_person" class="form-label">Resource Person Name</label>
       <input type="text" class="form-control" id="resource_person" name="resource_person" required>
   </div>
   <div class="mb-3">
       <label for="resource_person_photo" class="form-label">Resource Person Photo</label>
       <input type="file" class="form-control" id="resource_person_photo" name="resource_person_photo" accept="image/*" required>
   </div>
   <div class="mb-3">
   <label for="department" class="form-label">Department</label>
       <!-- Default department set to HOD's department -->
       <select id="department" class="form-control" name="department" required>
                   <option value="">All Departments</option>
                   <option value="CSE">CSE</option>
                   <option value="CSD">CSD</option>
                   <option value="ECE">ECE</option>
                   <option value="EEE">EEE</option>
                   <option value="AI">AI</option>
                   <option value="AIML">AIML</option>
                   <option value="MBA">MBA</option>
                   <option value="MCA">MCA</option>
                   <option value="MECH">MECH</option>
               </select>
   </div>
   <div class="mb-3">
       <label for="category" class="form-label">Event Category</label>
       <select class="form-control" id="category" name="category" required>
           <option value="1">Seminar</option>
           <option value="2">Webinar</option>
           <option value="3">FDP</option>
           <option value="4">Festival Event</option>
           <option value="5">Alumni Event</option>
           <option value="6">Workshop</option>
           <option value="7">Guest Lecture</option>
       </select>
   </div>
   <button type="submit" class="btn btn-primary">Add Event</button>
</form>
</div>



    <!-- Tabs to switch between Upcoming, Past, and Filtered Events -->
 <ul class="nav nav-tabs" id="event-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" href="#upcoming">Upcoming Events</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="past-tab" data-bs-toggle="tab" href="#past">Past Events</a>
            </li>
            <li class="nav-item">
      
          <a class="nav-link" id="filtered-tab" data-bs-toggle="tab" href="#filtered" style="display: none;">Filtered Events</a>
            </li>
</ul>








<div class="tab-content mt-3">
            <!-- Upcoming Events Tab -->
            <div class="tab-pane fade show active" id="upcoming">
                <h2 class="mt-4">Upcoming Events</h2>
                <table class="table table-bordered" id="upcoming-events-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Resource Person</th>
                            <th>faculty status</th>
                            <th>hod status</th>
                            <th>dean status</th>
                            <th>Final status</th>
                            <th>Details</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $upcomingEventsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['department']); ?></td>
                                <td><?= htmlspecialchars($row['event_name']); ?></td>
                                <td><?= htmlspecialchars($row['event_date']); ?></td>
                                <td><?= htmlspecialchars($row['category_name']); ?></td>
                                <td><?= htmlspecialchars($row['resource_person']); ?></td>
                                
                               
                                <td>
    <?php if ($row['added_by_role'] === 'faculty') : ?>
        <?php if (
            $row['hod_status'] === 'Approved' &&
            $row['dean_status'] === 'Approved' &&
            $row['principal_status'] === 'Pending'
        ): ?>
            <button class="btn btn-success btn-sm principal-approve-btn" data-event-id="<?= $row['id']; ?>">Approve</button>
            <button class="btn btn-danger btn-sm principal-reject-btn" data-event-id="<?= $row['id']; ?>">Reject</button>
        <?php elseif ($row['principal_status'] === 'Approved'): ?>
            <span class="badge bg-success">✅ Approved</span>
        <?php elseif ($row['principal_status'] === 'Rejected'): ?>
            <span class="badge bg-danger">❌ Rejected</span>
        <?php else: ?>
            <span class="badge bg-warning">⏳ Pending</span>
        <?php endif; ?>
    <?php else: ?>
        <span class="badge bg-warning">⏳ Pending</span>
    <?php endif; ?>
</td>



<td>
<?php
if (
    $row['added_by_role'] === 'hod' &&
    strtolower($row['dean_status']) === 'approved' &&
    strtolower($row['principal_status']) === 'pending'
): ?>
    <button class="btn btn-success btn-sm approve-btn" data-event-id="<?= $row['id']; ?>" data-role="principal">Approve</button>
    <button class="btn btn-danger btn-sm reject-btn" data-event-id="<?= $row['id']; ?>" data-role="principal">Reject</button>

<?php elseif ($row['added_by_role'] === 'hod'): ?>
    <?= getStatusBadge($row['principal_status']); ?>

<?php elseif ($row['added_by_role'] === 'faculty'): ?>
    <?= getStatusBadge($row['hod_status']); ?>

<?php else: ?>
    <span class="badge bg-warning "> ⏳Pending</span>
<?php endif; ?>

</td>

<td>
    <?php
    $role = $row['added_by_role'];
    $principalStatus = $row['principal_status'];
    $deanStatus = $row['dean_status'];
    $eventId = $row['id'];

    if ($role === 'dean') {
        // For events added by Dean
        if (strtolower($principalStatus) === 'pending') {

            ?>
            <button class="btn btn-success btn-sm dean-approve-btn" data-event-id="<?= $eventId; ?>" data-role="principal">Approve</button>
            <button class="btn btn-danger btn-sm dean-reject-btn" data-event-id="<?= $eventId; ?>" data-role="principal">Reject</button>
            <?php
        } else {
            // Show principal's decision as Dean status after action
            echo getStatusBadge($principalStatus);
        }
    } elseif ($role === 'principal') {
        // For events added by Principal
        echo getStatusBadge('pending'); // Dean status stays pending
    } else {
        // For HOD/Faculty events, show actual dean_status
        echo getStatusBadge($deanStatus);
    }
    ?>
</td>


<td>
    <?php
    $hod = $row['hod_status'];
    $dean = $row['dean_status'];
    $principal = $row['principal_status'];
    $role = $row['added_by_role'];

    if ($hod === 'Rejected' || $dean === 'Rejected' || $principal === 'Rejected') {
        echo '<span class="badge bg-danger">❌ Not Proceed</span>';
    } elseif (
        ($role === 'faculty' && $hod === 'Approved' && $dean === 'Approved' && $principal === 'Approved') ||
        ($role === 'hod' && $dean === 'Approved' && $principal === 'Approved') ||
        ($role === 'dean' && $principal === 'Approved') ||
        ($role === 'principal')
    ) {
        echo '<span class="badge bg-success">✅ Proceed</span>';
    } else {
        echo '<span class="badge bg-warning">⏳ Pending</span>';
    }
    ?>
</td>

<td>
    <a href="event_det.php?id=<?= $row['id']; ?>" class="btn btn-info btn-sm">View Details</a>
</td>
                        
                        <td>
                        <?php if ($_SESSION['user_id'] == $row['added_by']): ?>
                            <button class="btn btn-danger btn-sm" onclick="deleteEvent(<?= $row['id']; ?>)">Delete</button>
                            <?php endif; ?>
                        </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Past Events Tab -->
            <div class="tab-pane fade" id="past">
                <h2 class="mt-4">Past Events</h2>
                <table class="table table-bordered" id="past-events-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Resource Person</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $pastEventsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['department']); ?></td>
                                <td><?= htmlspecialchars($row['event_name']); ?></td>
                                <td><?= htmlspecialchars($row['event_date']); ?></td>
                                <td><?= htmlspecialchars($row['category_name']); ?></td>
                                <td><?= htmlspecialchars($row['resource_person']); ?></td>
                                <td>
                            <div id="photos-<?= $row['id']; ?>">
                                <?php if (!empty($row['event_photo'])): ?>
                                    <?php
                                    $photoPaths = explode(',', $row['event_photo']);
                                    foreach ($photoPaths as $photoPath):
                                    ?>
                                        <div class="photo-container">
                                            <img src="<?= htmlspecialchars($photoPath); ?>" width="100" class="m-1">
                                            <button class="btn btn-danger btn-sm delete-photo-btn" data-event-id="<?= $row['id']; ?>" data-photo-path="<?= $photoPath; ?>">Delete Photo</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No photos uploaded.</p>
                                <?php endif; ?>
                            </div>
                            <?php if ($_SESSION['user_id'] == $row['added_by']): ?>
                            <form id="upload-form-<?= $row['id']; ?>" class="mt-2" style="display:none;" method="POST" enctype="multipart/form-data">
    <input type="file" name="event_photos[]" accept="image/*" multiple required>
    <button type="submit" class="btn btn-success btn-sm mt-2">Upload Photos</button>
</form>
<button class="btn btn-primary btn-sm mt-2" onclick="toggleUploadForm(<?= $row['id']; ?>)">Upload Photos</button>
<?php endif; ?>

                        </td>
                        <td>
    <a href="event_det.php?id=<?= $row['id']; ?>" class="btn btn-info btn-sm">View Details</a>
</td>
                        
                        <td>
                        <?php if ($_SESSION['user_id'] == $row['added_by']): ?>
                            <button class="btn btn-danger btn-sm" onclick="deleteEvent(<?= $row['id']; ?>)">Delete Event</button>
                            <?php endif; ?>
                        </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

           
    <script>
$(document).ready(function() {
            $("#toggle-event-form").click(function() {
                $("#event-form-container").slideToggle(); // Smooth show/hide effect
            });
        });


        
$(document).ready(function () {
    $('#add-event-form').submit(function (e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: 'add_event.php', // ensure this path is correct
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                try {
                    const res = JSON.parse(response);

                    if (res.status === 'success') {
                        alert('Event added successfully!');

                        // Optional: You can reload the event list or dynamically add the row
                        location.reload(); // Quick way to show the newly added event
                    } else {
                        alert('Error: ' + res.message);
                        console.log(res);
                    }
                } catch (e) {
                    alert('Unexpected error. Check console.');
                    console.error(response);
                }
            },
            error: function (xhr, status, error) {
                alert('AJAX error: ' + error);
                console.log(xhr.responseText);
            }
        });
    });
});


function toggleUploadForm(eventId) {
            var form = document.getElementById('upload-form-' + eventId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        
        $(document).on('submit', 'form[id^="upload-form-"]', function(event) {
    event.preventDefault();
    
    var form = $(this);
    var formData = new FormData(this);
    var eventId = form.attr('id').split('-')[2]; 

    formData.append('id', eventId);  // Append event ID

    $.ajax({
        url: 'upload_event_photo.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            var data = JSON.parse(response);
            if (data.status === 'success') {
                alert('Photos uploaded successfully!');
                $('#photos-' + eventId).append(data.photosHtml); // Append new photos
            } else {
                alert('Error: ' + data.message);
            }
        },
        error: function() {
            alert('Error with the AJAX request.');
        }
    });
});
</script>
      
<script>

        // Delete event functionality (AJAX)
        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event?')) {
                $.ajax({
                    url: 'delete_event.php',
                    type: 'POST',
                    data: { id: eventId },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            $('tr[data-id="' + eventId + '"]').remove();
                            alert('Event deleted successfully!');
                        } else {
                            alert('Error deleting event.');
                        }
                    }
                });
            }
        }
        </script>
     
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

$(document).on('click', '.principal-approve-btn, .principal-reject-btn', function () {
    alert('Button clicked'); // 🔥 First, confirm this fires.

    let eventId = $(this).data('event-id');
    let status = $(this).hasClass('principal-approve-btn') ? 'Approved' : 'Rejected';

    $.ajax({
        url: 'update_event_status.php',
        type: 'POST',
        data: {
            event_id: eventId,
            role: 'principal',
            status: status
        },
        success: function (response) {
            try {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (e) {
                alert('Unexpected error. See console.');
                console.error(response);
            }
        },
        error: function (xhr) {
            alert('AJAX Error: ' + xhr.status);
            console.error(xhr.responseText);
        }
    });
});


</script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        if (e.target.matches('.approve-btn, .reject-btn')) {
            alert("status updated!"); // Debug: Check if this fires

            const eventId = e.target.getAttribute('data-event-id');
            const role = e.target.getAttribute('data-role');
            const action = e.target.classList.contains('approve-btn') ? 'approve' : 'reject';

            console.log(`Sending: event_id=${eventId}&role=${role}&action=${action}`); // Debug

            fetch('update_event_stat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `event_id=${eventId}&role=${role}&action=${action}`
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'Success') {
                    location.reload();
                } else {
                    alert('Failed: ' + data);
                }
            })
            .catch(error => alert('Error: ' + error));
        }
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        if (e.target.matches('.dean-approve-btn, .dean-reject-btn')) {
            alert("status updated!"); // Debug: Check if this fires

            const eventId = e.target.getAttribute('data-event-id');
            const role = e.target.getAttribute('data-role');
            const action = e.target.classList.contains('dean-approve-btn') ? 'approve' : 'reject';

            console.log(`Sending: event_id=${eventId}&role=${role}&action=${action}`); // Debug

            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `event_id=${eventId}&role=${role}&action=${action}`
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'Success') {
                    location.reload();
                } else {
                    alert('Failed: ' + data);
                }
            })
            .catch(error => alert('Error: ' + error));
        }
    });
});

    </script>
</body>
</html>