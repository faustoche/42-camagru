<?php

require_once __DIR__ . "/../models/User.php";

class HomeController {
	public function index() {

		$user = new Users();

		$request = "SELECT images.id, images.filename, users.username, COUNT(likes.user_id) AS likes 
					FROM images 
					INNER JOIN users ON images.user_id = users.id 
					LEFT JOIN likes ON images.id = likes.image_id 
					WHERE images.is_published = TRUE 
					GROUP BY images.id 
					ORDER BY images.created_at DESC";

		$statement = $user->getConnection()->prepare($request);
		$statement->execute();

		$images = $statement->fetchAll(PDO::FETCH_ASSOC);

		$currentPage = 1;
		$totalPages = 1;
		$totalImages = count($images);

		
		## Démarrage de la temporisation de sortie 
		## Mise en pause de l'affichage
		ob_start();

		## On charge la vue qu'on veut 
		require_once __DIR__ . '/../views/home.php';

		# Récupération du contenu mis en mémoire dans la variable $content
		## Nettoyage du tampon
		$content = ob_get_clean();

		## Appel du layout général qui va lire $content et l'afficher
		require_once __DIR__ . '/../views/layout.php';
	}
}