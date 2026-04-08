<div class="studio-app-wrapper">
    <div class="studio-app-window">

        <aside class="app-left-panel">
            <div class="app-search-bar">
                <input type="text" placeholder="Find a sticker ...">
                <button type="button" class="app-btn-small">OK</button>
            </div>

            <div class="studio-tabs-container">
                <div class="tabs-header">
                    <button type="button" class="tab-button active" onclick="switchTab(event, 'stickers-tab')">Stickers</button>
                    <button type="button" class="tab-button" onclick="switchTab(event, 'filters-tab')">Filters</button>
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
                        
                        <div class="sticker-filter-container" style="margin-bottom: 15px; text-align: center;">
                            <label for="sticker-category" style="font-weight: bold; font-size: 0.9rem;">Category : </label>
                            <select id="sticker-category" style="padding: 5px 10px; border-radius: 4px; border: 1px solid #dbdbdb;">
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
                <div class="canvas-placeholder" style="position: relative;">
                    <video id="video" autoplay style="max-width: 100%; border-radius: 8px;"></video>
                    <img id="uploaded-image" style="max-width: 100%; border-radius: 8px; display:none">
                    
                    <canvas id="canvas" style="display:none;"></canvas>

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
                                    <img src="/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Shot" class="shot-thumbnail" data-filename="<?= htmlspecialchars($img['filename']) ?>" data-published="<?= $img['is_published'] ? '1' : '0' ?>" style="cursor: pointer;">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </aside>

    </div>
</div>

<dialog id="gallery-modal" style="margin: auto; padding: 20px; border-radius: 8px; border: none; max-width: 80vw;">
	<div style="border-bottom: 1px solid #efefef; display: flex; justify-content: space-between; align-items: center;">
		<h3 style="margin-top: 0; text-align: center;">My gallery</h3>
		<button type="button" id="button-close-modal" style="background: none; border: none; font-size: 1.2rem; font-weight: bold; cursor: pointer; color: #262626;">✕</button>
	</div>

    <div id="modal-detail-view" style="text-align: center;">
        <br>
		<div style="flex-grow: 1; background-color: #EFEFEF; display: flex; align-items: center; justify-content: center; position: relative;">


			<button type="button" id="button-back" style="position: absolute; left: 15px; background:none; width: 35px; height: 35px; cursor: pointer; font-weight: bold;"><</button>
			<img id="detail-large-image" style="max-width: 100%; max-height: 50vh; border-radius: 8px;">
			<button type="button" id="button-next" style="position: absolute; right: 15px; background:none; width: 35px; height: 35px; cursor: pointer; font-weight: bold;">></button>

		</div>

        
        <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
            <button type="button" id="btn-publish" class="gallery-button">Publish</button>
            <button type="button" id="btn-unpublish" class="unpublish-button">Unpublish</button>
            <button type="button" id="btn-share" class="gallery-button">Share</button>
            <button type="button" id="btn-delete" class="gallery-button">Delete</button>
        </div>

        <div id="share-networks" style="margin-top: 15px; display: none; gap: 10px; justify-content: center;">
			<button type="button" id="btn-twitter" style="border: none; background: none; cursor: pointer;">
				<img src="/assets/twitter.png" alt="Twitter" width="32" height="32">
			</button>
			<button type="button" id="btn-facebook" style="border: none; background: none; cursor: pointer;">
				<img src="/assets/facebook.png" alt="Facebook" width="32" height="32">
			</button>
			<button type="button" id="btn-pinterest" style="border: none; background: none; cursor: pointer;">
				<img src="/assets/pinterest.png" alt="Pinterest" width="32" height="32">
			</button>
		</div>
    </div>
</dialog>

