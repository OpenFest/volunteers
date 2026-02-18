<?php

class View
{
	private string $pagesDir;
	private Router $router; // To access the base path for the links
	private Database $database; // Placeholder for future database usage

	/**
	 * Constructor.
	 *
	 * @param string $pagesDir The directory where page content files are stored.
	 * @param Router $router An instance of the Router to get the base path.
	 */
	public function __construct(string $pagesDir, Router $router, Database $database)
	{
		$this->pagesDir = rtrim($pagesDir, '/') . '/'; // Ensure trailing slash
		$this->router = $router;
		$this->database = $database; // Initialize the database connection
	}

	/**
	 * Renders a specific page and wraps it in a basic HTML layout.
	 *
	 * @param string $pageName The name of the page to render (e.g., 'home', 'about').
	 */
	public function render(string $pageName, array $data = []): void
    {
		$pageFile = $this->pagesDir . $pageName . '.php';
		$content = '';

		if (!file_exists($pageFile)) {
			// Fallback to 404 page if requested page doesn't exist
			header("HTTP/1.0 404 Not Found");
			$pageName = '404';
			$pageFile = $this->pagesDir . '404.php';
		}

		// Check if 404.php itself exists as a fallback
		if (!file_exists($pageFile)) {
			// If even 404.php is missing, display a simple error
			$content = '<h1>Error</h1><p>The requested page could not be found, and the 404 error page is missing.</p>';
		} else {
			extract($data);
			// Use output buffering to capture the content of the page file
			ob_start();
			include $pageFile;
			$content = ob_get_clean();
		}

		// Now, output the full HTML structure with the page content
		$title = ucwords(str_replace('_',' :: ', $pageName));
		$this->outputHtmlLayout($title, $content);
	}

	private function loadStyle(): array|string|null
    {

        //Deprecated: This method is used to load the CSS file and minify it.

		// Load and minify the CSS file
		// Note: This is a simple minification.
		// Load the CSS file and remove new lines and extra spaces
		if (!file_exists(CORE_DIR . '/style.css')) {
			// If the CSS file is missing, set an empty style
			return '';
		} // Load the CSS file and remove new lines and extra spaces
		else if (!is_readable(CORE_DIR . '/style.css')) {
			// If the CSS file is readable, load it
			return '';
			// If the CSS file is not readable, set an empty style
		}
		$style = str_replace(array("\r", "\n", "\t"), '', file_get_contents(CORE_DIR . '/style.css'));

//        return $style;
		// Remove new lines and extra spaces around CSS rules
        $style = preg_replace('/\s+/', ' ', $style); // Remove extra spaces
        $style = preg_replace('/\s*([{};:,])\s*/', '$1', $style); // Remove spaces around braces, colons, and semicolons
        // Remove semicolon before closing brace
        return preg_replace('/;}/', '}', $style);
	}


    /**
     * Outputs the basic HTML layout with the provided content.
     *
     * @param string $content The content to be placed within the layout.
     */
    private function outputHtmlLayout(string $title, string $content): void
    {
        $basePath = $this->router->getBasePath();
//        $style = $this->loadStyle(); //maybe we can reuse this some day, but for now we will load the CSS file directly in the HTML head, to avoid issues with missing or unreadable CSS files.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($title)?></title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css">
</head>
<body>
    <nav>
        <a class="nav-logo" href="<?php echo $basePath; ?>/"><img src="/assets/img/logo.png" alt="OpenFest"></a>
<?php
// If the user is logged in, show admin navigation, otherwise show public navigation
    if (isset($_SESSION['user'])) {
     if ($_SESSION['user']->isAdmin()) {
?>
        <a class="nav-item" href="<?php echo $basePath; ?>/backbone">Backbone</a>
        <a class="nav-item" href="<?php echo $basePath; ?>/backbone/users">Users</a>
        <a class="nav-item" href="<?php echo $basePath; ?>/backbone/volunteers">Volunteers</a>
        <a class="nav-item" href="<?php echo $basePath; ?>/backbone/teams">Teams</a>
        <a class="nav-item" href="<?php echo $basePath; ?>/backbone/conferences">Conferences</a>
<?php
     }
?>
        <a class="nav-item" href="<?php echo $basePath; ?>/logout">Logout [<span class="small"><?php echo $_SESSION['user']->getEmail() ?></span>]</a>
<?php
    } else {
        // Public navigation for non-admin users
?>
        <a class="nav-item" href="<?php echo $basePath; ?>/volunteers/new">Кандидатствай за доброволец</a>
<?php
    }
?>
    </nav>
    <div class="banner"></div>
    <div class="container">
        <?php echo $content; ?>
    </div>
</body>
</html>
<?php
    }
}