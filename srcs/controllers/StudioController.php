<?php

class StudioController {

	public function showStudio() {

		Auth::requireLogin();

		$stickerDir = scandir(__DIR__ . "/../public/stickers");

		$stickers = array_diff($stickerDir, ['.', '..']);

		ob_start();
		require_once __DIR__ . '/../views/studio.php';
		$content = ob_get_clean();
		require_once __DIR__ . '/../views/layout.php';


	}

	public function processCapture() {
		Auth::requireLogin();

		if (isset($_POST['image_data']) && isset($_POST['sticker'])) {

			$imageData = $_POST['image_data'];
			$stickerData = $_POST['sticker'] ?? null;

			if (empty($imageData) || empty($stickerData)) {
				echo "error: doesn't exist";
				exit();
			}

			$cleanData = str_replace('data:image/png;base64,', '', $imageData);
			$result = base64_decode($cleanData);
			$imageName = uniqid('image') . ".png";
			$imagePath = __DIR__ . '/../uploads/' . $imageName;
			$isSaved = file_put_contents($imagePath, $result);
		}


		$response = ['status' => 'success', 'saved' => '$isSaved'];
		header('Content-Type: application/json');
		echo json_encode($response);
		exit();

	}
}