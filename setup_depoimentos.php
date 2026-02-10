<?php
require_once __DIR__ . '/api/config.php';

try {
    $pdo = getConnection();
    
    // Criar tabela de depoimentos
    $sql = "CREATE TABLE IF NOT EXISTS depoimentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        depoimento TEXT NOT NULL,
        estrelas INT NOT NULL DEFAULT 5,
        ativo BOOLEAN DEFAULT TRUE,
        ordem INT DEFAULT 0,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Tabela 'depoimentos' criada com sucesso!\n";
    
    // Verificar se já existem depoimentos
    $count = $pdo->query("SELECT COUNT(*) FROM depoimentos")->fetchColumn();
    
    if ($count == 0) {
        // Inserir depoimentos iniciais
        $stmt = $pdo->prepare("INSERT INTO depoimentos (nome, depoimento, estrelas, ativo, ordem) VALUES (?, ?, ?, ?, ?)");
        
        $depoimentos = [
            ['Ana Luiza M.', 'Fiquei encantada com o topo de bolo. Ficou exatamente como eu imaginei, cada detalhe perfeito!', 5, 1, 1],
            ['Carlos Eduardo S.', 'Qualidade impecável! Os convites ficaram lindos e a entrega foi super rápida. Recomendo!', 5, 1, 2],
            ['Mariana Costa', 'Atendimento nota 10! Me ajudaram a escolher os melhores materiais para meu evento.', 5, 1, 3]
        ];
        
        foreach ($depoimentos as $dep) {
            $stmt->execute($dep);
        }
        
        echo "✓ " . count($depoimentos) . " depoimentos iniciais inseridos!\n";
    } else {
        echo "✓ Tabela já contém $count depoimento(s).\n";
    }
    
    echo "\n✅ Setup concluído com sucesso!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
