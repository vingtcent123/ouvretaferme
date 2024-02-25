<?php
namespace editor;

/**
 * Convert html got from a form to valid xml
 */
class XmlLib {

	/**
	 * Current DomDocument handled
	 *
	 * @var DomDocument
	 */
	protected $dom;

	/**
	 * Conversion options
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * Convert output of the editor to a XML string
	 *
	 * @param string $value
	 * @param array $options
	 * @return string
	 */
	public function fromHtml(?string $value, array $options = []): ?string {

		if($value === NULL) {
			return NULL;
		}

		$this->options = $options + [
			'acceptFigure' => FALSE,
			'draft' => FALSE
		];

		libxml_use_internal_errors(TRUE);

		$dom = new \DOMDocument();
		$dom->strictErrorChecking = FALSE;

		if(
			$value === '<p></p>' or
			$value === '<p><br></p>'
		) {
			$value = '';
		}

		$dom->loadHtml(
			'<?xml version="1.0" encoding="UTF-8"?>'.'<main>'.$value.'</main>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOXMLDECL | LIBXML_NOBLANKS
		);

		$this->dom = $dom;

		if($dom->childNodes->length === 1) {
			return NULL;
		}

		libxml_clear_errors();

		$main = $dom->childNodes[1];

		if($main->childNodes === NULL) {
			return NULL;
		}

		// First, repair XML
		// Avoir <p>xxx</p>vvv<p>yyy</p> -> <p>xxx</p><p>vvv</p><p>yyy</p>
		if($this->options['draft'] === FALSE) {
			$this->repair($main);
		}

		\util\DomLib::noWhitespaceNode($main);

		for($i = 0; $i < $main->childNodes->length; $i++) {

			$node = $main->childNodes[$i];

			$i += $this->cleanNode($node);

		}

		if($main->childNodes->length === 0) {
			return NULL;
		}

		// Workaround to avoid XML declaration
		$value = $dom->saveXml($main);

		// Final cleanup
		$value = preg_replace('/<li>\s*(.*?)\s*<\/li>/si', '<li>\\1</li>', $value);
		$value = preg_replace('/<p>\s*(.*?)\s*<\/p>/si', '<p>\\1</p>', $value);

		return $value;
	}

	protected function cleanNode(\DOMNode $node): int {

		switch($node->nodeName) {

			case 'p' :
				return $this->cleanParagraphNode($node);

			case 'ol' :
			case 'ul' :
				return $this->cleanListNode($node);

			case ($this->options['acceptFigure'] ? 'figure' : NULL) :
				return $this->cleanFigureNode($node);

			// Forbidden root node
			default :
				$node->parentNode->removeChild($node);
				return -1;

		}

	}

