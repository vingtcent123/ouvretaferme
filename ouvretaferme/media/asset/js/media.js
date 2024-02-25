document.delegateEventListener('click', 'a[data-action="media-upload"]', function(e) {

	e.preventDefault();

	this.nextElementSibling.click();

});

document.delegateEventListener('click', 'a[data-action="media-resize"]', function(e) {

	e.preventDefault();

	const id = this.getAttribute('data-id');
	const type = this.getAttribute('data-type');

	new (Media.classes[type])().getCropById(this, id);

});

document.delegateEventListener('click', 'a[data-action="media-delete"]', function(e) {

	e.preventDefault();

	const id = this.getAttribute('data-id');
	const type = this.getAttribute('data-type');

	new (Media.classes[type])().delete(this, id, type);

});

/**
 * Handles croppable and resizable medias
 */
class Media {

	static classes = {};

	loader = null;
	type;
	maxFiles = 1;
	currentLoader = 0;

	static input(target) {

		const id = target.getAttribute('data-id');
		const type = target.getAttribute('data-type');
		const crop = parseInt(target.getAttribute('data-crop'));

		new (Media.classes[type])().upload(target, id, crop, target.files);

		//target.value = '';

	}

	// Saves the image data after cropping - zooming
	save(context, action, id, data, position, length) {

		this.lockSubmit();

		const loader = 'progress-'+ this.currentLoader++;

		if(qs('#progress-media') === null) {
			document.body.insertAdjacentHTML('beforeend', '<div id="progress-media"></div>');
		}

		qs('#progress-media').insertAdjacentHTML('beforeend', ImageLoader.start(loader, '', position, length));

		ImageCrop.closePanel();

		const media = this;

		new Ajax.Query()
			.url('/media/:'+ action)
			.body(data)
			.xmlHttpRequest(xhr => {

				xhr.upload.addEventListener("progress", function(e) {

					if(e.lengthComputable) {

						const percentComplete = Math.floor((e.loaded / e.total) * 100);
						ImageLoader.update(loader, percentComplete);

					}

				}, false);

			})
			.then((json) => {

				ImageLoader.update(loader, 100);
				media.removeProgress(loader);

				media.onUploaded(context, id, json);

				media.unlockSubmit();

			}, () => {

				media.unlockSubmit();
				media.removeProgress(loader);

				Lime.Alert.showStaticError(ImageMessage['internalCrop']);

			});

	};

	removeProgress(loader) {

		ImageLoader.remove(loader);

		if(qs('#progress-media').innerHTML === '') {
			qs('#progress-media').remove();
		}

	};

	showCamera(id, data) {
		this.refreshCamera(id, data);
	};

	hideCamera(id, data) {
		this.refreshCamera(id, data);
	};

	refreshCamera(id, data) {

		const selector = '.media-image-upload[data-id="' + id + '"][data-type="' + this.type + '"]';

		qsa(selector, node => {

			const style = node.getAttribute('style');

			const div = document.createElement('div');
			div.innerHTML = data.camera;

			const newNode = div.firstChild;
			newNode.setAttribute('style', style);

			node.parentElement.replaceChild(newNode, node);

		});

	};

	getCropById(context, id) {

		return this.getCrop(context, id, null);

	};

	getCropByHash(context, hash) {

		return this.getCrop(context, null, hash);

	};

	getCrop(context, id, hash) {

		const media = this;

		new Ajax.Query(context)
			.url('/media/:getCrop')
			.body({
				id: id,
				hash: hash,
				type: this.type
			})
			.fetch()
			.then((json, errors) => {

				if(errors) {
					ImageCrop.closePanel();
				} else {
					media.getCropImage(context, id, hash, json).src = json.url;
				}

			});

	};

