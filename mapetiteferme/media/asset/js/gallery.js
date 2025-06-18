class GalleryMedia extends Media {

	type = 'gallery';
	maxFiles = 10;

	onUploaded(context, id, data) {

		const formData = new URLSearchParams();

		formData.set('hash', data.media.hash);

		if(data.media.takenAt) {
			formData.set('takenAt', data.media.takenAt.substring(0, 7));
		}

		context.firstParent('[data-media="gallery"]').post(formData);

		new Ajax.Query()
			.method('post')
			.url('/gallery/photo:doCreate')
			.body(formData)
			.fetch();

	};

}

Media.classes['gallery'] = GalleryMedia;