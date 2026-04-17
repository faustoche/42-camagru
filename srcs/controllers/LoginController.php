<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Session.php';

class LoginController {

	public function showLoginForm(array $tab = []) {
		Auth::requireGuest();
		ob_start();
		require_once __DIR__ . '/../views/login.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';
	}

	public function processLogin() {

		Auth::requireGuest();

		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide.1");
		}
		$user = new Users();
		$errors = [];

		if (isset($_POST['password']) && isset($_POST['email'])) {
			$password = $_POST['password'];
			$email = $_POST['email'];

			$userData = $user->getUserByEmail($email);

			if ($userData) {
				if (!$userData['confirmed']) {
					$errors['not-confirmed'] = "Please confirm your email before logging in.";
				} elseif (password_verify($password, $userData['password'])) {
					Session::set('user_id', $userData['id']);
					header('Location: /');
					exit();
				} else {
					$errors['invalid-password'] = "Password is invalid";
				}
			} else {
				$errors['invalid-email'] = "Email is invalid";
			}
		}
		$this->showLoginForm($errors);
	}
}