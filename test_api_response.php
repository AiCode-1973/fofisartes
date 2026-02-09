<?php
// Simula uma requisição GET para a API
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
require __DIR__ . '/api/produtos.php';
$output = ob_get_clean();

echo "--- INICIO DA RESPOSTA ---\n";
echo $output;
echo "\n--- FIM DA RESPOSTA ---\n";

if (json_decode($output)) {
    echo "JSON VALIDO\n";
} else {
    echo "JSON INVALIDO. Erro: " . json_last_error_msg() . "\n";
}
