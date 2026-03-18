const MODEL_URI = "/vendor/face-api/models";
const videoElement = document.getElementById('video');
const videoContainer = document.querySelector('.canvas-placeholder');
const activeFilterImage = new Image();
activeFilterImage.src = '/stickers/dog_ears.png';
let isTracking = false;

Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URI),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URI)
]).then(() => {
    console.log("Modèles IA prêts !");
}).catch((err) => {
    console.error("Erreur de chargement :", err);
});

videoElement.addEventListener("playing", () => {
    if (isTracking) return;
    isTracking = true;

    videoElement.width = videoElement.videoWidth;
    videoElement.height = videoElement.videoHeight;

    const canvas = faceapi.createCanvasFromMedia(videoElement);
    canvas.id = 'faceapi-overlay';
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.zIndex = '10';
    canvas.style.pointerEvents = 'none';
    videoContainer.appendChild(canvas);

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
        if (videoElement.paused || videoElement.ended) {
            isTracking = false;
            return;
        }

        try {
            const detections = await faceapi
                .detectAllFaces(videoElement, detectorOptions)
                .withFaceLandmarks();

            const offCtx = offscreenCanvas.getContext("2d");
            offCtx.clearRect(0, 0, offscreenCanvas.width, offscreenCanvas.height);

            if (detections && detections.length > 0) {
                const resizedDetections = faceapi.resizeResults(detections, displaySize);

                resizedDetections.forEach(detection => {
                    const box = detection.detection.box;
                    const filterWidth = box.width * 1.2;
                    const ratio = activeFilterImage.naturalHeight / activeFilterImage.naturalWidth;
                    const filterHeight = filterWidth * ratio;
                    const x = box.x - (filterWidth - box.width) / 2;
                    const y = box.y - (filterHeight * 0.45);

                    if (activeFilterImage.complete && activeFilterImage.naturalWidth > 0) {
                        offCtx.drawImage(activeFilterImage, x, y, filterWidth, filterHeight);
                    }
                });
            }

            const visibleCtx = canvas.getContext("2d");
            visibleCtx.clearRect(0, 0, canvas.width, canvas.height);
            visibleCtx.drawImage(offscreenCanvas, 0, 0);

        } catch (e) {
            console.error("Erreur :", e);
        }

        setTimeout(detectAndDraw, 50);
    }

    detectAndDraw();
});