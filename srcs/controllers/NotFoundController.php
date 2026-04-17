<?php

/**
 * Not found controller
 * Handle the fallback for invalid or missing routes.
 */
class NotFoundController {

	/**
	 * Set appropriate HTTP status code and renders the 404 error page.
	 */
	public function processNotFound() {
		http_response_code(404);
		$pageTitle = "404 - Not found";
		ob_start();
		require_once __DIR__ . '/../views/404.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';
	}
}