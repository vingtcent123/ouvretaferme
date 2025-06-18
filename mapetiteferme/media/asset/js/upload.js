class ImageLoader {

	static start(id, style, position, length) {

		let prefix = '';

		if(position !== undefined && length !== undefined && length > 1) {
			prefix = '['+ (position + 1) +' / '+ length +'] ';
		}

		let html = '<div class="progress-container" style="'+ (style ?? '') +'" id="progress-wrapper-' + id + '">';

			html += '<div class="progress progress-info" id="progress-' + id + '" data-widget="tmp" data-persistent="false">';
				html += '<div class="progress-bar"></div>';
			html += '</div>';
			html += '<div id="progress-state-' + id + '" data-widget="tmp" data-prefix="'+ prefix +'" class="progress-state">'+ prefix +'0 %</div>';

		html += '</div>';

		return html;

	};

	static update(id, percent) {

		const progressBar = qs('#progress-' + id +' .progress-bar');

		if(progressBar === null) {
			return;
		}

		const display = percent +'%';

		// Same progress value
		if(progressBar.style.width === display) {
			return;
		}

		progressBar.style.width = display;

		qs('#progress-state-' + id).innerHTML = qs('#progress-state-' + id).dataset.prefix + percent +' %';

		if(percent === 100) {
			ImageLoader.afterUpload(id);
		}

	};

	static afterUpload(id) {

		qs('#progress-state-' + id).innerHTML = qs('#progress-state-' + id).dataset.prefix + ImageMessage.treatment;

	};

	static remove(id) {
		qs('#progress-wrapper-' + id).remove();
	};

	static getPreview(file, img, previewSize, previewQuality) {

		const ratio = Math.min(1, previewSize / Math.max(img.naturalWidth, img.naturalHeight));

		if(file.size <= 3000000) {
			return null;
		}

		const canvasWidth = img.naturalWidth * ratio;
		const canvasHeight = img.naturalHeight * ratio;

		const canvas = document.createElement('canvas');
		canvas.width = canvasWidth;
		canvas.height = canvasHeight;

		const canvas2d = canvas.getContext('2d');
		canvas2d.fillStyle = 'white';
		canvas2d.fillRect(0, 0, canvasWidth, canvasHeight);
		canvas2d.drawImage(img, 0, 0, canvasWidth, canvasHeight);

		return canvas.toDataURL('image/jpeg', previewQuality);

	};

	static dataUrlToFile(url) {

		const BASE64_MARKER = ';base64,';

		if(url.indexOf(BASE64_MARKER) == -1) {

			let parts = url.split(',');
			let contentType = parts[0].split(':')[1];
			let raw = parts[1];

			return new Blob([raw], {type: contentType});
		}

		let parts = url.split(BASE64_MARKER);
		let contentType = parts[0].split(':')[1];
		let raw = window.atob(parts[1]);
		let rawLength = raw.length;

		let uInt8Array = new Uint8Array(rawLength);

		for(let i = 0; i < rawLength; ++i) {
			uInt8Array[i] = raw.charCodeAt(i);
		}

		return new Blob([uInt8Array], {type: contentType});

	}

}

document.delegateEventListener('navigation.sleep', function(e) {

	ImageStorage.kill();

});

document.delegateEventListener('click', 'a[data-action="upload-kill"]', function(e) {

	ImageStorage.kill();

});

class ImageStorage {

	static lastMaxParallel = 0;
	static killUpload = false;

	/**
	 * Upload a file
	 */
	static upload(files, maxFiles, type, onLoad, onError, maxParallel) {

		const accepts = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];

		maxParallel ??= 5;

		this.lastMaxParallel = maxParallel;

		if(files.length > maxFiles) {

			if(maxFiles === 1) {
				// We need to manage 1 et only 1 image
				Lime.Alert.showStaticError(ImageMessage.imageUploadOne);
			} else {
				Lime.Alert.showStaticError(ImageMessage.imageUploadLimit.replace('<number>', maxFiles));
			}

			return false;

		}

		// Checks files types
		let verifiedFiles = [];
		let errorTypeCounter = 0;

