#!/usr/bin/php
<?php
	require 'vendor/autoload.php';

	$downloadURL = $_REQUEST['DownloadURL'];




	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");

	$reader->setReadDataOnly(true);
	$spreadsheet = $reader->load($downloadURL);


	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument('1.0','UTF-8');
	$xml->startElement('spreadsheet');
	/** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $Worksheet */
	foreach ($spreadsheet->getAllSheets() as $worksheet) {
		$headers = [];
		print ("Writing Sheet : {$worksheet->getTitle()}...\n");
		$xml->startElement($worksheet->getTitle());

		foreach ($worksheet->toArray() as $i => $row) {
			if ($i == 0) {
				$headers = $row;
				continue;
			}
			$xml->startElement('row');
			foreach ($row as $key=>$value) {
				$xml->startElement($headers[$key]);
				$xml->writeCdata($value);
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


