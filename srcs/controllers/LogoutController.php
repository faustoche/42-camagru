<?php

/**
 * Logout controller
 */
class LogoutController {

	/**
	 * Destroys the active session and redirect to the home page.
	 */
	public function processLogout() {
		// Ensure only authenticated users can activate a logout
		Auth::requireLogin();
		
		// Clears all session variables and destroys the session
		Session::destroy();
		
		header('Location: /');
		exit();
	}
}