<script defer src="/vendor/face-api/face-api.min.js"></script>
<script defer src="/js/studio.js"></script>
<script>

    const video = document.getElementById('video');
    
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            video.srcObject = stream;
        })
        .catch(function(error) {
            console.error("Error: cannot access camera: ", error);
            alert("Cannot access camera. Please autorize access in your navigator.");
        });
    

    const buttonBackModal = document.getElementById('button-back');
    const buttonNextModal = document.getElementById('button-next');

    buttonBackModal.addEventListener('click', function() {

        const thumbnail = document.querySelectorAll('.shot-thumbnail');
        const index = Array.from(thumbnail).findIndex(image => image.getAttribute('data-filename') == currentEditingImage);

        if (index > 0) {
            const target = thumbnail[index - 1];
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
    
    buttonNextModal.addEventListener('click', function() {
        
        const thumbnail = document.querySelectorAll('.shot-thumbnail');
        const index = Array.from(thumbnail).findIndex(image => image.getAttribute('data-filename') == currentEditingImage);

        if (index >= 0) {
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

    ////// BUTTONS IN THE GALLERY
    const publishButton = document.getElementById('btn-publish');
    const unpublishButton = document.getElementById('btn-unpublish');
    const shareButton = document.getElementById('btn-share');
    const deleteButton = document.getElementById('btn-delete');

    shareButton.addEventListener('click', function() {
        if (!currentEditingImage)
            return ;

        // ne va pas fonctionner à cause de localhost mais on peut tester avec une vraie image
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


    //? PUBLISH PICTURE

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

					publishButton.style.display = 'none';
					unpublishButton.style.display = 'inline-block';

					const targetThumb = document.querySelector(`.shot-thumbnail[data-filename="${currentEditingImage}"]`);
					if (targetThumb) {
						targetThumb.setAttribute('data-published', '1');
					}
    
                    //galleryModal.close();
                    alert('Your photo is now public! Other users can like and comment.');
                }
            });

        }
    });

	//? UNPUBLISH PICTURE

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
    
                    //galleryModal.close();
                    alert('Your photo is now private. Nobody can see it, except you!');
                }
            });

        }
    });



    //? DELETE PICTURE

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
                    
                    // 1. Trouver toutes les miniatures actuelles et l'index de celle qu'on supprime
                    const thumbnails = Array.from(document.querySelectorAll('.shot-thumbnail'));
                    const currentIndex = thumbnails.findIndex(img => img.getAttribute('data-filename') == currentEditingImage);
                    
                    // 2. Déterminer quelle sera la prochaine image à afficher
                    let nextThumb = null;
                    if (currentIndex < thumbnails.length - 1) {
                        // Il y a une image SUIVANTE
                        nextThumb = thumbnails[currentIndex + 1];
                    } else if (currentIndex > 0) {
                        // C'était la dernière image, donc on prend la PRÉCÉDENTE
                        nextThumb = thumbnails[currentIndex - 1];
                    }

                    // 3. Supprimer l'image du DOM (de la grille de fond)
                    const imageToTrash = document.querySelector('.shot-thumbnail[data-filename="' + currentEditingImage + '"]');
                    if (imageToTrash) {
                        imageToTrash.closest('.app-shot-item').remove();
                    }

                    // 4. Mettre à jour la modale
                    if (nextThumb) {
                        // Afficher la nouvelle image
                        const detailLargeImage = document.getElementById('detail-large-image');
                        detailLargeImage.src = nextThumb.src;
                        currentEditingImage = nextThumb.getAttribute('data-filename');

                        // Mettre à jour l'affichage des boutons Publish/Unpublish pour la nouvelle image
                        const isPublished = nextThumb.getAttribute('data-published');
                        if (isPublished === '1') {
                            publishButton.style.display = 'none';
                            unpublishButton.style.display = 'inline-block';
                        } else {
                            publishButton.style.display = 'inline-block';
                            unpublishButton.style.display = 'none';
                        }
                    } else {
                        // Il n'y a plus aucune photo, on ferme la modale
                        galleryModal.close();
                        currentEditingImage = '';
                        
                        // Optionnel : remettre le message "No photos yet" dans le panneau latéral
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


     /////// MODALE D'OUVERTURE DE LA GALERIE 
    const galleryModal = document.getElementById('gallery-modal');
    const buttonClose = document.getElementById('button-close-modal');

    buttonClose.addEventListener('click', function() {
        galleryModal.close(); // On ferme le <dialog>
    });

    galleryModal.addEventListener('click', function(event) {
        if (event.target === galleryModal) {
            galleryModal.close();
        }
    });


    // Qu'est-ce qu'on est en train de regarder actuellement?
    let currentEditingImage = '';

    // On attache un événement de clic à chaque miniature de la grille
    const thumbnails = document.querySelectorAll('.shot-thumbnail');
    const detailLargeImage = document.getElementById('detail-large-image');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            // On lit l'adresse de l'image cliquée et son nom de fichier
            const imageSrc = this.src;
            currentEditingImage = this.getAttribute('data-filename');

            // On met à jour la grande image
            detailLargeImage.src = imageSrc;

			const isPublished = this.getAttribute('data-published');
			if (isPublished === '1') {
				publishButton.style.display = 'none';
				unpublishButton.style.display = 'inline-block';
			} else {
				publishButton.style.display = 'inline-block';
				unpublishButton.style.display = 'none';
			}

            // Bascule visuelle
            galleryModal.showModal();
        });
    });

    const uploadedImage = document.getElementById('uploaded-image');
    const webcamButton = document.getElementById('button-webcam');
    const captureButton = document.querySelector('.app-btn-save');


    webcamButton.addEventListener('click', function() {
        
        upload.value = '';
        
        navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            video.srcObject = stream;
            uploadedImage.style.display = 'none';
            video.style.display = 'block';
        })
        .catch(function(error) {
            console.error("Error: cannot access camera: ", error);
            alert("Cannot access camera. Please autorize access in your navigator.");
        });
    })

    const upload = document.querySelector('input[name="userfile"]');

    upload.addEventListener('change', function() {
        if (this.files[0]) {
            const reader = new FileReader();
            reader.onload = () => {
                
                uploadedImage.src = reader.result;

                if (video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());

                }

                video.style.display = 'none';
                uploadedImage.style.display = 'block';
            }
            
            reader.readAsDataURL(this.files[0]);

        }
    })

    const canvasPlaceholder = document.querySelector('.canvas-placeholder');
    canvasPlaceholder.style.overflow = 'hidden';

    const stickerItems = document.querySelectorAll('.app-sticker-item');

    stickerItems.forEach(item => {
        item.addEventListener('click', function() {
            const stickerFile = this.getAttribute('data-sticker');
            
            const newBox = document.createElement('div');
            newBox.className = 'sticker-box';
            newBox.style.position = 'absolute';
            newBox.style.top = '20px';
            newBox.style.left = '20px';
            newBox.style.border = '2px dashed #ff00aa';
            newBox.style.overflow = 'hidden';
            newBox.style.cursor = 'move';
            newBox.style.zIndex = '10';

            newBox.style.opacity = '0';
            newBox.style.transition = 'opacity 0.2s';

            newBox.style.maxWidth = (canvasPlaceholder.clientWidth - 20) + 'px';
            newBox.style.maxHeight = (canvasPlaceholder.clientHeight - 20) + 'px';

            const newImg = document.createElement('img');
            newImg.src = '/stickers/' + stickerFile;
            newImg.style.width = '100%';
            newImg.style.height = '100%';
            newImg.style.pointerEvents = 'none';

            newImg.onload = function() {
                const ratio = newImg.naturalWidth / newImg.naturalHeight;
                newBox.style.aspectRatio = ratio.toString();
                
                newBox.style.width = '120px';
                newBox.style.height = 'auto';
                
                newBox.style.resize = 'horizontal';

                newBox.style.opacity = '1';
            };

            newBox.appendChild(newImg);
            canvasPlaceholder.appendChild(newBox);

            let isDragging = false;
            let offsetX = 0;
            let offsetY = 0;

            newBox.addEventListener("mousedown", function(e) {
                if (e.offsetX > newBox.clientWidth - 20 && e.offsetY > newBox.clientHeight - 20) {
                    return;
                }
                isDragging = true;
                offsetX = e.clientX - newBox.offsetLeft;
                offsetY = e.clientY - newBox.offsetTop;
                newBox.style.cursor = 'grabbing';
            });

            document.addEventListener("mousemove", (e) => {
                if (isDragging) {
                    let newLeft = e.clientX - offsetX;
                    let newTop = e.clientY - offsetY;

                    // Calcul des limites maximales par rapport au conteneur de la vidéo
                    let maxLeft = canvasPlaceholder.clientWidth - newBox.offsetWidth;
                    let maxTop = canvasPlaceholder.clientHeight - newBox.offsetHeight;

                    let minLeft = maxLeft < 0 ? maxLeft : 0;
                    let realMaxLeft = maxLeft < 0 ? 0 : maxLeft;
                    let minTop = maxTop < 0 ? maxTop : 0;
                    let realMaxTop = maxTop < 0 ? 0 : maxTop;

                    // Blocage des coordonnées pour ne pas déborder (0 = bord haut/gauche)
                    if (newLeft < minLeft) newLeft = minLeft;
                    if (newTop < minTop) newTop = minTop;
                    if (newLeft > realMaxLeft) newLeft = realMaxLeft;
                    if (newTop > realMaxTop) newTop = realMaxTop;

                    newBox.style.left = newLeft + 'px';
                    newBox.style.top = newTop + 'px';

                    newBox.style.maxWidth = (canvasPlaceholder.clientWidth - newLeft) + 'px';
                    newBox.style.maxHeight = (canvasPlaceholder.clientHeight - newTop) + 'px';
                }
            });

            document.addEventListener("mouseup", () => {
                isDragging = false;
                newBox.style.cursor = 'move';
            });

            captureButton.disabled = false;
        });
    });

    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    const hiddenInput = document.getElementById('image_data');
    const form = document.getElementById('mainCaptureForm');

    captureButton.addEventListener('click', function(event) {
        event.preventDefault();

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

        let timeout = 3;

        captureButton.innerHTML = timeout;
        const intervalId = setInterval(function() {
            timeout--;
            captureButton.innerHTML = timeout;
        }, 1000);

        setTimeout(() => {
            clearInterval(intervalId);
            captureButton.innerHTML = "Take a picture!";
            canvas.width = realWidth;
            canvas.height = realHeight;
            context.drawImage(activeElem, 0, 0, realWidth, realHeight);

            const faceOverlay = document.getElementById('faceapi-overlay');
            if (faceOverlay) {
                context.drawImage(faceOverlay, 0, 0, realWidth, realHeight);
            }

            hiddenInput.value = canvas.toDataURL('image/png');
    
            // Calculs des ratios
            const activeRect = activeElem.getBoundingClientRect();
            const widthRatio = realWidth / activeRect.width;
            const heightRatio = realHeight / activeRect.height;
            
            let stickersArray = [];
            const allStickers = document.querySelectorAll('.sticker-box');
    
            // Analyse de chaque sticker présent sur la vidéo
            allStickers.forEach(box => {
                const img = box.querySelector('img');
                const srcParts = img.src.split('/');
                const filename = srcParts[srcParts.length - 1];
    
                // On prend les mesures EXACTES de la boîte sur l'écran physique
                const boxRect = box.getBoundingClientRect();
    
                // Différence mathématique pure, indépendante du HTML autour
                const exactDiffX = boxRect.left - activeRect.left;
                const exactDiffY = boxRect.top - activeRect.top;
    
                stickersArray.push({
                    src: filename,
                    x: Math.round(exactDiffX * widthRatio),
                    y: Math.round(exactDiffY * heightRatio),
                    width: Math.round(boxRect.width * widthRatio),
                    height: Math.round(boxRect.height * heightRatio)
                });
            });
    
            // Conversion en texte pour le PHP
            document.getElementById('stickers_data').value = JSON.stringify(stickersArray);
    
            const gallery = document.querySelector('.app-shots-grid');
            const empty_dropzone = document.querySelector('.empty-dropzone');
            

            // Envoi silencieux au serveur
            fetch('/studio/capture', {
                method: "POST",
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                console.log("Serveur :", data);
                allStickers.forEach(box => box.remove());
                captureButton.disabled = true;
                
                const newDiv = document.createElement("div");
                const img = document.createElement("img");
                img.className = "shot-thumbnail";
                img.setAttribute("data-filename", data.fileName);
                img.style.cursor = "pointer";
				img.setAttribute("data-published", "0"); // Ajoutez cette ligne lors de la création de la miniature

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

    document.addEventListener('keydown', function(event) {
        if (galleryModal.open) {
            if (event.key === 'ArrowLeft') {
                buttonBackModal.click();
            } else if (event.key === 'ArrowRight') {
                buttonNextModal.click();
            }
        }
    });

    // ==========================================
    // GESTION DES ONGLETS (NAVIGATEUR)
    // ==========================================
    function switchTab(event, tabId) {
        event.preventDefault();

        // 1. Masquer tous les contenus via la classe CSS
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // 2. Désactiver tous les boutons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // 3. Activer le bouton cliqué et son contenu
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }

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

// ==========================================
// GESTION DES FILTRES (Liée à face-api.js)
// ==========================================
const filterItems = document.querySelectorAll('.app-filter-item');

filterItems.forEach(item => {
    item.addEventListener('click', function() {
        const filterFile = this.getAttribute('data-filter');
        
        // Au lieu de créer un élément HTML, on met à jour la source de l'image
        // que face-api.js est en train d'utiliser en temps réel
        activeFilterImage.src = '/filters/' + filterFile;

        // On active le bouton pour prendre la photo
        document.querySelector('.app-btn-save').disabled = false;
    });
});

// ==========================================
// BOUTON REMOVE (UNIFIÉ)
// ==========================================
const removeButton = document.getElementById('remove-button');
removeButton.addEventListener('click', function() {
    
    // 1. Supprime tous les stickers statiques posés sur l'écran
    const allStickers = document.querySelectorAll('.sticker-box');
    allStickers.forEach(box => box.remove());
    
    // 2. Désactive le filtre dynamique sur le visage
    activeFilterImage.src = ''; 
    
    // 3. Désactive le bouton de capture
    document.querySelector('.app-btn-save').disabled = true;
});

</script>