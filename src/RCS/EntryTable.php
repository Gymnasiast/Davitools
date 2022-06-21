<?php
declare(strict_types=1);

namespace Davitools\RCS;

use function array_filter;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fread;
use function fwrite;
use function in_array;
use function scandir;
use function str_pad;
use function strtolower;
use function substr;
use function unpack;
use function utf8_decode;
use const PHP_EOL;

final class EntryTable
{
    const INDEX_ENTRY_FILENAME_LENGTH = 20;
    const INDEN_ENTRY_OFFSET_LENGTH = 4;

    public string $indexFile;
    public string $imageFile;
    public string $directory;

    /** @var Entry[] */
    public array $entries = [];

    /**
     * Create entry table from already existing .ind and .img files.
     *
     * @param string $indexFile
     * @param string $imageFile
     * @return static
     */
    public static function createFromFiles(string $indexFile, string $imageFile): self
    {
        $table = new self();
        $table->indexFile = $indexFile;
        $table->imageFile = $imageFile;

        $indexFileHandle = fopen($indexFile, 'rb');

        $numFiles = unpack('v', fread($indexFileHandle, 2))[1];
        $hits = [];

        for ($i = 0; $i < $numFiles; $i++)
        {
            $rawFilename = fread($indexFileHandle, self::INDEX_ENTRY_FILENAME_LENGTH);
            // C stops reading when it encounters a null character, even if there are stray characters after it.
            // We need to do this as well, because the files of A2 Racer II and III contain garbage characters after the null.
            $nullPos = strpos($rawFilename, "\x00");
            if ($nullPos !== false) {
                $rawFilename = substr($rawFilename, 0, $nullPos);
            }

            $filename = trim(utf8_encode($rawFilename));
            $offset = unpack('V', fread($indexFileHandle, self::INDEN_ENTRY_OFFSET_LENGTH))[1];

            if (in_array(strtolower($filename), $hits, true))
            {
                echo "Duplicate filename: {$filename}" . PHP_EOL;
            }

            //printf('File %d: "%s", offset %d' . PHP_EOL, $i, $filename, $offset);

            $table->entries[$i] = new Entry($filename, $offset);
            $hits[] = strtolower($filename);
        }

        fclose($indexFileHandle);
        return $table;
    }

    public function extract(string $outputDirectory): void
    {
        if (!file_exists($outputDirectory))
        {
            @mkdir($outputDirectory, 0777, true);
        }

        $imageFileHandle = fopen($this->imageFile, 'rb');

        $numFiles = count($this->entries);
        $entries = $this->entries;


        // To allow i + 1
        $entries[$numFiles] = new Entry('', filesize($this->imageFile));

        for ($i = 0; $i < $numFiles; $i++)
        {
            printf("File {$i}, name {$entries[$i]->filename}" . PHP_EOL);
            $size = $entries[$i + 1]->offset - $entries[$i]->offset;
            assert($size >= 0);
            if ($size === 0)
            {
                $contents = null;
            }
            else
            {
                $contents = fread($imageFileHandle, $size);
            }

            $fullPath = $outputDirectory . '/' . $entries[$i]->filename;
            file_put_contents($fullPath, $contents);
            if (!file_exists($fullPath)) {
                echo "Cannot write {$entries[$i]->filename}!\n";
            }
        }
    }

    public static function createFromDirectory(string $inputDirectory): self
    {
        $table = new self();
        $table->directory = $inputDirectory;

        // Filter out . and ..
        $files = array_filter(scandir($table->directory), static function ($entry) { return substr($entry, 0, 1) !== '.'; });
        $count = count($files);
        $cumulativeSize = 0;
        foreach ($files as $file)
        {
            $table->entries[] = new Entry($file, $cumulativeSize);
            $fullPath = "$inputDirectory/$file";
            $cumulativeSize += filesize($fullPath);
        }

        return $table;
    }

    public function pack(string $indexFile, string $imageFile)
    {
        $indexFileHandle = fopen($indexFile, 'wb');
        $imageFileHandle = fopen($imageFile, 'wb');

        $numEntries = count($this->entries);
        fwrite($indexFileHandle, pack('v', $numEntries), 2);

        foreach ($this->entries as $entry)
        {
            $filename = utf8_decode($entry->filename);
            $paddedFilename = str_pad($filename, self::INDEX_ENTRY_FILENAME_LENGTH, "\x00");

            fwrite($indexFileHandle, $paddedFilename, self::INDEX_ENTRY_FILENAME_LENGTH);
            fwrite($indexFileHandle, pack('V', $entry->offset), self::INDEN_ENTRY_OFFSET_LENGTH);

            $fullpath = "{$this->directory}/{$entry->filename}";
            $fileContents = file_get_contents($fullpath);
            fwrite($imageFileHandle, $fileContents);
        }

        fclose($indexFileHandle);
        fclose($imageFileHandle);
    }
}
