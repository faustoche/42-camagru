<?php

require_once __DIR__ . "/../models/User.php";

/**
 * Profile controller
 * Handles displaying and updating the authenticated user's account information
 */
class ProfileController {
	
	/**
	 * Fetch current user data and display the profile page
	 */
	public function showProfile() {
		Auth::requireLogin();
		$user = new Users();

		$user_id = $_SESSION['user_id'];

		$request = "SELECT username, email, email_notifications
					FROM users 
					WHERE id = :user_id";
		
		$statement = $user->getConnection()->prepare($request);
		$statement->execute([':user_id' => $user_id]);
		$fetchData = $statement->fetch(PDO::FETCH_ASSOC);

		ob_start();
		require_once __DIR__ . '/../views/profile.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';

	}

	/**
	 * Processes form submissions to update profile details or change the password.
	 */
	public function updateProfile() {
		Auth::requireLogin();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide.4");
		}
		$user = new Users();

		$user_id = $_SESSION['user_id'];

		// 1hndles profile updates
		if (isset($_POST['username'])) {
			$username = checkInput($_POST['username']);
			$email = checkInput($_POST['email']);
			$email_notifications = isset($_POST['notification']) ? 1 : 0;

			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				header('Location: /profile?error=invalid_email');
				exit();
			}

			// Ensures the new email isn't already taken by another user
			$stmt = $user->getConnection()->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
			$stmt->execute([':email' => $email, ':user_id' => $user_id]);
			if ($stmt->fetch()) {
				header('Location: /profile?error=email_taken');
				exit();
			}

			// Ensures the new username isn't already taken by ANOTHER user
			$stmtUser = $user->getConnection()->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
			$stmtUser->execute([':username' => $username, ':user_id' => $user_id]);
			if ($stmtUser->fetch()) {
				header('Location: /profile?error=username_taken');
				exit();
			}

			// Executes the update query
			$request = "UPDATE users SET username = :username, email = :email, email_notifications = :email_notifications WHERE id = :user_id";
			$statement = $user->getConnection()->prepare($request);
			$statement->execute([
				':username' => $username,
				':email' => $email,
				':email_notifications' => $email_notifications,
				':user_id' => $user_id
			]);

			header('Location: /profile?success=profile_updated');
			exit();
		}
		// handles password updates 
		elseif (isset($_POST['password']) && !empty($_POST['password'])) {
			$password = checkInput($_POST['password']);
			
			// Validate password
			if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password)) {
				header('Location: /profile?error=weak_password');
				exit();
			}

			// hash the new password
			$passwordHashed = password_hash($password, PASSWORD_ARGON2ID);
			
			// Update password field in the database
			$request = "UPDATE users SET password = :password WHERE id = :user_id";
			$statement = $user->getConnection()->prepare($request);
			$statement->execute([
				':password' => $passwordHashed,
				':user_id' => $user_id
			]);

			header('Location: /profile?success=profile_updated');
			exit();
		}

		header('Location: /profile');
		exit();
	}
}

/**
 * inputs against XSS and unnecessary spaces.
 */
function checkInput(string $data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}