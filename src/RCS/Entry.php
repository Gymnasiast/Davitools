<?php
declare(strict_types=1);

namespace Davitools\RCS;

final class Entry
{
    public string $filename;
    public int $offset;

    public function __construct(string $filename, int $offset)
    {
        $this->filename = $filename;
        $this->offset = $offset;
    }
}
