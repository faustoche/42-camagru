<?php

require_once __DIR__ . "/../models/User.php";

class StudioController {

	public function showStudio() {

		Auth::requireLogin();

		$stickerDir = scandir(__DIR__ . "/../public/stickers");

		$stickers = array_filter($stickerDir, fn($elem) => str_ends_with($elem, '.png'));


		$user = new Users();
		$user_id = $_SESSION['user_id'];
		$request = "SELECT filename FROM images WHERE user_id = :user_id ORDER BY created_at DESC";
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
			
			// Traduction du texte JSON en tableau exploitable par PHP
			$stickers = json_decode($stickersJson, true);

			// La boucle de montage : on répète l'action pour chaque élément
			if (is_array($stickers)) {
				foreach ($stickers as $sticker) {
					// Par sécurité, on extrait uniquement le nom final du fichier avec basename()
					$stickerPath = __DIR__ . '/../public/stickers/' . basename($sticker['src']);
					
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
}