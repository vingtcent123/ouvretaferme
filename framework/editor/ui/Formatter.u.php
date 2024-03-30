<?php
namespace editor;

/**
 * HtmlLib
 *
 */
abstract class FormatterUi {

	/**
	 * Current DomDocument handled
	 */
	protected \DOMDocument $dom;

	/**
	 * Convert XML for reador or editor?
	 *
	 * @var string
	 */
	protected string $for;

	protected array $options = [];

	/**
	 * Media cut in figures
	 *
	 * @var array
	 */
	private static $cut = [];

	protected function filterXml(string $value, array $options): \DOMElement {

		$this->parseXml($value);

		if($this->dom->childNodes->length === 0) {
			return $this->dom->createElement('main');
		}

		$main = $this->dom->firstChild;

		// Remove empty trailing <p>
		if($this->for === 'reador') {

			while(
				$main->lastChild and
				$main->lastChild->nodeName === 'p' and
				$main->lastChild->childNodes->length === 0
			) {
				$main->removeChild($main->lastChild);
			}

		}

		$this->options = $options + [
			'domain' => \Lime::getDomain()
		];

		for($i = 0; $i < $main->childNodes->length; $i++) {

			$node = $main->childNodes[$i];

			$i += $this->convertNode($node);

		}

		return $main;

	}

	public function parseXml(string $value): void {

		libxml_use_internal_errors(TRUE);

		$dom = new \DOMDocument();
		$dom->loadXml($value, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOBLANKS | LIBXML_NOXMLDECL);

		libxml_clear_errors();

		$this->dom = $dom;

	}

	public function extractXyz(string $xyz): ?\DOMNode {

		if($this->dom->childNodes->length === 0) {
			return NULL;
		}

		$main = $this->dom->firstChild;

		for($i = 0; $i < $main->childNodes->length; $i++) {

			$nodeFigure = $main->childNodes[$i];

			if($nodeFigure->nodeName === 'figure') {

				for($j = 0; $j < $nodeFigure->childNodes->length; $j++) {

					$nodeImage = $nodeFigure->childNodes[$j];

					if(
						$nodeImage->nodeName === 'image' and
						$nodeImage->getAttribute('xyz') === $xyz
					) {
						return $nodeImage;
					}

				}


			}

		}

		return NULL;

	}

	protected function convertNode(\DOMNode $node): int {

		switch($node->nodeName) {

			case 'p' :
				return $this->convertParagraphNode($node);

			case 'header' :
				return $this->convertHeaderNode($node);

			case 'ol' :
			case 'ul' :
				return $this->convertListNode($node);

			case 'figure' :
				return $this->convertFigureNode($node);

		}

	}

	/**
	 * Convert a <figure> node
	 *
	 * @param \DOMNode $node
	 */
	protected function convertFigureNode(\DOMNode $node, $options = []): int {

		// Empty figure from app
		if($node->childNodes->length === 0) {
			$node->parentNode->removeChild($node);
			return -1;
		}

		// Update attributes
		\util\DomLib::renameNodeAttributes($node, [
			'interactive' => 'data-interactive',
			'size' => 'data-size',
			'before' => 'data-before',
			'after' => 'data-after'
		]);

		if($node->getAttribute('data-interactive') === 'true') {

			// Size is facultative, so it can be missing
			if($node->hasAttribute('data-size') === FALSE) {
				$node->setAttribute('data-size', 'compressed');
			}

		} else {

			$node->removeAttribute('data-size');

		}

		$node->setAttribute('id', getId($this->for.'-figure-'));

		if($this->for === 'editor') {
			$node->setAttribute('contenteditable', 'false');
		}

		$node->setAttribute('data-widget', 'figure');

		$hasCaption = FALSE;

		// Clean child nodes (images, maps, videos, embeds)
		$length = 0;
		$orientations = [];

		$last = [
			'w' => NULL,
			'h' => NULL,
			's' => NULL
		];
		$lastOrientation = NULL;
		$lastNode = NULL;

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			if($childNode->nodeType !== XML_ELEMENT_NODE) {
				continue;
			}

			if($childNode->nodeName === 'figcaption') {

				$hasCaption = TRUE;

			} else {

				if($childNode->nodeName === 'image') {

					$orientation = $this->getImageOrientation($childNode);

				} else {
					$orientation = 'w';
				}

				$last[$orientation] = [$length, $childNode];
				$lastOrientation = $orientation;
				$lastNode = $childNode;

				$orientations[$length] = $orientation;
				$length++;

			}

		}

