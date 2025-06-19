new Lime.Instruction('gallery')
	.register('deletePhoto', function(id) {

		qsa('.gallery-photos', gallery => {

			gallery.qs('.gallery-photo[data-id="'+ id +'"]', photo => {

				photo.remove();

			})

		});

	});