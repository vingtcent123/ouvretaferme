<?php
namespace media;

abstract class MediaLib {

	protected $settings = [];
	protected $field = NULL;

	protected $type = NULL;
	protected $regenerate = FALSE;

	protected $output;

	public function __construct() {

		$this->type = preg_replace_callback('/([A-Z])/', function($data) { return '-'.strtolower($data[1]); }, lcfirst(substr(get_class($this), 6, -3)));

		$this->settings = \Setting::get($this->type);
		$this->field = $this->settings['field'];

	}

	/**
	 * Get an MediaLib instance for the given structure
	 */
	public static function getInstance(string $type): ?MediaLib {

		$class = \Setting::get($type)['class'] ?? NULL;

		if($class) {
			return new ('\media\\'.$class.'Lib')();
		} else {
			return NULL;
		}

	}

	/**
	 * Get module for this type of image
	 *
	 * @return \Module
	 */
	public function getModel(): ?\ModuleModel {

		$element = $this->settings['element'];

		if($element) {
			return $element::model();
		} else {
			return NULL;
		}

	}

	/**
	 * Get field for this type of image
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * Build elements from input
	 */
	public function buildElement(): \Element {
		return new \Element();
	}

	/**
	 * Puts a media on the server after cropping it if necessary
	 *
	 */
	public function putBasename(\Element $eElement, &$hash, string $basename, array $bounds, int $fileType = NULL): bool {

		try {
			$resource = new \Imagick($basename);
		} catch (\ImagickException $e) {
			return FALSE;
		}

		return $this->putImagick($eElement, $hash, $resource, $bounds, $fileType);

	}

	/**
	 * Puts a media from a file
	 *
	 */
	public function putFile(\Element $eElement, &$hash, string $file, array $bounds, int $fileType = NULL): bool {

		try {
			$resource = new \Imagick($file);
		} catch (\ImagickException $e) {
			return FALSE;
		}

		return $this->putImagick($eElement, $hash, $resource, $bounds, $fileType);

	}

	/**
	 * Puts a media on the server after cropping it if necessary
	 *
	 */
	public function putImagick(\Element $eElement, &$hash, \Imagick $resource, array $bounds, int $fileType = NULL): bool {

		if($this->regenerate) {
			$hash = NULL;
			$this->regenerate = FALSE;
		}

		$this->addMedia($eElement, $hash, $fileType);

		$toBasename = $this->type.'/'.$hash.'.'.MediaUi::getExtension($hash);

		if($bounds) {

			if(count($bounds) !== 4) {
				throw new \NotExpectedAction('Bounds parameter must be an array of 4 elements');
			}

			$toType = $this->type;

			// We extract a portion
			$apply = function(\Imagick $resource, &$result) use ($bounds, $toType) {

				if(\storage\ImageLib::extractImagePortion($bounds, $resource, $toType)) {
					$result['crop'] = $bounds;
				}

			};

			$result = \storage\ServerLib::put($this->type, $toBasename, $resource, $apply);

		} else {

			$result = \storage\ServerLib::put($this->type, $toBasename, $resource);

		}

		if($result) {

			$this->output = $result;
			$this->saveElement($eElement);

			return TRUE;

		} else {
			return FALSE;
		}

	}

	public function crop(\Element $eElement, string $basename, array $bounds): void {

		if(count($bounds) !== 4) {
			throw new \NotExpectedAction('Bounds parameter must be an array of 4 elements');
		}

		try {
			array_expects($bounds, ['top', 'left', 'width', 'height']);
		} catch(Exception $e) {
			throw new \NotExistsAction('Missing top, left, width, height properties in bounds parameter');
		}

		if($this->regenerate) {

			$hash = NULL;
			$this->putBasename($eElement, $hash, $basename, $bounds);

			return;

		}

		$bounds['top'] = (float)$bounds['top'];
		$bounds['left'] = (float)$bounds['left'];
		$bounds['width'] = (float)$bounds['width'];
		$bounds['height'] = (float)$bounds['height'];

		// We extract a portion
		$type = $this->type;
		$apply = function(\Imagick $resource, &$metadata) use ($bounds, $type) {

			if(\storage\ImageLib::extractImagePortion($bounds, $resource, $type)) {

				$metadata['crop'] = $bounds;

			}

		};

		\storage\ServerLib::reput($type, $basename, $apply);

		if($eElement->notEmpty()) {

			$this->incVersion($eElement);

			$this->saveElement($eElement);
		}

	}

