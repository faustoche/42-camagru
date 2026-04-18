<?php

require_once __DIR__ . "/../models/User.php";

/**
 * Studio controller
 * Handles image composition (webcam + stickers) entirely on the server side using the GD library ///// gd libray autorisee2e?
 */
class StudioController {

	/**
	 * Load assets and displays the studio interface
	 */
	public function showStudio() {

		Auth::requireLogin();

		// check the public directory to find available stickers
		$stickerDir = scandir(__DIR__ . "/../public/stickers");
		$stickers = array_filter($stickerDir, fn($elem) => str_ends_with($elem, '.png'));

		// loads specific face filters if the directory exists
		$filterPath = __DIR__ . "/../public/filters";
		$filters = [];
		if (is_dir($filterPath)) {
			$filterDir = scandir($filterPath);
			$filters = array_filter($filterDir, fn($elem) => str_ends_with($elem, '.png'));
		}

		// Fetch all images previously created by the currently logged user
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

	/**
	 * server-side image processing method.
	 *  base64 webcam capture and merges it with the selected stickers via GD
	 */
	public function processCapture() {
		ob_start();
		Auth::requireLogin();
		if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
			ob_clean();
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}

		// Verify the required data fields
		if (isset($_POST['image_data']) && isset($_POST['stickers_data'])) {

			$imageData = $_POST['image_data'];
			$stickersJson = $_POST['stickers_data'];

			if (empty($imageData)) {
				ob_clean();
				echo json_encode(['status' => 'error', 'message' => 'Image data is missing']);
				exit();
			}

			// Webcam base image preparation
			// base64 string, decodes it, and generates a unique filename
			$cleanData = str_replace('data:image/png;base64,', '', $imageData);
			$result = base64_decode($cleanData);
			$imageName = uniqid('image') . ".png";
			$imagePath = __DIR__ . '/../public/uploads/' . $imageName;

			// Creat the image resource from the decoded string
			$baseImage = imagecreatefromstring($result);
			if ($baseImage === false) {
				header('Content-Type: application/json');
				ob_clean();
				echo json_encode(['status' => 'error', 'message' => 'Invalid image format.']);
				exit();
			}
			
			$stickers = json_decode($stickersJson, true);

			//repeats the merging action for each submitted sticker
			if (is_array($stickers)) {
				foreach ($stickers as $sticker) {
					// Extracts the actual filename using str_replace to preven
					$safeRelativePath = str_replace(['../', '..\\'], '', $sticker['src']);
					$stickerPath = __DIR__ . '/../public/stickers/' . $safeRelativePath;
					
					if (file_exists($stickerPath)) {
						$stickerImage = imagecreatefrompng($stickerPath);
						
						// Check if the sticker requires rotation
						$angle = isset($sticker['angle']) ? floatval($sticker['angle']) : 0;
						
						if ($angle != 0) {
							// Create a transparent color 
							$transparent = imagecolorallocatealpha($stickerImage, 0, 0, 0, 127);
							
							// Rotate the image
							$rotatedImage = imagerotate($stickerImage, -$angle, $transparent);
							
							// Replace the original image resource with the rotated one
							$stickerImage = null;
							$stickerImage = $rotatedImage;
							
							// Prepares the alpha channel for blending transparency //////////////////////wtf
							imagealphablending($stickerImage, true);
							imagesavealpha($stickerImage, true);
						}

						//  coordinates and dimensions on the base image
						$dst_x = floatval($sticker['x']);
						$dst_y = floatval($sticker['y']);
						$dst_w = floatval($sticker['width']);
						$dst_h = floatval($sticker['height']);

						// to prevent fatal division by zero errors during ratio calculation
						if ($dst_w <= 0 || $dst_h <= 0) {
							continue;
						}

						// the sticker itself)
						$src_w_full = imagesx($stickerImage);
						$src_h_full = imagesy($stickerImage);

						$src_x = 0;
						$src_y = 0;
						$src_w = $src_w_full;
						$src_h = $src_h_full;

						$canvas_w = imagesx($baseImage);
						$canvas_h = imagesy($baseImage);

						// Ratios
						$ratio_x = $src_w_full / $dst_w;
						$ratio_y = $src_h_full / $dst_h;

						// cropif the sticker overflows thethe LEFT edge
						if ($dst_x < 0) {
							$crop_w_dst = abs($dst_x);
							$crop_w_src = $crop_w_dst * $ratio_x;
							$src_x += $crop_w_src;
							$src_w -= $crop_w_src;
							$dst_w -= $crop_w_dst;
							$dst_x = 0;
						}

						// cropif the sticker overflows the TOP edge
						if ($dst_y < 0) {
							$crop_h_dst = abs($dst_y);
							$crop_h_src = $crop_h_dst * $ratio_y;
							$src_y += $crop_h_src;
							$src_h -= $crop_h_src;
							$dst_h -= $crop_h_dst;
							$dst_y = 0;
						}

						// cropif the sticker overflows the RIGHT edge
						if ($dst_x + $dst_w > $canvas_w) {
							$overflow_dst = ($dst_x + $dst_w) - $canvas_w;
							$overflow_src = $overflow_dst * $ratio_x;
							$src_w -= $overflow_src;
							$dst_w -= $overflow_dst;
						}

						// cropif the sticker overflows the BOTTOM edge
						if ($dst_y + $dst_h > $canvas_h) {
							$overflow_dst = ($dst_y + $dst_h) - $canvas_h;
							$overflow_src = $overflow_dst * $ratio_y;
							$src_h -= $overflow_src;
							$dst_h -= $overflow_dst;
						}

						// merge the sticker scaling appropriately
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
					}
				}
			}

			// Save the composed image to the server as a PNG
			$isSaved = imagepng($baseImage, $imagePath);
			error_log("isSaved: " . var_export($isSaved, true));
			error_log("imagePath: " . $imagePath);
			error_log("baseImage: " . var_export($baseImage, true));

			$user = new Users();
			$user_id = $_SESSION['user_id'];

			$request = "INSERT INTO images (user_id, filename) VALUES (:user_id, :imageName)";

			$statement = $user->getConnection()->prepare($request);
			$statement->execute([':user_id' => $user_id, ':imageName' => $imageName]);

			$baseImage = null;
			
			ob_clean();
			$response = ['status' => 'success', 'saved' => $isSaved, 'fileName' => $imageName];
			header('Content-Type: application/json');
			echo json_encode($response);
			exit();
		}

		ob_clean();
		echo json_encode(['status' => 'error', 'message' => 'Missing data']);
		exit();
	}

