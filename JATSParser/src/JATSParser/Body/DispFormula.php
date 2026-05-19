<?php namespace JATSParser\Body;

class DispFormula extends AbstractElement
{

    private $id;
    private $label;
    private $texContent = null;
    private $textContent;

    public function __construct(\DOMElement $element)
    {
        parent::__construct($element);

        $this->id = $element->getAttribute('id') ?: null;
        $this->label = $this->extractFromElement("label", $element);

        // Prefer tex-math content for LaTeX rendering
        $texMath = $this->xpath->query("tex-math", $element);
        if ($texMath->length > 0) {
            $this->texContent = trim($texMath->item(0)->textContent);
        }

        // Full text content minus the label text
        $fullText = $element->textContent;
        if ($this->label !== null) {
            $fullText = str_replace($this->label, '', $fullText);
        }
        $this->textContent = trim($fullText);
    }

    public function getContent(): string
    {
        return $this->textContent;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getTexContent(): ?string
    {
        return $this->texContent;
    }

    public function getTextContent(): string
    {
        return $this->textContent;
    }

    public function hasLatex(): bool
    {
        return $this->texContent !== null;
    }
}
