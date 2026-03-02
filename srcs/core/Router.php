<?php

class Router {

	// dictionnaire pour garder en mémoire toutes les routes du site
	private $routes = [];

	public function get(string $path, string $controller, string $action) {
		$this->routes['GET'][$path] = [$controller, $action];
	}

	public function post(string $path, string $controller, string $action) {
		$this->routes['POST'][$path] = [$controller, $action];
	}

	public function resolve() {
		$method = $_SERVER['REQUEST_METHOD'];
		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		if (!isset($this->routes[$method][$path])) {
			http_response_code(404);
			echo "404 - Not found";
			return;
		}

		[$controllerName, $action] = $this->routes[$method][$path];

		require_once __DIR__ . '/../controllers/' . $controllerName . '.php';

		$controller = new $controllerName();
		$controller->$action();
	}
}