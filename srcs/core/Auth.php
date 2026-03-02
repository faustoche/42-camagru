<?php

require_once __DIR__ . '/Session.php';

class Auth {

	## On récupère le user connecté
	public static function isLoggedIn() {
		return (Session::get('user_id') !== null);
	}

	## Si quelqu'un essaie d'accéder à une page sans être connecté
	public static function requireLogin() {
		if (!Auth::isLoggedIn()) {
			header('Location: /login');
			exit;
		}
	}

	## Si une personne connectée veut accéder aux pages "guest"
	public static function requireGuest() {
		if (Auth::isLoggedIn()) {
			header('Location: /');
			exit;
		}
	}
}