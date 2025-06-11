<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title ?? 'My Web Platform') ?></title>
</head>
<body>
    <header>
        <h1>My Site Header</h1>
        <nav>
            <a href="/">Home</a>
            <!-- add more links -->
        </nav>
    </header>

    <main>
        <?php
            // This variable $content will contain the main view content
            echo $content;
        ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> My Web Platform</p>
    </footer>
</body>
</html>