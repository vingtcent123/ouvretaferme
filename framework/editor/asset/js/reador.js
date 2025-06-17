document.delegateEventListener('click', 'a[data-action="figure-extend"]', function(e) {

	const figureId = this.getAttribute('data-figure');
	const figureSelector = qs(figureId);

	const media = this.firstParent('div.editor-media');

	media.qs('div.reador-cut-link').remove();
	media.insertAdjacentHTML('afterend', Reador.cut[figureId][1]);

	const instanceId = '#'+ figureSelector.parentElement.id;

	Reador.reorganizeInstance(instanceId);

});

class Reador {

	static cut = {};

	static reorganize() {

		document.body.qsa('div.reador', reador => Reador.reorganizeInstance(reador));

	};

	static reorganizeInstance(instanceId) {

		qsa(instanceId +' figure[data-interactive="true"]', figureSelector => {

			if(figureSelector.childNodes.filter('.editor-media').length === 0) {
				return;
			}

			new GalleryEditor(figureSelector, {
				container: '.editor-media',
				onReorganized: () => ReaditorFigure.reorganize(figureSelector)
			}).listenResize();

			Reador.linkCut(figureSelector);

		});

	};

	static linkCut(figureSelector) {

		const cut = parseInt(figureSelector.getAttribute('data-cut'));

		if(cut > 0) {

			const mediaSelector = figureSelector.childNodes.filter('.editor-media:last-child')[0];
			const cutLink = Reador.cut['#'+ figureSelector.id][0];

			mediaSelector.insertAdjacentHTML('beforeend', cutLink);


		}

		figureSelector.removeAttribute('data-cut');

	};

	static saveCut(figureId, figureCut) {
		Reador.cut[figureId] = figureCut;
	}

}


/**
 * Manages the zoom on images
 *
 */
document.delegateEventListener('click', 'div.reador div.editor-image', function (e) {

	if(ReadorZoom.canZoom() === false) {
		return;
	}

	if(ReadorZoom.isZoomed(this)) {
		ReadorZoom.dezoom();
	} else {
		ReadorZoom.zoom(this);
	}

});

document.delegateEventListener('click', '#editor-image-backdrop, [data-action="reador-dezoom"]', function(e) {
	if(ReadorZoom.canZoom()) {
		ReadorZoom.dezoom();
	}
});

document.addEventListener('scroll', function() {
	if(ReadorZoom.canZoom()) {
		ReadorZoom.dezoom();
	}
});

document.addEventListener('keyup', function(e) {

	if(e.key === 'Escape' && ReadorZoom.canZoom()) {
		ReadorZoom.dezoom();
	}

});

document.addEventListener('navigation.wakeup', () => {
	qs('#editor-image-backdrop', node => node.style.display = 'none');
});

class ReadorZoom {

	static speed = 0.4;
	static isZooming = false; // if a zooming or a dezooming is being

	static canZoom() {
		return (
			ReadorZoom.isZooming === false
		);
	}

	static getZoomSize(width, height) {

		// Always 1000 for vertical photos
		if(width / height < 0.8) {
			return 1000;
		}

		if(window.matchMedia('(min-width: 1200px)').matches) {
			return 2000;
		}

		if(window.matchMedia('(min-width: 800px)').matches) {
			return 1000;
		}

		return 750;

	}

	static zoom(item) {

		ReadorZoom.startZooming(item);

		document.body.style.overflowX = 'hidden';

		const success = ReadorZoom.displayZoomedItem(item, function(item, positionX) {

			item.style.transition = 'margin ' + ReadorZoom.speed + 's, transform ' + ReadorZoom.speed + 's, width ' + ReadorZoom.speed + 's, height ' + ReadorZoom.speed + 's';
			item.style.marginLeft = positionX +'px';

			item.classList.add('editor-image-zoomed');

		});

		if(success === false) {
			return false;
		}

		if(qs('#editor-image-backdrop') === null) {
			document.body.insertAdjacentHTML('beforeend', '<div id="editor-image-backdrop"></div><a id="editor-image-backdrop-close" data-action="reador-dezoom">'+ Lime.Asset.icon('x') +'</a>');
		}

		setTimeout(() => ReadorZoom.stopZooming(item), ReadorZoom.speed * 1000);

	}

	static startZooming(item) {
		ReadorZoom.isZooming = true;
		item.classList.add('editor-image-zooming');
	}

