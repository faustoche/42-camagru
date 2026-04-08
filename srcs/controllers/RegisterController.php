<?php

require_once __DIR__ . '/../models/User.php';

//TODO: AMELIORER LE RENVOIS D'ERREUR - REMPLACER TOUS LES ECHOS

class RegisterController {

	public function showForm(array $tab = []) {
		Auth::requireGuest();
		## Démarrage de la temporisation de sortie 
		## Mise en pause de l'affichage
		ob_start();

		## On charge la vue qu'on veut 
		require_once __DIR__ . '/../views/register.php';

		# Récupération du contenu mis en mémoire dans la variable $content
		## Nettoyage du tampon
		$content = ob_get_clean();

		## Appel du layout général qui va lire $content et l'afficher
		require_once __DIR__ . '/../views/layout.php';
	}

	public function processRegistration() {

		Auth::requireGuest();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			die("Erreur de sécurité CSRF : requête invalide.");
		}

		$errors = [];

		$user = new Users();

		## Vérification que les 3 champs sont bien présents avec la superglobale
		if (!empty($_POST['username'])) {
			$username = checkInput($_POST['username']);
			if ($user->isUsernameTaken($username)) {
				$errors['username-taken'] = "Username is already taken";
			}

		} else {
			$errors['username-required'] = "Username is required";
		}

		## Vérification que l'email est bien sous forme d'email
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

		## Vérification que le mdp fait 8 characters
		if (!empty($_POST['password'])) {
			$password = checkInput($_POST['password']);
			if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/', $password))
				$errors['invalid-password'] = "Invalid password";
		} else {
			$errors['password-required'] = "Password is required";
		}

		if (!empty($errors)) {
			$this->showForm($errors);
		} else {
			$passwordHashed = password_hash($password, PASSWORD_ARGON2ID);
			$randomToken = bin2hex(random_bytes('15'));
			$user->saveUser($username, $email, $passwordHashed, $randomToken);

			$appUrl = $_SERVER['HTTP_HOST'];

			$confirmationPath = "http://" . $appUrl . "/confirm?token=" . $randomToken;
			$emailSubject = "Welcome! Please, verify your account.";
			$emailMessage = "Click on the link to verify your account: " . $confirmationPath;

			mail($email, $emailSubject, $emailMessage);

			$_SESSION['pending_email'] = $email;
			header('Location: /verify-notice');
			exit();
		}
	}

	public function confirmAccount() {
		if (!isset($_GET['token'])) {
			header('Location: /');
			exit();
		}

		$token = $_GET['token'];
		$user = new Users();
		$db = $user->getConnection();

		$request = $db->prepare("SELECT id FROM users WHERE confirmation_token = :token AND confirmed = FALSE");
		$request->execute([':token' => $token]);
		$result = $request->fetch();

		if ($result) {
			$update = $db->prepare("UPDATE users SET confirmed = TRUE, confirmation_token = NULL WHERE confirmation_token = :token");
			$update->execute([':token' => $token]);
			header('Location: /login');
		} else {
			header('Location: /');
		}
		exit();
	}

	public function showVerifyNotice() {
		// Si la personne arrive ici sans être passée par l'inscription, on la renvoie au login
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

## htmlspecialchar pour éviter les injections XSS
function checkInput(string $data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}