	/**
	 * Clean a <figure> node
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanFigureNode(\DOMNode $node) : int {

		// Clean attributes from figure
		$figureAttributes = [
			'data-interactive' => 'interactive',
			'data-size?' => 'size',
			'data-after?' => 'after',
			'data-before?' => 'before'
		];

		if($this->cleanNodeAttributes($node, $figureAttributes) === -1) {
			return -1;
		}

		$this->safeRenameNodeAttributes($node, $figureAttributes);

		\util\DomLib::noWhitespaceNode($node);

		// Remove hack span
		if(
			$node->lastChild and
			$node->lastChild->nodeName === 'span'
		) {
			$node->removeChild($node->lastChild);
		}

		// Clean child nodes (images, videos, embeds)
		$medias = 0;

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			if($this->cleanMediaNode($childNode) === -1) {
				$i--;
				continue;
			}

			if($childNode->nodeName === 'div') {
				$medias++;
			}

		}

		if(
			// Nothing in the figure
			$node->childNodes->length === 0 or
			// Only a caption in the figure
			($node->childNodes->length === 1 and $node->firstChild->nodeName === 'figcaption')
		) {
			$node->parentNode->removeChild($node);
			return -1;
		}

		if($node->getAttribute('before') === 'true') {

			if(
				$node->previousSibling === NULL or
				$node->previousSibling->nodeName !== 'p'
			) {
				$node->removeAttribute('before');
			}

		} else {
			$node->removeAttribute('before');
		}

		if($node->getAttribute('after') === 'true') {

			if(
				$node->nextSibling === NULL or
				$node->nextSibling->nodeName !== 'p'
			) {
				$node->removeAttribute('after');
			}

		} else {
			$node->removeAttribute('after');
		}

		if($node->getAttribute('interactive') === 'true') {

			if($node->hasAttribute('size')) {

				switch($node->getAttribute('size')) {

					// Left/right size are very special
					case 'left' :
					case 'right' :

						if(
							$medias > 1 or // Only 1 media
							in_array($node->firstChild->nodeName, ['hr', 'embed']) // Not supported for embeds
						) {
							$node->setAttribute('size', 'compressed');
						} else {

							// No captions for left size
							if($node->lastChild->nodeName === 'figcaption') {
								$node->removeChild($node->lastChild);
							}

						}

						break;

					case 'compressed' :
						break;

					default :
						$node->removeAttribute('size');
						break;


				}

			}

		} else {

			$node->removeAttribute('size');

		}

		return 0;

	}

	protected function cleanMediaNode(\DOMNode $node): int {

		\util\DomLib::noWhitespaceNode($node);

		switch($node->nodeName) {

			case 'figcaption' :
				return $this->cleanFigcaptionNode($node);

			case 'div' :

				$type = $node->getAttribute('data-type');

				switch($type) {

					case 'image' :
						return $this->cleanImageNode($node);

					case 'video' :
						return $this->cleanVideoNode($node);

					case 'hr' :
						return $this->cleanHrNode($node);

					case 'embed' :
						return $this->cleanEmbedNode($node, 'data-source');

					case 'quote' :
						return $this->cleanQuoteNode($node);

					case 'grid' :
						return $this->cleanGridNode($node);

					default:
						$node->parentNode->removeChild($node);
						return -1;

				}

			default :
				$node->parentNode->removeChild($node);
				return -1;
		}
	}

	protected function cleanFigcaptionNode(\DOMNode $node): int {

		if(
			$node->nextSibling or // Caption is not the last child of the figure
			$node->previousSibling === NULL or // Caption is the only child of the figure
			$node->previousSibling->nodeName === 'embed' or // No caption for embeds
			$node->previousSibling->nodeName === 'hr' // No caption for hrs
		) {

			$node->parentNode->removeChild($node);
			return -1;

		} else {

			// Clean caption
			$i = 0;

			while($i < $node->childNodes->length) {

				$childNode = $node->childNodes[$i];

				// Text nodes are OK
				if($childNode->nodeType === XML_TEXT_NODE) {

					$trimmedValue = str_replace(chr(0xC2).chr(0xA0), ' ', $childNode->nodeValue); // Convert nbsp
					$trimmedValue = trim($trimmedValue);

					if($trimmedValue === '') {
						$node->removeChild($childNode);
						$i--;
					}

				} else {

					if($this->cleanFigcaptionSubnode($childNode) === -1) {
						$i--;
					}

				}

				$i++;

			}

			if($node->childNodes->length > 0) {

				return $this->cleanNodeAttributes($node);

			} else {

				$node->parentNode->removeChild($node);
				return -1;

			}

		}


	}

	/* Clean figcaption subnodes : only allowed subnodes are kept */
	protected function cleanFigcaptionSubnode(\DOMNode $node): int {

		$node->parentNode->removeChild($node);
		return -1;

	}

	protected function cleanImageNode(\DOMNode $node): int {

		$title = $node->getAttribute('data-title');

		if(
			$title and
			mb_strlen($title) > \Setting::get('editor\mediaTitleLimit')
		) {
			$node->setAttribute('data-title', mb_substr($title, 0, \Setting::get('editor\mediaTitleLimit')));
		}

		$attributes = [
			'data-url' => 'url',
			'data-color' => 'color',
			'data-w' => 'width',
			'data-h' => 'height',
			'data-title?' => 'title',
			'data-link?' => 'link',
			'data-embed?' => 'embed',
		];

		if($this->isXyzNode($node)) {

			$attributes += [
				'data-xyz' => 'xyz',
				'data-xyz-w' => 'xyz-width',
				'data-xyz-h' => 'xyz-height',
				'data-xyz-version' => 'xyz-version',
			];

		}

		return $this->createMediaNode($node, 'image', $attributes);

	}

	protected function isXyzNode(\DOMNode $node) {
		return $node->hasAttribute('data-xyz');
	}

	protected function cleanVideoNode(\DOMNode $node): int {
		return $this->createMediaNode($node, 'video', [
			'data-source' => 'source',
			'data-url' => 'url',
			'data-w' => 'width',
			'data-h' => 'height',
		]);
	}

	protected function cleanHrNode(\DOMNode $node): int {

		// Figure must have only 1 child
		if($node->parentNode->childNodes->length !== 1) {
			$node->parentNode->removeChild($node);
			return -1;
		}

		return $this->createMediaNode($node, 'hr');

	}

