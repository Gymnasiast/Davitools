#!/usr/bin/env php
<?php
require __DIR__ . '/src/RCS/Entry.php';
require __DIR__ . '/src/RCS/EntryTable.php';

use Davitools\RCS\EntryTable;

if ($argc < 4)
{
    printf("Usage: rcs-extract.php <index file> <image file> <output directory>\n");
    die();
}

$indexFilename = $argv[1];
$imageFilename = $argv[2];
$outputDirectory = rtrim($argv[3], '/');

$table = EntryTable::createFromFiles($indexFilename, $imageFilename);
$table->extract($outputDirectory);
