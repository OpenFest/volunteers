<?php

class Router
{
 private string $basePath;

    /**
     * Constructor.
     *
     * @param string $basePath The base path of the application (e.g., '/my_vanilla_app').
     * Should be empty if the app is in the web server root.
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/'); // Remove trailing slash if present
    }

    /**
     * Get the requested page name based on the URL.
     *
     * @return string The sanitized page name (e.g., 'home', 'about', '404').
     */
    public function getRequestedPage(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        // Remove any query string parameters (e.g., ?id=123)
        $requestUri = strtok($requestUri, '?');

        // Remove the base path if the app is in a subdirectory.
        // Only strip it when it matches a full path segment (exact match or followed by '/')
        if (!empty($this->basePath) && ($requestUri === $this->basePath || str_starts_with($requestUri, $this->basePath . '/'))) {
            $requestUri = substr($requestUri, strlen($this->basePath));
        }

        // Default to 'home' if no specific path is requested (e.g., / or empty after stripping base path)
        $page = 'home';
        $trimmedUri = trim($requestUri, '/');
        if ($trimmedUri !== '') {
            // Remove leading/trailing slashes and sanitize for file name (e.g., /about/ -> about)
            $page = $trimmedUri;
            // Basic sanitization: only allow alphanumeric, hyphens, and underscores.
            $page = preg_replace('%[^a-zA-Z0-9_/-]%', '', $page);
			//if there are slashes, replace them with underscores
	        $page = str_replace('/', '_', $page);
        }

        return $page;
    }

    /**
     * Get the full base path for generating links.
     * @return string
     */
    public function getBasePath(): string
    {
	    return $this->basePath;
    }
}