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
                    <button type="button" class="toolbar-tool">📷<span>Webcam</span></button>
                    <label class="toolbar-tool upload-trigger">
                        ⬆<span>Upload</span>
                        <input type="file" name="userfile" accept="image/jpeg,image/png" style="display:none;">
                    </label>
                </div>
                <div class="toolbar-group">
                    <button type="button" class="toolbar-tool disabled">🗑️<span>Remove</span></button>
                    <button type="button" class="toolbar-tool disabled">⚙️<span>Settings</span></button>
                </div>
            </div>

            <div class="app-canvas-area">
                <div class="canvas-placeholder" style="position: relative;">
                    <video id="video" autoplay style="max-width: 100%; border-radius: 8px;"></video>
                    
                    <canvas id="canvas" style="display:none;"></canvas>

                </div>
            </div>
            
            <div class="app-info-banner">
                ℹ️ You can use your webcam or upload a picture (JPG, PNG).
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

            <div class="right-panel-actions">
                
                <button type="submit" form="mainCaptureForm" class="app-btn-save">
                    ⬇ Capture
                </button>
            </div>
            </form> 
        </aside>

    </div>
</div>

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
        });
    });

    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    const captureButton = document.querySelector('.app-btn-save');
    const hiddenInput = document.getElementById('image_data');
    const form = document.getElementById('mainCaptureForm');

    captureButton.addEventListener('click', function(event) {
        event.preventDefault();

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        hiddenInput.value = canvas.toDataURL('image/png');

        // Calculs des ratios
        const videoRect = video.getBoundingClientRect();
        const widthRatio = video.videoWidth / videoRect.width;
        const heightRatio = video.videoHeight / videoRect.height;
        
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
            const exactDiffX = boxRect.left - videoRect.left;
            const exactDiffY = boxRect.top - videoRect.top;

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
            
            const newDiv = document.createElement("div");
            const img = document.createElement("img");

            img.src = "/uploads/" + data.fileName;
            newDiv.appendChild(img);
            newDiv.className = "app-shot-item";

            if (empty_dropzone)
                empty_dropzone.remove();
            gallery.prepend(newDiv);
        })
        .catch(error => console.error("Erreur de requête :", error));
    });
</script>