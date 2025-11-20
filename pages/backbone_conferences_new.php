<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    _log('Processing new conference submission. User: ' . $_SESSION['user']->getUsername(), LOG_INFO);
    // Handle form submission to add a new conference
    $slug = $_POST['slug'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $location = $_POST['location'];
    $registration_open = $_POST['registration_open'];
    $registration_close = $_POST['registration_close'];

    // Validate required fields
    //if slug is taken, return error
    if (empty($slug) || empty($title) || empty($description) || empty($start_date) || empty($end_date) || empty($location) || empty($registration_open) || empty($registration_close)) {
        $error = 'All fields are required.';
    } else {
        // Check if the slug already exists
        $existingConference = $this->database->query(
            'SELECT * FROM conferences WHERE slug = :slug',
            [':slug' => $slug]
        );

        if ($existingConference) {
            $error = 'A conference with this slug already exists.';
        }
    }
    // if reg open is after reg close, return error
    if (isset($registration_open) && isset($registration_close) && $registration_open > $registration_close) {
        $error = 'Registration open date cannot be after registration close date.';
    }
    // if start date is after end date, return error
    if (isset($start_date) && isset($end_date) && $start_date > $end_date) {
        $error = 'Start date cannot be after end date.';
    }
    //if reg close is after start date, return error
    if (isset($registration_close) && isset($start_date) && $registration_close > $start_date) {
        $error = 'Registration close date cannot be after start date.';
    }
    if (isset($error)) {
        _log('Unable to add conference: ' . $error, LOG_ERR);
        echo "<div class='error'>$error</div>";
        return;
    }

    // Insert the new conference into the database
    $this->database->query(
        'INSERT INTO conferences (slug, title, description, start_date, end_date, location, registration_open, registration_close) VALUES (:slug, :title, :description, :start_date, :end_date, :location, :registration_open, :registration_close)',
        [
            ':slug' => $slug,
            ':title' => $title,
            ':description' => $description,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':location' => $location,
            ':registration_open' => $registration_open,
            ':registration_close' => $registration_close
        ]
    );
    // Log the addition of the new conference
    _log("New conference added: $slug");

    // Redirect to the conference overview page after adding
    header('Location: /backbone/conferences');
    exit;
}

?>
<div class="backbone-page">
    <div class="page-title">
        <h1>Add New Conference</h1>
    </div>

	<div class="pane full-width">
        <form method="post" action="/backbone/conferences/new">
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" required>
            </div>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div class="form-group">
                <label for="registration_open">Registration Open</label>
                <input type="date" id="registration_open" name="registration_open" required>
            </div>
            <div class="form-group">
                <label for="registration_close">Registration Close</label>
                <input type="date" id="registration_close" name="registration_close" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Add Conference</button>
            </div>
        </form>
	</div>
</div>
