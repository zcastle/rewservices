<?php

include_once('lib/mypdf.class.php');

class Reporte {

	function __construct($struct, $index, $data) {
		$this->app = \Slim\Slim::getInstance();
		$this->struct = $struct;
		$this->index = $index;
		$this->data = $data;
	}

	public function ver(){
		try {
	        $this->app->response()->header("Content-Type", "application/pdf");
	        $pdf = new MyPdf();
	        $pdf->setData($this->struct['cabecera'], $this->struct[$this->index]['detalle']);
	        $pdf->AddPage();
	        $pdf->SetFont('courier', '', 9);
	        $total = 0.0;
	        $grupo = null;
	        $grupoTotal = 0.0;
	        foreach ($this->data as $row) {
	        	if($this->struct[$this->index]['grupo']) {
	        		if ($row[$this->struct[$this->index]['grupo']]!=$grupo) {
	        			$pdf->SetFont('courier', 'B', 9);
	        			if($grupo!=null) {
	        				$pdf->Cell(165, 0, 'TOTAL '.$grupo, 0, 0, 'R');
	        				$pdf->Cell(35, 0, number_format($grupoTotal, 2), 0, true, 'R');
	        				$grupoTotal = 0.0;
	        				$pdf->Ln();
	        			}
		        		$pdf->Cell(50, 0, $row[$this->struct[$this->index]['grupo']], 0, true);
		        		$pdf->SetFont('courier', '', 9);
		        		$grupo = $row[$this->struct[$this->index]['grupo']];
		        	}
	        	}
	            foreach ($this->struct[$this->index]['detalle'] as $d) {
	            	if($d['f']==$this->struct[$this->index]['grupo']){
	            		$pdf->Cell($d['w'], 0, '');
	            	} else {
		                if ($d['n']) {
		                    $pdf->Cell($d['w'], 0, number_format($row[$d['f']], 2), 0, 0, 'R');
		                } else {
		                    $pdf->Cell($d['w'], 0, $row[$d['f']]);
		                }
		            }
	            }
	            if($this->struct[$this->index]['total']){
	            	$total += $row[$this->struct[$this->index]['total']['f']];
	            	$grupoTotal += $row[$this->struct[$this->index]['total']['f']];
	            };
	            $pdf->Ln();
	        }
	        if($grupoTotal>0 && $this->struct[$this->index]['grupo']){
	        	$pdf->SetFont('courier', 'B', 9);
	        	$pdf->Cell(165, 0, 'TOTAL '.$grupo, 0, 0, 'R');
				$pdf->Cell(35, 0, number_format($grupoTotal, 2), 0, true, 'R');
	        }
	        if($this->struct[$this->index]['total']){
	            $pdf->Ln();
	            $pdf->SetFont('courier', 'B', 10);
	            $pdf->Cell(165, 0, $this->struct[$this->index]['total']['t'], 'T', 0, 'R');
	            $pdf->Cell(35, 0, number_format($total, 2), 'T', 0, 'R');
	        }
	        $pdf->Output($this->struct['cabecera']['titulo'].'-'.$this->struct['cabecera']['del'].'-'.$this->struct['cabecera']['al'], 'I');
	    } catch (Exception $e) {
	        $this->app->response()->header("Content-Type", "application/json;charset=utf-8");
	        $result = array();
	        $result['success'] = false;
	        $result['message'] = $e->getMessage();
	        $result['line'] = $e->getLine();
	        $this->app->response()->write(json_encode($result));
	    }
	}

	public function descargar(){
		try {
	        $objPHPExcel = new PHPExcel();
	        $objPHPExcel->getProperties()->setCreator("JC")->setLastModifiedBy("JC")->setTitle($this->struct['cabecera']['titulo'])
	                     ->setSubject($this->struct['cabecera']['titulo'])->setDescription($this->struct['cabecera']['titulo'])->setKeywords($this->struct['cabecera']['titulo'])
	                     ->setCategory($this->struct['cabecera']['titulo']);
	        $objPHPExcel->getDefaultStyle()->getFont()->setSize(9);
	        $ActiveSheet = $objPHPExcel->setActiveSheetIndex(0);

	        $ActiveSheet->mergeCells('C2:F2');
	        $ActiveSheet->getStyle("C2")->applyFromArray(array(
	            'font' => array('bold' => true),
	            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
	        ));
	        $ActiveSheet->setCellValue('A1', $this->struct['cabecera']['cia']);
	        $ActiveSheet->setCellValue('C2', $this->struct['cabecera']['titulo']);
	        $ActiveSheet->setCellValue('C3', "DEL:");
	        $ActiveSheet->setCellValue('D3', $this->struct['cabecera']['del']);
	        $ActiveSheet->setCellValue('E3', "AL:");
	        $ActiveSheet->setCellValue('F3', $this->struct['cabecera']['al']);

	        $fila = 5;
	        $col = 65;
	        foreach ($this->struct[$this->index]['detalle'] as $h) {
	            $ActiveSheet->setCellValue(chr($col).$fila, $h['t']);
	            $col += 1;
	        }
	        $ActiveSheet->getStyle("A$fila:".chr($col-1)."$fila")->applyFromArray(array(
	            'font' => array('bold' => true)
	        ));

	        $fila=6;
	        foreach ($this->data as $row) {
	            $col = 65;
	            foreach ($this->struct[$this->index]['detalle'] as $d) {
	                $ActiveSheet->setCellValue(chr($col).$fila, $row[$d['f']]);
	                $col += 1;
	            }
	            $fila+=1;
	        }
	        /*$ActiveSheet
	           ->getStyle('A6')
	           ->getNumberFormat()
	           ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME3);*/

	        $ActiveSheet->setTitle($this->struct['cabecera']['titulo']);

	        header('Content-Type: application/vnd.ms-excel');
	        header('Content-Disposition: attachment;filename="'.$this->struct['cabecera']['titulo'].'-'.$this->struct['cabecera']['del'].'-'.$this->struct['cabecera']['al'].'".xls');
	        header('Cache-Control: max-age=0');
	        // If you're serving to IE 9, then the following may be needed
	        header('Cache-Control: max-age=1');
	        // If you're serving to IE over SSL, then the following may be needed
	        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	        header ('Pragma: public');
	        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	        $objWriter->save('php://output');
	    } catch (Exception $e) {
	        $this->app->response()->header("Content-Type", "application/json;charset=utf-8");
	        $result = array();
	        $result['success'] = false;
	        $result['message'] = $e->getMessage();
	        $result['line'] = $e->getLine();
	        $this->app->response()->write(json_encode($result));
	    }
	}

}

?>