	/**
	 * Clean a <blockquote> node
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanQuoteNode(\DOMNode $node): int {

		\util\DomLib::noWhitespaceNode($node->firstChild);

		$nodeBlockquote = $this->getQuoteNode($node);

		if($nodeBlockquote === NULL) {
			return -1;
		}

		$newNode = $this->createQuote($nodeBlockquote);

		if($newNode->childNodes->length === 0) {

			$node->parentNode->removeChild($node);
			return -1;

		} else {

			$about = $node->getAttribute('data-about');

			if(in_array($about, \Setting::get('editor\quotes')) === FALSE) {
				$about = \Setting::get('editor\quotes')[0];
			}

			$newNode->setAttribute('about', $about);

			$node->parentNode->replaceChild($newNode, $node);
			return 0;

		}

	}

	protected function createQuote(\DOMNode $node) : \DOMNode {

		$newNode = $this->dom->createElement('quote');

		// Only <p> as children for blockquote
		while($node->childNodes->length > 0) {

			if($this->cleanNode($node->firstChild) === 0) {
				$newNode->appendChild($node->firstChild);
			}

		}

		return $newNode;

	}

	protected function getQuoteNode(\DOMNode $node) {

		// Figure must have only 1 child
		if($node->parentNode->childNodes->length !== 1) {
			$node->parentNode->removeChild($node);
			return NULL;
		}

		// Node must have a div child
		// <div class="editor-quote">
		$nodeDiv = NULL;

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			if(
				$childNode->nodeName === 'div' and
				$childNode->getAttribute('class') === 'editor-quote'
			) {
				$nodeDiv = $childNode;
				break;
			}

		}

		if($nodeDiv === NULL) {
			$node->parentNode->removeChild($node);
			return NULL;
		}

		// Div must have a blockquote child
		// <blockquote>
		if(
			$nodeDiv->childNodes->length !== 2 or
			$nodeDiv->lastChild->nodeName !== 'blockquote'
		) {
			$node->parentNode->removeChild($node);
			return NULL;
		}

		$nodeBlockquote = $nodeDiv->lastChild;

		return $nodeBlockquote;
	}

	/**
	 * Clean a grid node
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanGridNode(\DOMNode $node): int {

		\util\DomLib::noWhitespaceNode($node->firstChild);

		$cells = $this->getGridCells($node);

		if($cells === NULL) {
			return -1;
		}

		$newNode = $this->dom->createElement('grid');

		// Only <p> as children for blockquote
		foreach($cells as $cell) {

			$newCell = $this->dom->createElement('cell');

			while($cell->childNodes->length > 0) {

				if($this->cleanNode($cell->firstChild) === 0) {
					$newCell->appendChild($cell->firstChild);
				}

			}

			$newNode->appendChild($newCell);

		}

		$columns = $node->firstChild->getAttribute('data-columns');

		if($columns < 2 or $columns > 4) {
			$columns = 2;
		}

		$newNode->setAttribute('columns', $columns);

		$node->parentNode->replaceChild($newNode, $node);

		return 0;


	}

	protected function getGridCells(\DOMNode $node): ?array {

		// Figure must have only 1 child
		if($node->parentNode->childNodes->length !== 1) {
			$node->parentNode->removeChild($node);
			return NULL;
		}

		// Node must have on child of class="editor-grid"
		if(
			$node->childNodes->length !== 1 or
			$node->firstChild->getAttribute('class') !== 'editor-grid'
		) {
			$node->parentNode->removeChild($node);
			return NULL;
		}

		$nodeGrid = $node->firstChild;

		$cells = [];

		// Get cells
		for($i = 0; $i < $nodeGrid->childNodes->length; $i++) {

			$nodeCell = $nodeGrid->childNodes[$i];

			if(
				$nodeCell->nodeName === 'div' and
				$nodeCell->getAttribute('contenteditable') === 'true'
			) {
				$cells[] = $nodeCell;
			}

		}

		if($cells === []) {
			$node->parentNode->removeChild($node);
			return NULL;
		}

		return $cells;
	}

	protected function cleanEmbedNode(\DOMNode $node, string $attributeSource): string {
		// Can't have embed in a figure that has more than 1 child
		if($node->parentNode->childNodes->length > 1) {
			return $node->parentNode->removeChild($node);

		} else {

			switch($node->getAttribute($attributeSource)) {

				case 'link' :
					return $this->createMediaNode($node, 'embed', [
						'data-source' => 'source',
						'data-url' => 'url',
						'data-link-image-url' => 'link-image-url',
						'data-link-description' => 'link-description',
						'data-link-title' => 'link-title'
					]);

				case 'ouvretaferme':
					return $this->createMediaNode($node, 'embed', [
						'data-source' => 'source',
						'data-url' => 'url',
						'data-html' => 'html'
					]);

				default :
					return $node->parentNode->removeChild($node);
			}
		}
	}

	/**
	 * Create a media node for a figure
	 *
	 * @param \DOMNode $node
	 * @param string $tagName video, embed, image
	 * @param array $attributes Attributes matching
	 */
	protected function createMediaNode(\DOMNode $node, string $tagName, array $attributes = []): int {

		// Attributes must be there
		if($this->cleanNodeAttributes($node, $attributes) === -1) {
			return -1;
		}

		$newNode = $this->dom->createElement($tagName);

		foreach($attributes as $from => $to) {

			$optional = str_ends_with($from, '?');

			if($optional) {
				$from = substr($from, 0, -1);
			}

			if($optional) {

				if($node->hasAttribute($from) and $node->getAttribute($from) !== '') {
					$newNode->setAttribute($to, $node->getAttribute($from));
				}

			} else {
				$newNode->setAttribute($to, $node->getAttribute($from));
			}

		}

		$node->parentNode->replaceChild($newNode, $node);
		return 0;
	}

