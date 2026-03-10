<div class="studio-app-wrapper">
    <div class="studio-app-window">

        <aside class="app-left-panel">
            <div class="app-search-bar">
                <input type="text" placeholder="Find a sticker ...">
                <button type="button" class="app-btn-small">OK</button>
            </div>

            <form action="/studio/capture" method="POST" enctype="multipart/form-data" id="mainCaptureForm" class="app-sticker-form">
                
                <input type="hidden" name="image_data" id="image_data">
                <input type="hidden" name="stickers_data" id="stickers_data">

                <div class="app-sticker-grid">
                    <?php if (!empty($stickers)): ?>
                        <?php foreach ($stickers as $index => $sticker): ?>
                            <div class="app-sticker-item" data-sticker="<?= htmlspecialchars($sticker) ?>">
                                <img src="/stickers/<?= htmlspecialchars($sticker) ?>" alt="Sticker">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="app-empty-text">No stickers.</p>
                    <?php endif; ?>
                </div>
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
            
            <div style="display: flex; justify-content: center;">
                <button type="submit" form="mainCaptureForm" class="app-btn-save" disabled>
                    Take a picture
                </button>
            </div>
        </main>

        <aside class="app-right-panel">
            <div class="right-panel-tools">
                <h3 class="panel-title">My Shots</h3>
                
                <div class="app-shots-grid">
                    <?php if (empty($userImages)): ?>
                        <div class="empty-dropzone">
                            <p>No photos yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userImages as $img): ?>
                            <div class="app-shot-item">
                                <img src="/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Shot">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" id="button-manage-gallery">Manage my gallery</button>

        </aside>

    </div>
</div>

<dialog id="gallery-modal" style="margin: auto; padding: 20px; border-radius: 8px; border: none; max-width: 80vw;">
    <h3 style="margin-top: 0;">My gallery</h3>


    <div id="modal-grid-view" style="display: flex; flex-wrap: wrap; gap: 10px;">
        <?php if (!empty($userImages)): ?>
            <?php foreach ($userImages as $img): ?>
                <img src="/uploads/<?= htmlspecialchars($img['filename']) ?>" 
                     class="gallery-thumbnail" 
                     data-filename="<?= htmlspecialchars($img['filename']) ?>" 
                     style="width: 120px; height: 90px; object-fit: cover; cursor: pointer; border-radius: 4px;">
            <?php endforeach; ?>
        <?php else: ?>
            <p>No photos yet.</p>
        <?php endif; ?>
    </div>


    <div id="modal-detail-view" style="display: none; text-align: center;">
        <button id="back-button" class="app-btn-small" style="margin-bottom: 15px;">Back to the gallery</button>
        <br>
        <img id="detail-large-image" style="max-width: 100%; max-height: 50vh; border-radius: 8px;">
        
        <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
            <button id="btn-publish" class="app-btn-small" style="background-color: #4CAF50;">Publish</button>
            <button id="btn-share" class="app-btn-small" style="background-color: #1DA1F2;">Share</button>
            <button id="btn-delete" class="app-btn-small" style="background-color: #ff4444;">Delete</button>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: right;">
        <button type="button" id="button-close-modal" class="app-btn-small">Close window</button>
    </div>
