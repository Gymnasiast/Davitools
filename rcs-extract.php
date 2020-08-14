#!/usr/bin/env php
<?php
if ($argc < 3)
{
    printf("Usage: rcs-extract.php <index file> <image file> <output directory>\n");
    die();
}

$indexFilename = $argv[1];
$imageFilename = $argv[2];
$outputDirectory = rtrim($argv[3], '/');

$indexFileHandle = fopen($indexFilename, 'rb');
$imageFileHandle = fopen($imageFilename, 'rb');

$indices = [];

$numFiles = unpack('v', fread($indexFileHandle, 2))[1];

for ($i = 0; $i < $numFiles; $i++)
{
    $filename = trim(utf8_encode(fread($indexFileHandle, 20)));
    $offset = unpack('V', fread($indexFileHandle, 4))[1];

    printf('File %d: "%s", offset %d' . PHP_EOL, $i, $filename, $offset);

    $indices[$i] = ['filename' => $filename, 'offset' => $offset];
}
// To allow i + 1
$indices[$numFiles] = ['filename' => '', 'offset' => filesize($imageFilename)];

for ($i = 0; $i < $numFiles; $i++)
{
    $size = $indices[$i + 1]['offset'] - $indices[$i]['offset'];
    assert($size >= 0);
    if ($size === 0)
    {
        $contents = null;
    }
    else
    {
        $contents = fread($imageFileHandle, $size);
    }
    echo $indices[$i]['filename'];
    file_put_contents($outputDirectory . '/' . $indices[$i]['filename'], $contents);
    echo PHP_EOL;
}

fclose($indexFileHandle);
