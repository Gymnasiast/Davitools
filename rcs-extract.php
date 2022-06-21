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
    $rawFilename = fread($indexFileHandle, 20);
    // C stops reading when it encounters a null character, even if there are stray characters after it.
    // We need to do this as well, because the files of A2 Racer II and III contain garbage characters after the null.
    $nullPos = strpos($rawFilename, "\x00");
    if ($nullPos !== false) {
        $rawFilename = substr($rawFilename, 0, $nullPos);
    }

    $filename = trim(utf8_encode($rawFilename));
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
