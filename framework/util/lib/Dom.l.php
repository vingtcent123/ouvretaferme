<?php
namespace util;

/**
 * Dom handling
 *
 */
class DomLib {

	/**
	 * Displays the
	 * @param \DOMNode $node
	 */
	public static function debugAttributes(\DOMNode $node) {

		for($i = 0; $i < $node->attributes->length; $i++) {
			debug($node->attributes[$i]->name);
		}

	}

	/**
	 * Remove whitespaces (\r, \t, \n) in a node
	 *
	 * @param \DOMNode $node
	 */
	public static function noWhitespaceNode(\DOMNode $node) {

		if($node->nodeType !== XML_ELEMENT_NODE) {
			return;
		}

		// Left trim
		for($i = 0; $i < $node->childNodes->length; $i++) {

			$childNode = $node->childNodes[$i];

			if(self::removeWhitespace($childNode)) {
				$i--;
			}

		}

	}

	/**
	 * Trim whitespaces (\r, \t, \n) in a node
	 *
	 * @param \DOMNode $node
	 */
	public static function trimNode(\DOMNode $node) {

		if($node->nodeType !== XML_ELEMENT_NODE) {
			return;
		}

		// Left trim
		while($node->childNodes->length > 0) {

			$childNode = $node->childNodes[0];

			if(self::removeWhitespace($childNode) === FALSE) {
				break;
			}

		}

		// Right trim
		while($node->childNodes->length > 0) {

			$childNode = $node->childNodes[$node->childNodes->length - 1];

			if(self::removeWhitespace($childNode) === FALSE) {
				break;
			}

		}

	}

	/**
	 * Rename attributes in a node
	 * /!\ There is no check for the other attributes
	 *
	 * @param \DOMNode $node
	 * @param array $attributes List of attributes to rename
	 * @return string
	 */
	public static function renameNodeAttributes(\DOMNode $node, array $attributes) {

		foreach($attributes as $from => $to) {

			if($node->hasAttribute($from)) {

				if($to !== NULL) {

					$value = $node->getAttribute($from);
					$node->setAttribute($to, $value);

				}

				$node->removeAttribute($from);

			}

		}

	}

	/**
	 * Rename attributes of a node in a safe mode : remove attributes that are not required in $attributes array
	 *
	 * @param  \DOMNode $node
	 * @param  array    $attributes
	 */
	public static function safeRenameNodeAttributes(\DOMNode $node, array $attributes) {

		foreach($attributes as $from => $to) {

			if(substr($from, -1) === '?') {

				unset($attributes[$from]);
				$attributes[substr($from, 0, -1)] = $to;

			}

		}

		// Remove attributes from figure
		for($i = 0; $i < $node->attributes->length; $i++) {

			$attribute = $node->attributes->item(0);
			$value = $attribute->value;

			$node->setAttribute($attributes[$attribute->name], $value);
			$node->removeAttribute($attribute->name);

		}

	}

	/**
	 * Removed unauthorized attributes from a node
	 *
	 * @param \DOMNode $node
	 * @param array $attributes List of authorized attributes
	 *
	 * @return int
	 */
	public static function cleanNodeAttributes(\DOMNode $node, array $attributes = []): int {

		// Remove attributes from figure
		$nAttributesFound = 0;

		foreach($attributes as $from => $to) {

			if(substr($from, -1) === '?') {

				unset($attributes[$from]);
				$attributes[substr($from, 0, -1)] = $to;
				$nAttributesFound++;

			}

		}

		for($i = $node->attributes->length - 1; $i >= 0; $i--) {

			$attribute = $node->attributes->item($i);

			if(
				$attributes === [] or
				isset($attributes[$attribute->name]) === FALSE
			) {

				$node->removeAttribute($attribute->name);

			} else {

				$nAttributesFound++;

			}

		}

		//at least one attribute is missing
		if($nAttributesFound < count($attributes)) {

			$node->parentNode->removeChild($node);
			return -1;

		}

		return 0;

	}

	/**
	 * Rename a node
	 *
	 * @param \DOMDocument $dom
	 * @param \DOMNode $node
	 * @return string
	 */
	public static function renameNode(\DOMDocument $dom, \DOMNode $node, $newTag): \DOMNode {

		$newNode = $dom->createElement($newTag);

		for($i = 0; $i < $node->attributes->length; $i++) {
			$attribute = $node->attributes->item($i);
			$newNode->setAttribute($attribute->name, $attribute->value);
		}

		while($node->childNodes->length > 0) {
			$newNode->appendChild($node->firstChild);
		}

		$node->parentNode->replaceChild($newNode, $node);

		return $newNode;

	}

	/**
	 * Remove a node from the dom and copy its children to the parent
	 *
	 * @param \DOMNode $node
	 * @return string
	 */
	public static function removeNode(\DOMNode $node) :int {

		$delta = 0;

		$parentNode = $node->parentNode;

		if($node->nodeType === XML_ELEMENT_NODE) {

			while($node->childNodes->length > 0) {
				$parentNode->insertBefore($node->firstChild, $node);
				$delta++;
			}

		}

		$parentNode->removeChild($node);

		$delta--;

		return $delta;

	}

	private static function removeWhitespace(\DOMNode $node) {

		if(self::isWhitespace($node)) {
			$node->parentNode->removeChild($node);
			return TRUE;
		} else {
			return FALSE;
		}

	}

	public static function isWhitespace(\DOMNode $node) {

		return (
			(
				$node->nodeType === XML_TEXT_NODE and
				preg_match('/^\s*$/', $node->nodeValue) > 0
			) or
			(
				$node->nodeType === XML_ELEMENT_NODE and
				$node->getAttribute('data-whitespace') === '1'
			)
		);

	}

}
?>