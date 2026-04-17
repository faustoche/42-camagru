<?php

/**
 * Router class
 * Maps HTTP requests to correspond controllers 
 */
class Router {

	// Dictionary to store routes
	private $routes = [];

	public function get(string $path, string $controller, string $action) {
		$this->routes['GET'][$path] = [$controller, $action];
	}

	public function post(string $path, string $controller, string $action) {
		$this->routes['POST'][$path] = [$controller, $action];
	}

	/**
	 * Dispatch incoming HTTP request to correct controller
	 */
	public function resolve() {
		// Retrieve current HTTP method
		$method = $_SERVER['REQUEST_METHOD'];
		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		// If the route doesn't exist -> 404 fallback
		if (!isset($this->routes[$method][$path])) {
			require_once __DIR__ . '/../controllers/NotFoundController.php';
			$controller = new NotFoundController();
			$controller->processNotFound();
			return;
		}

		// Extract controller name and method from matched route
		[$controllerName, $action] = $this->routes[$method][$path];

		// Require the controller file 
		require_once __DIR__ . '/../controllers/' . $controllerName . '.php';

		// Execute requested action
		$controller = new $controllerName();
		$controller->$action();
	}
}