		for(let i = 0; i < files.length; i++) {

			const file = files[i];

			// Check image type
			if(accepts.indexOf(file.type) === -1) {
				errorTypeCounter++;
			} else {
				verifiedFiles[verifiedFiles.length] = file;
			}

		}

		if(errorTypeCounter > 0) {

			if(errorTypeCounter === files.length) {

				if(files.length === 1) {
					Lime.Alert.showStaticError(ImageMessage.imageTypeOne);
				} else {
					Lime.Alert.showStaticError(ImageMessage.imageTypeAll);
				}

				onError();

				return;

			} else {

				Lime.Alert.showStaticError(ImageMessage.imageTypeSeveral);

			}


		}

		this.readFiles(verifiedFiles, type, onLoad, onError, maxParallel);

	};

	static readFiles(files, type, onLoad, onError, maxParallel) {

		let started = 0;
		let done = 0;

		this.killUpload = false;
		this.newProgressCount(files.length);

		const startPromise = () => {

			if(
				this.killUpload || // Terminé sur demande de l'utilisateur
				done >= files.length // Plus de fichier à lire
			) {
				this.killProgress();
				return;
			}

			const position = started++;
			const file = files[position];

			if(file === undefined) {
				return;
			}

			new Promise(resolve => {

				this.readFile(
					file,
					type,
					(file, img) => {
						onLoad(resolve, file, position, img);
					},
					() => {
						onError(resolve);
					}
				);

			}).then(() => {

				done++;

				this.incrementProgressCurrent();

				startPromise();

			});

		}

		for(let parallel = 0; parallel < maxParallel; parallel++) {
			startPromise();
		}

		return true;

	};

	static kill() {
		this.killUpload = true;
	};

	static progressCount = 0;
	static progressCurrent = 0;

	static newProgressCount(number) {

		if(this.progressCount === 0) {

			let html = '<div id="upload-progress">';
				html += ImageMessage.imageProgressUpload;
			html += '</div>';

			qs('main').insertAdjacentHTML('beforeend', html);

			this.progressCurrent = 0;

		}

		this.progressCount += number;

		qs('#upload-progress').setAttribute('data-n', this.progressCount);
		qs('#upload-progress-current').innerHTML = this.progressCurrent + 1;
		qs('#upload-progress-count').innerHTML = this.progressCount;

	};

	static incrementProgressCurrent() {

		// Il a peut-être été tué entre temps
		if(qs('#upload-progress') === null) {
			return;
		}

		this.progressCurrent++;

		qs('#upload-progress-current').innerHTML = this.progressCurrent;

		if(this.progressCount - this.progressCurrent <= this.lastMaxParallel) {
			qs('#upload-progress-kill').style.display = 'none';
		} else {
			qs('#upload-progress-kill').style.display = 'block';
		}

	};

	static killProgress() {

		this.progressCount = 0;
		this.progressCurrent = 0;

		setTimeout(() => {
			qs('#upload-progress', node => node.remove());
		}, 250);

	};

	static readFile(file, type, onLoad, onError) {

		onLoad = onLoad || function(file, img) {

		};

		onError = onError || function() {

		};

		if(file.size === 0) {
			onError();
			return;
		}

		const img = new Image();

		img.onload = () => {

			if(this.checkErrors(file, type, img) === false) {
				onError();
			} else {
				onLoad(file, img);
			}

		};

		img.onerror = () => {
			onError();
		};

		img.src = URL.createObjectURL(file);

	};

	static checkErrors(file, type, img) {

		let error = null;

		// Check image weight
		if(ImageConf.sizeMax !== undefined && file.size > ImageConf.sizeMax * 1024 * 1024) {
			error = ImageMessage.imageSize;
		}

		// Check image min width and height
		const requiredSize = ImageConf.imagesRequiredSize[type];

		if(
			requiredSize !== undefined &&
			(
				(requiredSize.width === undefined && (img.width < requiredSize || img.height < requiredSize)) ||
				(img.width < requiredSize.width || img.height < requiredSize.height)
			)
		) {
			error = requiredSize.error;
		}

		if(error !== null) {
			Lime.Alert.showStaticError(error);
			return false;
		}

		return true;
	};

}

