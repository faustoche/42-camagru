<?php

class NotFoundController {

	public function processNotFound() {
		http_response_code(404);
        $pageTitle = "404 - Not found";
        ob_start();
        require_once __DIR__ . '/../views/404.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layout.php';
	}
}