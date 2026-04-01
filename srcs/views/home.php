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
					<img class="home-thumbnail" data-filename="<?= htmlspecialchars($img['filename']) ?>" style="cursor: pointer;"  src="/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Photo by <?= htmlspecialchars($img['username']) ?>">
					<div class="overlay" style="pointer-events: none;">
						<span style="font-size:0.8rem; color:#fff;">
							★ <?= (int)$img['likes'] ?> &nbsp; by <?= htmlspecialchars($img['username']) ?>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

	</div>

	<div id="scroll-anchor" style="height: 20px; text-align: center; padding: 20px; margin-top: 20px;">
		<span id="loading-spinner" style="display: none; color: #8e8e8e; font-size: 0.9rem;">Charging more pictures... 📷</span>
	</div>

</main>
<dialog id="gallery-modal" style="margin: auto; padding: 0; border-radius: 8px; border: none; max-width: 900px; width: 90vw; height: 60vh; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">

	<div id="modal-detail-view" style="display: flex; flex-direction: row; height: 100%;">
		
		<div style="flex-grow: 1; background-color: #EFEFEF; display: flex; align-items: center; justify-content: center; position: relative;">
			
			<button type="button" id="button-back" style="position: absolute; left: 15px; background:none; width: 35px; height: 35px; cursor: pointer; font-weight: bold;"><</button>
			
			<img id="detail-large-image" style="max-width: 100%; max-height: 100%; object-fit: contain;">
			
			<button type="button" id="button-next" style="position: absolute; right: 15px; background:none; width: 35px; height: 35px; cursor: pointer; font-weight: bold;">></button>
			
		</div>

		<div style="width: 350px; min-width: 350px; display: flex; flex-direction: column; background: #fff; border-left: 1px solid #efefef;">
			
			<div style="padding: 15px; border-bottom: 1px solid #efefef; display: flex; justify-content: space-between; align-items: center;">
				<div id="modal-header-info" style="font-size: 0.85rem; color: #8e8e8e;">
					</div>
				<button type="button" id="button-close-modal" style="background: none; border: none; font-size: 1.2rem; font-weight: bold; cursor: pointer; color: #262626;">✕</button>
			</div>

			<div id="comments-container" style="flex-grow: 1; overflow-y: auto; padding: 15px; word-wrap: break-word; overflow-wrap: break-word; display:flex; flex-direction: column;">
				<div style="text-align:center;">
					Posted by user145341 on January 16th 2026
				</div>

				<div id="no-comments-msg" style="margin: auto; text-align: center; color: #8e8e8e; font-size: 0.95rem;">
					<span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">💬</span>
					No comments yet. Be the first one to add a comment!
				</div>

			</div>

			<div style="padding: 15px; border-top: 1px solid #efefef; display: flex; align-items: center; gap: 10px;">
				<img id="btn-like" src="/assets/heart.png" style="width: 28px; <?= isset($_SESSION['user_id']) ? 'cursor: pointer;' : 'cursor: default;' ?>" alt="Like">
				<p id="like-count-text" style="margin: 0; font-weight: 600; color: #262626;">0 likes</p>
			</div>

			<?php if (isset($_SESSION['user_id'])): ?>
				<div style="padding: 15px; border-top: 1px solid #efefef; display: flex; gap: 10px;">
					<input type="text" id="comment-input" placeholder="Add a comment..." style="border: none; outline: none; flex-grow: 1; font-size: 0.95rem;">
					<button type="button" id="btn-send-comment" style="color: #0095f6; background: none; border: none; font-weight: 600; cursor: pointer;">Post</button>
				</div>
			<?php else: ?>
				<div style="padding: 15px; border-top: 1px solid #efefef; text-align: center;">
					<p style="margin: 0; font-size: 0.9rem; color: #8e8e8e;">Log in to like and comment.</p>
				</div>
			<?php endif; ?>

		</div>
	</div>
</dialog>

<script>


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
				csrf_token: document.getElementById('csrf_token').value // LIGNE À AJOUTER
			})
		});
	});

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
						csrf_token: document.getElementById('csrf_token').value // LIGNE À AJOUTER
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success') {
						input.value = ''; // On vide le champ
						loadSocialData(currentEditingImage); // On recharge la liste des commentaires
					}
				});
			});
		}

		//? OUVERTURE DE LA MODALE POUR VOIR CHAQUE PHOTO SUR LA GRILLE

	let currentEditingImage = '';
	const galleryModal = document.getElementById('gallery-modal');
	const thumbnails = document.querySelectorAll('.home-thumbnail');
	const detailLargeImage = document.getElementById('detail-large-image');

	thumbnails.forEach(thumb => {
		thumb.addEventListener('click', function() {
			// On lit l'adresse de l'image cliquée et son nom de fichier
			const imageSrc = this.src;
			currentEditingImage = this.getAttribute('data-filename');

			// On met à jour la grande image
			detailLargeImage.src = imageSrc;

			// Bascule visuelle
			galleryModal.showModal();
			loadSocialData(currentEditingImage);
		});
	});

		//? FERMETURE DE LA MODALE

	const buttonClose = document.getElementById('button-close-modal');

	buttonClose.addEventListener('click', function() {
		galleryModal.close(); // On ferme le <dialog>
	});

	galleryModal.addEventListener('click', function(event) {
		if (event.target === galleryModal) {
			galleryModal.close();
		}
	});


		//? CLIC GAUCHE ET CLIC DROIT POUR CHANGER DE PHOTO

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


		//? FLÈCHE GAUCHE ET FLÈCHE DROITE POUR CHANGER DE PHOTO

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


	//? PAGINATION INFINIE

	let currentPage = 1;
	let isFetching = false;
	let hasMore = true;

	const anchor = document.getElementById('scroll-anchor');
	const grid = document.querySelector('.gallery-grid');
	const loadingSpinner = document.getElementById('loading-spinner');

	// On configure un truc pour observer la gallery
	const observer = new IntersectionObserver((entries) => {
		if (entries[0].isIntersecting && !isFetching && hasMore) {
			loadMoreImages();
		}
	}, { 
		rootMargin: "200px" // 200px avant le chargement
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