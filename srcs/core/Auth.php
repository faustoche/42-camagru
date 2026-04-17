<?php

require_once __DIR__ . '/Session.php';

/**
 * Authentication helper class
 * Provides methods to check user login states
 */

class Auth {

	/**
	 * Check if a user is logged in
	 * @return true if user_id exists
	 */
	public static function isLoggedIn() {
		return (Session::get('user_id') !== null);
	}

	/**
	 * Protects routes that need authentication
	 * Redirects no identified guest to login page
	 */
	public static function requireLogin() {
		if (!Auth::isLoggedIn()) {
			header('Location: /login');
			exit;
		}
	}

	/**
	 * Protect guest routes like login and register
	 * Redirects identified users to home page
	 */
	public static function requireGuest() {
		if (Auth::isLoggedIn()) {
			header('Location: /');
			exit;
		}
	}
}