document.delegateEventListener('click', 'a[data-action="upload-crop"]', function(e) {

	const bounds = ImageCrop.getPosition(this.getAttribute('data-type'));

	ImageCrop.onSuccess(bounds);

});

class ImageCrop {

	static maxZoomFactor = 2; // 100% du slide correspond à un zoom x2 par rapport à la taille de référence
	static requiredZoomFactor = 1.1; // Required zoom factor to display slider
	static overlayDimension = 40;
	static onSuccess = null;

	static closePanel() {

		qs('#panel-media-crop', node => Lime.Panel.close(node));

	};

	static call(type, img, bounds, onSuccess) {

		this.onSuccess = onSuccess || function() { };

		const panel = qs('#panel-media-crop');

		if(panel === null) {
			throw 'No panel';
		}

		const preview = qs('#' + type + '-preview');

		const width = img.naturalWidth;
		const height = img.naturalHeight;

		const useWidth = parseInt(panel.getAttribute('data-width-use'));
		const useHeight = parseInt(panel.getAttribute('data-height-use'));

		const maxPreviewWidth = panel.qs('.panel-body').offsetWidth - 30 - 2 * this.overlayDimension;
		const maxPreviewHeight = Math.max(500, window.innerHeight - 500);

		const previewRatio = Math.min(1, Math.min(maxPreviewWidth / useWidth, maxPreviewHeight / useHeight));

		const previewWidth = useWidth * previewRatio;
		const previewHeight = useHeight * previewRatio;

		panel.setAttribute('data-width', previewWidth);
		panel.setAttribute('data-height', previewHeight);

		// Il faut chercher quel côté a le ratio le plus petit par rapport aux tailles de référence
		// Puis caler l'autre côté par rapport à ça
		const base = (width / previewWidth < height / previewHeight) ? 'width' : 'height';

		let newWidth;
		let newHeight;

		if(base === 'width') {

			newWidth = previewWidth;
			newHeight = Math.round(height * newWidth / width);

		} else {

			newHeight = previewHeight;
			newWidth = Math.round(width * newHeight / height);

		}

		// Set size of global preview container
		preview.style.width = (previewWidth + 2 * this.overlayDimension) + 'px';
		preview.style.height = (previewHeight + 2 * this.overlayDimension) + 'px';

		// Add resize window
		const borderSize = 2;
		const windowCorner = this.overlayDimension - borderSize;
		const windowWidth = previewWidth + borderSize * 2;
		const windowHeight = previewHeight + borderSize * 2;
		const windowShape = 'resize-window-'+ panel.getAttribute('data-shape');


		preview.insertAdjacentHTML(
			'beforeend',
			'<div class="resize-window '+ windowShape +'" style="top: '+ windowCorner +'px; left: '+ windowCorner +'px; width: ' + windowWidth + 'px; height: ' + windowHeight + 'px;"></div>'
		);

		// Add the image layer
		const canvas = this.getCanvas(img);
		canvas.setAttribute('id', type + '-image');
		canvas.setAttribute('class', 'resize-canvas');

		preview.insertAdjacentElement('beforeend', canvas);

		let imageWidth
		let imageHeight;
		let imageLeft;
		let imageTop;

		if(bounds) {

			imageWidth = previewWidth / (bounds.width / 100);
			imageHeight = previewHeight / (bounds.height / 100);

			imageLeft = -1 * (bounds.left / 100 * imageWidth) + this.overlayDimension;
			imageTop = -1 * (bounds.top / 100 * imageHeight) + this.overlayDimension;

		} else {

			// On centre l'image en haut et à gauche
			if(base === 'width') {
				imageTop = (previewHeight - newHeight) / 2 + this.overlayDimension;
				imageLeft = (newWidth - previewWidth) / 2 + this.overlayDimension;
			} else if(base === 'height') {
				imageTop = (newHeight - previewHeight) / 2 + this.overlayDimension;
				imageLeft = (previewWidth - newWidth) / 2 + this.overlayDimension;
			}

			imageWidth = newWidth;
			imageHeight = newHeight;

		}

		qs('#' + type + '-image', node => {
			node.style.top = imageTop+'px';
			node.style.left = imageLeft+'px';
			node.style.width = imageWidth+'px';
			node.style.height = imageHeight+'px';
		});

		if(width <= useWidth && height <= useHeight) {

			panel.qs('.panel-body').insertAdjacentHTML('beforeend', '<div class="resize-warning">'+
				''+ Lime.Asset.icon('exclamation-triangle-fill') +' '+ ImageMessage.imageNoResize +
			'</div>');

			return;

		}

		// Init zoom infos
		let zoomDefault = null;
		let zoomFactor = this.getZoomFactor(width, height, useWidth, useHeight);

		if(zoomFactor > ImageConf.imageCropRequiredFactor) {

			const currentFactor = (imageHeight / newHeight);
			zoomDefault = (currentFactor - 1) / (zoomFactor - 1) * 100;

		} else {
			zoomFactor = 1;
		}

		// Add the draggable layer
		preview.insertAdjacentHTML(
			'beforeend',
			'<div id="resize-'+ type +'-drag" class="resize-drag-container"></div>'
		);

		this.updateDragContainer(type, imageWidth, imageHeight, previewWidth, previewHeight);

		new ImagePan(canvas, qs('#resize-'+ type +'-drag'));

		// Add zoom slider
		if(zoomFactor > 1) {

			preview.insertAdjacentHTML('afterend', '<div id="' + type + '-zoom" class="resize-zoom">'+
				'<div class="resize-icon">'+ Lime.Asset.icon('dash') +'</div>'+
				'<input type="range" min="1" max="100" step="0.1" class="form-control" id="' + type + '-slider" value="'+ zoomDefault +'">'+
				'<div class="resize-icon">'+ Lime.Asset.icon('plus') +'</div>'+
			'</div>');

			const slider = qs('#' + type + '-slider');

			slider.addEventListener('input', e => {

				const factor = 1 + (zoomFactor - 1) * slider.value / 100;

				const image = qs('#' + type + '-image');

				const currentImageWidth = image.offsetWidth;
				const currentImageHeight = image.offsetHeight;

				const newImageWidth = Math.round(newWidth * factor);
				const newImageHeight = Math.round(newHeight * factor);

				const containerPosition = this.updateDragContainer(type, newImageWidth, newImageHeight, previewWidth, previewHeight);

				let newImageTop = image.offsetTop - (newImageHeight - currentImageHeight) / 2;
				let newImageLeft = image.offsetLeft - (newImageWidth - currentImageWidth) / 2;

				if(newImageTop < containerPosition.top) {
					newImageTop = containerPosition.top;
				}
				if(newImageLeft < containerPosition.left) {
					newImageLeft = containerPosition.left;
				}

				if(newImageTop + newImageHeight > containerPosition.top + containerPosition.height) {
					newImageTop = containerPosition.top + containerPosition.height - newImageHeight;
				}

				if(newImageLeft + newImageWidth > containerPosition.left + containerPosition.width) {
					newImageLeft = containerPosition.left + containerPosition.width - newImageWidth;
				}

				qs('#' + type + '-image', node => {
					node.style.top = newImageTop+'px';
					node.style.left = newImageLeft+'px';
					node.style.width = newImageWidth+'px';
					node.style.height = newImageHeight+'px';
				});

			});

		} else {

			preview.insertAdjacentElement('afterend', '<div class="resize-warning">'+
				'<div>'+ Lime.Asset.icon('exclamation-triangle-fill') +' '+ ImageMessage.imageNoZoom +'</div>'+
			'</div>');

		}

		qs('#' + type + '-zoom').style.display = 'flex';
		qs('#' + type + '-image').style.display = 'block';

	};

