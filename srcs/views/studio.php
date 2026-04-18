<div class="studio-app-wrapper">
	<div class="studio-app-window">
		<?php
			$enableBonus = (getenv('ENABLE_BONUS') === '1' || (isset($_SERVER['ENABLE_BONUS']) && $_SERVER['ENABLE_BONUS'] === '1'));
		?>
		<aside class="app-left-panel">
			<div class="studio-tabs-container">
				<div class="tabs-header">
					<button type="button" class="tab-button active" onclick="switchTab(event, 'stickers-tab')">Stickers</button>
					<?php if ($enableBonus): ?>
					<button type="button" class="tab-button" onclick="switchTab(event, 'filters-tab')">Filters</button>
					<?php endif; ?>
				</div>

				<div class="tabs-content-wrapper">
					<div id="stickers-tab" class="tab-content active">
						<?php
						$allStickersPaths = glob(__DIR__ . '/../public/stickers/*/*.png') ?: [];
						$categories = [];
						$stickerElements = [];
						
						foreach ($allStickersPaths as $path) {
							$filename = basename($path);
							$category = basename(dirname($path));
							
							if (!in_array($category, $categories)) {
								$categories[] = $category;
							}
							
							$stickerElements[] = [
								'file' => $category . '/' . $filename,
								'category' => $category
							];
						}
						?>
						
						<div class="sticker-filter-container">
							<label for="sticker-category" class="sticker-category-label">Category : </label>
							<select id="sticker-category" class="sticker-category-select">
								<option value="all">All</option>
								<?php foreach ($categories as $cat): ?>
									<option value="<?= htmlspecialchars($cat) ?>"><?= ucfirst(htmlspecialchars($cat)) ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="app-sticker-grid">
							<?php if (!empty($stickerElements)): ?>
								<?php foreach ($stickerElements as $sticker): ?>
									<div class="app-sticker-item" data-sticker="<?= htmlspecialchars($sticker['file']) ?>" data-category="<?= htmlspecialchars($sticker['category']) ?>">
										<img src="/stickers/<?= htmlspecialchars($sticker['file']) ?>" alt="Sticker">
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="app-empty-text">No sticker.</p>
							<?php endif; ?>
						</div>
					</div>

					<?php if ($enableBonus): ?>
					<div id="filters-tab" class="tab-content">
						<div class="app-sticker-grid">
							<?php if (!empty($filters)): ?>
								<?php foreach ($filters as $filter): ?>
									<div class="app-filter-item" data-filter="<?= htmlspecialchars($filter) ?>">
										<img src="/filters/<?= htmlspecialchars($filter) ?>" alt="Filtre">
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="app-empty-text">No filter.</p>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<form action="/studio/capture" method="POST" enctype="multipart/form-data" id="mainCaptureForm">
				<input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken()) ?>">
				<input type="hidden" name="image_data" id="image_data">
				<input type="hidden" name="stickers_data" id="stickers_data">
				<input type="hidden" name="filter_data" id="filter_data"> 
			</form>
		</aside>

		<main class="app-center-panel">
			<div class="app-top-toolbar">
				<div class="toolbar-group">
					<button type="button" class="toolbar-tool" id="button-webcam">📷<span>Webcam</span></button>
					<label class="toolbar-tool upload-trigger">
						⬆<span>Upload</span>
						<input type="file" name="userfile" accept="image/jpeg,image/png" style="display:none;">
					</label>
				</div>
				<div class="toolbar-group">
					<button type="submit" form="mainCaptureForm" class="app-btn-save" disabled>
						Take a picture
					</button>
				</div>
				<div class="toolbar-group">
					<button type="button" class="toolbar-tool" id="remove-button">🗑️<span>Remove</span></button>
				</div>
			</div>

			<div class="app-canvas-area">
				<div class="canvas-placeholder">
					<video id="video" class="video-stream-box" autoplay></video>
					<img id="uploaded-image" class="uploaded-image-box">
					
					<canvas id="canvas" style="display:none;"></canvas>
					<div id="countdown-overlay" class="countdown-overlay-box"></div>

				</div>
			</div>
		</main>

		<aside class="app-right-panel">
			<div class="right-panel-tools">
				<h3 class="panel-title">My Shots</h3>
				
				<div class="shots-scroll-wrapper">
					<div class="app-shots-grid">
						<?php if (empty($userImages)): ?>
							<div class="empty-dropzone">
								<p>No photos yet</p>
							</div>
						<?php else: ?>
							<?php foreach ($userImages as $img): ?>
								<div class="app-shot-item">
									<img src="/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Shot" class="shot-thumbnail shot-thumbnail-img" data-filename="<?= htmlspecialchars($img['filename']) ?>" data-published="<?= $img['is_published'] ? '1' : '0' ?>">
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</aside>

	</div>
