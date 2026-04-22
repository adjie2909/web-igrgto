<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class NoRenameFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null)
    {
        $this->ensureDirectoryExists(dirname($path));

        $result = @file_put_contents($path, $content, LOCK_EX);

        if ($result === false) {
            throw new RuntimeException("Unable to write file: {$path}");
        }

        if (! is_null($mode)) {
            @chmod($path, $mode);
        }
    }

    public function move($path, $target)
    {
        $this->copy($path, $target);

        return $this->delete($path);
    }
}