	/**
	 * allowing a user to delete their own picture.
	 */
	public function deleteCapture() {
		Auth::requireLogin();
		$user = new Users();

		$input = json_decode(file_get_contents('php://input'), true);

		if (!isset($input['csrf_token']) || !Session::validateCsrfToken($input['csrf_token'])) {
			echo json_encode(['status' => 'error', 'message' => 'CSRF Token invalid']);
			exit();
		}
		$filename = $input['filename'];
		if (empty($filename)) {
			exit();
		}

		// Checkif the user requesting deletion is owner of the image
		$request = "SELECT user_id FROM images WHERE filename = :filename";
		$statement = $user->getConnection()->prepare($request);
		$statement->execute([':filename' => $filename]);
		$fetchData = $statement->fetch(PDO::FETCH_ASSOC);

		// fails if the user is not the owner 
		if (!$fetchData || $fetchData['user_id'] != $_SESSION['user_id']) {
			exit();
		} else {
			// Delete the file
			$imagePath = __DIR__ . '/../public/uploads/' . $filename;
			if (file_exists($imagePath)) {
				unlink($imagePath);
			}

			// Remove from the database
			$deleteRequest = "DELETE FROM images WHERE filename = :filename";
			$statement = $user->getConnection()->prepare($deleteRequest);
			$statement->execute([':filename' => $filename]);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}
	}

	/**
	 * make an image visible in the public gallery.
	 */
	public function publishCapture() {
		Auth::requireLogin();
		$user = new Users();
		
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
			// Updates the database
			$publishRequest = "UPDATE images SET is_published = TRUE WHERE filename = :filename";
			$statement = $user->getConnection()->prepare($publishRequest);
			$statement->execute([':filename' => $filename]);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}
	}

	/**
	 * hide an image from the public gallery.
	 */
	public function unpublishCapture() {
		Auth::requireLogin();
		$user = new Users();
		
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

			// Updates the database
			$publishRequest = "UPDATE images SET is_published = FALSE WHERE filename = :filename";
			$statement = $user->getConnection()->prepare($publishRequest);
			$statement->execute([':filename' => $filename]);

			header('Content-Type: application/json');
			echo json_encode(['status' => 'success']);
			exit();
		}
	}
}