<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * User model
 * Handles database operations for user registration, authentication, and management
 */
class Users {

	private $pdoConnection;

	function __construct() {
		$db = new Database();
		$this->pdoConnection = $db->getConnection();
	}

	/**
	 * Returns the current PDO connection object
	 */
	public function getConnection() { return $this->pdoConnection; }

	/**
	 * Checks if a given username already exists in the database
	 */
	public function isUsernameTaken(string $username) {
		// Using named parameters prevents SQL injection 
		$request = 'SELECT id FROM users WHERE username = :username';
		
		// prepare() is used to insert variable into query
		$statement = $this->pdoConnection->prepare($request);

		// execute() binds $username value to :username placeholder
		$statement->execute([':username' => $username]);

		// fetch() retrieves the first matching row found by the query
		$result = $statement->fetch();
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if a given email address already exists in the database
	 */
	public function isEmailTaken(string $email) {

		$request = 'SELECT id FROM users WHERE email = :email';
		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':email' => $email]);

		$result = $statement->fetch();
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Inserts new registered user into database
	 */
	public function saveUser(string $username, string $email, string $password, string $confirmationToken) {
		
		$request = 'INSERT INTO users (username, email, password, confirmation_token) VALUES (:username, :email, :password, :confirmationToken)';
		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':username' => $username, ':email' => $email, ':password' => $password, ':confirmationToken' => $confirmationToken]);
	}

	/**
	 * Checks if a user exists by their email
	 */
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

	/**
	 * Save token and expiration date for password recovery
	 */
	public function saveResetToken(string $email, $token, $expiration) {
		// Update user with the new generated token and expiration time
		$request = 'UPDATE users SET reset_token = :token, reset_token_expires_at = :expiration WHERE email = :email';

		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':token' => $token, ':email' => $email, ':expiration' => $expiration]);
	}

	/**
	 * Validate a password reset token
	 */
	public function isValidRequestToken($token) {
		// Check if the token exists & if the current time (now) is before the expiration time
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

	/**
	 * Update user's password and clear recovery tokens
	 */
	public function updatePasswordWithToken($token, $hashedPassword) {
		$request = 'UPDATE users SET password = :hashedPassword, reset_token_expires_at = NULL, reset_token = NULL WHERE reset_token = :token';
		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':hashedPassword' => $hashedPassword, ':token' => $token]);
	}

	/**
	 * Retrieves specific user credentials needed for the login process
	 */
	public function getUserByEmail($email) {
		$request = 'SELECT id, password, confirmed FROM users WHERE email = :email';
		$statement = $this->pdoConnection->prepare($request);
		$statement->execute([':email' => $email]);
		
		return $statement->fetch();
	}
}