#!/usr/bin/php
<?php
	require 'vendor/autoload.php';

	$downloadURL = $_REQUEST['DownloadURL'];

	if (!filter_var($downloadURL,FILTER_VALIDATE_URL)) {
		http_response_code(400);
		die ("No valid URL Given");
	}

	$client = new \GuzzleHttp\Client();
	$tempFilePath = tempnam("/tmp", 'xsl2xml');

	$response = $client->request("GET",$downloadURL, ['sink' => $tempFilePath]);

	if ($response->getStatusCode() != 200)  {
		http_response_code(504);
		die ("Could not download $DownloadURL");
	}


	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");

	$reader->setReadDataOnly(true);
	$spreadsheet = $reader->load($tempFilePath);


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


