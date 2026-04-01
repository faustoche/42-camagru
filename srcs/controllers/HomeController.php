<?php

require_once __DIR__ . "/../models/User.php";

class HomeController {
	public function index() {

		$user = new Users();

		$perPage = 20;
		$currentPage = max(1, (int)($_GET['page'] ?? 1));
		$offset = ($currentPage - 1) * $perPage;

		// Compte total
		// $countStmt = $user->getConnection()->query("SELECT COUNT(*) FROM images WHERE is_published = TRUE");
		// $totalImages = (int)$countStmt->fetchColumn();
		// $totalPages = max(1, (int)ceil($totalImages / $perPage));

		$request = "SELECT images.id, images.filename, users.username, COUNT(likes.user_id) AS likes 
					FROM images 
					INNER JOIN users ON images.user_id = users.id 
					LEFT JOIN likes ON images.id = likes.image_id 
					WHERE images.is_published = TRUE 
					GROUP BY images.id 
					ORDER BY images.created_at DESC
					LIMIT :limit OFFSET :offset";

		$statement = $user->getConnection()->prepare($request);
		$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
		$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
		$statement->execute();
		$images = $statement->fetchAll(PDO::FETCH_ASSOC);

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

	public function getImageDetails() {
		$input = json_decode(file_get_contents('php://input'), true);
		$filename = $input['filename'];
		if (!$filename) exit();

		$user = new Users();
		$db = $user->getConnection();

		// 1. Trouver l'ID de l'image
		$req = $db->prepare("SELECT images.id, images.created_at, users.username AS author FROM images INNER JOIN users ON images.user_id = users.id WHERE images.filename = :filename");
		$req->execute([':filename' => $filename]);
		$imageData = $req->fetch(PDO::FETCH_ASSOC);
		if (!$imageData) exit();
		$imageId = $imageData['id'];
		$author = $imageData['author'];
		$date = $imageData['created_at'];

		// 2. Compter les likes
		$reqLike = $db->prepare("SELECT COUNT(*) AS total FROM likes WHERE image_id = :image_id");
		$reqLike->execute([':image_id' => $imageId]);
		$likes = $reqLike->fetch(PDO::FETCH_ASSOC)['total'];

		// 3. Savoir si l'utilisateur connecté a liké (pour afficher le bon coeur)
		$userLiked = false;
		if (isset($_SESSION['user_id'])) {
			$reqCheck = $db->prepare("SELECT 1 FROM likes WHERE image_id = :image_id AND user_id = :user_id");
			$reqCheck->execute([':image_id' => $imageId, ':user_id' => $_SESSION['user_id']]);
			if ($reqCheck->fetch()) {
				$userLiked = true;
			}
		}

		// 4. Récupérer les commentaires
		$reqCom = $db->prepare("SELECT comments.content, users.username FROM comments INNER JOIN users ON comments.user_id = users.id WHERE comments.image_id = :image_id ORDER BY comments.created_at ASC");
		$reqCom->execute([':image_id' => $imageId]);
		$comments = $reqCom->fetchAll(PDO::FETCH_ASSOC);

		header('Content-Type: application/json');
		echo json_encode([
			'likes' => $likes, 
			'comments' => $comments,
			'user_liked' => $userLiked,
			'author' => $author,
			'date' => $date
		]);
		exit();
	}

	public function toggleLike() {
		Auth::requireLogin();
		$input = json_decode(file_get_contents('php://input'), true);

		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}
		$filename = $input['filename'];
		
		$user = new Users();
		$db = $user->getConnection();
		$userId = $_SESSION['user_id'];

		$req = $db->prepare("SELECT id FROM images WHERE filename = :filename");
		$req->execute([':filename' => $filename]);
		$imageId = $req->fetch(PDO::FETCH_ASSOC)['id'];

		$checkReq = $db->prepare("SELECT 1 FROM likes WHERE image_id = :image_id AND user_id = :user_id");
		$checkReq->execute([':image_id' => $imageId, ':user_id' => $userId]);

		if ($checkReq->fetch()) {
			// Il y a un like, on le supprime
			$del = $db->prepare("DELETE FROM likes WHERE image_id = :image_id AND user_id = :user_id");
			$del->execute([':image_id' => $imageId, ':user_id' => $userId]);
		} else {
			// Pas de like, on l'ajoute
			$ins = $db->prepare("INSERT INTO likes (user_id, image_id) VALUES (:user_id, :image_id)");
			$ins->execute([':user_id' => $userId, ':image_id' => $imageId]);
		}

		header('Content-Type: application/json');
		echo json_encode(['status' => 'success']);
		exit();
	}

	public function addComment() {
		Auth::requireLogin();
		$input = json_decode(file_get_contents('php://input'), true);

		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}

		$filename = $input['filename'];
		$content = htmlspecialchars($input['content']); // Sécurité de base
		
		$user = new Users();
		$db = $user->getConnection();
		
		$req = $db->prepare("SELECT id FROM images WHERE filename = :filename");
		$req->execute([':filename' => $filename]);
		$imageId = $req->fetch(PDO::FETCH_ASSOC)['id'];

		$ins = $db->prepare("INSERT INTO comments (user_id, image_id, content) VALUES (:user_id, :image_id, :content)");
		$ins->execute([
			':user_id' => $_SESSION['user_id'],
			':image_id' => $imageId,
			':content' => $content
		]);

		header('Content-Type: application/json');
		echo json_encode(['status' => 'success']);

		$reqOwner = $db->prepare("
			SELECT users.id AS owner_id, users.email, users.email_notifications 
			FROM images 
			INNER JOIN users ON images.user_id = users.id 
			WHERE images.filename = :filename
		");
		$reqOwner->execute([':filename' => $filename]);
		$ownerData = $reqOwner->fetch(PDO::FETCH_ASSOC);

		if ($ownerData) {

			if ($ownerData['email_notifications'] == 1 && $ownerData['owner_id'] != $_SESSION['user_id']) {
				
				$to = $ownerData['email'];
				$subject = "Camagru - You just received a new comment !";

				$message = "Hello,\n\n";
				$message .= "A user just left a comment on your post.\n";
				$message .= "Go on Camagru to see it!\n\n";
				$message .= "Team Camagru.";
				
				$headers = "From: no-reply@camagru.com\r\n";
				$headers .= "Reply-To: no-reply@camagru.com\r\n";
				$headers .= "X-Mailer: PHP/" . phpversion();

				mail($to, $subject, $message, $headers);
			}
		}
		exit();
	}

	public function loadImageGallery() {
		$input = json_decode(file_get_contents('php://input'), true);

		// chargeement de 10 images par default pour le scroll -> est-ce que on passe a 20?
		$perPage = 20;
		$currentPage = max(1, (int)($input['page'] ?? 1));
		$offset = ($currentPage - 1) * $perPage;

		$user = new Users();

		$request = "SELECT images.id, images.filename, users.username, COUNT(likes.user_id) AS likes 
                FROM images 
                INNER JOIN users ON images.user_id = users.id 
                LEFT JOIN likes ON images.id = likes.image_id 
                WHERE images.is_published = TRUE 
                GROUP BY images.id 
                ORDER BY images.created_at DESC
                LIMIT :limit OFFSET :offset";
		
		$statement = $user->getConnection()->prepare($request);
		$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
		$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
		$statement->execute();

		$images = $statement->fetchAll(PDO::FETCH_ASSOC);
		header('Content-Type: application/json');
		echo json_encode(['images' => $images]);
		exit();
	}
}