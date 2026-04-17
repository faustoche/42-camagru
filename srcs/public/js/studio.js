const MODEL_URI = "/vendor/face-api/models";
const videoElement = document.getElementById('video');
const videoContainer = document.querySelector('.canvas-placeholder');
const activeFilterImage = new Image();
activeFilterImage.src = ''; // ON le laisse à vide
let isTracking = false;

// Dictionnaire pour configuré les filtres
const filtersConfig = {
    'dog_ears.png': {
        anchor: 'head',  // S'accroche sur le haut de la boîte du visage
        widthRatio: 1.2, // 120% de la largeur du visage
        offsetY: -0.4   // Remonte de 45% vers le haut
    },
    'cat_ears.png': {
        anchor: 'head',
        widthRatio: 1.2,
        offsetY: -0.65
    },
    'cat_face.png': {
        anchor: 'head',
        widthRatio: 1.4,
        offsetY: -0.45
    },
    'couronne.png': {
        anchor: 'head',
        widthRatio: 0.9,
        offsetY: -1.5
    },
    'tiare.png': {
        anchor: 'head',
        widthRatio: 0.9,
        offsetY: -1
    },
    'shrek_ears.png': {
        anchor: 'head',
        widthRatio: 1.2,
        offsetY: -0.7
    },
    'crown.png': {
        anchor: 'head',
        widthRatio: 1.3,
        offsetY: -0.7
    },
    'pink_hair.png': {
        anchor: 'head',
        widthRatio: 1.3,
        offsetY: -0.4
    },
    'dalmatien.png': {
        anchor: 'head',
        widthRatio: 1.3,
        offsetY: -0.2
    },
    'raccoon.png': {
        anchor: 'head',
        widthRatio: 0.9,
        offsetY: -0.3
    },
    'rainbow.png': {
        anchor: 'head',
        widthRatio: 0.8,
        offsetY: 0.2
    },
    'hello_kitty.png': {
        anchor: 'head',
        widthRatio: 1.8,
        offsetY: -0.25
    },
    'labubu.png': {
        anchor: 'head',
        widthRatio: 1.6,
        offsetY: -0.3
    },

    'cute.png': {
        anchor: 'head',
        widthRatio: 0.7,
        offsetY: 0.1
    },
    'shy.png': {
        anchor: 'head',
        widthRatio: 0.7, 
        offsetY: 0.6
    },

};

Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URI),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URI)
]).then(() => {
    console.log("Models are ready !");
}).catch((err) => {
    console.error("Loading error :", err);
});
// Déclaration globale
window.isTracking = false;

videoElement.addEventListener("playing", () => {
    if (window.isTracking) return;

    if (videoElement.videoWidth === 0) {
        setTimeout(() => {
            videoElement.dispatchEvent(new Event("playing"));
        }, 100);
        return;
    }

    window.isTracking = true;

    const oldCanvas = document.getElementById('faceapi-overlay');
    if (oldCanvas) {
        oldCanvas.remove();
    }

    videoElement.width = videoElement.videoWidth;
    videoElement.height = videoElement.videoHeight;

    const canvas = faceapi.createCanvasFromMedia(videoElement);
    canvas.id = 'faceapi-overlay';
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.zIndex = '10';
    canvas.style.pointerEvents = 'none';
    
    const videoContainer = document.querySelector('.canvas-placeholder');
    if (videoContainer) videoContainer.appendChild(canvas);

    const offscreenCanvas = document.createElement('canvas');

    const detectorOptions = new faceapi.TinyFaceDetectorOptions({
        inputSize: 320,
        scoreThreshold: 0.3
    });

    const displaySize = {
        width: videoElement.clientWidth,
        height: videoElement.clientHeight
    };
    faceapi.matchDimensions(canvas, displaySize);
    offscreenCanvas.width = displaySize.width;
    offscreenCanvas.height = displaySize.height;

    async function detectAndDraw() {
        // Sécurité en début de boucle
        if (!window.isTracking || videoElement.paused || videoElement.ended || !videoElement.srcObject) {
            window.isTracking = false;
            return;
        }

        try {
            const detections = await faceapi
                .detectAllFaces(videoElement, detectorOptions)
                .withFaceLandmarks();

            // Sécurité post-analyse : on abandonne le dessin si le flux a été coupé entre-temps
            if (!window.isTracking) return;

            const offCtx = offscreenCanvas.getContext("2d");
            offCtx.clearRect(0, 0, offscreenCanvas.width, offscreenCanvas.height);

            if (detections && detections.length > 0 && activeFilterImage.src && activeFilterImage.complete && activeFilterImage.naturalWidth > 0) {
                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                const srcParts = activeFilterImage.src.split('/');
                const currentFilterName = decodeURIComponent(srcParts[srcParts.length - 1]);
                
                const config = filtersConfig[currentFilterName] || { anchor: 'head', widthRatio: 1.0, offsetY: 0 };

                resizedDetections.forEach(detection => {
                    const box = detection.detection.box;
                    const landmarks = detection.landmarks;
                    
                    const filterWidth = box.width * config.widthRatio;
                    const ratio = activeFilterImage.naturalHeight / activeFilterImage.naturalWidth;
                    const filterHeight = filterWidth * ratio;
                    
                    let x = 0; let y = 0;

                    if (config.anchor === 'head') {
                        x = box.x - (filterWidth - box.width) / 2;
                        y = box.y + (filterHeight * config.offsetY);
                    } 
                    else if (config.anchor === 'nose') {
                        const nose = landmarks.getNose();
                        const noseTip = nose[3]; 
                        x = noseTip.x - (filterWidth / 2);
                        y = noseTip.y - (filterHeight / 2) + (filterHeight * config.offsetY);
                    } 
                    else if (config.anchor === 'mouth') {
                        const mouth = landmarks.getMouth();
                        const mouthCenterX = mouth.reduce((sum, pt) => sum + pt.x, 0) / mouth.length;
                        const mouthCenterY = mouth.reduce((sum, pt) => sum + pt.y, 0) / mouth.length;
                        x = mouthCenterX - (filterWidth / 2);
                        y = mouthCenterY - (filterHeight / 2) + (filterHeight * config.offsetY);
                    }

                    const mirroredX = displaySize.width - (x + filterWidth);
                    offCtx.drawImage(activeFilterImage, mirroredX, y, filterWidth, filterHeight);
                });
            }

            const visibleCtx = canvas.getContext("2d");
            visibleCtx.clearRect(0, 0, canvas.width, canvas.height);
            visibleCtx.drawImage(offscreenCanvas, 0, 0);

        } catch (e) {
            // Silencieux en cas de coupure de flux vidéo brutale
        }

        // Relance de la boucle seulement si le tracking est toujours autorisé
        if (window.isTracking) {
            requestAnimationFrame(detectAndDraw);
        }
    }

    detectAndDraw();
});