	/**
	 * Clean a <p> node
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanParagraphNode(\DOMNode $node) {

		\util\DomLib::trimNode($node);

		$attributes = [
			'data-align?' => 'align',
			'data-lat?' => 'lat',
			'data-lon?' => 'lon',
			'data-name?' => 'name',
			'data-id?' => 'id',
			'data-zoom?' => 'zoom',
		];

		return $this->createParagraphElement($node, $attributes);

	}

	protected function createParagraphElement(\DOMNode $node, array $attributes): int {

		if($node->hasAttribute('data-align')) {

			if(
				in_array($node->getAttribute('data-align'), ['center', 'right', 'justify']) === FALSE
			) {
				$node->removeAttribute('data-align');
			}

		}

		if($node->hasAttribute('data-header')) {

			$this->cleanChildHeaderNodes($node);

			$attributes['data-header'] = 'size';

			$this->cleanNodeAttributes($node, $attributes);
			$this->safeRenameNodeAttributes($node, $attributes);

			\util\DomLib::renameNode($this->dom, $node, 'header');

			return 0;

		} else {

			\util\DomLib::trimNode($node);
			$this->cleanChildTextNodes($node);

			$this->cleanNodeAttributes($node, $attributes);
			$this->safeRenameNodeAttributes($node, $attributes);

			return 0;

		}
	}

	/**
	 * Clean a <ol> and <ul> node
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanListNode(\DOMNode $node): int {

		\util\DomLib::trimNode($node);

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			if($childNode->nodeName !== 'li') {
				$node->removeChild($childNode);
				$i--;
			} else {
				\util\DomLib::trimNode($childNode);
				$this->cleanChildTextNodes($childNode);
			}

		}

		if($node->childNodes->length === 0) {
			$node->parentNode->removeChild($node);
			return -1;
		} else {
			$this->cleanNodeAttributes($node);
			return 0;
		}

	}

	/**
	 * Keep only authorized tags in a text (<b>, <i>, <u>, <s>, <a>, <smiley>)
	 *
	 * @param string $node
	 */
	protected function cleanTextNode(\DOMNode $node) : int {

		switch($node->nodeType) {

			case XML_ELEMENT_NODE :

				$this->cleanChildTextNodes($node);

				switch($node->nodeName) {

					// Clean attributes
					case 'b' :
					case 'i' :
					case 'u' :
						return $this->cleanNodeAttributes($node);

					case 'a' :
						return $this->cleanLinkNode($node);

					default :
						return \util\DomLib::removeNode($node);

				}

			case XML_TEXT_NODE :

				$newValue = str_replace(["\n", "\t", chr(194).chr(160), "\r"], [' ', ' ', ' ', ''], $node->nodeValue);
				$newValue = preg_replace('/[ 	]{2,}/', ' ', $newValue); // Replace double spaces and tabs
				$newValue = preg_replace('/ ([\?\!\.\:\;])/', chr(194).chr(160).'\\1', $newValue);

				$node->nodeValue = $newValue;

				return 0;

			default : // CDATA ...
				return \util\DomLib::removeNode($node);

		}


	}

	/**
	 * Keep only authorized tags in a header (<smiley>)
	 *
	 * @param string $node
	 */
	protected function cleanHeaderNode(\DOMNode $node) : int {

		if($node->nodeType !== XML_ELEMENT_NODE) {
			return 0;
		}

		$this->cleanChildHeaderNodes($node);

		return \util\DomLib::removeNode($node);

	}

