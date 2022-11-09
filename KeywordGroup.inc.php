<?php

namespace JATSParser\Body;

use ChromePhp;

class KeywordGroup extends AbstractElement
{

  /* section title */
  private $title;

  /* @var $type int type os a section: 1, 2, 3, 4 -> what means section, subsection, subsubsection, etc. */
  private $type;

  /* unique section id */
  // private $id;

  /* @var $content array */
  private $content;



  function __construct(\DOMElement $keywordGroup,$xpath)
  {
    parent::__construct($keywordGroup);
    $this->xpath = $xpath;
    $this->title = $this->extractFromElement("title", $keywordGroup);
    $this->extractContent($keywordGroup);
  }

  public function getTitle(): ?string
  {
    return $this->title;
  }

  public function getContent(): array
  {
    return $this->content;
  }

  public function getType(): int
  {
    return $this->type;
  }




  private function extractType(\DOMElement $section)
  {
    $parentElements = $this->xpath->query("parent::sec", $section);
    if (!is_null($parentElements)) {
      $this->type += 1;
      foreach ($parentElements as $parentElement) {
        $this->extractType($parentElement);
      }
    }
  }
  //
  // private function ifHasSections(\DOMElement $section)
  // {
  // 	$childSections = $this->xpath->query("sec", $section);
  // 	if ($childSections->length > 0) {
  // 		$this->hasSections = true;
  // 	} else {
  // 		$this->hasSections = false;
  // 	}
  // 	$sectionsTitles = $this->xpath->query("sec/title", $section);
  // 	foreach ($sectionsTitles as $sectionsTitle) {
  // 		$this->childSectionsTitles[] = $sectionsTitle->textContent;
  // 	}
  // }

  private function extractContent(\DOMElement $kwdGroupNode)
  {
    $content = array();

			foreach ($this->xpath->query("./kwd", $kwdGroupNode) as $kwdNode) {
              $content[]= $kwdNode->nodeValue;
        }

    $this->content = $content;
  }
}
