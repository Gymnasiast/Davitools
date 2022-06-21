#!/usr/bin/env php
<?php
require __DIR__ . '/src/RCS/Entry.php';
require __DIR__ . '/src/RCS/EntryTable.php';

use Davitools\RCS\EntryTable;

if ($argc < 4)
{
    printf("Usage: rcs-pack.php <input directory > <index file> <image file>\n");
    die();
}

$inputDirectory = rtrim($argv[1], '/');
$indexFilename = $argv[2];
$imageFilename = $argv[3];

$table = EntryTable::createFromDirectory($inputDirectory);
$table->pack($indexFilename, $imageFilename);