</div>

<dialog id="gallery-modal" class="studio-modal-box">
	<div class="studio-modal-header">
		<h3 class="studio-modal-title">My gallery</h3>
		<button type="button" id="button-close-modal" class="btn-close-modal">✕</button>
	</div>

	<div id="modal-detail-view" class="studio-modal-detail-view">
		<br>
		<div class="modal-image-container">


			<button type="button" id="button-back" class="modal-nav-btn modal-nav-btn-left"><</button>
			<img id="detail-large-image" class="studio-large-image">
			<button type="button" id="button-next" class="modal-nav-btn modal-nav-btn-right">></button>

		</div>

		
		<div class="studio-modal-actions">
			<button type="button" id="btn-publish" class="gallery-button">Publish</button>
			<button type="button" id="btn-unpublish" class="unpublish-button">Unpublish</button>
			<button type="button" id="btn-share" class="gallery-button">Share</button>
			<button type="button" id="btn-delete" class="gallery-button">Delete</button>
		</div>

		<div id="share-networks" class="share-networks-box">
			<button type="button" id="btn-twitter" class="btn-share-network">
				<img src="/assets/twitter.png" alt="Twitter" width="32" height="32">
			</button>
			<button type="button" id="btn-facebook" class="btn-share-network">
				<img src="/assets/facebook.png" alt="Facebook" width="32" height="32">
			</button>
			<button type="button" id="btn-pinterest" class="btn-share-network">
				<img src="/assets/pinterest.png" alt="Pinterest" width="32" height="32">
			</button>
		</div>
	</div>
</dialog>

<?php if ($enableBonus): ?>
	<script defer src="/vendor/face-api/face-api.min.js"></script>
	<script defer src="/js/studio.js"></script>
<?php else: ?>
	<script>
		const activeFilterImage = new Image();
		activeFilterImage.src = '';
	</script>
<?php endif; ?>

