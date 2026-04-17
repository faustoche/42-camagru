<?php

require_once __DIR__ . '/../models/User.php';

// TODO: IMPROVE ERROR RETURNS - REPLACE ALL ECHOS

/**
 * REGISTER CONTROLLER
 * Handle account creation, validation, and email confirmation 
 */
class RegisterController {

	/**
	 * Display registration form
	 */
	public function showForm(array $tab = []) {
		Auth::requireGuest();
		
		ob_start();
		require_once __DIR__ . '/../views/register.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';
	}

	/**
	 * Process the registration form data
	 */
	public function processRegistration() {

		Auth::requireGuest();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide");
		}

		$errors = [];
		$user = new Users();

		// Verifies that all 3 fields are present and valid
		if (!empty($_POST['username'])) {
			$username = checkInput($_POST['username']);
			if ($user->isUsernameTaken($username)) {
				$errors['username-taken'] = "Username is already taken";
			}
		} else {
			$errors['username-required'] = "Username is required";
		}

		if (!empty($_POST['email'])) {
			$email = checkInput($_POST['email']);
			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				$errors['invalid-email'] = "Invalid email format";
			if ($user->isEmailTaken($email)) {
				$errors['email-taken'] = "Email is already taken";
			}
		} else {
			$errors['email-required'] = "Email is required";
		}

		if (!empty($_POST['password'])) {
			$password = checkInput($_POST['password']);
			if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password))
				$errors['invalid-password'] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
		} else {
			$errors['password-required'] = "Password is required";
		}

		if (!empty($errors)) {
			$this->showForm($errors);
		} else {
			// Nhash the password and generate a validation token
			$passwordHashed = password_hash($password, PASSWORD_ARGON2ID);
			$randomToken = bin2hex(random_bytes('15'));
			
			// Save the new inactive user to the database
			$user->saveUser($username, $email, $passwordHashed, $randomToken);

			// Prepare and send the verification email
			$appUrl = $_SERVER['HTTP_HOST'];
			$confirmationPath = "http://" . $appUrl . "/confirm?token=" . $randomToken;
			$emailSubject = "Welcome! Please, verify your account.";
			$emailMessage = "Click on the link to verify your account: " . $confirmationPath;

			mail($email, $emailSubject, $emailMessage);

			// Store email in session to display the "Verify Notice" page 
			$_SESSION['pending_email'] = $email;
			header('Location: /verify-notice');
			exit();
		}
	}

	/**
	 * Validate the account when the user clicks the link in their email
	 */
	public function confirmAccount() {
		if (!isset($_GET['token'])) {
			header('Location: /');
			exit();
		}

		$token = $_GET['token'];
		$user = new Users();
		$db = $user->getConnection();

		// Search for the token for an unconfirmed account
		$request = $db->prepare("SELECT id FROM users WHERE confirmation_token = :token AND confirmed = FALSE");
		$request->execute([':token' => $token]);
		$result = $request->fetch();

		// If found, set confirmed to tru and delete the token
		if ($result) {
			$update = $db->prepare("UPDATE users SET confirmed = TRUE, confirmation_token = NULL WHERE confirmation_token = :token");
			$update->execute([':token' => $token]);
			header('Location: /login');
		} else {
			header('Location: /');
		}
		exit();
	}

	/**
	 * Display a prompt asking the user to check their email inbox.
	 */
	public function showVerifyNotice() {
		if (!isset($_SESSION['pending_email'])) {
			header('Location: /login');
			exit();
		}

		$email = $_SESSION['pending_email'];

		ob_start();
		require_once __DIR__ . '/../views/verify_notice.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';
	}

	/**
	 * resend the verification email if requested by the user
	 */
	public function resendVerificationEmail() {
		if (!isset($_SESSION['pending_email'])) {
			echo json_encode(['status' => 'error', 'message' => 'Session expired. Please register again or log in.']);
			exit();
		}

		$email = $_SESSION['pending_email'];
		$user = new Users();
		$db = $user->getConnection();

		$req = $db->prepare("SELECT confirmation_token, confirmed FROM users WHERE email = :email");
		$req->execute([':email' => $email]);
		$userData = $req->fetch(PDO::FETCH_ASSOC);

		if ($userData && $userData['confirmed'] == 0) {
			$token = $userData['confirmation_token'];
			
			$appUrl = $_SERVER['HTTP_HOST'];
			$confirmationPath = "http://" . $appUrl . "/confirm?token=" . $token;
			
			$emailSubject = "Welcome! Please, verify your account.";
			$emailMessage = "Click on the link to verify your account: " . $confirmationPath;

			mail($email, $emailSubject, $emailMessage);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}

		header('Content-Type: application/json');
		echo json_encode(['status' => 'error', 'message' => 'Account already verified or not found.']);
		exit();
	}
}

function checkInput(string $data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}