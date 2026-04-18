<main class="container">
	<input type="hidden" id="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken()) ?>">

	<p class="section-title">Latest artworks</p>

	<div class="gallery-grid">

		<?php if (empty($images)): ?>
			<div class="gallery-empty">
				<span class="big-icon">📷</span>
				No photos yet — be the first to create one!
			</div>
		<?php else: ?>
			<?php foreach ($images as $img): ?>
				<div class="gallery-item">
					<img class="home-thumbnail" data-filename="<?= htmlspecialchars($img['filename']) ?>" src="/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Photo by <?= htmlspecialchars($img['username']) ?>">
					<div class="overlay">
						<span class="overlay-span">
							★ <?= (int)$img['likes'] ?> &nbsp; by <?= htmlspecialchars($img['username']) ?>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

	</div>

	<div id="scroll-anchor" class="scroll-anchor-container">
		<span id="loading-spinner" class="loading-spinner-text">Charging more pictures... 📷</span>
	</div>

</main>
<dialog id="gallery-modal" class="home-modal">

	<div id="modal-detail-view" class="modal-detail-layout">
		
		<div class="modal-image-container">
			
			<button type="button" id="button-back" class="modal-nav-btn modal-nav-btn-left"><</button>
			
			<img id="detail-large-image" class="home-large-image">
			
			<button type="button" id="button-next" class="modal-nav-btn modal-nav-btn-right">></button>
			
		</div>

		<div class="modal-sidebar">
			
			<div class="modal-header-section">
				<div id="modal-header-info" class="modal-header-info-text">
					</div>
				<button type="button" id="button-close-modal" class="btn-close-modal">✕</button>
			</div>

			<div id="comments-container" class="comments-container-box">
				<div class="comment-date-placeholder">
					Posted by user145341 on January 16th 2026
				</div>

				<div id="no-comments-msg" class="no-comments-msg-box">
					<span class="no-comments-icon">💬</span>
					No comments yet. Be the first one to add a comment!
				</div>

			</div>

			<div class="modal-like-section">
				<img id="btn-like" class="like-icon-img" src="/assets/heart.png" style="<?= isset($_SESSION['user_id']) ? 'cursor: pointer;' : 'cursor: default;' ?>" alt="Like">
				<p id="like-count-text" class="like-count-text-style">0 likes</p>
			</div>

			<?php if (isset($_SESSION['user_id'])): ?>
				<div class="modal-comment-input-section">
					<input type="text" id="comment-input" class="comment-input-box" placeholder="Add a comment...">
					<button type="button" id="btn-send-comment" class="btn-send-comment-style">Post</button>
				</div>
			<?php else: ?>
				<div class="login-prompt-box">
					<p class="login-prompt-text">Log in to like and comment.</p>
				</div>
			<?php endif; ?>

		</div>
	</div>
</dialog>