	getCropImage(context, id, hash, json) {

		const img = new Image;

		const media = this;

		img.onload = function() {

			ImageCrop.call(media.type, img, json.bounds, function(newBounds) {

				const loader = 'progress-'+ this.currentLoader++;
				document.body.insertAdjacentHTML('beforeend', '<div id="progress-media">'+ ImageLoader.start(loader, '') + '</div>');
				ImageCrop.closePanel();

				new Ajax.Query()
					.url('/media/:crop')
					.body({
						id: id,
						hash: hash,
						type: media.type,
						basename: json.basename,
						fileType: json.filetype,
						bounds: JSON.stringify(newBounds)
					})
					.xmlHttpRequest(xhr => {

						//Upload progress
						xhr.upload.addEventListener("progress", function(e) {

							if(e.lengthComputable) {

								const percentComplete = Math.floor((e.loaded / e.total) * 100);
								ImageLoader.update(loader, percentComplete);

							}

						}, false);

					})
					.then((json) => {

						ImageLoader.update(loader, 100);
						media.removeProgress(loader);
						media.onUploaded(context, id, json);

					}, () => {

						ImageLoader.update(loader, 100);
						media.removeProgress(loader);

					});

			});

		};

		return img;

	};

	onUploaded(context, id, data) {
		this.showCamera(id, data);
	};

	rotate(mediaSelector, id, hash, angle) {

		const media = this;

		new Ajax.Query(mediaSelector)
			.url('/media/:rotate')
			.body({
				id: id,
				hash: hash,
				type: this.type,
				angle: angle
			})
			.fetch()
			.then((json) => {
				media.onRotated(mediaSelector, json);
			});

	};

	onRotated(mediaSelector, json) {

	};

	upload(context, id, crop, files) {

		if(crop) {
			this.uploadCrop(context, id, files);
		} else {
			this.uploadSave(context, id, files);
		}

	};

	uploadSave(context, id, files) {

		const onLoad = (resolve, file, position, img) => {

			const data = new FormData();
			data.set('id', id);
			data.set('type', this.type);
			data.set('file', file);
			data.set('bounds', JSON.stringify([]));

			this.save(context, 'put', id, data, position, files.length);

			resolve();

		};

		const onError = function(resolve) {
			resolve();
		};

		ImageStorage.upload(files, this.maxFiles, this.type, onLoad, onError);

	};

	uploadCrop(context, id, files) {

		const onLoad = (file, img) => {

			const media = this;

			new Ajax.Query()
				.url('/media/:getCropPanel')
				.body({
					id: id,
					type: media.type,
					width: img.width,
					height: img.height
				})
				.fetch()
				.then((json) => {

					ImageCrop.call(media.type, img, null, function(bounds) {

						const data = new FormData();
						data.set('id', id);
						data.set('type', media.type);
						data.set('file', file);
						data.set('bounds', JSON.stringify(bounds));

						media.save(context, 'put', id, data, 0, 1);

					});

				});

		};

		const onError = (res) => {
			resolve();
		};

		const reader = new FileReader();
		const image = new Image;
		const file = files[0];

		reader.onload = e => {

			image.src = e.target.result;
			ImageStorage.readFile(file, this.type, onLoad);

		};
		reader.readAsDataURL(file);

	};

	delete(context, id) {

		const media = this;

		new Ajax.Query(context)
			.url('/media/:delete')
			.body({
				id: id,
				type: this.type,
				size: 'l'
			})
			.fetch()
			.then((json) => {

				ImageCrop.closePanel();
				media.onDeleted(id, json);

			});

	};

	onDeleted(id, data) {
		this.hideCamera(id, data);
	}

	lockSubmit() {

		qs('#' + this.type + '-save-button', node => {
			node.classList.add('disabled');
			node.setAttribute('disabled', 'disabled');
		});

		qs('#' + this.type + '-cancel-button', node => {
			node.classList.add('disabled');
			node.setAttribute('disabled', 'disabled');
		});

		qs('[data-message="send"]', node => node.innerHTML = ImageMessage.sendOne + ' '+ Lime.Asset.icon('upload') +' ');

	};

	unlockSubmit() {

		qs('#' + this.type + '-save-button', node => {
			node.classList.remove('disabled');
			node.removeAttribute('disabled', 'disabled');
		});

		qs('#' + this.type + '-cancel-button', node => {
			node.classList.remove('disabled');
			node.removeAttribute('disabled', 'disabled');
		});

		qs('[data-message="send"]', node => node.innerHTML = '');

	};

};