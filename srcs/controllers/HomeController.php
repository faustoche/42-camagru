<?php

require_once __DIR__ . "/../models/User.php";

/**
 * Home controller
 * Manages the main public gallery, infinite scrolling, and user interactions (likes, comments)
 */
class HomeController {
	
	/**
	 * Displays the main page (gallery) with infinite pagination.
	 */
	public function index() {

		$user = new Users();
		$perPage = 20;
		$currentPage = max(1, (int)($_GET['page'] ?? 1));
		$offset = ($currentPage - 1) * $perPage;

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

		// Starts output buffering to pause direct HTML output ////////////////////////////// WTF DOES IT MEANS
		ob_start();

		// Load the views
		require_once __DIR__ . '/../views/home.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';
	}

	/**
	 * Retrieves details of a specific image (author, date, likes, comments)
	 */
	public function getImageDetails() {

		$input = json_decode(file_get_contents('php://input'), true);
		$filename = $input['filename'];
		if (!$filename) exit();

		$user = new Users();
		$db = $user->getConnection();

		$req = $db->prepare("SELECT images.id, images.created_at, users.username AS author FROM images INNER JOIN users ON images.user_id = users.id WHERE images.filename = :filename");
		$req->execute([':filename' => $filename]);
		$imageData = $req->fetch(PDO::FETCH_ASSOC);
		if (!$imageData) exit();
		$imageId = $imageData['id'];
		$author = $imageData['author'];
		$date = $imageData['created_at'];

		// Counts the total number of likes for this image
		$reqLike = $db->prepare("SELECT COUNT(*) AS total FROM likes WHERE image_id = :image_id");
		$reqLike->execute([':image_id' => $imageId]);
		$likes = $reqLike->fetch(PDO::FETCH_ASSOC)['total'];

		// Checks if the logged user has already liked image
		$userLiked = false;
		if (isset($_SESSION['user_id'])) {
			$reqCheck = $db->prepare("SELECT 1 FROM likes WHERE image_id = :image_id AND user_id = :user_id");
			$reqCheck->execute([':image_id' => $imageId, ':user_id' => $_SESSION['user_id']]);
			if ($reqCheck->fetch()) {
				$userLiked = true;
			}
		}

		// Fetch comments associated with this image
		$reqCom = $db->prepare("SELECT comments.content, users.username FROM comments INNER JOIN users ON comments.user_id = users.id WHERE comments.image_id = :image_id ORDER BY comments.created_at ASC");
		$reqCom->execute([':image_id' => $imageId]);
		$comments = $reqCom->fetchAll(PDO::FETCH_ASSOC);

		// Returns datas as a JSON response to the frontend
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

	/**
	 * add or remove a like for logged user on a specific image
	 */
	public function toggleLike() {
		Auth::requireLogin();
		$input = json_decode(file_get_contents('php://input'), true);

		// Validate CSRF token to prevent attack
		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}
		$filename = $input['filename'];
		
		$user = new Users();
		$db = $user->getConnection();
		$userId = $_SESSION['user_id'];

		// Retrieve the image ID based on the provided filename
		$req = $db->prepare("SELECT id FROM images WHERE filename = :filename");
		$req->execute([':filename' => $filename]);
		$imageId = $req->fetch(PDO::FETCH_ASSOC)['id'];

		// Checks if the user has already liked the image
		$checkReq = $db->prepare("SELECT 1 FROM likes WHERE image_id = :image_id AND user_id = :user_id");
		$checkReq->execute([':image_id' => $imageId, ':user_id' => $userId]);

		if ($checkReq->fetch()) {
			// If a like exists, remove it (unlike)
			$del = $db->prepare("DELETE FROM likes WHERE image_id = :image_id AND user_id = :user_id");
			$del->execute([':image_id' => $imageId, ':user_id' => $userId]);
		} else {
			// If no like exists, add it
			$ins = $db->prepare("INSERT INTO likes (user_id, image_id) VALUES (:user_id, :image_id)");
			$ins->execute([':user_id' => $userId, ':image_id' => $imageId]);
		}

		header('Content-Type: application/json');
		echo json_encode(['status' => 'success']);
		exit();
	}

	/**
	 * add a comment to an image and notifies the owner via email
	 */
	public function addComment() {
		Auth::requireLogin();
		$input = json_decode(file_get_contents('php://input'), true);

		// CSRF validation
		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}

		$filename = $input['filename'];
		// prevent XSS attacks
		$content = htmlspecialchars($input['content']);
		
		$user = new Users();
		$db = $user->getConnection();
		
		// Retrieves the image ID
		$req = $db->prepare("SELECT id FROM images WHERE filename = :filename");
		$req->execute([':filename' => $filename]);
		$imageId = $req->fetch(PDO::FETCH_ASSOC)['id'];

		// Inserts the new comment into the database
		$ins = $db->prepare("INSERT INTO comments (user_id, image_id, content) VALUES (:user_id, :image_id, :content)");
		$ins->execute([
			':user_id' => $_SESSION['user_id'],
			':image_id' => $imageId,
			':content' => $content
		]);

		header('Content-Type: application/json');
		echo json_encode(['status' => 'success']);

		// Retrieves the image owner's preferences and email address
		$reqOwner = $db->prepare("
			SELECT users.id AS owner_id, users.email, users.email_notifications 
			FROM images 
			INNER JOIN users ON images.user_id = users.id 
			WHERE images.filename = :filename
		");
		$reqOwner->execute([':filename' => $filename]);
		$ownerData = $reqOwner->fetch(PDO::FETCH_ASSOC);

		if ($ownerData) {
			// If the owner has email notifications enabled & is not the one commenting
			if ($ownerData['email_notifications'] == 1 && $ownerData['owner_id'] != $_SESSION['user_id']) {
				
				// Prepare and send the notification email
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

	/**
	 * oads the next batch of images for infinite scrolling
	 */
	public function loadImageGallery() {
		$input = json_decode(file_get_contents('php://input'), true);
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