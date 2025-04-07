<?php
// convert_plugin.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pluginZip'])) {
    $uploadDir = __DIR__ . '/uploads/';
    $convertedDir = __DIR__ . '/converted_plugins/';
    
    if (!file_exists($uploadDir)) mkdir($uploadDir);
    if (!file_exists($convertedDir)) mkdir($convertedDir);

    $zipPath = $uploadDir . basename($_FILES['pluginZip']['name']);
    move_uploaded_file($_FILES['pluginZip']['tmp_name'], $zipPath);

    $extractPath = $uploadDir . uniqid('plugin_');
    mkdir($extractPath);

    $zip = new ZipArchive;
    if ($zip->open($zipPath) === TRUE) {
        $zip->extractTo($extractPath);
        $zip->close();
        unlink($zipPath);
    } else {
        die("Error al descomprimir el archivo.");
    }

    // Modificar plugin.yml
    $pluginYmlPath = $extractPath . '/plugin.yml';
    if (file_exists($pluginYmlPath)) {
        $contents = file_get_contents($pluginYmlPath);
        $contents = preg_replace('/api:\s*\[.*?\]/', 'api: [5.0.0]', $contents);
        file_put_contents($pluginYmlPath, $contents);
    }

    // Recorrer archivos PHP
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extractPath));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $php = file_get_contents($file);

            // Cambios comunes
            $php = str_replace('pocketmine\Player', 'pocketmine\player\Player', $php);
            $php = str_replace('Player $', 'Player $', $php); // por si acaso

            // Ejemplo de cambio de eventos antiguos (agrega más según sea necesario)
            $php = str_replace('PlayerInteractEvent', 'player\PlayerInteractEvent', $php);
            $php = str_replace('EntityDamageEvent', 'event\entity\EntityDamageEvent', $php);

            file_put_contents($file, $php);
        }
    }

    // Crear ZIP final
    $finalZipPath = $convertedDir . 'plugin_convertido_' . time() . '.zip';
    $zip = new ZipArchive;
    if ($zip->open($finalZipPath, ZipArchive::CREATE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($extractPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    } else {
        die("No se pudo crear el ZIP del plugin convertido.");
    }

    // Eliminar carpeta temporal
    function deleteDir($dirPath) {
        if (!is_dir($dirPath)) return;
        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$dirPath/$file";
            is_dir($filePath) ? deleteDir($filePath) : unlink($filePath);
        }
        rmdir($dirPath);
    }

    deleteDir($extractPath);

    // Descargar automáticamente
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($finalZipPath) . '"');
    header('Content-Length: ' . filesize($finalZipPath));
    readfile($finalZipPath);

    // Borrar archivo descargado tras envío
    unlink($finalZipPath);
    exit;
}

echo "Método no permitido o archivo no enviado.";
