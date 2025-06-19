<?php
namespace media;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'imageInvalid' => s("L'image fournie est invalide ou le téléchargement a échoué."),
			default => NULL

		};

	}

	public function getJavascript(): array {

		return [
			'treatment' => s("Traitement..."),
			'sendOne' => s("Envoi de l'image..."),
			'resizeAndSend' => s("Redimensionnement et envoi des images..."),
			'internalPreupload' => s("Une erreur est survenue pendant le téléchargement de votre image, veuillez vérifier votre connexion internet ou réessayez ultérieurement !"),
			'internalUpload' => s("Une erreur est survenue pendant l'enregistrement de votre image, veuillez vérifier votre connexion internet ou réessayez ultérieurement !"),
			'internalCrop' => s("Une erreur est survenue pendant le redimensionnement de votre image, veuillez vérifier votre connexion internet ou réessayez ultérieurement !"),
			'imageInvalid' => $this->getError('imageInvalid'),
			'imageProgressUpload' => s("Téléchargement de la photo {current} de {number}...", ['current' => '<span id="upload-progress-current"></span>', 'number' => '<span id="upload-progress-count"></span>']).' <span id="upload-progress-kill">'.s("(<link>arrêter</link>)", ['link' => '<a data-action="upload-kill">']).'</span>',
			'imageUploadOne' => s("Vous ne pouvez envoyer qu'une seule image à la fois."),
			'imageUploadLimit' => s("Veuillez sélectionner un nombre limité d'images (maximum <number>)"),
			'imageTypeSeveral' => s("Certaines des images sélectionnées n'étaient pas au format JPEG ou PNG et n'ont pas été téléchargées."),
			'imageTypeOne' => s("Veuillez sélectionner une image valide."),
			'imageTypeAll' => s("Veuillez sélectionner des images valides."),
			'imageSize' => s("L'image sélectionnée est trop grosse, elle doit peser moins de {maxSize}Mo.", ['maxSize' => \Setting::get('media\maxImageSize')]),
			'imageNoZoom' => s("La qualité de l'image est insuffisante pour proposer le zoom"),
			'imageNoResize' => s("La qualité de l'image est insuffisante pour permettre un recadrage")
		];

	}

	public function getRequiredSize(int $width, int $height): string {
		return s("La résolution de votre image n'est pas suffisante (le minimum est {width}x{height} pixels)", ['width' => $width, 'height' => $height]);
	}

}
?>