	public function copy(\Element $eElement, &$hash, string $toType, string $basenameFrom): void {

		$this->addMedia($eElement, $hash);

		$basenameTo = MediaUi::getInstance($toType)->getBasenameByHash($hash);

		\storage\ServerLib::copy($basenameTo, $basenameFrom);

		if($eElement->notEmpty()) {
			$this->output = $this->getMetadata($hash);
			$this->saveElement($eElement);
		}

	}

	/**
	 * Rotates a media
	 *
	 *
	 */
	public function rotate(\Element $eElement, &$hash, int $angle): bool {

		$this->addMedia($eElement, $hash);

		$basename = $this->type.'/'.$hash.'.'.MediaUi::getExtension($hash);
		$result = \storage\ServerLib::rotate($this->type, $basename, $angle);

		if($result) {

			$this->output = $result;
			$this->saveElement($eElement);

			return TRUE;

		} else {
			return FALSE;
		}

	}

	/**
	 * Send a media
	 *
	 */
	public function send(\Element $eElement, &$hash, string $sourceFile, int $fileType = NULL): bool {

		$this->addMedia($eElement, $hash, $fileType);

		$path = $this->type.'/'.$hash.'.'.MediaUi::getExtension($hash);

		if(MediaUi::getExtension($hash) === 'pdf' or \storage\ImageLib::isImage($sourceFile) === FALSE) {

			 \Setting::get('media\mediaDriver')->sendBinary(file_get_contents($sourceFile), $path);

		} else {

			$resource = new \Imagick($sourceFile);

			\Setting::get('media\mediaDriver')->sendResource($resource, $path);

			$this->output = \storage\ServerLib::extractMetadata($resource);

		}

		$this->saveElement($eElement);

		return TRUE;

	}

	protected function saveElement(\Element $eElement): void {

		if($eElement->notEmpty()) {

			$eElement->expects([$this->field]);

			$color = $this->getColor();

			if($color !== NULL) {
				$eElement[$this->field] = substr($eElement[$this->field], 0, 20).$this->getColor().substr($eElement[$this->field], -3);
			}

			$eElement->model()
				->select($this->field)
				->update($eElement);

		}

	}

	/**
	 * Returns main color of last image
	 */
	public function getColor() {
		return $this->output['color'] ?? NULL;
	}

	/**
	 * Returns crop info about the last image
	 */
	public function getCrop() {
		return $this->output['crop'] ?? NULL;
	}

	/**

	 * Returns width of the last image
	 */
	public function getWidth() {
		return $this->output['width'] ?? NULL;
	}

	/**
	 * Returns height of the last image
	 */
	public function getHeight() {
		return $this->output['height'] ?? NULL;
	}

	/**
	 * Returns date of the last image
	 */
	public function getTakenAt() {
		return $this->output['takenAt'] ?? NULL;
	}

	/**
	 * Increment version
	 */
	public function incVersion(\Element $eElement): void {

		$eElement[$this->field] = substr($eElement[$this->field], 0, 27).sprintf('%03d', ((int)substr($eElement[$this->field], -3) + 1) % 1000);

	}

	/**
	 * Get metadata for the given hash
	 *
	 * @param string $hash
	 * @return string
	 */
	public function getMetadata(string $hash): ?array {

		$result = \Setting::get('media\mediaDriver')->getMetadata($this->type.'/'.$hash.'.'.MediaUi::getExtension($hash));

		if($result) {
			return $result;
		} else {
			return NULL;
		}

	}