	static stopZooming(item) {
		ReadorZoom.isZooming = false;
		item.classList.remove('editor-image-zooming');
	}

	static displayZoomedItem(item, positionCallback) {

		const media = item.parentElement;
		const image = item.qs('img');

		if(image === null) {
			return false;
		}

		const xyz = media.getAttribute('data-xyz');
		const url = image.getAttribute('src');

		let isLoaded = false;
		let displayHeight;
		let displayWidth;

		// If XYZ, load big images if the one actually display is too small
		if(xyz) {

			const sourceWidth = parseInt(media.getAttribute('data-w'));
			const sourceHeight = parseInt(media.getAttribute('data-h'));

			const urlMatch = url.match(/\/photo\/([0-9]{3,4})\//);

			if(urlMatch) {

				const currentSize = parseInt(urlMatch[1]);
				const requiredSize = ReadorZoom.getZoomSize(sourceWidth, sourceHeight);

				if(currentSize < requiredSize) {

					const reductor = Math.max(1, Math.max(sourceWidth, sourceHeight) / requiredSize);

					displayWidth = Math.round(sourceWidth / reductor);
					displayHeight = Math.round(sourceHeight / reductor);

					const newUrl = url.replace('/photo/' + currentSize + '/', '/photo/' + requiredSize + '/');

					const imageBigger = new Image();
					imageBigger.src = newUrl;
					imageBigger.onload = function() {

						image.remove();

						item.insertAdjacentElement('beforeend', image);

					}

					isLoaded = true;

				}

			}

			if(isLoaded === false) {

				const image = new Image();
				image.src = url;

				displayWidth = image.width;
				displayHeight = image.height;

			}

		}

		let deltaX = 0;
		let deltaY = 0;

		const screenWidth = window.innerWidth;
		const screenHeight = window.innerHeight;

		let containerWidth = screenWidth;
		let containerHeight = screenHeight;

		containerHeight -= 40; // Margin for metadata

		if(media.parentElement.getAttribute('data-length') !== '1') {

			containerWidth -= 100;
			deltaX += 50;

			containerHeight -= 50;
			deltaY += 25;

		}

		let imageWidth;
		let imageHeight;

		if(displayWidth > containerWidth || displayHeight > containerHeight) {

			const ratio = Math.min(containerWidth / displayWidth, containerHeight / displayHeight);

			imageWidth = displayWidth * ratio;
			imageHeight = displayHeight * ratio;

		} else {

			imageWidth = Math.min(displayWidth, containerWidth);
			imageHeight = Math.min(displayHeight, containerHeight);

		}

		// Get current position of the image
		const imageZoomedPositionLeft = Math.floor((containerWidth - imageWidth) / 2),
			imageZoomedPositionTop = Math.floor(window.scrollY + (containerHeight - imageHeight) / 2);

		const imagePositionTop = item.getBoundingClientRect().top + window.scrollY,
			imagePositionLeft = item.getBoundingClientRect().left + window.scrollX;

		const positionX = Math.floor(imageZoomedPositionLeft - imagePositionLeft) + deltaX,
			positionY = Math.floor(imageZoomedPositionTop - imagePositionTop) + deltaY;

		item.style.marginTop = positionY +'px';
		item.style.width = imageWidth +'px';
		item.style.height = imageHeight +'px';

		positionCallback(item, positionX, imageWidth);

		const reador = item.firstParent('.reador');

		// Timeout when animation loading ends
		setTimeout(() => {

			ReadorZoomNavigator.init(item);

		}, ReadorZoom.speed * 1000);

		return true;

	}

	static hideZoomedItem(item, delay) {

		item.qsa('.nav-left, .nav-right', node => node.remove());

		window.setTimeout(function() {

			item.style.marginLeft = '';
			item.style.marginTop = '';
			item.style.width = '';
			item.style.height = '';

		}, 0);

		window.setTimeout(function() {
			item.classList.remove('editor-image-zoomed');
		}, delay);

	}

	static dezoom() {

		const item = qs('.editor-image-zoomed');

		if(item === null) {
			return;
		}

		ReadorZoom.startZooming(item);

		item.style.transition = 'margin '+ ReadorZoom.speed +'s, transform '+ ReadorZoom.speed +'s, width '+ ReadorZoom.speed +'s, height '+ ReadorZoom.speed +'s';

		ReadorZoom.hideZoomedItem(item, ReadorZoom.speed * 1000);

		const backdrop = qs('#editor-image-backdrop');

		backdrop.style.transition = ReadorZoom.speed +'s';
		backdrop.style.backgroundColor = 'transparent';

		qs('#editor-image-backdrop-close').remove();

		setTimeout(function() {

			backdrop.remove();
			document.body.style.overflowX = 'auto';

			ReadorZoom.stopZooming(item);

		}, ReadorZoom.speed * 1000);

	}

	static isZoomed(item) {
		return item.classList.contains('editor-image-zoomed');
	}

};

class ReadorZoomNavigator {

