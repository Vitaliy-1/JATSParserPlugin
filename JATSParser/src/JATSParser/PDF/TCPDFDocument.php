<?php

namespace JATSParser\PDF;

use ChromePhp;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

require_once(__DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php');

import('plugins.generic.jatsParser.ChromePhp');

class TCPDFDocument extends \TCPDF
{
  protected $footerHtml;

  function __construct(string $htmlDocument = null)
  {

    // setting up PDF
    //TODO modificar este constructor para poder cambiar el formato de la página y su orientación
    parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
  }

  public function setFooterHtml($footerHtml)
  {
    $this->footerHtml = $footerHtml;
  }

  public function Header()
  {
    $headerfont = $this->getHeaderFont();
    $headerdata = $this->getHeaderData();
    if ($this->header_xobjid === false) {
      // start a new XObject Template
      $this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
      $headerfont = $this->getHeaderFont();
      $headerdata = $this->getHeaderData();
      $this->y = $this->header_margin;
      if ($this->rtl) {
        $this->x = $this->w - $this->original_rMargin;
      } else {
        $this->x = $this->original_lMargin;
      }
      // set starting margin for text data cell
      $header_x = $this->original_lMargin;

      $cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.2);
      $this->SetTextColorArray($this->header_text_color);
      // header title
      $this->SetY(15);
      $this->SetX($header_x);
      $this->MultiCell(0, 0, $headerdata['title'], 0, '', 0, 1, '', '', true, 0, false, true, 0, 'M', false);
      $this->SetFont($headerfont[1], '', $headerfont[2]);

      // print an ending header line
      $this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $headerdata['line_color']));
      $this->SetY(22.4409);
      //
      $this->SetX($this->original_lMargin);
      $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');

      // $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, 'a', '', 0, 'C');

      $this->endTemplate();
    }
    // print header template
    $x = 0;
    $dx = 0;
    if (!$this->header_xobj_autoreset and $this->booklet and (($this->page % 2) == 0)) {
      // adjust margins for booklet mode
      $dx = ($this->original_lMargin - $this->original_rMargin);
    }
    if ($this->rtl) {
      $x = $this->w + $dx;
    } else {
      $x = 0 + $dx;
    }
    $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
    if ($this->header_xobj_autoreset) {
      // reset header xobject template at each page
      $this->header_xobjid = false;
    }
  }

  /**
   * This method is used to render the page footer.
   * It is automatically called by AddPage() and could be overwritten in your own inherited class.
   * @public
   */
  public function Footer()
  {
    $pagenumtxt =  $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
    //Print page number
    $this->setFont('times', '', 8);
    // $this->SetY(-10.541);
    $this->SetY($this->footer_margin);
    // $this->writeHTML($this->footerHtml . $pagenumtxt, true, false, false, false, 'C');
    $this->writeHTMLCell(0, 10, '', '', $this->footerHtml . $pagenumtxt, 'T', 0, false, true, 'C', false);
    // $this->Cell(0, 0, $this->getAliasRightShift() . $pagenumtxt, 'T', 0, 'R');
  }
}
