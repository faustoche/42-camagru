<?php

require_once __DIR__ . '/../core/Database.php';

## Construction du système d'inscription

class Users {

	private $pdoConnection;

	function __construct() {
		$db = new Database();
		$this->pdoConnection = $db->getConnection();
	}

	public function getConnection() { return $this->pdoConnection; }

	public function isUsernameTaken(string $username) {
		## where email = :email protége contre les injections SQL
		## On sépare la requête des valeurs
		$request = 'SELECT id FROM users WHERE username = :username';
		
		## On utilise prepare() car on a nos variables dans la requête
		$statement = $this->pdoConnection->prepare($request);

		## On dit à PDO par quoi remplacer email et username
		## via notre tableau associatif.
		## Notre PDA va nettoyer les variables et les insérer dans notre requête
		$statement->execute([':username' => $username]);

		## On fetch sur notre variable statement
		## Fetch va aller chercher la premiére ligne de résultat trouvée par la requête
		## Si fetch trouve qqchose -> l'user ou l'email existe déjà alors return true
		## Sinon return false

		$result = $statement->fetch();
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	public function isEmailTaken(string $email) {
		## where email = :email protége contre les injections SQL
		## On sépare la requête des valeurs
		$request = 'SELECT id FROM users WHERE email = :email';
		
		## On utilise prepare() car on a nos variables dans la requête
		$statement = $this->pdoConnection->prepare($request);

		## On dit à PDO par quoi remplacer email et username
		## via notre tableau associatif.
		## Notre PDA va nettoyer les variables et les insérer dans notre requête
		$statement->execute([':email' => $email]);

		## On fetch sur notre variable statement
		## Fetch va aller chercher la premiére ligne de résultat trouvée par la requête
		## Si fetch trouve qqchose -> l'user ou l'email existe déjà alors return true
		## Sinon return false

		$result = $statement->fetch();
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	public function saveUser(string $username, string $email, string $password, string $confirmationToken) {
		
		## Insertion du user dans la table
		$request = 'INSERT INTO users (username, email, password, confirmation_token) VALUES (:username, :email, :password, :confirmationToken)';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':username' => $username, ':email' => $email, ':password' => $password, ':confirmationToken' => $confirmationToken]);
	}

	public function findUserByEmail(string $email) {

		$request = 'SELECT email FROM users WHERE email = :email';
		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':email' => $email]);

		$result = $statement->fetch();
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	public function saveResetToken(string $email, $token, $expiration) {
		$request = 'UPDATE users SET reset_token = :token, reset_token_expires_at = :expiration WHERE email = :email';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':token' => $token, ':email' => $email, ':expiration' => $expiration]);
	}

	public function isValidRequestToken($token) {
		$request = 'SELECT reset_token FROM users WHERE reset_token = :reset_token AND reset_token_expires_at > NOW()';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':reset_token' => $token]);

		$result = $statement->fetch();
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	public function updatePasswordWithToken($token, $hashedPassword) {
		$request = 'UPDATE users SET password = :hashedPassword, reset_token_expires_at = NULL, reset_token = :token';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':hashedPassword' => $hashedPassword, ':token' => $token]);
	}

	public function getUserByEmail($email) {
		$request = 'SELECT id, password FROM users WHERE email = :email';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':email' => $email]);

		return $statement->fetch();
	}
}