class ImageConf {
	static sizeMax = 20;
	static imageCropRequiredFactor = 1.1;
	static imagesRequiredSize = {
		'user-vignette': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		},
		'editor': {
			width: 50,
			height: 50,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 50x50 pixels)"
		},
		'plant-vignette': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		},
		'gallery': {
			width: 200,
			height: 200,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 200x200 pixels)"
		},
		'farm-vignette': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		},
		'farm-logo': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		},
		'farm-banner': {
			width: 500,
			height: 100,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 500x100 pixels)"
		},
		'product-vignette': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		},
		'tool-vignette': {
			width: 256,
			height: 256,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 256x256 pixels)"
		},
		'website-logo': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		},
		'website-favicon': {
			width: 256,
			height: 256,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 256x256 pixels)"
		},
		'shop-logo': {
			width: 512,
			height: 512,
			error: "La résolution de votre image n'est pas suffisante (le minimum est 512x512 pixels)"
		}
	}
};

class ImageMessage {
	static treatment = "Traitement...";
	static sendOne = "Envoi de l'image...";
	static resizeAndSend = "Redimensionnement et envoi des images...";
	static internalPreupload = "Une erreur est survenue pendant le téléchargement de votre image, veuillez vérifier votre connexion internet ou réessayez ultérieurement !";
	static internalUpload = "Une erreur est survenue pendant l'enregistrement de votre image, veuillez vérifier votre connexion internet ou réessayez ultérieurement !";
	static internalCrop = "Une erreur est survenue pendant le redimensionnement de votre image, veuillez vérifier votre connexion internet ou réessayez ultérieurement !";
	static imageInvalid = "L'image fournie est invalide ou le téléchargement a échoué.";
	static imageProgressUpload = "Téléchargement de la photo <span id=\"upload-progress-current\"></span> de <span id=\"upload-progress-count\"></span>... <span id=\"upload-progress-kill\">(<a data-action=\"upload-kill\">arrêter</a>)</span>";
	static imageUploadOne = "Vous ne pouvez envoyer qu'une seule image à la fois.";
	static imageUploadLimit = "Veuillez sélectionner un nombre limité d'images (maximum <number>)";
	static imageTypeSeveral = "Certaines des images sélectionnées n'étaient pas au format JPEG ou PNG et n'ont pas été téléchargées.";
	static imageTypeOne = "Veuillez sélectionner une image valide.";
	static imageTypeAll = "Veuillez sélectionner des images valides.";
	static imageSize = "L'image sélectionnée est trop grosse, elle doit peser moins de 20Mo.";
	static imageNoZoom = "La qualité de l'image est insuffisante pour proposer le zoom";
	static imageNoResize = "La qualité de l'image est insuffisante pour permettre un recadrage"
};
