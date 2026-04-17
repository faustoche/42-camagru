<?php

class NotFoundController {

	public function processNotFound() {
		Auth::requireLogin();
		Session::destroy();
		header('Location: /404');
		exit();
	}
}