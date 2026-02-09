<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
    $method = 'PUT';
}
$pdo = getConnection();

// Helper: get images for a product
function getProductImages($pdo, $produtoId) {
    $stmt = $pdo->prepare("SELECT id, imagem, posicao FROM produto_imagens WHERE produto_id = ? ORDER BY posicao ASC, id ASC");
    $stmt->execute([$produtoId]);
    return $stmt->fetchAll();
}

// Helper: upload multiple images and insert into produto_imagens
function uploadAndInsertImages($pdo, $produtoId, $files, $startPosition = 0) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $inserted = [];
    $pos = $startPosition;

    // Normalize $_FILES array for multiple files
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $fileCount; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

        if ($error !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        $filename = uniqid('prod_') . '.' . $ext;
        $destino = $uploadDir . $filename;

        if (move_uploaded_file($tmpName, $destino)) {
            $imgPath = 'uploads/' . $filename;
            $stmt = $pdo->prepare("INSERT INTO produto_imagens (produto_id, imagem, posicao) VALUES (?, ?, ?)");
            $stmt->execute([$produtoId, $imgPath, $pos]);
            $inserted[] = $imgPath;
            $pos++;
        }
    }
    return $inserted;
}

// Helper: delete image file from disk
function deleteImageFile($imgPath) {
    $fullPath = __DIR__ . '/../' . $imgPath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

// Helper: update the legacy imagem column with the first image
function updateLegacyImage($pdo, $produtoId) {
    $imgs = getProductImages($pdo, $produtoId);
    $firstImg = !empty($imgs) ? $imgs[0]['imagem'] : '';
    $stmt = $pdo->prepare("UPDATE produtos SET imagem = ? WHERE id = ?");
    $stmt->execute([$firstImg, $produtoId]);
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $produto = $stmt->fetch();
            if ($produto) {
                $produto['imagens'] = getProductImages($pdo, $produto['id']);
                jsonResponse($produto);
            } else {
                jsonResponse(['erro' => 'Produto não encontrado'], 404);
            }
        } else {
            $sql = "SELECT * FROM produtos ORDER BY criado_em DESC";
            if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
                $sql = "SELECT * FROM produtos WHERE categoria = ? ORDER BY criado_em DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_GET['categoria']]);
            } else {
                $stmt = $pdo->query($sql);
            }
            $produtos = $stmt->fetchAll();
            foreach ($produtos as &$p) {
                $p['imagens'] = getProductImages($pdo, $p['id']);
            }
            unset($p);
            jsonResponse($produtos);
        }
        break;

    case 'POST':
        $nome = sanitizeInput($_POST['nome'] ?? '');
        $descricao = sanitizeInput($_POST['descricao'] ?? '');
        $categoria = sanitizeInput($_POST['categoria'] ?? '');
        $valor = $_POST['valor'] ?? 0;

        if (empty($nome) || empty($categoria)) {
            jsonResponse(['erro' => 'Nome e categoria são obrigatórios'], 400);
        }
        
        $valor = validateNumeric($valor, 'valor');

        try {
            // Insert product first (imagem column will be updated after)
            $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, categoria, valor, imagem) VALUES (?, ?, ?, ?, '')");
            $stmt->execute([$nome, $descricao, $categoria, $valor]);
            $produtoId = $pdo->lastInsertId();
        } catch (PDOException $e) {
            jsonResponse(['erro' => 'Erro ao cadastrar produto: ' . $e->getMessage()], 500);
        }

        // Upload multiple images (field name: imagens[])
        if (isset($_FILES['imagens'])) {
            uploadAndInsertImages($pdo, $produtoId, $_FILES['imagens']);
        }
        // Backward compat: also accept single 'imagem' field
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            uploadAndInsertImages($pdo, $produtoId, $_FILES['imagem']);
        }

        updateLegacyImage($pdo, $produtoId);

        jsonResponse(['sucesso' => true, 'id' => $produtoId, 'mensagem' => 'Produto cadastrado com sucesso!'], 201);
        break;

    case 'PUT':
        // Support multipart via POST with _method=PUT
        $id = $_POST['id'] ?? ($_GET['id'] ?? null);
        $nome = sanitizeInput($_POST['nome'] ?? '');
        $descricao = sanitizeInput($_POST['descricao'] ?? '');
        $categoria = sanitizeInput($_POST['categoria'] ?? '');
        $valor = $_POST['valor'] ?? 0;

        if (!$id || !is_numeric($id)) {
            jsonResponse(['erro' => 'ID do produto é obrigatório e deve ser numérico'], 400);
        }
        if (empty($nome) || empty($categoria)) {
            jsonResponse(['erro' => 'Nome e categoria são obrigatórios'], 400);
        }
        
        $valor = validateNumeric($valor, 'valor');

        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produtoAtual = $stmt->fetch();

        if (!$produtoAtual) {
            jsonResponse(['erro' => 'Produto não encontrado'], 404);
        }

        // Remove specific images by their IDs
        if (isset($_POST['remover_imagem_ids'])) {
            $idsToRemove = json_decode($_POST['remover_imagem_ids'], true);
            if (is_array($idsToRemove) && count($idsToRemove) > 0) {
                $placeholders = implode(',', array_fill(0, count($idsToRemove), '?'));
                $stmtSel = $pdo->prepare("SELECT imagem FROM produto_imagens WHERE id IN ($placeholders) AND produto_id = ?");
                $stmtSel->execute(array_merge($idsToRemove, [$id]));
                $imagensRemover = $stmtSel->fetchAll();
                foreach ($imagensRemover as $img) {
                    deleteImageFile($img['imagem']);
                }
                $stmtDel = $pdo->prepare("DELETE FROM produto_imagens WHERE id IN ($placeholders) AND produto_id = ?");
                $stmtDel->execute(array_merge($idsToRemove, [$id]));
            }
        }

        // Legacy: remove all images flag
        if (isset($_POST['remover_imagem']) && $_POST['remover_imagem'] === '1') {
            $allImgs = getProductImages($pdo, $id);
            foreach ($allImgs as $img) {
                deleteImageFile($img['imagem']);
            }
            $pdo->prepare("DELETE FROM produto_imagens WHERE produto_id = ?")->execute([$id]);
        }

        // Get current max position
        $stmtMaxPos = $pdo->prepare("SELECT COALESCE(MAX(posicao), -1) FROM produto_imagens WHERE produto_id = ?");
        $stmtMaxPos->execute([$id]);
        $maxPos = (int) $stmtMaxPos->fetchColumn() + 1;

        // Upload new images
        if (isset($_FILES['imagens'])) {
            uploadAndInsertImages($pdo, $id, $_FILES['imagens'], $maxPos);
        }
        // Backward compat: single 'imagem' field
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            uploadAndInsertImages($pdo, $id, $_FILES['imagem'], $maxPos);
        }

        // Update product fields
        try {
            $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, categoria = ?, valor = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao, $categoria, $valor, $id]);
        } catch (PDOException $e) {
            jsonResponse(['erro' => 'Erro ao atualizar produto: ' . $e->getMessage()], 500);
        }

        updateLegacyImage($pdo, $id);

        jsonResponse(['sucesso' => true, 'mensagem' => 'Produto atualizado com sucesso!']);
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$id || !is_numeric($id)) {
            jsonResponse(['erro' => 'ID do produto é obrigatório e deve ser numérico'], 400);
        }

        $stmt = $pdo->prepare("SELECT id FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch();

        if ($produto) {
            try {
                // Delete all image files
                $allImgs = getProductImages($pdo, $id);
                foreach ($allImgs as $img) {
                    deleteImageFile($img['imagem']);
                }
                // ON DELETE CASCADE will remove produto_imagens rows
                $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
                $stmt->execute([$id]);
                jsonResponse(['sucesso' => true, 'mensagem' => 'Produto excluído com sucesso!']);
            } catch (PDOException $e) {
                jsonResponse(['erro' => 'Erro ao excluir produto: ' . $e->getMessage()], 500);
            }
        } else {
            jsonResponse(['erro' => 'Produto não encontrado'], 404);
        }
        break;

    default:
        jsonResponse(['erro' => 'Método não permitido'], 405);
}
