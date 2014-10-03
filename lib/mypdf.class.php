<?php
class MyPdf extends TCPDF {
    
    function __construct() {
        parent::__construct();
        $this->setTitle('REPORTE');
        $this->setCreator("JC");
        $this->setAuthor('JC');
        $this->SetTopMargin(29);
        $this->SetLeftMargin(4);
        $this->SetRightMargin(4);
    }

    public function setData($cabecera, $campos) {
        $this->cabecera = $cabecera;
        $this->campos = $campos;
    }
            
    public function Header() {
        $this->SetY(5);
        $this->SetFont('', '', 10, '', true);
        $this->Cell(0, 0, $this->cabecera['cia'], 0, true, 'L');
        $this->SetFont('', 'B', 14, '', true);
        $this->Cell(0, 0, $this->cabecera['titulo'], 0, true, 'C');
        $this->SetFont('', '', 10, '', true);
        $this->Cell(0, 0, "DEL: ".$this->cabecera['del']." AL: ".$this->cabecera['al'], 0, 1, 'C');
        $this->ln();
        $this->SetFont('courier', 'B', 10);
        foreach ($this->campos as $h) {
            if ($h['n']) {
                $this->Cell($h['w'], 0, $h['t'], 0, 0, 'R');
            } else {
                $this->Cell($h['w'], 0, $h['t']);
            }
        }
    }

    public function Footer() {
        $this->SetY(-10);
        $this->SetFont('courier', 'I', 8);
        $this->Cell(0, 0, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 'T', true);
        $datetime = new DateTime(); 
        $datetime->setTimezone(new DateTimeZone('America/Lima')); 
        $fechahora = $datetime->format('d/m/Y h:i A');
        $this->Cell(0, 0, "Fecha Impresión: $fechahora", 0, true);
    }
}
?>