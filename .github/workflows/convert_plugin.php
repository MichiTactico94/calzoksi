<?php
require_once 'utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin'])) {
    $pharPath = $_FILES['plugin']['tmp_name'];
    $name = pathinfo($_FILES['plugin']['name'], PATHINFO_FILENAME);
    $tmpExtract = "uploads/$name/";

    // Extraer contenido del .phar
    mkdir($tmpExtract, 0777, true);
    $phar = new PharData($pharPath);
    $phar->extractTo($tmpExtract, null, true);

    // Modificar plugin.yml
    $pluginYml = "$tmpExtract/plugin.yml";
    if (file_exists($pluginYml)) {
        $data = file_get_contents($pluginYml);
        $data = preg_replace('/api:\s*\[.*?\]/', 'api: [5.0.0]', $data);
        file_put_contents($pluginYml, $data);
    }

    // Añadir mas logica

    // Crear nuevo .phar
    $newPharPath = "output/{$name}_converted.phar";
    if (file_exists($newPharPath)) unlink($newPharPath);
    $phar = new Phar($newPharPath);
    $phar->buildFromDirectory($tmpExtract);
    $phar->setStub('<?php __HALT_COMPILER();');

    // Limpiar
    deleteDirectory($tmpExtract);

    echo json_encode([
        "success" => true,
        "message" => "Plugin convertido con éxito",
        "download" => $newPharPath
    ]);
}
