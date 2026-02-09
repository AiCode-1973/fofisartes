<?php
require_once __DIR__ . '/api/config.php';

try {
    $pdo = getConnection();
    
    // Revert to the original image path that exists on the remote server
    $originalImage = 'uploads/prod_698a21df67d5d.png';
    $productId = 4;
    
    echo "Reverting product ID $productId to use image: $originalImage\n";
    
    // Update main product table
    $stmt = $pdo->prepare("UPDATE produtos SET imagem = ? WHERE id = ?");
    $stmt->execute([$originalImage, $productId]);
    
    // Update product_images table
    $stmt = $pdo->prepare("UPDATE produto_imagens SET imagem = ? WHERE produto_id = ?");
    $stmt->execute([$originalImage, $productId]);
    
    echo "Revert complete.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
