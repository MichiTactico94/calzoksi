<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['world'])) {
    $worldZip = $_FILES['world']['tmp_name'];
    $name = pathinfo($_FILES['world']['name'], PATHINFO_FILENAME);
    $tmpExtract = "uploads/$name/";

    // Extraer mundo ZIP
    mkdir($tmpExtract, 0777, true);
    $zip = new ZipArchive;
    if ($zip->open($worldZip) === TRUE) {
        $zip->extractTo($tmpExtract);
        $zip->close();
    }

    // Aquí iría la conversión real de chunks (experimental)

    $newZipPath = "output/{$name}_converted.zip";
    $zip = new ZipArchive;
    if ($zip->open($newZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpExtract),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($tmpExtract));
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
    }

    deleteDirectory($tmpExtract);

    echo json_encode([
        "success" => true,
        "message" => "Mundo preparado (experimental)",
        "download" => $newZipPath
    ]);
}
