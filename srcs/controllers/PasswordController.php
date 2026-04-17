<?php

require_once __DIR__ . '/../models/User.php';

/**
 * Password controller
 * Manages the "forgot password" and password resetting via email tokens
 */
class PasswordController {
	
	/**
	 * Process the request when a user forgets their password
	 * Generates a token and sends a recovery email
	 */
	public function processNewPassword() {
		Auth::requireGuest();

		// Validates CSRF token to ensure the request is legitimate
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide2.");
		}
		$email = trim($_POST['email']);
		$user = new Users();

		// If the email exists, proceed with token generation
		if ($user->findUserByEmail($email)) {
			// Generate a secure, random 30-character hex token
			$randomToken = bin2hex(random_bytes('15'));

			// Set token expiration time to exactly 1 hour from now
			$expirationTime = date("Y-m-d H:i:s", strtotime("+1 hour"));

			// Save the token and expiration in the database
			$user->saveResetToken($email, $randomToken, $expirationTime);
			$appUrl = $_SERVER['HTTP_HOST'];

			// Prepare and send recovery email containing the unique link
			$confirmationPath = "http://" . $appUrl . "/reset?token=" . $randomToken;
			$emailSubject = "Forgot your password?";
			$emailMessage = "Click on the link to change your password: " . $confirmationPath;

			mail($email, $emailSubject, $emailMessage);

			header('Location: /login');
			exit();
		}
	}

	/**
	 * Displays the form to type a new password, only if the token is valid
	 */
	public function showResetForm() {

		Auth::requireGuest();

		$user = new Users();

		// Check if the token is present in the URL
		if (isset($_GET['token'])) {
			$token = $_GET['token'];
			
			// Verify that the token exists in DB and is not expired
			if ($user->isValidRequestToken($token)) {
				ob_start();
				require_once __DIR__ . '/../views/reset.php';
				$content = ob_get_clean();
				require_once __DIR__ . '/../views/layout.php';
			} else {
				// Invalid or expired token redirect to home
				header('Location: /');
				exit();
			}
		} else {
			header('Location: /');
			exit();
		}
	}

	/**
	 * Process the submission of the new password
	 */
	public function processReset() {
		
		Auth::requireGuest();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide. 3");
		}
		$user = new Users();

		if (isset($_POST['password']) && isset($_POST['token'])) {
			$password = $_POST['password'];
			$token = $_POST['token'];

			// Strict password policy check
			if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password)) {
				die("Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.");
			}
			
			// Final verification of the token before updating the database
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