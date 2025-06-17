class ReaditorFigure {

	static reorganize(figureSelector) {

		this.updateMargin(figureSelector);
		this.updateCaption(figureSelector);

	};

	static updateMargin(figureSelector) {

		const previous = figureSelector.previousElementSibling;

		if(
			previous !== null &&
			previous.tagName === 'FIGURE' &&
			previous.getAttribute('data-interactive') === 'true' &&
			previous.getAttribute('data-size') !== 'left' &&
			previous.getAttribute('data-size') !== 'right'
		) {
			figureSelector.style.marginTop = '0px';
		} else {
			figureSelector.style.marginTop = '';
		}

		const next = figureSelector.nextElementSibling;

		if(
			next !== null &&
			next.tagName === 'FIGURE' &&
			next.getAttribute('data-interactive') === 'true' &&
			next.getAttribute('data-size') !== 'left' &&
			next.getAttribute('data-size') !== 'right'
		) {
			figureSelector.style.marginBottom = '0px';
		} else {
			figureSelector.style.marginBottom = '';
		}

	};

	static updateCaption(figureSelector) {

		const figcaptionSelector = figureSelector.qs('figcaption');

		if(figcaptionSelector === null) {
			return;
		}

		// Caption max size
		const captionSize = figcaptionSelector.offsetWidth;
		const captionMaxSize = figcaptionSelector.parentElement.offsetWidth - 30;

		let captionPadding;

		if(captionSize > captionMaxSize) {
			captionPadding = ((captionSize - captionMaxSize) / 2) +'px';
		} else {
			captionPadding = '';
		}

		figcaptionSelector.style.paddingLeft = captionPadding;
		figcaptionSelector.style.paddingRight = captionPadding;

	}

};
