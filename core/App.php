<?php

class App
{
	private Router $router;
	private View $view;
	private Database $database; // Placeholder for future database integration

	/**
	 * Constructor.
	 * Initializes the Router and View components.
	 *
	 * @param string $basePath The base path of the application.
	 * @param string $pagesDir The directory where page content files are stored.
	 */
	public function __construct(string $basePath, string $pagesDir, Database $database)
	{
		$this->router = new Router($basePath);
		$this->view = new View($pagesDir, $this->router, $database);
		$this->database = $database; // Initialize the database connection
	}

	/**
	 * Runs the application.
	 * Determines the requested page and renders it.
	 */
	public function run(): void
	{
		session_start();
		if (isset($_SESSION['user']) && $_SESSION['user'] instanceof User) {
			// Refresh the user data from the database on each request
			try{
				$_SESSION['user']->reload();
			} catch (UserNotFoundException $e) {
				// No user - reset the session
				_log('Error reloading user data: ' . $e->getMessage());
				unset($_SESSION['user']);
			} catch (Exception $e) {
				// General error
				_log('Error reloading user data: ' . $e->getMessage());
			}
		}
		$page = $this->router->getRequestedPage();
		$this->view->render($page);
	}
}