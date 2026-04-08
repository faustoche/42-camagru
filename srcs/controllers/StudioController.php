<?php

require_once __DIR__ . "/../models/User.php";

class StudioController {

	public function showStudio() {

		Auth::requireLogin();

		$stickerDir = scandir(__DIR__ . "/../public/stickers");

		$stickers = array_filter($stickerDir, fn($elem) => str_ends_with($elem, '.png'));

		$filterPath = __DIR__ . "/../public/filters";
		$filters = [];
		if (is_dir($filterPath)) {
			$filterDir = scandir($filterPath);
			$filters = array_filter($filterDir, fn($elem) => str_ends_with($elem, '.png'));
		}


		$user = new Users();
		$user_id = $_SESSION['user_id'];
		$request = "SELECT filename, is_published FROM images WHERE user_id = :user_id ORDER BY created_at DESC";
		$statement = $user->getConnection()->prepare($request);
		$statement->execute([':user_id' => $user_id]);

		$userImages = $statement->fetchAll(PDO::FETCH_ASSOC);

		ob_start();
		require_once __DIR__ . '/../views/studio.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';


	}

	public function processCapture() {

		Auth::requireLogin();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}

		// On vérifie le nouveau champ de données (stickers_data)
		if (isset($_POST['image_data']) && isset($_POST['stickers_data'])) {

			$imageData = $_POST['image_data'];
			$stickersJson = $_POST['stickers_data'];

			if (empty($imageData)) {
				echo json_encode(['status' => 'error', 'message' => 'Image data is missing']);
				exit();
			}

			// Préparation de la toile de fond (webcam)
			$cleanData = str_replace('data:image/png;base64,', '', $imageData);
			$result = base64_decode($cleanData);
			$imageName = uniqid('image') . ".png";
			$imagePath = __DIR__ . '/../public/uploads/' . $imageName;

			$baseImage = imagecreatefromstring($result);
			if ($baseImage === false) {
				header('Content-Type: application/json');
				echo json_encode(['status' => 'error', 'message' => 'Invalid image format.']);
				exit();
			}
			
			// Traduction du texte JSON en tableau exploitable par PHP
			$stickers = json_decode($stickersJson, true);

			// La boucle de montage : on répète l'action pour chaque élément
			if (is_array($stickers)) {
				foreach ($stickers as $sticker) {
					// Par sécurité, on extrait uniquement le nom final du fichier avec basename()
					$safeRelativePath = str_replace(['../', '..\\'], '', $sticker['src']);
                    $stickerPath = __DIR__ . '/../public/stickers/' . $safeRelativePath;
					
					if (file_exists($stickerPath)) {
						$stickerImage = imagecreatefrompng($stickerPath);
						
						imagecopyresampled(
							$baseImage, 
							$stickerImage, 
							$sticker['x'], $sticker['y'], 
							0, 0, 
							$sticker['width'], $sticker['height'], 
							imagesx($stickerImage), imagesy($stickerImage)
						);
						
						imagedestroy($stickerImage);
					}
				}
			}

			// Sauvegarde finale sur le disque
			$isSaved = imagepng($baseImage, $imagePath);

			$user = new Users();
			$user_id = $_SESSION['user_id'];

			// Création d'une requête SQL pour lier l'image à l'user
			$request = "INSERT INTO images (user_id, filename) VALUES (:user_id, :imageName)";

			$statement = $user->getConnection()->prepare($request);
			$statement->execute([':user_id' => $user_id, ':imageName' => $imageName]);

			imagedestroy($baseImage);
			
			// Réponse propre au navigateur
			$response = ['status' => 'success', 'saved' => $isSaved, 'fileName' => $imageName];
			header('Content-Type: application/json');
			echo json_encode($response);
			exit();
		}

		echo json_encode(['status' => 'error', 'message' => 'Missing data']);
		exit();
	}

	public function deleteCapture() {
		Auth::requireLogin();

		$user = new Users();

		// Lecture du json entrant
		$input = json_decode(file_get_contents('php://input'), true);

		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}
		$filename = $input['filename'];
		if (empty($filename)) {
			exit();
		}

		$request = "SELECT user_id FROM images WHERE filename = :filename";
		$statement = $user->getConnection()->prepare($request);
		$statement->execute([':filename' => $filename]);

		$fetchData = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$fetchData || $fetchData['user_id'] != $_SESSION['user_id']) {
			exit();
		} else {
			$imagePath = __DIR__ . '/../public/uploads/' . $filename;
			if (file_exists($imagePath)) {
				unlink($imagePath);
			}

			$deleteRequest = "DELETE FROM images WHERE filename = :filename";
			$statement = $user->getConnection()->prepare($deleteRequest);
			$statement->execute([':filename' => $filename]);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}
	}

	public function publishCapture() {
		Auth::requireLogin();

		$user = new Users();
		// Lecture du json entrant
		$input = json_decode(file_get_contents('php://input'), true);
		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}
		$filename = $input['filename'];
		if (empty($filename)) {
			exit();
		}

		$request = "SELECT user_id FROM images WHERE filename = :filename";
		$statement = $user->getConnection()->prepare($request);
		$statement->execute([':filename' => $filename]);

		$fetchData = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$fetchData || $fetchData['user_id'] != $_SESSION['user_id']) {
			exit();
		} else {
			$publishRequest = "UPDATE images SET is_published = TRUE WHERE filename = :filename";
			$statement = $user->getConnection()->prepare($publishRequest);
			$statement->execute([':filename' => $filename]);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}
	}

	public function unpublishCapture() {
		Auth::requireLogin();

		$user = new Users();
		// Lecture du json entrant
		$input = json_decode(file_get_contents('php://input'), true);
		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}
		$filename = $input['filename'];
		if (empty($filename)) {
			exit();
		}

		$request = "SELECT user_id FROM images WHERE filename = :filename";
		$statement = $user->getConnection()->prepare($request);
		$statement->execute([':filename' => $filename]);

		$fetchData = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$fetchData || $fetchData['user_id'] != $_SESSION['user_id']) {
			exit();
		} else {
			$publishRequest = "UPDATE images SET is_published = FALSE WHERE filename = :filename";
			$statement = $user->getConnection()->prepare($publishRequest);
			$statement->execute([':filename' => $filename]);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}
	}
}