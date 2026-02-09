<?php
require_once __DIR__ . '/api/config.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT p.id, p.nome, p.imagem FROM produtos p LIMIT 5");
$rows = $stmt->fetchAll();
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Nome: {$r['nome']} | Imagem: {$r['imagem']}\n";
    $imgs = $pdo->prepare("SELECT id, imagem FROM produto_imagens WHERE produto_id = ?");
    $imgs->execute([$r['id']]);
    foreach ($imgs->fetchAll() as $img) {
        echo "  -> produto_imagens[{$img['id']}]: {$img['imagem']}\n";
        $localPath = __DIR__ . '/' . $img['imagem'];
        echo "     Local exists: " . (file_exists($localPath) ? 'YES' : 'NO') . " ($localPath)\n";
    }
}