	static canLeave = true;

	static init(item) {

		ReadorZoomNavigator.canLeave = true;

		const parentItem = item.firstParent('.editor-media[data-type="image"]');

		document.addEventListener('keyup', function(e) {

			if(ReadorZoom.isZoomed(item)) {
				if(e.key === 'ArrowLeft') {
					ReadorZoomNavigator.goLeft(parentItem);
				} else if(e.key === 'ArrowRight') {
					ReadorZoomNavigator.goRight(parentItem);
				}
			}
		}, {once: true});

		if(parentItem.firstNextSiblingMatches('.editor-media[data-type="image"]')) {

			item.insertAdjacentHTML('beforeend', '<div class="nav-right">'+ Lime.Asset.icon('chevron-right') +'</div>');
			item.qs('.nav-right').addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				ReadorZoomNavigator.goRight(parentItem);
			});

		}

		if(parentItem.firstPreviousSiblingMatches('.editor-media[data-type="image"]')) {

			item.insertAdjacentHTML('beforeend', '<div class="nav-left">'+ Lime.Asset.icon('chevron-left') +'</div>');
			item.qs('.nav-left').addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				ReadorZoomNavigator.goLeft(parentItem);
			});

		}

	}

	static goLeft(target) {

		if(ReadorZoomNavigator.canLeave === false) {
			return;
		}

		ReadorZoomNavigator.canLeave = false;
		ReadorZoom.isZooming = true;

		const previousTarget = target.firstPreviousSiblingMatches('.editor-media[data-type="image"]');

		if(previousTarget) {

			target.qs('.editor-image-zoomed').style.transition = 'none';
			ReadorZoom.hideZoomedItem(target.qs('.editor-image-zoomed'), 0);

			window.setTimeout(function() {
				ReadorZoomNavigator.canLeave = true;
				ReadorZoom.isZooming = false;
			}, ReadorZoom.speed * 1000);

			const previousImage = previousTarget.qs('.editor-image');

			if(previousImage !== null) {

				ReadorZoom.displayZoomedItem(previousImage, function(item, positionX, imageWidth) {

					item.style.marginLeft = (positionX - imageWidth) +'px';

					window.setTimeout(function() {

						item.style.transition = 'margin-left '+ ReadorZoom.speed +'s';
						item.style.marginLeft = positionX +'px';

						item.classList.add('editor-image-zoomed');

					}, 0);

				});

			}

		} else {
			ReadorZoom.dezoom(target.qs('.editor-image-zoomed'));
		}
	};

	static goRight(target) {

		if(ReadorZoomNavigator.canLeave === false) {
			return;
		}

		ReadorZoomNavigator.canLeave = false;
		ReadorZoom.isZooming = true;

		const nextTarget = target.firstNextSiblingMatches('.editor-media[data-type="image"]');

		if(nextTarget) {

			target.qs('.editor-image-zoomed').style.transition = 'none';
			ReadorZoom.hideZoomedItem(target.qs('.editor-image-zoomed'), 0);

			window.setTimeout(function() {
				ReadorZoomNavigator.canLeave = true;
				ReadorZoom.isZooming = false;
			}, ReadorZoom.speed * 1000);

			const nextImage = nextTarget.qs('.editor-image');

			if(nextImage !== null) {

				ReadorZoom.displayZoomedItem(nextImage, function(item, positionX, imageWidth) {

					item.style.marginLeft = (positionX - imageWidth) +'px';

					window.setTimeout(function() {

						item.style.transition = 'margin-left '+ ReadorZoom.speed +'s';
						item.style.marginLeft = positionX +'px';

						item.classList.add('editor-image-zoomed');

					}, 0);



				});

			}

		} else {
			ReadorZoom.dezoom(target.qs('.editor-image-zoomed'));
		}
	}

}