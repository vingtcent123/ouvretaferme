class EditorMedia extends Media {

	type = 'editor';

	onUploaded(context, id, data) {

		const media = qs('div.editor-media[data-xyz="'+ data.media.hash +'"]');
		media.setAttribute('data-xyz-version', data.media.version);

		// Rebuild HTML to fix a very strange issue #1800
		let html = '<a class="editor-image">';
			html += '<img src="'+ data.media.urls.xl +'"/>';
		html += '</a>';

		media.innerHTML = html;

	};

	onRotated(mediaSelector, data) {

		qs('#panel-editor-media-configure', node => Lime.Panel.close(node));

		mediaSelector.setAttribute('data-xyz-w', data.media.width);
		mediaSelector.setAttribute('data-xyz-h', data.media.height);
		mediaSelector.setAttribute('data-xyz-version', data.media.version);
		mediaSelector.setAttribute('data-xyz-url', data.media.urls.xl);

		mediaSelector.setAttribute('data-w', data.media.width);
		mediaSelector.setAttribute('data-h', data.media.height);

		mediaSelector.qs('.editor-image img', image => image.src = data.media.urls.xl);

		const figureSelector = mediaSelector.parentElement;
		const instanceId = '#'+ figureSelector.parentNode.id;

		EditorFigure.reorganize(instanceId, figureSelector);

	};

};

Media.classes['editor'] = EditorMedia;