	protected function addMedia(\Element $eElement, ?string &$hash, int $fileType = NULL) {

		if($eElement->notEmpty()) {
			$eElement->expects(['id', $this->field]);
		}

		// Check the fileType if authorized or not
		$types = (array)$this->settings['imageOutputType'];

		if(
			$fileType === NULL or
			in_array($fileType, $types) === FALSE
		) {
			$fileType = first($types);
		}

		if($fileType === IMAGETYPE_JPEG) {
			$letter = 'j';
		} elseif($fileType === IMAGETYPE_PNG) {
			$letter = 'p';
		} elseif($fileType === IMAGETYPE_GIF) {
			$letter = 'g';
		} elseif($fileType === IMAGETYPE_PDF) {
			$letter = 'f';
		} else {
			throw new \Exception('Bad file type ('.$fileType.')');
		}

		$eUser = \user\ConnectionLib::getOnline();

		// Match hash validity
		if($hash === NULL) {

			$hashGenerated = \media\UtilLib::generateHash().$letter;

		} else {

			// Check letter
			if($letter !== $hash[19]) { // Image format has changed (ie: image switched from jpeg to png)
				$hashGenerated = substr($hash, 0, 19).$letter;
			} else {
				$hashGenerated = NULL;
			}

		}

		// Hash needs to be generated
		if($hashGenerated !== NULL) {

			$hash = $hashGenerated;

			$eMedia = new \media\Media([
				'user' => $eUser,
				'type' => $this->type,
				'status' => \media\Media::ACTIVE,
				'updatedAt' => new \Sql('NOW()'),
				'hash' => $hash
			]);

			\media\Media::model()
				->option('add-replace')
				->insert($eMedia);

			if($eElement->notEmpty()) {

				$color = $this->getColor() ?? '#000000';
				$eElement[$this->field] = $hash.$color.'001';

			}

		} else {

			// Checks hash consistency
			if(
				$hash[19] !== 'p' and
				$hash[19] !== 'j' and
				$hash[19] !== 'g' and
				$hash[19] !== 'f'
			) {
				throw new \Exception('Hash '.$hash.' must end with j (for jpeg files), p (for png files) or g (for gif files) or f (for pdf files)');
			}

			if($eElement->notEmpty()) {

				if(substr($eElement[$this->field], 0, 20) !== $hash) {
					throw new \Exception('Hash inconsistency in Media::addMedia()');
				}

			}

			$eMedia = new \media\Media([
				'user' => $eUser,
				'type' => $this->type,
				'status' => \media\Media::ACTIVE,
				'updatedAt' => new \Sql('NOW()'),
				'hash' => $hash
			]);

			\media\Media::model()
				->option('add-replace')
				->insert($eMedia);

			if($eElement->notEmpty()) {
				$this->incVersion($eElement);
			}

		}

	}

	/**
	 * Duplicates the media entry and the physical medias
	 */
	public function duplicate(\Element $eElement) {

		if($eElement[$this->field] === NULL) {
			return;
		}

		$eElementOld = $eElement;

		$this->duplicateMedia($eElement);

		$basenameTo = $this->type.'/'.$eElement[$this->field].'.'.MediaUi::getExtension($eElementOld[$this->field]);
		$basenameFrom = $this->type.'/'.$eElementOld[$this->field].'.'.MediaUi::getExtension($eElement[$this->field]);

		\storage\ServerLib::copy($basenameTo, $basenameFrom);

	}

	protected function duplicateMedia(\Element $eElement): void {

		$currentHash = $eElement[$this->field];
		$letter = substr($currentHash, -1);

		$eUser = \user\ConnectionLib::getOnline();
		$hash = \media\UtilLib::generateHash().$letter;

		$eMedia = new \media\Media([
			'user' => $eUser,
			'type' => $this->type,
			'status' => \media\Media::ACTIVE,
			'updatedAt' => new \Sql('NOW()'),
			'hash' => $hash
		]);

		\media\Media::model()->insert($eMedia);

		if($eElement->notEmpty()) {

			$eElement[$this->field] = $hash.'#000000'.'000';

		}

	}

	/**
	 * Sets the \media\Media to DELETED
	 * Asks for the \media\Media to be DELETED too
	 * (will be deleted later)
	 */
	public function delete(\Element $eElement): void {

		if($eElement->empty()) {
			throw new \Exception('Element is missing');
		}

		$hash = $eElement[$this->field];

		// Media already deleted
		if($hash === NULL) {
			return;
		}

		$eElement[$this->field] = NULL;

		$this->getModel($eElement)
			->select($this->field)
			->update($eElement);

		$this->deleteMedia($hash);

	}

	protected function deleteMedia(string $hash): void {

		\media\Media::model()
			->whereHash('IN', $hash)
			->whereType($this->type)
			->update([
				'updatedAt' => new \Sql('NOW()'),
				'status' => \media\Media::DELETED
			]);

	}

	/**
	 * Set unused medias of the type at DELETED
	 *
	 */
	public function clean(): bool {

		if($this->field !== NULL) {

			$active = [];

			$cElement = $this->getModel()
				->select('id', $this->field)
				->where($this->field, '!=', NULL)
				->recordset()
				->getCollection();

			foreach($cElement as $eElement) {

				$active[] = substr($eElement[$this->field], 0, 20);

				if(count($active) > 100) {
					$this->activeMedias($active);
				}

			}

			if($active) {
				$this->activeMedias($active);
			}

		} else {

			\media\Media::model()
				->whereType($this->type)
				->update([
					'status' => \media\Media::ACTIVE
				]);

		}

		return TRUE;

	}

	protected function activeMedias(array $hashes): void {

		$hashes = array_map(function($value) {
			return substr($value, 0, 20);
		}, $hashes);

		\media\Media::model()
			->whereHash('IN', $hashes)
			->whereType($this->type)
			->update([
				'status' => \media\Media::ACTIVE
			]);

	}

}
?>
