<?php

## Construction du système d'inscription

class Users {

	private $pdoConnection;

	function __construct() {
		$db = new Database();
		$this->pdoConnection = $db->getConnection();
	}

	public function isUserOrEmailTaken(string $email, string $username) {
		## where email = :email protége contre les injections SQL
		## On sépare la requête des valeurs
		$request = 'SELECT id FROM users WHERE email = :email OR username = :username';
		
		## On utilise prepare() car on a nos variables dans la requête
		$statement = $this->pdoConnection->prepare($request);

		## On dit à PDO par quoi remplacer email et username
		## via notre tableau associatif.
		## Notre PDA va nettoyer les variables et les insérer dans notre requête
		$statement->execute([':email' => $email, ':username' => $username]);

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

	public function saveUser(string $username, string $email, string $password) {
		
		$passwordHashed = password_hash($password, PASSWORD_ARGON2ID);
		## Insertion du user dans la table
		$request = 'INSERT INTO users VALUES (username = :username, email = :email, password = :password)';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':username' => $username, ':email' => $email, ':password' => $passwordHashed]);

		## On fetch sur notre variable statement
		## Fetch va aller chercher la premiére ligne de résultat trouvée par la requête
		## Si fetch trouve qqchose -> l'user ou l'email existe déjà alors return true
		## Sinon return false
		$result = $statement->fetch();
		if ($result) {
			echo "Cannot save user in database";
			return true;
		} else {
			return false;
		}
	}
}