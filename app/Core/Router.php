<?php
namespace Core;

class Router {
	private array $routes = [];
	private Request $request;

	public function __construct(Request $request) {
		$this->request = $request;
	}

	public function get($uri, $action) {
		$this->routes['GET'][$uri] = $action;
	}

	public function dispatch(): void
	{
		$uri = $this->request->getUri();
		$method = $this->request->getMethod();

		$action = $this->routes[$method][$uri] ?? null;

		if ($action) {
			list($controller, $method) = explode('@', $action);
			$controllerInstance = new $controller();
			call_user_func([$controllerInstance, $method]);
		} else {
			http_response_code(404);
			View::render('errors/404');
		}
	}
}
