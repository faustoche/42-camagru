<?php

## Mise en place du PDO (PHP Data Objects)
## On fait la liaison entre PHP et MariaDB car ils parlent pas la même langue
## PDO est une interface native de PHP, qui va servir de pont
## Il va permettre de lutter contre les injections SQL. 
## Les requêtes sont préparées, au lieu d'insérer les mots du user dans la requête, 
## le PDO envoie d'abord la structure de la requête à la base de données
## puis il envoie ensuite les données du user séparement 
## On doit récupérer les valeurs des données de notre .env 
## PHP peut lire les variables nativement

/**
 * Database connection class
 * PDO gives an interface to prevent SQL injections
 * Connection to MariaDB
 */

class Database {

	// Storing PDO instance
	private $pdo;

	function __construct() {

		// Retrieve database credentials from env variables
		$host = getenv('DB_HOST');
		$name = getenv('DB_NAME');
		$port = getenv('DB_PORT');
		$user = getenv('DB_USER');
		$pwd = getenv('DB_PASS');

		// Change DNS format
		$dsn = 'mysql:' . 'host=' . $host . ';dbname=' . $name . ';port=' . $port;

		// PDO security and data handling.
		// Throw exceptions for SQL errors and fetch results
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		];
		
		// Prevent fatal errors and credentials leaks
		try {

			// Creating new PDO instance
			$this->pdo = new PDO($dsn, $user, $pwd, $options);

		} catch (PDOException $error) {
			echo 'Error connecting on database';
			die();
		}
	}

	/**
	 * Returns active PDO connection object
	 */
	public function getConnection() {
		return $this->pdo;
	}
}