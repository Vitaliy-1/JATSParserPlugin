<?php namespace JATSParser\HTML;

use JATSParser\Body\JATSElement;
use JATSParser\Body\Text as JATSText;

class Text {

	public static function extractText(JATSText $jatsText, \DOMNode $domElement) : void {
		// Get DOMDocument
		$domDocument = $domElement->ownerDocument;
		if (!$domDocument) $domDocument = $domElement;

		// Dealing with simple text (without any properties)
		$nodeTypes = $jatsText->getType();
		if (in_array("normal", $nodeTypes)) {
			$textNode = $domDocument->createTextNode($jatsText->getContent());
			$domElement->appendChild($textNode);
			unset($nodeTypes);
		}

		// Renaming text properties into standard HTML node element
		$typeArray = array();
		if (isset($nodeTypes)) {
			foreach ($nodeTypes as $nodeType) {
				switch ($nodeType) {
					case "italic":
						$typeArray[] = "i";
						break;
					case "bold":
						$typeArray[] = "b";
						break;
					case "sup":
						$typeArray[] = "sup";
						break;
					case "sub":
						$typeArray[] = "sub";
						break;
					case "underline":
						$typeArray[] = "u";
						break;
					case is_array($nodeType):
						foreach ($nodeType as $elementName => $elementAttrs) {
							if ($elementName === "xref") {
								$newArray = array();
								foreach ($elementAttrs as $attrKey => $attrValue) {
									if ($attrKey === "rid") {
										$newArray ["href"] = "#" . $attrValue;
									} elseif ($attrKey === "ref-type") {
										$newArray ["class"] = $attrValue;
									} else {
										$newArray[$attrKey] = $attrValue;
									}
								}
								$typeArray[]["a"] = $newArray;
							} else if ($elementName = "ext-link") {
								$newArray = array();
								foreach ($elementAttrs as $attrKey => $attrValue) {
									if ($attrKey === "xlink:href") {
										$newArray["href"] = $attrValue;
									} else if ($attrKey = "ext-link-type") {
										$newArray["class"] = "JATSParser__link-" . $attrValue;
									}
								}
								$typeArray[]["a"] = $newArray;
							}
						}
						break;
				}
			}
		}

		// Dealing with text that has only one property, e.g. italic, bold, link
		if (count($typeArray) === 1) {
			foreach ($typeArray as $typeKey => $type) {
				if (!is_array($type)) {
					$nodeElement = $domDocument->createElement($type);
					$nodeElement->nodeValue = htmlspecialchars($jatsText->getContent());
					$domElement->appendChild($nodeElement);
				} else {
					foreach ($type as $insideKey => $insideType) {
						$nodeElement = $domDocument->createElement($insideKey);
						$nodeElement->nodeValue = trim(htmlspecialchars($jatsText->getContent()));
						if (is_array($insideType)) {
							foreach ($insideType as $nodeAttrKey => $nodeAttrValue) {
								$nodeElement->setAttribute($nodeAttrKey, $nodeAttrValue);
							}
						}
						$domElement->appendChild($nodeElement);
					}

				}
			}

			// Dealing with complex cases -> text with several properties
		} else {
			/* @var $prevElement array of DOMElements */
			$prevElements = array();
			foreach ($typeArray as $key => $type) {
				if (!is_array($type)) {
					$nodeElement = $domDocument->createElement($type);
				} else {
					foreach ($type as $insideKey => $insideType) {
						$nodeElement = $domDocument->createElement($insideKey);
						if (is_array($insideType)) {
							foreach ($insideType as $nodeAttrKey => $nodeAttrValue) {
								$nodeElement->setAttribute($nodeAttrKey, $nodeAttrValue);
							}
						}
					}
				}

				array_push($prevElements, $nodeElement);

				if ($key === 0) {
					$domElement->appendChild($prevElements[0]);
				} elseif (($key === (count($typeArray) - 1))) {
					$nodeElement->nodeValue = htmlspecialchars($jatsText->getContent());

					foreach ($prevElements as $prevKey => $prevElement) {
						if ($prevKey !== (count($prevElements) -1)) {
							$prevElement->appendChild(next($prevElements));
						}
					}
				}
			}
		}
	}

	public static function checkPunctuation(string $label) {
		$label = trim($label);
		if (preg_match("/\.$|:$/", $label, $matches) === 0) {
			$label .= ".";
		}
		return $label;
	}

}
