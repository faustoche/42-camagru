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

			// Préparation de la webcam
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
						
						$angle = isset($sticker['angle']) ? floatval($sticker['angle']) : 0;
                        
                        if ($angle != 0) {
                            // Création d'une couleur transparente pour combler les "vides" créés par la rotation
                            $transparent = imagecolorallocatealpha($stickerImage, 0, 0, 0, 127);
                            
                            // Rotation de l'image (l'angle est inversé pour correspondre au CSS)
                            $rotatedImage = imagerotate($stickerImage, -$angle, $transparent);
                            
                            // On remplace l'image d'origine par la version pivotée en mémoire
							$stickerImage = null;
                            //imagedestroy($stickerImage);
                            $stickerImage = $rotatedImage;
                            
                            // Préparation de la transparence pour la fusion
                            imagealphablending($stickerImage, true);
                            imagesavealpha($stickerImage, true);
                        }


						$dst_x = floatval($sticker['x']);
                        $dst_y = floatval($sticker['y']);
                        $dst_w = floatval($sticker['width']);
                        $dst_h = floatval($sticker['height']);

                        // 🔴 SÉCURITÉ : On ignore le sticker s'il n'a pas de dimension 
                        // pour éviter une erreur fatale de division par zéro
                        if ($dst_w <= 0 || $dst_h <= 0) {
                            continue;
                        }

                        $src_w_full = imagesx($stickerImage);
                        $src_h_full = imagesy($stickerImage);

                        $src_x = 0;
                        $src_y = 0;
                        $src_w = $src_w_full;
                        $src_h = $src_h_full;

                        $canvas_w = imagesx($baseImage);
                        $canvas_h = imagesy($baseImage);

                        // Ratios pour conserver les proportions si on coupe
                        $ratio_x = $src_w_full / $dst_w;
                        $ratio_y = $src_h_full / $dst_h;

                        // 1. Rognage si ça déborde à gauche
                        if ($dst_x < 0) {
                            $crop_w_dst = abs($dst_x);
                            $crop_w_src = $crop_w_dst * $ratio_x;
                            $src_x += $crop_w_src;
                            $src_w -= $crop_w_src;
                            $dst_w -= $crop_w_dst;
                            $dst_x = 0;
                        }

                        // 2. Rognage si ça déborde en haut
                        if ($dst_y < 0) {
                            $crop_h_dst = abs($dst_y);
                            $crop_h_src = $crop_h_dst * $ratio_y;
                            $src_y += $crop_h_src;
                            $src_h -= $crop_h_src;
                            $dst_h -= $crop_h_dst;
                            $dst_y = 0;
                        }

                        // 3. Rognage si ça déborde à droite
                        if ($dst_x + $dst_w > $canvas_w) {
                            $overflow_dst = ($dst_x + $dst_w) - $canvas_w;
                            $overflow_src = $overflow_dst * $ratio_x;
                            $src_w -= $overflow_src;
                            $dst_w -= $overflow_dst;
                        }

                        // 4. Rognage si ça déborde en bas
                        if ($dst_y + $dst_h > $canvas_h) {
                            $overflow_dst = ($dst_y + $dst_h) - $canvas_h;
                            $overflow_src = $overflow_dst * $ratio_y;
                            $src_h -= $overflow_src;
                            $dst_h -= $overflow_dst;
                        }

                        if ($dst_w > 0 && $dst_h > 0 && $src_w > 0 && $src_h > 0) {
                            imagecopyresampled(
                                $baseImage, 
                                $stickerImage, 
                                (int)round($dst_x), (int)round($dst_y), 
                                (int)round($src_x), (int)round($src_y), 
                                (int)round($dst_w), (int)round($dst_h), 
                                (int)round($src_w), (int)round($src_h)
                            );
                        }

						
						$stickerImage = null;
						//unset($stickerImage);
						//imagedestroy($stickerImage);
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

			$baseImage = null;
			//unset($baseImage);
			//imagedestroy($baseImage);
			
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