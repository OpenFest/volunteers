<?php

namespace Core;

class View
{
  public static function renderSimple($view, $data = []): void
  {
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';

        if (file_exists($viewPath)) {
            extract($data);
            include $viewPath;
        } else {
            echo "View not found: $viewPath";
        }
    }

    public static function render($view, $data = [], $layout = 'layouts/main'): void
    {
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../../views/' . $layout . '.php';

        if (!file_exists($viewPath)) {
            echo "View not found: $viewPath";
            return;
        }

		if (!file_exists($layoutPath)) {
			// fallback to simple render if layout not found
			self::renderSimple($view, $data);
			return;
		}

        // Extract variables for the view
        extract($data);

        // Capture view output into $content
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // Render layout and inject $content
	    include $layoutPath;
    }
}