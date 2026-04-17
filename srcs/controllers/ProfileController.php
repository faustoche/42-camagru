<?php

require_once __DIR__ . "/../models/User.php";

class ProfileController {
	
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

	public function updateProfile() {
		Auth::requireLogin();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide.4");
		}
		$user = new Users();

		$user_id = $_SESSION['user_id'];

		if (isset($_POST['username'])) {
			$username = checkInput($_POST['username']);
			$email = checkInput($_POST['email']);
			$email_notifications = isset($_POST['notification']) ? 1 : 0;


			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				header('Location: /profile?error=invalid_email');
				exit();
			}

			$stmt = $user->getConnection()->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
			$stmt->execute([':email' => $email, ':user_id' => $user_id]);
			if ($stmt->fetch()) {
				header('Location: /profile?error=email_taken');
				exit();
			}

			$stmtUser = $user->getConnection()->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
			$stmtUser->execute([':username' => $username, ':user_id' => $user_id]);
			if ($stmtUser->fetch()) {
				header('Location: /profile?error=username_taken');
				exit();
			}

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
		elseif (isset($_POST['password']) && !empty($_POST['password'])) {
			$password = checkInput($_POST['password']);
			
			if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password)) {
				header('Location: /profile?error=weak_password');
				exit();
			}

			$passwordHashed = password_hash($password, PASSWORD_ARGON2ID);
			
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

function checkInput(string $data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}
