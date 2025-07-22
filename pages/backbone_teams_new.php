<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission to add a new team
    $conference = $_POST['conference'];
    $slug = $_POST['slug'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    // Validate required fields
    if (empty($conference) || empty($slug) || empty($name) || empty($description)) {
        $error = 'All fields are required.';
    } else {
        // Check if the slug already exists in the selected conference
        $existingTeam = $this->database->query(
            'SELECT * FROM teams WHERE conference = :conference AND slug = :slug',
            [':conference' => $conference, ':slug' => $slug]
        );

        if ($existingTeam) {
            $error = 'A team with this slug already exists in this conference.';
        }
    }

    if (isset($error)) {
        echo "<div class='error'>$error</div>";
        return;
    }

    // Insert the new team into the database
    $this->database->query(
        'INSERT INTO teams (conference, slug, name, description) VALUES (:conference, :slug, :name, :description)',
        [
            ':conference' => $conference,
            ':slug' => $slug,
            ':name' => $name,
            ':description' => $description
        ]
    );

    header('Location: /backbone/teams');
    exit;
}
?>
<div class="backbone-page">
	<div class="page-title">
		<h1>New Team</h1>
	</div>

	<div class="pane full-width">
        <form method="post" action="/backbone/teams/new">
            <div class="form-group">
                <label for="conference">Conference</label>
                <select id="conference" name="conference" required>
                    <?php foreach ($this->database->query('SELECT * FROM conferences ORDER BY start_date DESC') as $conference): ?>
                        <option value="<?php echo htmlspecialchars($conference->slug); ?>">
                            <?php echo htmlspecialchars($conference->title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" required>
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Add Team</button>
            </div>
        </form>
	</div>
