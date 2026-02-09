<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getConnection();
    echo "Conexão com o banco remoto estabelecida com sucesso!\n\n";

    // Criar tabela produtos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            categoria VARCHAR(100) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            imagem VARCHAR(255),
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela 'produtos' criada com sucesso!\n";

    // Criar tabela categorias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela 'categorias' criada com sucesso!\n";

    // Inserir categorias padrão
    $stmt = $pdo->query("SELECT COUNT(*) FROM categorias");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $pdo->exec("
            INSERT INTO categorias (nome) VALUES
            ('Personalizados'),
            ('Topos de Bolo'),
            ('Cópias'),
            ('Gráfica'),
            ('Outros')
        ");
        echo "Categorias padrão inseridas com sucesso!\n";
    } else {
        echo "Categorias já existem ($count registros). Inserção ignorada.\n";
    }

    echo "\nSetup concluído com sucesso!";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
