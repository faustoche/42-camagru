<?php

require_once __DIR__ . '/../models/User.php';

class PasswordController {
	
	public function processNewPassword() {
		Auth::requireGuest();
		$email = trim($_POST['email']);

		$user = new Users();

		if ($user->findUserByEmail($email)) {
			$randomToken = bin2hex(random_bytes('15'));

			$expirationTime = date("Y-m-d H:i:s", strtotime("+1 hour"));

			$user->saveResetToken($email, $randomToken, $expirationTime);
			$appUrl = $_SERVER['HTTP_HOST'];

			$confirmationPath = "http://" . $appUrl . "/reset?token=" . $randomToken;
			$emailSubject = "Forgot your password?";
			$emailMessage = "Click on the link to change your password: " . $confirmationPath;

			mail($email, $emailSubject, $emailMessage);

			header('Location: /login');
			exit();
		}
	}

	public function showResetForm() {

		Auth::requireGuest();

		$user = new Users();

		if (isset($_GET['token'])) {
			$token = $_GET['token'];
			
			if ($user->isValidRequestToken($token)) {
				ob_start();
				require_once __DIR__ . '/../views/reset.php';
				$content = ob_get_clean();
				require_once __DIR__ . '/../views/layout.php';
			} else {
				header('Location: /');
				exit();
			}
		} else {
			header('Location: /');
			exit();
		}
	}

	public function processReset() {
		
		Auth::requireGuest();
		$user = new Users();

		if (isset($_POST['password']) && isset($_POST['token'])) {
			$password = $_POST['password'];
			$token = $_POST['token'];

			if ($user->isValidRequestToken($token)) {
				$passwordHashed = password_hash($password, PASSWORD_ARGON2ID);
				$user->updatePasswordWithToken($token, $passwordHashed);
				header('Location: /login');
				exit();
			}
		}
		header('Location: /');
		exit();
	}
}