<?php

class View
{
 private $pagesDir;
    private $router; // To access base path for links
    private $database; // Placeholder for future database integration

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
    public function render(string $pageName, array $data = [])
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
        $title = ucfirst($pageName);
        $this->outputHtmlLayout($title, $content);
    }

    /**
     * Outputs the basic HTML layout with the provided content.
     *
     * @param string $content The content to be placed within the layout.
     */
    private function outputHtmlLayout(string $title, string $content)
    {
        $basePath = $this->router->getBasePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($title)?></title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
        nav a { margin-right: 15px; text-decoration: none; color: #333; }
        nav a:hover { color: #007bff; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
    </style>
</head>
<body>
    <nav>
        <a href="<?php echo $basePath; ?>/">Home</a>
        <a href="<?php echo $basePath; ?>/about">About Us</a>
        <a href="<?php echo $basePath; ?>/contact">Contact</a>
        <a href="<?php echo $basePath; ?>/nonexistent">Non-Existent Page</a>
    </nav>
    <hr>
    <div class="container">
        <?php echo $content; ?>
    </div>
</body>
</html>
<?php
    }
}