		if($hasCaption or $this->for === 'editor') {
			$showCaption = TRUE;
		} else {
			$showCaption = FALSE;
		}

		$node->setAttribute('data-caption', $showCaption ? 'true' : 'false');

		// On mobile reador, try to not end the line with a vertical item
		$isMobile = \util\DeviceLib::isMobile();

		if(
			$this->for === 'reador' and
			$isMobile and
			$lastOrientation === 'h' and
			$length > 2 and
			$length % 2 === 1
		) {

			if($last['w']) {
				list($swapPosition, $swapNode) = $last['w'];
			} else if($last['s']) {
				list($swapPosition, $swapNode) = $last['s'];
			} else {
				$swapPosition = NULL;
				$swapNode = NULL;
			}

			if($swapNode) {

				$swapNode->parentNode->insertBefore($swapNode, $lastNode);
				$lastNode->parentNode->insertBefore($lastNode, $swapNode);

				$orientations[] = $orientations[$swapPosition];
				unset($orientations[$swapPosition]);
				$orientations = array_merge($orientations); // Rebuild keys from 0 to n-1

			}

		}

		// Display each child node
		$position = 0;
		$length = 0;

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			if($childNode->nodeType !== XML_ELEMENT_NODE) {
				$node->removeChild($childNode);
				$i--;
			} else if($childNode->nodeName === 'figcaption') {

				if($this->for === 'editor') {
					$childNode->setAttribute('contenteditable', 'true');
					$childNode->setAttribute('placeholder', (new \editor\EditorUi())->labels()['captionFigure']);
					$childNode->setAttribute('style', 'display: block');
				} else {

					if(mb_strlen($childNode->firstChild->nodeValue) > \Setting::get('editor\captionLimit')) {
						$childNode->firstChild->nodeValue = mb_substr($childNode->firstChild->nodeValue, 0, \Setting::get('editor\captionLimit')).'...';
					}

				}

				$this->convertTextNode($childNode);

			} else {

				$length++;

				$newChildNode = $this->dom->createElement('div');
				$newChildNode->setAttribute('class', 'editor-media');
				$newChildNode->setAttribute('data-type', $childNode->nodeName);

				if($childNode->hasAttribute('width')) {
					$newChildNode->setAttribute('data-w', $childNode->getAttribute('width'));
				}

				if($childNode->hasAttribute('height')) {
					$newChildNode->setAttribute('data-h', $childNode->getAttribute('height'));
				}

				switch($childNode->nodeName) {

					case 'image' :
						$this->convertImageNode($childNode, $newChildNode, $position, $orientations, $node->getAttribute('data-size'));
						$position++;
						break;

					case 'video' :
						$this->convertVideoNode($childNode, $newChildNode);
						break;

					case 'hr' :
						$this->convertHrNode($childNode, $newChildNode);
						break;

					case 'quote' :
						$this->convertQuoteNode($childNode, $newChildNode);
						break;

					case 'grid' :
						$this->convertGridNode($childNode, $newChildNode);
						break;

				}

				$node->replaceChild($newChildNode, $childNode);

			}
		}

		if($this->for === 'reador') {
			$node->setAttribute('data-length', $length);
		}

		$this->cutFigureNode($node, $length);

		// Add default figcaption if interactive and missing
		if(
			$this->for === 'editor' and
			$hasCaption === FALSE and
			$node->getAttribute('data-interactive') === 'true'
		) {

			$childNode = $this->dom->createElement('figcaption');
			$childNode->setAttribute('contenteditable', 'true');
			$childNode->setAttribute('placeholder', (new \editor\EditorUi())->labels()['captionFigure']);

			if($node->getAttribute('data-size') === 'left' or $node->getAttribute('data-size') === 'right') {
				$childNode->setAttribute('style', 'display: none');
			} else {
				$childNode->setAttribute('style', 'display: block');
			}

			$node->appendChild($childNode);

		}

		// Hack to avoid selection issue in figures
		if($this->for === 'editor') {

			$span = $this->dom->createElement('span');
			$span->setAttribute('contenteditable', 'false');
			$node->appendChild($span);

		}

		return 0;

	}

	private function cutFigureNode(\DOMNode $node, int $length) {

		if($this->for === 'reador') {

			$max = 15;
			$maxForFull = 4;

			if($max !== NULL and $length > $max + $maxForFull) { // Too much images

				$maxCut = $length - $max;
				$maxNode = $this->dom->createDocumentFragment();

				for($i = 0; $i < $maxCut; $i++) {
					$maxNode->appendChild($node->childNodes[$max]);
				}

				$maxValue = $this->dom->saveHTML($maxNode);

				self::$cut[$node->getAttribute('id')] = [$maxCut, $maxValue];

			} else {
				$maxCut = 0;
			}

			$node->setAttribute('data-cut', $maxCut);

		}

	}

	/**
	 * Create an image node for a figure
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode $newNode
	 * @param int $position
	 * @param array $orientations
	 * @param string $figureLength
	 * @param string $figureSize
	 */
	protected function convertImageNode(\DOMNode $node, \DOMNode $newNode, int $position, array $orientations, string $figureSize) {

		$orientation = $orientations[$position];
		$figureCount = count($orientations);

		$url = $this->getImageUrl($node, $position, $orientation, $figureCount, $figureSize, $this->for === 'editor' ? 'xl' : NULL);

		$previewColor = $node->getAttribute('color') ?: '#bbbbbb';

		if($this->for === 'reador' and $node->hasAttribute('link')) {
			$imageNode = $this->dom->createElement('a');
		} else {
			$imageNode = $this->dom->createElement('div');
		}

		$imageNode->setAttribute('class', 'editor-image');

		if(str_ends_with($url, '.jpg')) {
			$imageNode->setAttribute('style', 'background-color:'.$previewColor.';');
		}

		$displayNode = $this->dom->createElement('img');
		$displayNode->setAttribute('src', $url);

		if($node->hasAttribute('title')) {
			$displayNode->setAttribute('alt', $node->getAttribute('title'));
		}

		$imageNode->appendChild($displayNode);

		if($this->for === 'reador') {

			if($node->hasAttribute('link')) {

				$link = $node->getAttribute('link');

				try {

					['domain' => $domain] = analyze_url($link);

					if($domain !== $this->options['domain']) {

						$imageNode->setAttribute('target', '_blank');

						if($this->options['domain'] === \Lime::getDomain()) {
							$imageNode->setAttribute('rel', 'nofollow');
						}

					}

				} catch(\Exception) {

				}

				$imageNode->setAttribute('href', $link);

			}

			$title = $node->getAttribute('title');

			if($title) {

				$titleContentNode = $this->dom->createDocumentFragment();
				$titleContentNode->appendXml('<span>'.encode($title).'</span>'.\Asset::icon('three-dots'));

				$titleNode = $this->dom->createElement('div');
				$titleNode->setAttribute('class', 'editor-image-title');
				$titleNode->appendChild($titleContentNode);

				$imageNode->appendChild($titleNode);

			}

		}

		$xyzHash = $node->getAttribute('xyz');


		if($xyzHash) {
			$xyzVersion = $node->getAttribute('xyz-version');

			$newNode->setAttribute('data-xyz', $xyzHash);
			$newNode->setAttribute('data-xyz-version', $xyzVersion);

			if($this->for === 'editor') {
				$newNode->setAttribute('data-xyz-w', $node->getAttribute('xyz-width'));
				$newNode->setAttribute('data-xyz-h', $node->getAttribute('xyz-height'));
			}

		}

		if($this->for === 'editor') {

			$newNode->setAttribute('data-url', $url);

			$newNode->setAttribute('data-color', $node->getAttribute('color'));
			$newNode->setAttribute('data-title', $node->getAttribute('title'));
			$newNode->setAttribute('data-link', $node->getAttribute('link'));

		}

		$newNode->appendChild($imageNode);

	}

	protected function getImageUrl(\DomNode $node, int $position, string $orientation, int $figureCount, string $figureSize, $format = NULL) : string {

		if($node->getAttribute('xyz')) {

			$hash = $node->getAttribute('xyz');

			// Always get original format for GIFs
			if(substr($hash, -1, 1) === 'g') {
				$format = 'original';
			}

		}

		if($node->getAttribute('xyz')) {

			$xyzHash = $node->getAttribute('xyz');
			$xyzVersion = (int)$node->getAttribute('xyz-version');

			if($format === NULL) {
				$format = $this->getImageFormat($xyzHash, $position, $orientation, $figureCount, $figureSize);
			}

			if($format === 'original') {
				$url = (new \media\EditorUi())->getUrlByHash($xyzHash, NULL, $xyzVersion);
			} else {
				$url = (new \media\EditorUi())->getUrlByHash($xyzHash, $format, $xyzVersion);
			}

		} else {
			$url = $node->getAttribute('url');
		}

		return $url;
	}

	protected function getImageFormat(string $xyz, int $position, string $orientation, int $figureCount, string $figureSize): string {

		$isMobile = \util\DeviceLib::isMobile();

		if($isMobile) {

			// This is the last image and it is vertical, so it is BIG (as it can be on a single line on mobile)
			// We hate vertical images!
			if(
				$position === $figureCount - 1 and
				$orientation === 'h'

			) {
				return 'l';
			} else {
				return 'm';
			}

		} else {

			$lineCount = $this->countImagesByLine($isMobile, $position, $figureCount);

			switch($figureSize) {

				case 'left' :

					return 'm';

				case 'compressed' :
				default :

					// Only one image, so it is big or large!
					if($figureCount === 1) {
						if($orientation === 'h') {
							return 'xl';
						} else {
							return 'l';
						}
					}

					return 'm';

			}

		}

	}

	protected function countImagesByLine(bool $isMobile, int $position, int $figureCount) {

		switch($figureCount) {

			case 1 :
			case 2 :
				return $figureCount;

			case 4 :
				return 2;

			default :

				if($isMobile) {

					if(
						$position === $figureCount - 1 and // last image
						$figureCount % 2 === 1 // Impair
					) {
						return 1;
					} else {
						return 2;
					}

				} else {

					if(
						($position >= $figureCount - 4) and // 4 last images
						$figureCount % 3 === 1
					) {
						return 2;
					} else if(
						($position >= $figureCount - 2) and // 2 last images
						$figureCount % 3 === 2
					) {
						return 2;
					} else {
						return 3;
					}

				}

		}

	}

	protected function buildDownload(\DOMNode $node): string {

		if(\Privilege::can('editor\admin')) {

			$url = preg_replace('/\/photo\/([0-9]{3,4})\//', '/photo/', $node->getAttribute('url'));
			return '<div><input onclick="this.select(); document.execCommand(&quot;copy&quot;);" value="'.$node->getAttribute('xyz').'" class="form-control"/>&nbsp;/&nbsp;<a href="'.$url.'?&amp;download=1" target="_blank">'.\Asset::icon('download').' '.s("Télécharger").'</a>&nbsp;/&nbsp;</div>';

		} else {
			return '';
		}

	}

	/**
	 * Convert a video node for a figure
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode $newNode
	 */
	protected function convertVideoNode(\DOMNode $node, \DOMNode $newNode) {

		$videoNode = $this->dom->createElement('div');
		$videoNode->setAttribute('class', 'editor-video');

		$newNode->setAttribute('data-source', $node->getAttribute('source'));
		$newNode->setAttribute('data-url', $node->getAttribute('url'));

		$displayNode = $this->dom->createElement('iframe');
		$displayNode->setAttribute('frameborder', 0);
		$displayNode->setAttribute('allowfullscreen', 'true');
		$displayNode->setAttribute('src', $node->getAttribute('url'));

		$videoNode->appendChild($displayNode);

		$newNode->appendChild($videoNode);

	}

	/**
	 * Create a hr for a figure
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode $newNode
	 */
	protected function convertHrNode(\DOMNode $node, \DOMNode $newNode) {

		$divNode = $this->dom->createDocumentFragment();
		$divNode->appendXML('<div class="editor-hr">&#149; &#149; &#149;</div>');

		$newNode->appendChild($divNode);

	}

	public static function getQuoteIcons(): array {
		return [
			'quote' => [
				'icon' => 'quote',
				'label' => s("Citation")
			],
			'localization' => [
				'icon' => 'geo-alt-fill',
				'label' => s("Localisation")
			],
			'calendar' => [
				'icon' => 'calendar-week',
				'label' => s("Quand ?")
			],
			'money' => [
				'icon' => 'piggy-bank',
				'label' => s("Argent")
			],
			'gooddeal' => [
				'icon' => 'lightbulb',
				'label' => s("Bon plan")
			],
			'like' => [
				'icon' => 'heart',
				'label' => s("J'ai aimé")
			],
			'food' => [
				'icon' => 'egg-fried',
				'label' => s("Nourriture")
			]
		];
	}

	/**
	 * Clean a <blockquote> node
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode $newNode
	 */
	protected function convertQuoteNode(\DOMNode $node, \DOMNode $newNode) {

		$about = $node->getAttribute('about') ?: 'quote';

		if($this->for === 'editor') {
			$newNode->setAttribute('data-about', $about);
		}

		$divNode = $this->dom->createElement('div');
		$divNode->setAttribute('class', 'editor-quote');

		$icons = self::getQuoteIcons();

		$divIcon = $this->dom->createDocumentFragment();
		$divIcon->appendXML(\Asset::icon($icons[$about]['icon']));

		$divImageNode = $this->dom->createElement('div');
		$divImageNode->setAttribute('class', 'editor-quote-image');
		$divImageNode->appendChild($divIcon);

		if($this->for === 'editor') {
			$divImageNode->setAttribute('data-action', 'quote-image');
			$divImageNode->setAttribute('placeholder', $icons[$about]['label']);
			$divImageNode->setAttribute('title', (new \editor\EditorUi())->labels()['quoteIcon']);
		}

		$divNode->appendChild($divImageNode);

		$blockquoteNode = $this->dom->createElement('blockquote');

		if($this->for === 'editor') {
			$blockquoteNode->setAttribute('contenteditable', 'true');
			$blockquoteNode->setAttribute('tabindex', '-1');
		}

		while($node->childNodes->length > 0) {

			if($this->convertNode($node->firstChild) === 0) {
				$blockquoteNode->appendChild($node->firstChild);
			}

		}

		$divNode->appendChild($blockquoteNode);

		$newNode->appendChild($divNode);

	}

	/**
	 * Clean a grid node
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode $newNode
	 */
	protected function convertGridNode(\DOMNode $node, \DOMNode $newNode) {

		$columns = $node->getAttribute('columns') ?: 2;

		$newGridNode = $this->dom->createElement('div');
		$newGridNode->setAttribute('class', 'editor-grid');
		$newGridNode->setAttribute('data-columns', $columns);

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$newCellNode = $this->dom->createElement('div');

			if($this->for === 'editor') {
				$newCellNode->setAttribute('contenteditable', 'true');
				$newCellNode->setAttribute('tabindex', '-1');
			}

			$cellNode = $node->childNodes[$i];

			while($cellNode->childNodes->length > 0) {

				if($this->convertNode($cellNode->firstChild) === 0) {
					$newCellNode->appendChild($cellNode->firstChild);
				}

			}

			$newGridNode->appendChild($newCellNode);

		}

		$newNode->appendChild($newGridNode);

	}

	/**
	 * Convert a <p> node
	 *
	 * @param \DOMNode $node
	 */
	protected function convertParagraphNode(\DOMNode $node): int {

		if($node->childNodes->length === 0) {

			$newNode = $this->dom->createElement('br');
			$node->appendChild($newNode);

		}

		$this->convertTextNode($node);

		return 0;

	}

	/**
	 * Convert a <header> node
	 *
	 * @param \DOMNode $node
	 */
	protected function convertHeaderNode(\DOMNode $node): int {

		if($this->for === 'editor') {
			$newNode = $this->dom->createElement('p');
			$newNode->setAttribute('data-header', $node->getAttribute('size'));
		} else {

			switch($node->getAttribute('size')) {

				case '0' :
					$newNode = $this->dom->createElement('h2');
					break;

				case '1' :
					$newNode = $this->dom->createElement('h3');
					break;

				case '2' :
					$newNode = $this->dom->createElement('h4');
					break;

			}

		}

		$node->removeAttribute('size');

		for($i = 0; $i < $node->attributes->length; $i++) {
			$attribute = $node->attributes->item($i);
			$newNode->setAttribute($attribute->name, $attribute->value);
		}

		while($node->childNodes->length > 0) {
			$newNode->appendChild($node->firstChild);
		}

		$node->parentNode->replaceChild($newNode, $node);

		return $this->convertParagraphNode($newNode);

	}

	/**
	 * Convert a text node
	 *
	 * @param string $node
	 */
	protected function convertTextNode(\DOMNode $node) {

		if($node->nodeType !== XML_ELEMENT_NODE) {
			return;
		}

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];
			$this->convertTextNode($childNode);

		}

		switch($node->nodeName) {

			case 'a' :
				return $this->convertLinkNode($node);

			case 'p':
			case 'h2':
			case 'h3':

				$attributes = [
					'align' => 'data-align',
				];

				\util\DomLib::renameNodeAttributes($node, $attributes);

		}

	}

	/**
	 * Convert a link node
	 *
	 * @param \DOMNode $node
	 */
	protected function convertLinkNode(\DOMNode $node) {

		if($this->options['links'] ?? TRUE) {

			$url = $node->getAttribute('url');

			\util\DomLib::renameNodeAttributes($node, [
				'url' => 'href'
			]);

			if($this->for === 'reador') {

				try {

					['domain' => $domain] = analyze_url($url);

					if($domain !== $this->options['domain']) {

						$node->setAttribute('target', '_blank');

						if($this->options['domain'] === \Lime::getDomain()) {
							$node->setAttribute('rel', 'nofollow');
						}

					}

				} catch(\Exception) {

				}

			}

		} else {
			\util\DomLib::removeNode($node);
		}


	}


	/*********************************** EXPORT METHODS ***********************************/
	public function getFromXml(string $value, array $options = []): string {

		$main = $this->filterXml($value, $options);

		$value = $this->dom->saveHTML($main);
		$value = substr($value, 6, -7);

		return $value;
	}

	public static function getCut() {
		return self::$cut;
	}


	private function convertListNode(\DOMNode $node): int {

		for($i = 0; $i < $node->childNodes->length; $i++) {

			// each <li> is a text node
			$childNode = $node->childNodes[$i];
			$this->convertTextNode($childNode);

		}

		return 0;

	}

	private function getImageOrientation(\DomNode $node) : string {

		if($node->hasAttribute('width')) {
			$width = $node->getAttribute('width');
		}

		if($node->hasAttribute('height')) {
			$height = $node->getAttribute('height');
		}
		$ratio = $width / $height;

		if($ratio < 0.85) {
			$orientation = 'h';
		} else if($ratio > 1.15) {
			$orientation = 'w';
		} else {
			$orientation = 's';
		}

		return $orientation;
	}

}
?>
