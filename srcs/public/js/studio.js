/**
 * Face API configuration and tracking
 * Handles loading AI models, detecting faces in the webcam and overlaying selected filters based on facial landmarks
 */

// Define the path to the face-api AI models
const MODEL_URI = "/vendor/face-api/models";

// Select the video element and its container
const videoElement = document.getElementById('video');
const videoContainer = document.querySelector('.canvas-placeholder');

// Create a new image object to hold selected filter
const activeFilterImage = new Image();
activeFilterImage.src = '';
let isTracking = false; // preventing multiple tracking loops

/**
 * Filters configuration
 * Defines how each specific filter should be positioned relative to the face
 * anchor: facial feature to attach the filter
 * widthRatio: size of the filter relative to the detected face width
 * offsetY: vertical adjustment (negative moves it up, positive moves it down)
 */
const filtersConfig = {
	'dog_ears.png': {
		anchor: 'head',
		widthRatio: 1.2,
		offsetY: -0.4
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

/**
 * Model loading
 * Loads required networks for face detection and landmark recognition
 */
Promise.all([
	faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URI),
	faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URI)
]).then(() => {
	console.log("Models are ready !");
}).catch((err) => {
	console.error("Loading error :", err);
});

// flag to ensure only one tracking runs at a time
window.isTracking = false;

/**
 * Triggered when the webcam stream starts playing
 */

videoElement.addEventListener("playing", () => {
	// Prevent multiple initialization
	if (window.isTracking) return;

	// Make sure video dimensions are available before proceed
	if (videoElement.videoWidth === 0) {
		setTimeout(() => {
			videoElement.dispatchEvent(new Event("playing"));
		}, 100);
		return;
	}

	window.isTracking = true;

	// Remove any existing overlay canvas to avoid duplicates
	const oldCanvas = document.getElementById('faceapi-overlay');
	if (oldCanvas) {
		oldCanvas.remove();
	}

	// Sync the video element resolution with actual stream resolution
	videoElement.width = videoElement.videoWidth;
	videoElement.height = videoElement.videoHeight;

	// Create a transparent canvas overlay directly above the video for drawing filters
	const canvas = faceapi.createCanvasFromMedia(videoElement);
	canvas.id = 'faceapi-overlay';
	canvas.style.position = 'absolute';
	canvas.style.top = '0';
	canvas.style.left = '0';
	canvas.style.zIndex = '10';
	canvas.style.pointerEvents = 'none';
	
	// Append the canvas
	const videoContainer = document.querySelector('.canvas-placeholder');
	if (videoContainer) videoContainer.appendChild(canvas);

	// offscreen canvas to process drawing without flickering
	const offscreenCanvas = document.createElement('canvas');

	// detector for speed and efficiency ///////////////////////////////////// TO RECHECK
	const detectorOptions = new faceapi.TinyFaceDetectorOptions({
		inputSize: 320,
		scoreThreshold: 0.3
	});

	// mattch canvas dimensions to the displayed video size
	const displaySize = {
		width: videoElement.clientWidth,
		height: videoElement.clientHeight
	};
	faceapi.matchDimensions(canvas, displaySize);
	offscreenCanvas.width = displaySize.width;
	offscreenCanvas.height = displaySize.height;

	/**
	 * Dectection and drawing loop
	 * Runs forever to process frames
	 */
	async function detectAndDraw() {
		// abort missions if anything is disabled or missing
		if (!window.isTracking || videoElement.paused || videoElement.ended || !videoElement.srcObject) {
			window.isTracking = false;
			return;
		}

		try {
			// asynchrone face detection on video frame //////////////////////////////// WHY ASYNCHROBE
			const detections = await faceapi
				.detectAllFaces(videoElement, detectorOptions)
				.withFaceLandmarks();

			// if stream abort, abord drawing
			if (!window.isTracking) return;

			// new frame processing
			const offCtx = offscreenCanvas.getContext("2d");
			offCtx.clearRect(0, 0, offscreenCanvas.width, offscreenCanvas.height);

			// only if a face is detected and a valid filter is selected
			if (detections && detections.length > 0 && activeFilterImage.src && activeFilterImage.complete && activeFilterImage.naturalWidth > 0) {
				
				// detection coordinates to match the display size 
				const resizedDetections = faceapi.resizeResults(detections, displaySize);
				
				// extract filter filename and configuration
				const srcParts = activeFilterImage.src.split('/');
				const currentFilterName = decodeURIComponent(srcParts[srcParts.length - 1]);
				
				// fetch position or fallback to no filter
				const config = filtersConfig[currentFilterName] || { anchor: 'head', widthRatio: 1.0, offsetY: 0 };

				// loop for all the faces
				resizedDetections.forEach(detection => {
					const box = detection.detection.box;
					const landmarks = detection.landmarks;
					
					// target width and aspect ratio
					const filterWidth = box.width * config.widthRatio;
					const ratio = activeFilterImage.naturalHeight / activeFilterImage.naturalWidth;
					const filterHeight = filterWidth * ratio;
					
					let x = 0; let y = 0;

					// x y coordinates based on anchor point
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

					// mirror config after several complaints from Amandine
					const mirroredX = displaySize.width - (x + filterWidth);
					
					// Draw filter on the canvas
					offCtx.drawImage(activeFilterImage, mirroredX, y, filterWidth, filterHeight);
				});
			}

			// Transfer processed offscreen frame to canvas
			const visibleCtx = canvas.getContext("2d");
			visibleCtx.clearRect(0, 0, canvas.width, canvas.height);
			visibleCtx.drawImage(offscreenCanvas, 0, 0);

		} catch (e) {
			// Silent fail
		}

		// Relaunch the loop
		if (window.isTracking) {
			requestAnimationFrame(detectAndDraw);
		}
	}
	
	detectAndDraw();
});