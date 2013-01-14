<?php

// Include CFPropertyList library
require('CFPropertyList.php');


// Main configuration
$inQuery = $argv[1] ?: '';
$root = exec('printf $HOME').'/Library/Application Support/Transmit/Metadata/';
$reRowQuery = '/'.preg_quote($inQuery).'/i';
$reValidName = '/\.favoriteMetadata$/';
$results = array();
$defaultPorts = array(
	'FTP' => ':21',
	'SFTP' => ':22',
	'HTTP' => ':80',
	'HTTPS' => ':443');


// Reading Transmit Metadata files
if (($dp = @opendir($root)) !== false)
{
	while ($fName = readdir($dp))
	{
		if (is_file($root.$fName) && preg_match($reValidName, $fName) && $pList = new CFPropertyList($root.$fName))
		{
			$data = $pList->toArray();
			$rowQuery = join(' ', array(
				$data['com_panic_transmit_nickname'],
				$data['com_panic_transmit_username'],
				$data['com_panic_transmit_server'],
				$data['com_panic_transmit_remotePath']));

			if (preg_match($reRowQuery, $rowQuery))
				$results[] = array(
					'uid' => $data['com_panic_transmit_uniqueIdentifier'],
					'arg' => $root.$fName,
					'title' => $data['com_panic_transmit_nickname'],
					'subtitle' => strtolower(
						$data['com_panic_transmit_protocol'].'://'.
						($data['com_panic_transmit_username'] ? $data['com_panic_transmit_username'].'@' : '').
						$data['com_panic_transmit_server'].
						($data['com_panic_transmit_port'] ? ':'.$data['com_panic_transmit_port'] : $defaultPorts[$data['com_panic_transmit_protocol']])).
						$data['com_panic_transmit_remotePath'],
					'icon' => 'icon.png',
					'valid' => true);
		}
	}
} else
	// Unable to open Transmit folder
	$results[] = array(
		'uid' => 'notfound',
		'arg' => 'notfound',
		'title' => 'Favorites Folder Not Found',
		'subtitle' => 'Unable to locate Transmit favorites folder',
		'icon' => 'icon.png',
		'valid' => false);

// No favorites matched
if (!count($results))
	$results[] = array(
		'uid' => 'none',
		'arg' => 'none',
		'title' => 'No Favorites Found',
		'subtitle' => 'No favorites matching your query were found',
		'icon' => 'icon.png',
		'valid' => false);


// Preparing the XML output file
$xmlObject = new SimpleXMLElement("<items></items>");
foreach($results AS $rows)
{
	$nodeObject = $xmlObject->addChild('item');
	$nodeKeys = array_keys($rows);
	foreach ($nodeKeys AS $key)
		$nodeObject->{ $key == 'uid' || $key == 'arg' ? 'addAttribute' : 'addChild' }($key, $rows[$key]);
}

// Print the XML output
echo $xmlObject->asXML();  

?>