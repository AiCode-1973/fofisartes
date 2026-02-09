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

    // Criar tabela produto_imagens
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produto_imagens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produto_id INT NOT NULL,
            imagem VARCHAR(255) NOT NULL,
            posicao INT DEFAULT 0,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela 'produto_imagens' criada com sucesso!\n";

    // Migrar imagens existentes da coluna produtos.imagem para produto_imagens
    $stmtMigrate = $pdo->query("SELECT id, imagem FROM produtos WHERE imagem IS NOT NULL AND imagem != ''");
    $existentes = $stmtMigrate->fetchAll();
    foreach ($existentes as $prod) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM produto_imagens WHERE produto_id = ? AND imagem = ?");
        $check->execute([$prod['id'], $prod['imagem']]);
        if ($check->fetchColumn() == 0) {
            $ins = $pdo->prepare("INSERT INTO produto_imagens (produto_id, imagem, posicao) VALUES (?, ?, 0)");
            $ins->execute([$prod['id'], $prod['imagem']]);
        }
    }
    if (count($existentes) > 0) {
        echo "Migração de " . count($existentes) . " imagem(ns) existente(s) concluída!\n";
    }

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