<script>

	/**
	 * Fetch and display likes and comments for a specific image
	 */
	function loadSocialData(filename) {
		if (!filename) return;

		fetch('/home/details', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ filename: filename })
		})
		.then(response => response.json())
		.then(data => {
			const headerInfo = document.getElementById('modal-header-info');
			const dateObj = new Date(data.date);
			const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
			headerInfo.innerHTML = `<span style="color: #FF1493;">Posted by <b>${data.author}</b> on ${formattedDate}</span>`;

			document.getElementById('like-count-text').textContent = data.likes + ' likes';

			const heartButton = document.getElementById('btn-like');
			if (data.user_liked) {
				heartButton.src = '/assets/heart_full.png';
			} else {
				heartButton.src = '/assets/heart.png';
			}
			
			const container = document.getElementById('comments-container');
			container.innerHTML = '';

			if (data.comments.length === 0) {
				container.innerHTML = `
				<div id="no-comments-msg" style="margin: auto; text-align: center; color: #8e8e8e; font-size: 0.95rem;">
					<span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">💬</span>
					No comments yet. Be the first one to add a comment!
				</div>`;
			} else {
				data.comments.forEach(comment => {
					const div = document.createElement('div');
					div.style.marginBottom = '15px';
					div.style.fontSize = '0.95rem';
					div.style.lineHeight = '1.4';
					
					div.innerHTML = `<span style="color: #0095f6; font-weight: 600; margin-right: 8px;">${comment.username}</span><span style="color: #262626; word-break: break-word;">${comment.content}</span>`;
					
					container.appendChild(div);
				});
			}
		})
	}

	/**
	 * Handle like button, updating UI immediately and sending request to server
	 */
	document.getElementById('btn-like').addEventListener('click', function() {
		if (!currentEditingImage) return;

		if (!document.getElementById('btn-send-comment')) {
			alert("Please log in to like this photo");
			return ;
		}

		const isLiked = this.src.includes('heart_full');
		let countElem = document.getElementById('like-count-text');
		let currentCount = parseInt(countElem.textContent);
		let newLikeCount;

		if (isLiked) {
			this.src = '/assets/heart.png';
			newLikeCount = currentCount - 1;
			countElem.textContent = (currentCount - 1) + ' likes';
		} else {
			this.src = '/assets/heart_full.png';
			newLikeCount = currentCount + 1;
			countElem.textContent = (currentCount + 1) + ' likes';
		}

		const targetThumb = document.querySelector(`.home-thumbnail[data-filename="${currentEditingImage}"]`);
		if (targetThumb) {
			const overlaySpan = targetThumb.nextElementSibling.querySelector('span');
			if (overlaySpan) {
				overlaySpan.innerHTML = overlaySpan.innerHTML.replace(/★\s*\d+/, `★ ${newLikeCount}`)
			}
		}

		this.classList.add('pop-animation');
		setTimeout(() => this.classList.remove('pop-animation'), 200);


		fetch('/home/toggle-like', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ 
				filename: currentEditingImage,
				csrf_token: document.getElementById('csrf_token').value 
			})
		});
	});

	/**
	 * Handle new comment
	 */
	const btnSend = document.getElementById('btn-send-comment');
		if (btnSend) {
			btnSend.addEventListener('click', function() {
				const input = document.getElementById('comment-input');
				const text = input.value.trim();
				
				if (!text || !currentEditingImage) return;

				fetch('/home/add-comment', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ 
						filename: currentEditingImage, 
						content: text,
						csrf_token: document.getElementById('csrf_token').value 
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success') {
						input.value = '';
						loadSocialData(currentEditingImage);
					}
				});
			});
		}

	/**
	 * Modal to view images from the grid
	 */
	let currentEditingImage = '';
	const galleryModal = document.getElementById('gallery-modal');
	const thumbnails = document.querySelectorAll('.home-thumbnail');
	const detailLargeImage = document.getElementById('detail-large-image');

	thumbnails.forEach(thumb => {
		thumb.addEventListener('click', function() {

			// Read source and filename of clicked image
			const imageSrc = this.src;
			currentEditingImage = this.getAttribute('data-filename');

			// Update modal image
			detailLargeImage.src = imageSrc;

			// Show modal and load social data
			galleryModal.showModal();
			loadSocialData(currentEditingImage);
		});
	});

	/**
	 * Closing the modal
	 */
	const buttonClose = document.getElementById('button-close-modal');

	buttonClose.addEventListener('click', function() {
		galleryModal.close(); 
	});

	galleryModal.addEventListener('click', function(event) {
		if (event.target === galleryModal) {
			galleryModal.close();
		}
	});


	/**
	 * Navigation inside the modal
	 */
	const buttonBackModal = document.getElementById('button-back');
	const buttonNextModal = document.getElementById('button-next');

	buttonBackModal.addEventListener('click', function() {

		const thumbnail = document.querySelectorAll('.home-thumbnail');
		const index = Array.from(thumbnail).findIndex(image => image.getAttribute('data-filename') == currentEditingImage);

		if (index > 0) {
			const target = thumbnail[index - 1];
			const detailLargeImage = document.getElementById('detail-large-image');
			detailLargeImage.src = target.src;
			currentEditingImage = target.getAttribute('data-filename');
			loadSocialData(currentEditingImage);
		}

	});
	
	buttonNextModal.addEventListener('click', function() {
		
		const thumbnail = document.querySelectorAll('.home-thumbnail');
		const index = Array.from(thumbnail).findIndex(image => image.getAttribute('data-filename') == currentEditingImage);

		if (index >= 0 && index < thumbnail.length - 1) {
			const target = thumbnail[index + 1];
			const detailLargeImage = document.getElementById('detail-large-image');
			detailLargeImage.src = target.src;
			currentEditingImage = target.getAttribute('data-filename');
			loadSocialData(currentEditingImage);
		}
	});


	/**
	 * Keyboard navigation
	 */
	document.addEventListener('keydown', function(event) {
		if (galleryModal.open) {
			if (event.key === 'ArrowLeft') {
				buttonBackModal.click();
			} else if (event.key === 'ArrowRight') {
				buttonNextModal.click();
			}
		}
	})

	document.addEventListener('keydown', function(event) {
		if (galleryModal.open) {
			if (event.key === 'Enter') {
				btnSend.click();
			}
		}
	})


	/**
	 * Infinite pagination
	 */
	let currentPage = 1;
	let isFetching = false;
	let hasMore = true;

	const anchor = document.getElementById('scroll-anchor');
	const grid = document.querySelector('.gallery-grid');
	const loadingSpinner = document.getElementById('loading-spinner');

	// Setup an observer to trigger loading more images
	const observer = new IntersectionObserver((entries) => {
		if (entries[0].isIntersecting && !isFetching && hasMore) {
			loadMoreImages();
		}
	}, { 
		rootMargin: "200px" // Trigger loading 200px before reaching the end
	});

	if (anchor) {
		observer.observe(anchor);
	}

	function loadMoreImages() {
		isFetching = true;
		loadingSpinner.style.display = 'block';
		currentPage++;

		fetch('/home/load-more', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ page: currentPage })
		})
		.then(response => response.json())
		.then(data => {
			loadingSpinner.style.display = 'none';
			
			if (data.images.length === 0) {
				hasMore = false;
				return;
			}

			const emptyMsg = document.querySelector('.gallery-empty');
			if (emptyMsg) {
				emptyMsg.remove();
			}

			data.images.forEach(img => {
				const item = document.createElement('div');
				item.className = 'gallery-item';
				item.innerHTML = `
					<img class="home-thumbnail" data-filename="${img.filename}" style="cursor: pointer;" src="/uploads/${img.filename}" alt="Photo by ${img.username}">
					<div class="overlay" style="pointer-events: none;">
						<span style="font-size:0.8rem; color:#fff;">
							★ ${img.likes} &nbsp; by ${img.username}
						</span>
					</div>
				`;
				grid.appendChild(item);

				const newThumbnail = item.querySelector('.home-thumbnail');
				newThumbnail.addEventListener('click', function() {
					const imageSrc = this.src;
					currentEditingImage = this.getAttribute('data-filename');
					document.getElementById('detail-large-image').src = imageSrc;
					document.getElementById('gallery-modal').showModal();
					loadSocialData(currentEditingImage);
				});
			});

			isFetching = false;
		})
		.catch(error => {
			console.error("Error loading pictures :", error);
			isFetching = false;
			loadingSpinner.style.display = 'none';
		});
	}

</script>