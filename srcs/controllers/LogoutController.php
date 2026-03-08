<?php

class LogoutController {

	public function processLogout() {
		Auth::requireLogin();
		Session::destroy();
		header('Location: /');
		exit();
	}
}