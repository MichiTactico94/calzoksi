<?php
function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        deleteDirectory($dir . DIRECTORY_SEPARATOR . $item);
    }
    return rmdir($dir);
}
