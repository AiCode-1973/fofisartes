<?php
require_once __DIR__ . '/api/config.php';

try {
    echo "Testando conexao com o banco de dados...\n";
    $pdo = getConnection();
    echo "Conexao OK!\n";

    echo "Verificando tabela de produtos...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produtos");
    $total = $stmt->fetchColumn();
    echo "Total de produtos encontrados: " . $total . "\n";

    if ($total > 0) {
        $stmt = $pdo->query("SELECT * FROM produtos LIMIT 1");
        $prod = $stmt->fetch();
        echo "Exemplo de produto:\n";
        print_r($prod);
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
