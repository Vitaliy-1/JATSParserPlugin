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
			$cell_height = $this->getCellHeight($headerfont[2] / $this->k);
			

			// set starting margin for text data cell
			if ($this->getRTL()) {
				$header_x = $this->original_rMargin;
			} else {
				$header_x = $this->original_lMargin; 
			}
			$cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.2);
			$this->SetTextColorArray($this->header_text_color);
			// header title
			$this->SetX($header_x);
			//$this->Cell($cw, $cell_height, $headerdata['title'], 0, 1, 'L', 0, 'c', 0);
			$this->MultiCell($cw, $cell_height, $headerdata['title'], 0, '', 0, 1, '', '', true, 0, false, true, 0, 'B', false);
			$this->SetFont('times', '', 10);
			$this->Cell($cw, $cell_height, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 1, 'R', 0, 'R', 0);

			// print an ending header line
			$this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $headerdata['line_color']));
			// $this->SetY((2.835 / $this->k) + max($imgy, $this->y));
			if ($this->rtl) {
				$this->SetX($this->original_rMargin);
			} else {
				$this->SetX($this->original_lMargin);
			}
			$this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
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
		$cur_y = $this->y;
		$this->SetTextColorArray($this->footer_text_color);
		//set style for cell border
		$line_width = (0.85 / $this->k);
		$this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));

		//print document barcode
		$barcode = $this->getBarcode();
		if (!empty($barcode)) {
			$this->Ln($line_width);
			$barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin) / 3);
			$style = array(
				'position' => $this->rtl ? 'R' : 'L',
				'align' => $this->rtl ? 'R' : 'L',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'padding' => 0,
				'fgcolor' => array(0, 0, 0),
				'bgcolor' => false,
				'text' => false
			);
			$this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
		}
		$w_page = isset($this->l['w_page']) ? $this->l['w_page'] . ' ' : '';
		if (empty($this->pagegroups)) {
			$pagenumtxt = $w_page . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
		} else {
			$pagenumtxt = $w_page . $this->getPageNumGroupAlias() . ' / ' . $this->getPageGroupAlias();
		}
		$this->SetY($cur_y);
		//Print page number
		if ($this->getRTL()) {
			$this->SetX($this->original_rMargin);
			$this->setFont('times', '', 8);
			$this->writeHTML($this->footerHtml, false, false, false, false, 'C');
			// $this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
			// $this->Cell(0, 0, $this->footerHtml, 'T', 0, 'L');
		} else {
			$this->SetX($this->original_lMargin);
			$this->setFont('times', '', 8);
			$this->writeHTML($this->footerHtml, false, false, false, false, 'C');
			// $this->Cell(0, 0, $this->getAliasRightShift() . $pagenumtxt, 'T', 0, 'R');
			// $this->Cell(0, 0, $this->footerHtml, 'T', 0, 'L');
		}
	}
}
