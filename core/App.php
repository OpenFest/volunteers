<?php

class App
{
 private $router;
    private $view;

    /**
     * Constructor.
     * Initializes the Router and View components.
     *
     * @param string $basePath The base path of the application.
     * @param string $pagesDir The directory where page content files are stored.
     */
    public function __construct(string $basePath, string $pagesDir)
    {
        $this->router = new Router($basePath);
        $this->view = new View($pagesDir, $this->router);
    }

    /**
     * Runs the application.
     * Determines the requested page and renders it.
     */
    public function run()
    {
        $page = $this->router->getRequestedPage();
        $this->view->render($page);
    }
}