	/**
	 * Clean a link node
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanLinkNode(\DOMNode $node) : int {

		$attributesLink = [
			'href' => 'url',
		];

		if(\Privilege::can('user\admin')) {

			$attributesLink += [
				'data-button?' => 'button'
			];

		}

		if($this->cleanNodeAttributes($node, $attributesLink) === -1) {
			return -1;
		}

		$this->safeRenameNodeAttributes($node, $attributesLink);

		return 0;

	}

	/**
	 * Same as cleanNodes() about text
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanChildTextNodes(\DOMNode $node) {

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			$i += $this->cleanTextNode($childNode);

		}

	}

	/**
	 * Same as cleanNodes() about header
	 *
	 * @param \DOMNode $node
	 */
	protected function cleanChildHeaderNodes(\DOMNode $node) {

		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			$i += $this->cleanHeaderNode($childNode);

		}

	}

	/**
	 * Removed unauthorized attributes from a node
	 *
	 * @param \DOMNode $node
	 * @param array $attributes List of authorized attributes
	 * @return string
	 */
	protected function cleanNodeAttributes(\DOMNode $node, array $attributes = []): int {

		return \util\DomLib::cleanNodeAttributes($node, $attributes);

	}

	/**
	 * Rename attributes of a node
	 *
	 * @param  \DOMNode $node
	 * @param  array    $attributes
	 */
	protected function safeRenameNodeAttributes(\DOMNode $node, array $attributes) {

		return \util\DomLib::safeRenameNodeAttributes($node, $attributes);

	}

	/**
	 * Convert plain text to a XML string
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function prepareFromText(string $value): string {

		$lines = preg_split('/\n\r?/', $value);

		$h = '';

		foreach($lines as $line) {
			$h .= '<p>'.$line.'</p>';
		}

		return $h;

	}


	public function extractImages(string $value): array {

		libxml_use_internal_errors(TRUE);

		if(
			$value === '<p></p>' or
			$value === '<p><br></p>'
		) {
			return [];
		}
		$dom = new \DOMDocument();
		$dom->strictErrorChecking = FALSE;
		$dom->loadXml($value, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOBLANKS | LIBXML_NOXMLDECL);

		$main = $dom->childNodes[0];

		$tags = [];

		for($i = 0; $i < $main->childNodes->length; $i++) {

			$node = $main->childNodes[$i];

			if($node->nodeName !== 'figure') {
				continue;
			}

			for($j = 0; $j < $node->childNodes->length; $j++) {

				$mediaNode = $node->childNodes[$j];

				if($mediaNode->nodeName !== 'image') {
					continue;
				}

				if($mediaNode->hasAttribute('xyz') === FALSE) {
					continue;
				}

				$url = $mediaNode->getAttribute('url');

				// TODO OVH : ajouter une meilleure vérification ? Quid si on prend plusieurs containers ?
				if(preg_match('/\/photo\/([0-9]+\/)?([a-z0-9]+)\.(jpg|png|gif)/si', $url, $matches) === 0) {
					throw new \Exception('Invalid URL '.$url);
				}

				$hash = $matches[2];
				$extension = $matches[3];

				$tags[] = [
					'url' => $url,
					'hash' => $hash,
					'extension' => $extension,
				];

			}

		}

		return $tags;
	}

	protected function repair(\DOMNode $main) {

		$allowed = ['p', 'header', 'ol', 'ul', 'figure'];

		$i = 0;
		$nodeFix = NULL;
		$fixed = NULL;

		while($i < $main->childNodes->length) {

			$node = $main->childNodes[$i];

			if(\util\DomLib::isWhitespace($node) or $node->nodeType !== XML_ELEMENT_NODE) {
				$i++;
				continue;
			}

			if(in_array($node->nodeName, $allowed) === FALSE) {

				if($fixed === NULL) {
					$fixed = $this->dom->saveXml($main);
				}

				if($nodeFix === NULL) {
					$nodeFix = $this->dom->createElement('p');
				}

				$nodeFix->appendChild($node);
				$main->removeChild($node);

				$i--;

			} else {

				if($nodeFix !== NULL) {

					$main->insertBefore($nodeFix, $node);
					$nodeFix = NULL;

				}

			}

			$i++;

		}

		if($nodeFix !== NULL) {
			$main->appendChild($nodeFix);
		}

		if($fixed) {
			trigger_error('Repair invalid XML ('.$fixed.') to ('.$this->dom->saveXml($main).')', E_USER_NOTICE);
		}

	}

}
?>
