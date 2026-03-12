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

	public function getImageDetails($filename) {

		//? RÉCUPÉRATION DE FILENAME

		$input = json_decode(file_get_contents('php://input'), true);
		$filename = $input['filename'];
		$user = new Users();
		$db = $user->getConnection();


		//? REQUEST POUR RÉCUPÉRER L'ID

		$imageRequest = $db->prepare("SELECT id FROM images WHERE filename = :filename");
		$imageRequest->execute([':filename' => $filename]);
		$imageData = $imageRequest->fetch(PDO::FETCH_ASSOC);
		$imageId = $imageData['id'];

		//? REQUEST POUR COMPTER LE NOMBRE DE LIKES

		$likeRequest = $db->prepare("SELECT COUNT(*) AS total_likes FROM likes WHERE image_id = :image_id");
		$likeRequest->execute([':image_id' => $imageId]);
		$likesData = $likeRequest->fetch(PDO::FETCH_ASSOC);
		$totalLikes = $likesData['total_likes'];

		//? REQUEST POUR RÉCUPÉRER LES COMMENTAIRES

		$commentRequest = $db->prepare("
				SELECT comments.content, comments.created_at, users.username
				FROM comments
				INNER JOIN users ON comments.user_id = users.id
				WHERE comments.image_id = :image_id
				ORDER BY comments.created_at ASC
		");

		$commentRequest->execute([':image_id' => $imageId]);
		$allComments = $commentRequest->fetchAll(PDO::FETCH_ASSOC);

		//? ENVOIS DES 2 DANS MON JSON

		echo json_encode([
			'likes' => $totalLikes,
			'comments' => $allComments
		]);
		
		exit();
	}
}