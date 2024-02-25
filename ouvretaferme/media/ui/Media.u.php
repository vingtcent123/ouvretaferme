<?php
namespace media;

class MediaUi {

	protected $settings = [];
	protected $field = NULL;

	protected $type = NULL;
	protected bool $crop = TRUE;

	public function __construct() {

		$this->type = preg_replace_callback(
			'/([A-Z])/',
			function($data) {
				return '-'.strtolower($data[1]);
			},
			lcfirst(substr(get_class($this), 6, -2))
		);

		$this->settings = \Setting::get($this->type);
		$this->field = $this->settings['field'];

		\Asset::js('media', 'media.js');
		\Asset::css('media', 'media.css');

		\Asset::js('media', $this->type.'.js');

		\Asset::css('media', 'upload.css');
		\Asset::js('media', 'upload.js');

		\Asset::js('media', 'conf.js');

	}

	/**
	 * Get an MediaUi instance for the given structure
	 */
	public static function getInstance(string $type): MediaUi {

		$class = \Setting::get($type)['class'] ?? NULL;

		if($class) {
			$class = '\media\\'.$class.'Ui';
			return new $class();
		} else {
			throw new \Exception('Invalid class for type \''.$type.'\'');
		}

	}

	public function getField() {
		return $this->field;
	}

	public static function getExtension($hash): string {

		$letter = substr($hash, 19, 1);
		return \Setting::get('imagesExtensions')[$letter];

	}

	/**
	 * Get string for background CSS property
	 *
	 */
	public function getBackgroundByElement(\Element $eElement, string $size = NULL): string {

		if($eElement->empty()) {
			return '';
		}

		$style = '';

		$color = $this->getColorByElement($eElement);

		if($color) {
			$style = 'background-color: '.$color.';';
		}

		$url = $this->getUrlByElement($eElement, $size);

		if($url) {
			$style .= 'background-image: url('.$url.');';
		}


		return $style;

	}


	/**
	 * Get string for background color CSS property
	 */
	public function getColorByElement(\Element $eElement) {

		if($eElement->empty()) {
			return NULL;
		}

		if($this->field === NULL) {
			throw new \Exception('Type not compatible');
		}

		return $eElement[$this->field] ? substr($eElement[$this->field], 20, 7) : NULL;

	}


	/**
	 * Returns the URL for a media
	 */
	public function getUrlByElement(\Element $eElement, ?string $size = NULL) {

		if($eElement->empty()) {
			return NULL;
		}

		if($this->field === NULL) {
			throw new \Exception('Type not compatible');
		}

		$eElement->expects([$this->field]);

		$hash = $eElement[$this->field];

		if($hash !== NULL) {

			if(strlen($hash) === 30) {
				$version = (int)substr($hash, -3);
			} else {
				$version = NULL;
			}

			return $this->getUrlByHash($hash, $size, $version);
		} else {
			return NULL;
		}


	}

	/**
	 * Returns the URL of a media hash
	 */
	public function getUrlByHash(string $hash, $size = NULL, ?int $version = NULL) {

		$basename = $this->getBasenameByHash($hash, $size);

		if($basename !== NULL) {
			$basename = \Setting::get('mediaUrl').'/'.$basename;
		}

		if($version !== NULL) {
			$basename .= '?'.$version;
		}

		return $basename;

	}

	public function getBasenameByHash(string $hash, $size = NULL) {

		$formats = $this->settings['imageFormat'];

		if(strlen($hash) > 20) {
			$hash = substr($hash, 0, 20);
		}

		if($size !== NULL) {
			if(array_key_exists($size, $formats)) {
				$format = $formats[$size];
				return $this->type.'/'.(is_int($format) ? $format : implode('x', $format)).'/'.$hash.'.'.self::getExtension($hash);
			} else {
				return NULL;
			}
		} else {
			return $this->type.'/'.$hash.'.'.self::getExtension($hash);
		}

	}

	public function getFile(string $id, array $attributes = []): string {

		return '<input id="'.$id.'" type="file" '.attrs($attributes).'>';

	}