	static getZoomFactor(width, height, useWidth, useHeight) {

		let zoomFactor = this.maxZoomFactor;

		zoomFactor = Math.min(zoomFactor, width / useWidth);
		zoomFactor = Math.min(zoomFactor, height / useHeight);

		return zoomFactor;

	};

	static updateDragContainer(type, imageWidth, imageHeight, previewWidth, previewHeight) {

		const containerWidth = imageWidth * 2 - previewWidth;
		const containerHeight = imageHeight * 2 - previewHeight;
		const containerTop = this.overlayDimension - (containerHeight - previewHeight) / 2;
		const containerLeft = this.overlayDimension - (containerWidth - previewWidth) / 2;

		qs('#resize-'+ type +'-drag', node => {
			node.style.top = containerTop+'px';
			node.style.left = containerLeft+'px';
			node.style.width = containerWidth+'px';
			node.style.height = containerHeight+'px';
		});

		return {
			top: containerTop,
			left: containerLeft,
			width: containerWidth,
			height: containerHeight
		};

	};

	static getPosition(type) {

		const panel = qs('#panel-media-crop');
		const image = qs('#' + type + '-image');

		const previewWidth = parseInt(panel.getAttribute('data-width'));
		const previewHeight = parseInt(panel.getAttribute('data-height'));

		const width = image.offsetWidth;
		const height = image.offsetHeight;

		const imageTop = -1 * parseInt(image.style.top) + this.overlayDimension;
		const imageLeft = -1 * parseInt(image.style.left) + this.overlayDimension;

		let boundTop = Math.floor(imageTop / height * 100 * 10000) / 10000;
		boundTop = Math.max(boundTop, 0);
		boundTop = Math.min(boundTop, 90);

		let boundLeft = Math.floor(imageLeft / width * 100 * 10000) / 10000;
		boundLeft = Math.max(boundLeft, 0);
		boundLeft = Math.min(boundLeft, 90);

		let boundWidth = Math.floor(previewWidth / width * 100 * 10000) / 10000;
		boundWidth = Math.max(boundWidth, 10);
		boundWidth = Math.min(boundWidth, 100 - boundLeft);

		let boundHeight = Math.floor(previewHeight / height * 100 * 10000) / 10000;
		boundHeight = Math.max(boundHeight, 10);
		boundHeight = Math.min(boundHeight, 100 - boundTop);

		return {
			'top':  boundTop,
			'left': boundLeft,
			'width': boundWidth,
			'height': boundHeight
		};

	};