</dialog>

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
    

        /////// MODALE D'OUVERTURE DE LA GALERIE 
        const galleryModal = document.getElementById('gallery-modal');
        const buttonManageGallery = document.getElementById('button-manage-gallery');
        const buttonClose = document.getElementById('button-close-modal');

        buttonManageGallery.addEventListener('click', function() {
            galleryModal.showModal(); // On ouvre le <dialog>
        });

        buttonClose.addEventListener('click', function() {
            galleryModal.close(); // On ferme le <dialog>
            // On réinitialise l'affichage pour la prochaine ouverture
            document.getElementById('modal-grid-view').style.display = 'flex';
            document.getElementById('modal-detail-view').style.display = 'none';
        });


        ////////// AFFICHAGE DE LA GRILLE
        const modalGridView = document.getElementById('modal-grid-view');
        const modalDetailView = document.getElementById('modal-detail-view');
        const detailLargeImage = document.getElementById('detail-large-image');
        const backButton = document.getElementById('back-button');

        // Qu'est-ce qu'on est en train de regarder actuellement?
        let currentEditingImage = '';

        // On attache un événement de clic à chaque miniature de la grille
        const thumbnails = document.querySelectorAll('.gallery-thumbnail');
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // On lit l'adresse de l'image cliquée et son nom de fichier
                const imageSrc = this.src;
                currentEditingImage = this.getAttribute('data-filename');

                // On met à jour la grande image
                detailLargeImage.src = imageSrc;

                // Bascule visuelle
                modalGridView.style.display = 'none';
                modalDetailView.style.display = 'block';
            });
        });

        // Go back
        backButton.addEventListener('click', function() {
            modalDetailView.style.display = 'none';
            modalGridView.style.display = 'flex';
            currentEditingImage = ''; // On vide la mémoire
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
            reader.readAsDataURL(this.files[0]);
            reader.onload = () => {
                
                uploadedImage.src = reader.result;

                // pour éviter de garder le bouton de la caméra allumé
                video.srcObject.getTracks().forEach(track => track.stop());
                video.style.display = 'none';
                uploadedImage.style.display = 'block';
            }
        }
    })

    const canvasPlaceholder = document.querySelector('.canvas-placeholder');
    const stickerItems = document.querySelectorAll('.app-sticker-item');

    stickerItems.forEach(item => {
        item.addEventListener('click', function() {
            const stickerFile = this.getAttribute('data-sticker');
            
            const newBox = document.createElement('div');
            newBox.className = 'sticker-box';
            newBox.style.position = 'absolute';
            newBox.style.top = '20px';
            newBox.style.left = '20px';
            newBox.style.width = '120px';
            newBox.style.height = '120px';
            newBox.style.border = '2px dashed #ff00aa';
            newBox.style.resize = 'both';
            newBox.style.overflow = 'hidden';
            newBox.style.cursor = 'move';
            newBox.style.zIndex = '10';

            const newImg = document.createElement('img');
            newImg.src = '/stickers/' + stickerFile;
            newImg.style.width = '100%';
            newImg.style.height = '100%';
            newImg.style.pointerEvents = 'none';

            newImg.onload = function() {
                const ratio = newImg.naturalHeight / newImg.naturalWidth;
                newBox.style.width = '120px';
                newBox.style.height = Math.round(120 * ratio) + 'px';
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
					const maxLeft = canvasPlaceholder.clientWidth - newBox.offsetWidth;
					const maxTop = canvasPlaceholder.clientHeight - newBox.offsetHeight;

					// Blocage des coordonnées pour ne pas déborder (0 = bord haut/gauche)
					if (newLeft < 0) newLeft = 0;
					if (newTop < 0) newTop = 0;
					if (newLeft > maxLeft) newLeft = maxLeft;
					if (newTop > maxTop) newTop = maxTop;

					newBox.style.left = newLeft + 'px';
					newBox.style.top = newTop + 'px';
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
    
                img.src = "/uploads/" + data.fileName;
                newDiv.appendChild(img);
                newDiv.className = "app-shot-item";
    
                if (empty_dropzone)
                    empty_dropzone.remove();
                gallery.prepend(newDiv);

                const newModalThumb = document.createElement("img");
                newModalThumb.src = "/uploads/" + data.fileName;
                newModalThumb.className = "gallery-thumbnail";
                newModalThumb.setAttribute("data-filename", data.fileName);

                newModalThumb.style.width = "120px";
                newModalThumb.style.height = "90px";
                newModalThumb.style.objectFit = "cover";
                newModalThumb.style.cursor = "pointer";
                newModalThumb.style.borderRadius = "4px";

                newModalThumb.addEventListener('click', function() {
                    const detailLargeImage = document.getElementById('detail-large-image');
                    const modalGridView = document.getElementById('modal-grid-view');
                    const modalDetailView = document.getElementById('modal-detail-view');
                    
                    detailLargeImage.src = this.src;
                    currentEditingImage = this.getAttribute('data-filename');
                    
                    modalGridView.style.display = 'none';
                    modalDetailView.style.display = 'block';
                });

                const modalGridView = document.getElementById('modal-grid-view');
                const emptyModalText = modalGridView.querySelector('p'); // On cherche le <p> "No photos yet."
                if (emptyModalText) {
                    emptyModalText.remove();
                }

                // On insère la nouvelle miniature tout en haut de la grille
                modalGridView.prepend(newModalThumb);
            })
            .catch(error => console.error("Request error :", error));
            
        }, 3000);


        
    });

    const remove_stickers = document.getElementById('remove-button');

        remove_stickers.addEventListener('click', function() {
            const allStickers = document.querySelectorAll('.sticker-box');
            allStickers.forEach(box => box.remove());
            captureButton.disabled = true;
        });
</script>