	public function getCamera(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {

		if($this->field === NULL) {
			return '';
		}

		$has = ($eElement[$this->field] !== NULL);

		if($has) {
			$title = s("Modifier la photo");
		} else {
			$title = s("Ajouter une photo");
		}

		$h = '<div class="media-image-upload '.($has ? '' : 'media-image-upload-empty').' dropdown';

		if($has) {
			$h .= ' media-image-yes';
		} else {
			$h .= ' media-image-no';
		}

		$h .= '" data-type="'.$this->type.'" data-id="'.$eElement['id'].'"';

		if($size !== NULL) {
			$h .= ' style="'.$this->getSquareCss($size).'"';
		} else if($width !== NULL or $height !== NULL) {
			$h .= ' style="'.$this->getRectangleCss($width, $height).'"';
		}

		$h .= '>';

			$h .= '<div class="media-image-content">';
				$h .= $this->getCameraContent($eElement, $size, $width, $height);
			$h .= '</div>';

			$h .= '<div class="media-image-action">';

				$h .= '<a class="media-image-dropdown" data-dropdown="bottom-start" title="'.$title.'">'.\Asset::icon('camera-fill').'</a>';

				$h .= $this->getDropdown($eElement);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return '';
	}


	/**
	 * Display panel for cropping images
	 *
	 */
	public function getCropPanel(int $width, int $height): \Panel {

		$footer = '<div id="'.$this->type.'-edit-button">';
		$footer .= ' <a data-action="upload-crop" id="'.$this->type.'-save-button" class="btn btn-secondary" data-type="'.$this->type.'">'.s("Enregistrer").'</a>';
		$footer .= ' <a onclick="Lime.Panel.closeLast()" id="'.$this->type.'-cancel-button" class="btn btn-secondary">'.s("Annuler").'</a>';
		$footer .= ' <span data-message="send" class="message-info"></span>';
		$footer .= '</div>';

		$attributes = [
			'data-shape' => $this->settings['imageCropShape'] ?? 'rectangle'
		];

		$formatCrop = $this->settings['imageCropReference'] ?? NULL;

		if($formatCrop !== NULL) {

			if(is_string($formatCrop)) {
				$format = $this->settings['imageFormat'][$formatCrop];
			} else {
				$format = $formatCrop;
			}

		} else {
			$format = last($this->settings['imageFormat']);
		}


		if(is_array($format)) {

			$attributes += [
				'data-width-use' => $format[0],
				'data-height-use' => $format[1],
			];

		} else {

			// Calculates max used size of the photo
			$factor = $format / max($width, $height);

			$attributes += [
				'data-width-use' => $width * $factor,
				'data-height-use' => $height * $factor,
			];

		}

		return new \Panel(
			id: 'panel-media-crop',
			title: s("Positionnez et redimensionnez ..."),
			body: '<div id="'.$this->type.'-preview" class="resize-preview"></div>',
			footer: $footer,
			layer: FALSE,
			attributes: $attributes
		);

	}

	protected function getDropdown(\Element $eElement): string {

		$data = 'data-type="'.$this->type.'" data-id="'.($eElement['id'] ?? NULL).'"';

		$h = '<ul class="dropdown-list bg-primary" data-id="'.($eElement['id'] ?? NULL).'">';

			if($eElement[$this->field] !== NULL) {

				if($this->crop) {
					$h .= '<a id="'.$this->type.'-resize" data-action="media-resize" class="dropdown-item" '.$data.'>'.s("Recadrer cette photo").'</a>';
				}
				$h .= '<a id="'.$this->type.'-delete" data-action="media-delete" class="dropdown-item" '.$data.'>'.s("Supprimer cette photo").'</a>';

				$h .= '<div class="dropdown-divider"></div>';

				$labelUpload = s("Choisir une autre photo sur le disque");
				$labelCapture = s("Prendre une nouvelle photo");

			} else {
				$labelUpload = NULL;
				$labelCapture = NULL;
			}

			$linkAttributes = ['class' => 'dropdown-item'];

			$inputAttributes = [
				'data-id' => ($eElement['id'] ?? NULL)
			];

			$h .= $this->getUploadLink($labelUpload, $linkAttributes, $inputAttributes);
			$h .= $this->getCaptureLink($labelCapture,  $linkAttributes, $inputAttributes);

		$h .= '</ul>';

		return $h;

	}

	public function getDropdownLinks(?string $label, string $btn, array $uploadInputAttributes = [], array $captureInputAttributes = []): string {

		$label ??= s("Ajouter une photo");

		$h = $this->getUploadLink(
			$label,
			['class' => 'btn '.$btn.' hide-touch'],
			['multiple' => 'multiple']
		);
		$h .= '<a data-dropdown="top-right" class="btn '.$btn.' hide-notouch">'.$label.'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= $this->getUploadLink(linkAttributes: ['class' => 'dropdown-item'], inputAttributes: $uploadInputAttributes);
			$h .= $this->getCaptureLink(linkAttributes: ['class' => 'dropdown-item'], inputAttributes: $captureInputAttributes);
		$h .= '</div>';

		return $h;

	}

	public function getBothLinks(?string $labelUpload = NULL, ?string $labelCapture = NULL, array $linkAttributes = [], array $inputAttributes = []): string {

		$h = $this->getUploadLink($labelUpload, $linkAttributes, $inputAttributes);
		$h .= '<span class="media-upload-capture">';
			$h .= ' <b>'.s("ou").'</b> ';
			$h .= $this->getCaptureLink($labelCapture, $linkAttributes, $inputAttributes);
		$h .= '</span>';

		return $h;

	}

	public function getUploadLink(?string $label = NULL, array $linkAttributes = [], array $inputAttributes = []): string {

		\Asset::css('util', 'form.css');

		$label ??= s("Choisir une photo sur le disque");

		$inputAttributes += [
			'data-id' => NULL
		];

		$h = '<a data-action="media-upload" '.attrs($linkAttributes).'>'.$label.'</a>';
		$h .= '<input onchange="Media.input(this)" data-type="'.$this->type.'" data-crop="'.(int)$this->crop.'" type="file" '.attrs($inputAttributes).'>';

		return $h;

	}

	public function getCaptureLink(?string $label = NULL, array $linkAttributes = [], array $inputAttributes = []): string {

		$label ??= s("Prendre une photo");

		$linkAttributes['class'] ??= '';
		$linkAttributes['class'] .= ' media-upload-capture';

		$inputAttributes['accept'] = 'image/*';
		$inputAttributes[] = 'capture';

		return $this->getUploadLink($label, $linkAttributes, $inputAttributes);

	}

	public static function getSquareCss(int|string $size): string {

		if(is_string($size) and ctype_alpha($size)) {
			return 'width: 100%; height: 100%; min-width: 100%;';
		} else {
			return 'width: '.$size.'; height: '.$size.'; min-width: '.$size.'; font-size: '.self::getFactorSize($size, 0.45).';';
		}

	}

	public static function getRectangleCss(int|string $width, int|string $height): string {

		$css = '';

		if(is_string($width) and ctype_alpha($width)) {
			$css .= 'width: 100%; min-width: 100%;';
		} else {
			$css .= 'width: '.$width.'; min-width: '.$width.';';
		}

		if(is_string($height) and ctype_alpha($height)) {
			$css .= 'height: 100%;';
		} else {
			$css .= 'height: '.$height.';';
		}

		return $css;

	}

	public static function getFactorSize(int|string $size, float $factor): int|string {

		if(is_string($size)) {

			if(str_ends_with($size, 'px')) {
				return ((float)$size * $factor).'px';
			} else if(str_ends_with($size, 'rem')) {
				return ((float)$size * $factor).'rem';
			} else if(str_ends_with($size, 'cm')) {
				return ((float)$size * $factor).'cm';
			} else if(str_ends_with($size, '%')) {
				return '1rem';
			} else if(ctype_alpha($size)) {
				return '1rem';
			} else {
				throw new \Exception('Invalid size format');
			}

		} else {
			return $size * $factor;
		}

	}

	public function convertToFormat(int|string $size): string {

		if(is_string($size)) {

			// La taille est multipliée par 2 pour tenir compte des écrans avec des fortes résolutions
			if(str_ends_with($size, 'px')) {
				$checkSize = (int)$size * 2;
			} else if(str_ends_with($size, 'rem')) {
				$checkSize = (int)$size * 15 * 2; // 1rem = 15px (arbitrary)
			} else if(str_ends_with($size, 'cm')) {
				$checkSize = (int)$size * 200; // 1cm = 200px (arbitrary)
			} else if(str_ends_with($size, '%')) {
				$checkSize = 1024;
			} else if(ctype_alpha($size)) { // Format valide
				return $size;
			} else {
				throw new \Exception('Invalid size format');
			}

		} else {
			$checkSize = $size;
		}

		$formats = \Setting::get($this->type)['imageFormat'];

		foreach($formats as $name => $value) {

			$formatSize = is_int($value) ? $value : $value[0];

			if($formatSize > $checkSize) {
				return $name;
			}

		}

		return $name;

	}

}

?>
