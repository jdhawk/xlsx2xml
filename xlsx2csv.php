<?php
	require 'vendor/autoload.php';
	use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
	use \PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;
	use \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
	use \PhpOffice\PhpSpreadsheet\IOFactory;

	$downloadURL = $_REQUEST['DownloadURL'];

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

	$reader->setReadDataOnly(true);
	$spreadsheet = $reader->load($tempFilePath);

	header ('Content-Type: application/xml');
	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument('1.0','UTF-8');
	$xml->startElement('spreadsheet');
	/** @var Worksheet $Worksheet */
	foreach ($spreadsheet->getAllSheets() as $worksheet) {
		$xml->startElement($worksheet->getTitle());
		foreach ($worksheet->getRowIterator() as $row) {
			if ($row->getRowIndex() == 1) {
				continue;
			}
			$xml->startElement('row');
			$cellIterator = $row->getCellIterator();
			foreach ($cellIterator as $cell) {
				$xml->startElement($worksheet->getCellByColumnAndRow(1,Coordinate::columnIndexFromString($cell->getColumn()))->getValue());
				$xml->writeCdata($cell->getFormattedValue());
				$xml->endElement();
			}
			$xml->endElement();
			if ($i % 1000 == 0) {
				echo $xml->flush(true);
			}
		}
		$xml->endElement();
		echo $xml->flush(true);
	}
	$xml->endElement();
	echo $xml->flush(true);

	unlink($tempFilePath);
