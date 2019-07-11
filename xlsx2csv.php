<?php
	require 'vendor/autoload.php';
	use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
	use \PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;
	use \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
	use \PhpOffice\PhpSpreadsheet\IOFactory;

	$downloadURL = $_REQUEST['DownloadURL'];
	$hasHeaders  = $_REQUEST['NoHeaders'] ? false : true;

	if (!filter_var($downloadURL,FILTER_VALIDATE_URL)) {
		http_response_code(400);
		die ("No valid URL Given");
	}

	$client = new \GuzzleHttp\Client();
	$tempFilePath = tempnam(sys_get_temp_dir(), 'xlsx2xml');

	$response = $client->request("GET",$downloadURL, ['sink' => $tempFilePath]);

	if ($response->getStatusCode() != 200)  {
		http_response_code(504);
		die ("Could not download $DownloadURL");
	}


	$reader = IOFactory::createReader("Xlsx");

	//$reader->setReadDataOnly(true);
	$spreadsheet = $reader->load($tempFilePath);

	header ('Content-Type: application/xml');
	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument('1.0','UTF-8');
	$xml->startElement('spreadsheet');
	/** @var Worksheet $Worksheet */
	foreach ($spreadsheet->getAllSheets() as $worksheet) {
		$xml->startElement('sheet');
		$xml->writeAttribute('title',$worksheet->getTitle());
		foreach ($worksheet->getRowIterator() as $row) {
			if ($row->getRowIndex() == 1 && $hasHeaders) {
				continue;
			}
			$xml->startElement('row');
			$xml->writeAttribute('row', $row->getRowIndex());
			$cellIterator = $row->getCellIterator();
			foreach ($cellIterator as $cell) {
				$elementName = $hasHeaders ? $worksheet->getCellByColumnAndRow(Coordinate::columnIndexFromString($cell->getColumn()),1)->getValue() : $cell->getColumn();
				$xml->startElement($elementName);
				$xml->writeAttribute('format', $cell->getStyle()->getNumberFormat()->getFormatCode());
				$xml->writeAttribute('cell', $cell->getColumn().$row->getRowIndex());
				$xml->writeCdata($cell->getFormattedValue());
				$xml->endElement();
			}
			$xml->endElement();
			if ($row->getRowIndex() % 100 == 0) {
				echo $xml->flush(true);
			}
		}
		$xml->endElement();
		echo $xml->flush(true);
	}
	$xml->endElement();
	echo $xml->flush(true);

	unlink($tempFilePath);
