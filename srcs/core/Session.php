<?php

/*
Le protocole HTTP est fondamentalement "sans état" (stateless). Cela signifie que chaque requête envoyée par 
le navigateur au serveur est traitée de manière totalement indépendante. 
Le serveur n'a aucune mémoire des requêtes précédentes : 
il ne peut pas savoir, par défaut, si l'utilisateur qui demande la page B est le même que 
celui qui vient de s'authentifier sur la page A.

Le mécanisme de session résout ce problème structurel :
Il permet de stocker des variables (les données de l'utilisateur) de manière sécurisée côté serveur.
Il génère un identifiant unique de session, qui est transmis au navigateur (généralement via un cookie).
Lors des requêtes suivantes, le navigateur renvoie cet identifiant, permettant au serveur de retrouver les variables associées et de "reconnaître" l'utilisateur.
*/

/**
 * Session Management class
 * HTTP is stateless to store user data across requests and manage CSRF tokens
 */

class Session {

	## La gestion des sessions représente une fonctionnalité globale 
	## au sein de l'application. Instancier un nouvel objet Session 
	## à chaque lecture ou écriture de donnée serait redondant et inefficace. 
	## L'utilisation de méthodes statiques permet d'invoquer ces fonctions 
	## directement depuis n'importe quel emplacement de l'architecture 
	## (routeur, contrôleur, etc.) en agissant comme une boîte à outils utilitaire globale.

	/**
	 * Starting PHP session if not already active
	 */
	public static function start() {

		$status = session_status();
		if ($status == PHP_SESSION_NONE) {
			session_start();
		}
	}

	/**
	 * Store a value in $SESSION array under the specified key
	 */
	public static function set(string $key, $value) {
		$_SESSION[$key] = $value;
	}

	/**
	 * Retrieve value fron SESSION array
	 * return value if it exists
	 */
	public static function get(string $key) {
		return $_SESSION[$key] ?? null;
	}

	public static function destroy() {
		session_destroy();
	}

	/**
	 * Generate a secure CSRF token
	 * Stored in the session
	 */
	public static function generateCsrfToken() {
		if (empty($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}

		return $_SESSION['csrf_token'];
	}

	/**
	 * Validate CSRF token against the one stored in the session
	 */
	public static function validateCsrfToken($token) {
		if (empty($_SESSION['csrf_token']) || empty($token)) {
			return false;
		}
		return hash_equals($_SESSION['csrf_token'], $token);
	}
}