<script>

	/**
	 * Request access to user camera
	 */
	const video = document.getElementById('video');
	
	navigator.mediaDevices.getUserMedia({ video: true })
		.then(function(stream) {
			video.srcObject = stream;
		})
		.catch(function(error) {
			console.error("Error: cannot access camera: ", error);
			alert("Cannot access camera. Please autorize access in your navigator.");
		});
	
	/**
	 * Modal mavigation
	 */
	const buttonBackModal = document.getElementById('button-back');
	const buttonNextModal = document.getElementById('button-next');

	buttonBackModal.addEventListener('click', function() {

		const thumbnail = document.querySelectorAll('.shot-thumbnail');
		const index = Array.from(thumbnail).findIndex(image => image.getAttribute('data-filename') == currentEditingImage);

		// Check if there is a previous image in the array
		if (index > 0) {
			const target = thumbnail[index - 1];
			const detailLargeImage = document.getElementById('detail-large-image');
			detailLargeImage.src = target.src;
			currentEditingImage = target.getAttribute('data-filename');

			// Update button based on publication statuts
			const isPublished = target.getAttribute('data-published');
			if (isPublished === '1') {
				publishButton.style.display = 'none';
				unpublishButton.style.display = 'inline-block';
			} else {
				publishButton.style.display = 'inline-block';
				unpublishButton.style.display = 'none';
			}
		}
	});
	
	buttonNextModal.addEventListener('click', function() {
		
		const thumbnail = document.querySelectorAll('.shot-thumbnail');
		const index = Array.from(thumbnail).findIndex(image => image.getAttribute('data-filename') == currentEditingImage);

		// Check if there is a next image in the array
		if (index >= 0 && index < thumbnail.length - 1) {
			const target = thumbnail[index + 1];
			const detailLargeImage = document.getElementById('detail-large-image');
			detailLargeImage.src = target.src;
			currentEditingImage = target.getAttribute('data-filename');

			const isPublished = target.getAttribute('data-published');
			if (isPublished === '1') {
				publishButton.style.display = 'none';
				unpublishButton.style.display = 'inline-block';
			} else {
				publishButton.style.display = 'inline-block';
				unpublishButton.style.display = 'none';
			}
		}
	});

	/**
	 * Share, publish, delete
	 */
	const publishButton = document.getElementById('btn-publish');
	const unpublishButton = document.getElementById('btn-unpublish');
	const shareButton = document.getElementById('btn-share');
	const deleteButton = document.getElementById('btn-delete');

	shareButton.addEventListener('click', function() {
		if (!currentEditingImage)
			return ;

		// Will not work with localhost, but works with any other url
		const urlImage = "http://localhost:8080/uploads/" + currentEditingImage;
		const sharedNetworks = document.getElementById('share-networks');
		const sharedTwitter = document.getElementById('btn-twitter');
		const sharedFacebook = document.getElementById('btn-facebook');
		const sharedPinterest = document.getElementById('btn-pinterest');

		sharedNetworks.style.display = 'flex';

		sharedTwitter.addEventListener('click', function() {
			window.open('https://twitter.com/intent/tweet?text=Check%20out%20my%20new%20picture%20on%20Camagru%21&url=' + urlImage);
		})

		sharedFacebook.addEventListener('click', function() {
			window.open('https://www.facebook.com/sharer/sharer.php?u=' + urlImage);
		})

		sharedPinterest.addEventListener('click', function() {
			window.open('http://pinterest.com/pin/create/button/?media=&description=Check%20out%20my%20new%20picture%20on%20Camagru!&url=' + urlImage);
		})
	});


	// PUBLISH LOGIC
	publishButton.addEventListener('click', function() {
		if (!currentEditingImage)
			return ;

		if (confirm("Are you sure you want to publish your photo to the main gallery?")) {
			fetch('/studio/publish', {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ 
					filename: currentEditingImage,
					csrf_token: document.getElementById('csrf_token').value
				})
			})
			.then(response => response.json())
			.then (data => {
				if (data.status === 'success') {
					// Toggle button visibility immediately
					publishButton.style.display = 'none';
					unpublishButton.style.display = 'inline-block';

					// Update the HTML in the background list
					const targetThumb = document.querySelector(`.shot-thumbnail[data-filename="${currentEditingImage}"]`);
					if (targetThumb) {
						targetThumb.setAttribute('data-published', '1');
					}
					alert('Your photo is now public! Other users can like and comment.');
				}
			});

		}
	});

	// UNPUBLISH LOGIC
	unpublishButton.addEventListener('click', function() {
		if (!currentEditingImage)
			return ;

		if (confirm("Are you sure you want to make this photo private?")) {
			fetch('/studio/unpublish', {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ 
					filename: currentEditingImage,
					csrf_token: document.getElementById('csrf_token').value
				})
			})
			.then(response => response.json())
			.then (data => {
				if (data.status === 'success') {
					unpublishButton.style.display = 'none';
					publishButton.style.display = 'inline-block';

					const targetThumb = document.querySelector(`.shot-thumbnail[data-filename="${currentEditingImage}"]`);
					if (targetThumb) {
						targetThumb.setAttribute('data-published', '0');
					}
					alert('Your photo is now private. Nobody can see it, except you!');
				}
			});

		}
	});


	// DELETE LOGIC
	deleteButton.addEventListener('click', function() {
		if (!currentEditingImage)
			return ;

		if (confirm("Are you sure you want to delete this post?")) {
			fetch('/studio/delete', {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ 
					filename: currentEditingImage,
					csrf_token: document.getElementById('csrf_token').value
				})
			})
			.then(response => {
				if (!response.ok) throw new Error("Network error");
				return response.json();
			})
			.then(data => {
				if (data.status === 'success') {
					
					// Find current thumbnails and index of the one being deleted
					const thumbnails = Array.from(document.querySelectorAll('.shot-thumbnail'));
					const currentIndex = thumbnails.findIndex(img => img.getAttribute('data-filename') == currentEditingImage);
					
					// Check which image to show next
					let nextThumb = null;
					if (currentIndex < thumbnails.length - 1) {
						nextThumb = thumbnails[currentIndex + 1];
					} else if (currentIndex > 0) {
						nextThumb = thumbnails[currentIndex - 1];
					}

					// Remove image
					const imageToTrash = document.querySelector('.shot-thumbnail[data-filename="' + currentEditingImage + '"]');
					if (imageToTrash) {
						imageToTrash.closest('.app-shot-item').remove();
					}

					// Update modal state
					if (nextThumb) {
						// Show replacement image
						const detailLargeImage = document.getElementById('detail-large-image');
						detailLargeImage.src = nextThumb.src;
						currentEditingImage = nextThumb.getAttribute('data-filename');

						// Sync button states
						const isPublished = nextThumb.getAttribute('data-published');
						if (isPublished === '1') {
							publishButton.style.display = 'none';
							unpublishButton.style.display = 'inline-block';
						} else {
							publishButton.style.display = 'inline-block';
							unpublishButton.style.display = 'none';
						}
					} else {
						galleryModal.close();
						currentEditingImage = '';
						
						const gallery = document.querySelector('.app-shots-grid');
						if (gallery && gallery.children.length === 0) {
							gallery.innerHTML = '<div class="empty-dropzone"><p>No photos yet</p></div>';
						}
					}
				}
			})
			.catch (error => {
				console.error("Error deleting picture: ", error);
			});
		}
	});

	/**
	 * Modal opening
	 */
	const galleryModal = document.getElementById('gallery-modal');
	const buttonClose = document.getElementById('button-close-modal');

	buttonClose.addEventListener('click', function() {
		galleryModal.close();
	});

	// Close modal when clicking outside
	galleryModal.addEventListener('click', function(event) {
		if (event.target === galleryModal) {
			galleryModal.close();
		}
	});

	let currentEditingImage = '';

	const thumbnails = document.querySelectorAll('.shot-thumbnail');
	const detailLargeImage = document.getElementById('detail-large-image');

	thumbnails.forEach(thumb => {
		thumb.addEventListener('click', function() {
			const imageSrc = this.src;
			currentEditingImage = this.getAttribute('data-filename');

			detailLargeImage.src = imageSrc;

			const isPublished = this.getAttribute('data-published');
			if (isPublished === '1') {
				publishButton.style.display = 'none';
				unpublishButton.style.display = 'inline-block';
			} else {
				publishButton.style.display = 'inline-block';
				unpublishButton.style.display = 'none';
			}

			galleryModal.showModal();
		});
	});

	/**
	 * Webcam and upload image
	 */
	const uploadedImage = document.getElementById('uploaded-image');
	const webcamButton = document.getElementById('button-webcam');
	const captureButton = document.querySelector('.app-btn-save');

	// Switch to webcam mode
	webcamButton.addEventListener('click', function() {
		upload.value = '';
		
		navigator.mediaDevices.getUserMedia({ video: true })
		.then(function(stream) {
			video.srcObject = stream;
			video.play();
			uploadedImage.style.display = 'none';
			video.style.display = 'block';

			// Clean stickers or filters
			document.querySelectorAll('.sticker-box').forEach(box => box.remove());
			activeFilterImage.src = '';
			const faceOverlay = document.getElementById('faceapi-overlay');
			if (faceOverlay) {
				const ctx = faceOverlay.getContext('2d');
				ctx.clearRect(0, 0, faceOverlay.width, faceOverlay.height);
			}

			// Disable capture button
			document.querySelector('.app-btn-save').disabled = true;

			// enable the filters tab
			const filtersTabBtn = document.querySelector('.tab-button[onclick*="filters-tab"]');
			if (filtersTabBtn) {
				filtersTabBtn.disabled = false;
				filtersTabBtn.style.opacity = '1';
				filtersTabBtn.style.cursor = 'pointer';
			}
		})
		.catch(function(error) {
			console.error("Error: cannot access camera: ", error);
			alert("Cannot access camera. Please autorize access in your navigator.");
		});
	})

	// uploaded image mode
	const upload = document.querySelector('input[name="userfile"]');
	upload.addEventListener('change', function() {
		if (this.files[0]) {
			const reader = new FileReader();
			reader.onload = () => {
				
				uploadedImage.src = reader.result;

				// Stop the webcam stream to save cpu and ressources 
				if (video.srcObject) {
					window.isTracking = false;
					video.pause();
					video.srcObject.getTracks().forEach(track => track.stop());
					video.srcObject = null;
				}

				// Swap visibility
				video.style.display = 'none';
				uploadedImage.style.display = 'block';

				// Clean stickers or filters
				activeFilterImage.src = '';
				document.querySelectorAll('.sticker-box').forEach(box => box.remove());
				const faceOverlay = document.getElementById('faceapi-overlay');
				if (faceOverlay) {
					const ctx = faceOverlay.getContext('2d');
					ctx.clearRect(0, 0, faceOverlay.width, faceOverlay.height);
				}

				// Disable capture button 
				document.querySelector('.app-btn-save').disabled = true;

				// UI back to the sticekrs tab (no filter on upload image)
				const stickersTabButton = document.querySelector('.tab-button[onclick*="stickers-tab"]');
				if (stickersTabButton)
					stickersTabButton.click();

				const filterTabButton = document.querySelector('.tab-button[onclick*=filters-tab]');
				if (filterTabButton) {
					filterTabButton.disabled = true;
					filterTabButton.style.opacity = '0.4';
					filterTabButton.style.cursor = 'not-allowed';
				}
			}
			
			reader.readAsDataURL(this.files[0]);
		}
	})

	/**
	 * Sticker interaction
	 */
	const canvasPlaceholder = document.querySelector('.canvas-placeholder');
	canvasPlaceholder.style.overflow = 'hidden';

	const stickerItems = document.querySelectorAll('.app-sticker-item');

	stickerItems.forEach(item => {
		item.addEventListener('click', function() {
			// need webcam or upload before adding a sticker
			if (!video.srcObject && uploadedImage.style.display !== 'block') {
				alert("Please authorize camera's access or upload a picture before adding a sticker.");
				return;
			}
			
			const stickerFile = this.getAttribute('data-sticker');
			document.querySelectorAll('.sticker-box').forEach(box => box.classList.remove('selected'));

			// wrapper for the new sticker
			const newBox = document.createElement('div');
			newBox.className = 'sticker-box selected'; 
			newBox.style.position = 'absolute';
			newBox.style.top = '0px';
			newBox.style.left = '0px';
			newBox.style.zIndex = '10';

			// center on screen
			let angle = 0;
			let scale = 1;
			let tX = canvasPlaceholder.clientWidth / 2 - 60; 
			let tY = canvasPlaceholder.clientHeight / 2 - 60;

			newBox.style.visibility = 'hidden';
			newBox.style.transform = `translate(${tX}px, ${tY}px) rotate(${angle}deg) scale(${scale})`;
			newBox.dataset.angle = angle; // need to store angle in HTML for backend processing

			const newImg = document.createElement('img');
			newImg.src = '/stickers/' + stickerFile;
			newImg.style.width = '100%';
			newImg.style.height = '100%';
			newImg.style.pointerEvents = 'none';

			const content = document.createElement('div');
			content.className = 'sticker-content';
			newBox.appendChild(newImg);

			// handles and rotation
			['tl', 'tr', 'bl', 'br', 'rotate'].forEach(type => {
				const h = document.createElement('div');
				h.className = `sticker-handle handle-${type}`;
				h.dataset.type = type;
				newBox.appendChild(h);
			});
			canvasPlaceholder.appendChild(newBox);

			// keep the ratio
			newImg.onload = function() {
				const ratio = newImg.naturalWidth / newImg.naturalHeight;
				newBox.style.width = '120px';
				newBox.style.height = (120 / ratio) + 'px';
				tX = canvasPlaceholder.clientWidth / 2 - 60;
				tY = canvasPlaceholder.clientHeight / 2 - (120 / ratio) / 2;
				newBox.style.transform = `translate(${tX}px, ${tY}px) rotate(${angle}deg) scale(${scale})`;
				newBox.style.visibility = 'visible';
			};

			// mouse event
			newBox.addEventListener('mousedown', function(e) {
				e.stopPropagation();
				
				// Select the sticker
				document.querySelectorAll('.sticker-box').forEach(box => box.classList.remove('selected'));
				newBox.classList.add('selected');

				const startClientX = e.clientX;
				const startClientY = e.clientY;

				// Check if a specific handle was clicked
				if (e.target.classList.contains('sticker-handle')) {
					const type = e.target.dataset.type;
					const rect = newBox.getBoundingClientRect();
					const centerX = rect.left + rect.width / 2;
					const centerY = rect.top + rect.height / 2;
					
					if (type === 'rotate') {
						// Rotation using arctangent calculation
						const startMouseAngle = Math.atan2(e.clientY - centerY, e.clientX - centerX);
						const startAngle = angle;
						
						document.onmousemove = function(moveEvent) {
							const currentMouseAngle = Math.atan2(moveEvent.clientY - centerY, moveEvent.clientX - centerX);
							const angleDiff = (currentMouseAngle - startMouseAngle) * (180 / Math.PI);
							angle = startAngle + angleDiff;
							newBox.style.transform = `translate(${tX}px, ${tY}px) rotate(${angle}deg) scale(${scale})`;
							newBox.dataset.angle = angle; 
						};
					} else {
						// Scaling using hypotenuse calculation
						const startScale = scale;
						const startDist = Math.hypot(e.clientX - centerX, e.clientY - centerY);
						
						document.onmousemove = function(moveEvent) {
							const currentDist = Math.hypot(moveEvent.clientX - centerX, moveEvent.clientY - centerY);
							scale = startScale * (currentDist / startDist);
							if (scale < 0.2) scale = 0.2;
							newBox.style.transform = `translate(${tX}px, ${tY}px) rotate(${angle}deg) scale(${scale})`;
						};
					}
				} else {
					// movement
					const startX = tX;
					const startY = tY;
					
					document.onmousemove = function(moveEvent) {
						tX = startX + (moveEvent.clientX - startClientX);
						tY = startY + (moveEvent.clientY - startClientY);
						newBox.style.transform = `translate(${tX}px, ${tY}px) rotate(${angle}deg) scale(${scale})`;
					};
				}

				// Clean event listeners on mouse release
				document.onmouseup = function() {
					document.onmousemove = null;
					document.onmouseup = null;
				};
			});

			// Enable capture button
			captureButton.disabled = false;
		});
	});

	// Deselect sticker when clicking anywhere else
	document.addEventListener('mousedown', function(e) {
		if (e.target.closest('#remove-button'))
			return;
		if (!e.target.closest('.sticker-box')) {
			document.querySelectorAll('.sticker-box').forEach(box => box.classList.remove('selected'));
		}
	});

	/**
	 * Capture and send via ajax
	 */
	const canvas = document.getElementById('canvas');
	const context = canvas.getContext('2d');
	const hiddenInput = document.getElementById('image_data');
	const form = document.getElementById('mainCaptureForm');

	captureButton.addEventListener('click', function(event) {
		event.preventDefault();
		captureButton.disabled = true;

		// webcam or upload
		const imageCaptured = document.getElementById('uploaded-image');
		let activeElem;
		let realWidth;
		let realHeight;

		if (imageCaptured.style.display === 'block') {
			activeElem = imageCaptured;
			realWidth = activeElem.naturalWidth;
			realHeight = activeElem.naturalHeight;
		} else {
			activeElem = video;
			realWidth = activeElem.videoWidth;
			realHeight = activeElem.videoHeight;
		}

		// countdown
		let timeout = 3;
		const countdownOverlay = document.getElementById('countdown-overlay');
		countdownOverlay.style.display = 'block';
		countdownOverlay.innerHTML = timeout;

		const intervalId = setInterval(function() {
			timeout--;
			if (timeout > 0) {
				countdownOverlay.innerHTML = timeout;
			}
		}, 1000);

		// execution of capture after countdown
		setTimeout(() => {
			clearInterval(intervalId);
			countdownOverlay.style.display = 'none';
			
			canvas.width = realWidth;
			canvas.height = realHeight;

			context.save();

			// mirror picture
			if (activeElem === video) {
				context.translate(realWidth, 0);
				context.scale(-1, 1);
			}

			context.drawImage(activeElem, 0, 0, realWidth, realHeight);
			context.restore();

			// for face filter to be included
			const faceOverlay = document.getElementById('faceapi-overlay');
			if (faceOverlay) {
				context.drawImage(faceOverlay, 0, 0, realWidth, realHeight);
			}
			
			// Store base image in form
			hiddenInput.value = canvas.toDataURL('image/png');
	
			// Calculate scale ratio
			let renderedWidth, renderedHeight, renderedLeft, renderedTop;
			const activeRect = activeElem.getBoundingClientRect();

			if (activeElem === video) {
				const videoRatio = activeElem.videoWidth / activeElem.videoHeight;
				const rectRatio = activeRect.width / activeRect.height;

				if (videoRatio > rectRatio) {
					renderedWidth = activeRect.width;
					renderedHeight = activeRect.width / videoRatio;
					renderedLeft = activeRect.left;
					renderedTop = activeRect.top + (activeRect.height - renderedHeight) / 2;
				} else {
					renderedHeight = activeRect.height;
					renderedWidth = activeRect.height * videoRatio;
					renderedTop = activeRect.top;
					renderedLeft = activeRect.left + (activeRect.width - renderedWidth) / 2;
				}
			} else {
				renderedWidth = activeRect.width;
				renderedHeight = activeRect.height;
				renderedLeft = activeRect.left;
				renderedTop = activeRect.top;
			}

			const widthRatio = realWidth / renderedWidth;
			const heightRatio = realHeight / renderedHeight;
			
			// Collect metadata for all positioned stickers
			let stickersArray = [];
			const allStickers = document.querySelectorAll('.sticker-box');
	
			allStickers.forEach(box => {
				const img = box.querySelector('img');
				const imgUrl = new URL(img.src);
				const filename = decodeURIComponent(imgUrl.pathname.split('/stickers/')[1]);
	
				const boxRect = box.getBoundingClientRect();
	
				const exactDiffX = boxRect.left - renderedLeft;
				const exactDiffY = boxRect.top - renderedTop;
	
				// Map CSS coordinates to original image scale
				let finalX = exactDiffX * widthRatio;
				const finalY = exactDiffY * heightRatio;
				const finalWidth = boxRect.width * widthRatio;
				const finalHeight = boxRect.height * heightRatio;

				stickersArray.push({
					src: filename,
					x: Math.round(finalX),
					y: Math.round(finalY),
					width: Math.round(finalWidth),
					height: Math.round(finalHeight),
					angle: parseFloat(box.dataset.angle) || 0
				});
			});
	
			document.getElementById('stickers_data').value = JSON.stringify(stickersArray);
	
			const gallery = document.querySelector('.app-shots-grid');
			const empty_dropzone = document.querySelector('.empty-dropzone');
			
			fetch('/studio/capture', {
				method: "POST",
				body: new FormData(form)
			})
			.then(response => response.json())
			.then(data => {
				// clean after capture
				allStickers.forEach(box => box.remove());
				activeFilterImage.src = '';
				captureButton.disabled = true;
				
				// Add new image to the gallery
				const newDiv = document.createElement("div");
				const img = document.createElement("img");
				img.className = "shot-thumbnail";
				img.setAttribute("data-filename", data.fileName);
				img.style.cursor = "pointer";
				img.setAttribute("data-published", "0");

				img.addEventListener('click', function() {
					const detailLargeImage = document.getElementById('detail-large-image');
					const galleryModal = document.getElementById('gallery-modal');
					detailLargeImage.src = this.src;
					currentEditingImage = this.getAttribute('data-filename');
					galleryModal.showModal();
				});
	
				img.src = "/uploads/" + data.fileName;
				newDiv.appendChild(img);
				newDiv.className = "app-shot-item";
	
				if (empty_dropzone)
					empty_dropzone.remove();
				gallery.prepend(newDiv);
			})
			.catch(error => console.error("Request error :", error));
			
		}, 3000);
	});

	/**
	 * Navigation
	 */
	document.addEventListener('keydown', function(event) {
		if (galleryModal.open) {
			if (event.key === 'ArrowLeft') {
				buttonBackModal.click();
			} else if (event.key === 'ArrowRight') {
				buttonNextModal.click();
			}
		}
	});

	// Stickers and filters tabs 
	function switchTab(event, tabId) {
		event.preventDefault();

		document.querySelectorAll('.tab-content').forEach(content => {
			content.classList.remove('active');
		});

		document.querySelectorAll('.tab-button').forEach(button => {
			button.classList.remove('active');
		});

		document.getElementById(tabId).classList.add('active');
		event.currentTarget.classList.add('active');
	}

	// Handles category filtering for stickers
	document.getElementById('sticker-category').addEventListener('change', function() {
		const selectedCategory = this.value;
		const allStickerItems = document.querySelectorAll('.app-sticker-item');

		allStickerItems.forEach(item => {
			const itemCategory = item.getAttribute('data-category');
			
			if (selectedCategory === 'all' || itemCategory === selectedCategory) {
				item.style.display = 'inline-block'; 
			} else {
				item.style.display = 'none';
			}
		});
	});

	// Handle filter selection
	const filterItems = document.querySelectorAll('.app-filter-item');

	filterItems.forEach(item => {
		item.addEventListener('click', function() {

			if (!video.srcObject && uploadedImage.style.display !== 'block') {
				alert("Please authorize camera's access or upload a picture before adding a sticker.");
				return;
			}
			const filterFile = this.getAttribute('data-filter');
			
			// live rendering
			activeFilterImage.src = '/filters/' + filterFile;

			document.querySelector('.app-btn-save').disabled = false;
		});
	});

	// Removing selected stickers or filters
	const removeButton = document.getElementById('remove-button');
	removeButton.addEventListener('click', function() {
		
		const selectedSticker = document.querySelector('.sticker-box.selected');

		if (selectedSticker) {
			selectedSticker.remove();
		} else {
			activeFilterImage.src = ''; // Clear filter if no sticker is selected
		}

		// Disable capture button if canvas is empty
		const remainingStickers = document.querySelectorAll('.sticker-box');
		if (remainingStickers.length === 0 && (!activeFilterImage.src || activeFilterImage.src === ''))
			document.querySelector('.app-btn-save').disabled = true;
	});

</script>