	static getCanvas(img) {

		const ratio = Math.min(1, 1000 / Math.max(img.naturalWidth, img.naturalHeight));

		const canvasWidth = Math.floor(img.naturalWidth * ratio);
		const canvasHeight = Math.floor(img.naturalHeight * ratio);

		const canvas = document.createElement('canvas');
		canvas.width = canvasWidth;
		canvas.height = canvasHeight;

		const canvas2d = canvas.getContext('2d');
		canvas2d.fillStyle = 'white';
		canvas2d.fillRect(0, 0, canvasWidth, canvasHeight);
		canvas2d.drawImage(img, 0, 0, canvasWidth, canvasHeight);

		return canvas;

	}

};

class ImagePan {

	constructor(selector, container) {

		let previousX;
		let previousY;

		const start = (e) => {

			previousX = e.clientX;
			previousY = e.clientY;

		}

		const end = (e) => {

			const selectorRect = selector.getBoundingClientRect();
			const containerRect = container.getBoundingClientRect();

			const minX = parseFloat(selectorRect.left) - parseFloat(containerRect.left);
			const maxX = parseFloat(selectorRect.right) - parseFloat(containerRect.right);

			const minY = parseFloat(selectorRect.top) - parseFloat(containerRect.top);
			const maxY = parseFloat(selectorRect.bottom) - parseFloat(containerRect.bottom);

			const deltaX = Math.max(maxX, Math.min(minX, previousX - e.clientX));
			const deltaY = Math.max(maxY, Math.min(minY, previousY - e.clientY));

			previousX = e.clientX;
			previousY = e.clientY;

			const style = getComputedStyle(selector);

			const newX = parseFloat(style.left) - deltaX;
			const newY = parseFloat(style.top) - deltaY;

			selector.style.left = newX +'px';
			selector.style.top = newY +'px';

		}

		selector.addEventListener('mousedown', (e) => {

			e.preventDefault();

			start(e);

		});

		selector.addEventListener('mousemove', function(e) {

			e.preventDefault();

			if(isMouseDown) {
				end(e);
			}

		});

		selector.addEventListener('touchstart', (e) => {

			e.preventDefault();

			start(e.changedTouches[0]);

		});

		selector.addEventListener('touchmove', function(e) {

			e.preventDefault();

			if(isTouchDown) {
				end(e.changedTouches[0]);
			}
		});

	}


}
