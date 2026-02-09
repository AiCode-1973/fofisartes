<?php
require_once __DIR__ . '/api/config.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, nome, imagem FROM produtos");
    $products = $stmt->fetchAll();
    
    echo "Check Products:\n";
    foreach ($products as $p) {
        echo "ID: " . $p['id'] . " | Name: " . $p['nome'] . " | Image: " . $p['imagem'] . "\n";
        
        // Check local file
        if ($p['imagem']) {
            $localPath = __DIR__ . '/' . $p['imagem'];
            if (file_exists($localPath)) {
                echo "  [OK] Local file exists.\n";
            } else {
                echo "  [MISSING] Local file not found: $localPath\n";
            }
        }
        
        // Check extra images
        $stmtImg = $pdo->prepare("SELECT imagem FROM produto_imagens WHERE produto_id = ?");
        $stmtImg->execute([$p['id']]);
        $imgs = $stmtImg->fetchAll();
        foreach ($imgs as $img) {
             $localPath = __DIR__ . '/' . $img['imagem'];
             if (file_exists($localPath)) {
                echo "  [OK] Extra image exists: " . $img['imagem'] . "\n";
            } else {
                echo "  [MISSING] Extra image not found: " . $img['imagem'] . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
