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
			if (preg_match('/[^A-Za-z0-9]+/', $password) || strlen($password) < 8)
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

			header('Location: /');
			exit();
		}
	}
}

## htmlspecialchar pour éviter les injections XSS
function checkInput(string $data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}
