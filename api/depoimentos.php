<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

// GET - Listar depoimentos
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if ($id) {
        // Buscar depoimento específico
        $stmt = $pdo->prepare("SELECT * FROM depoimentos WHERE id = ?");
        $stmt->execute([$id]);
        $depoimento = $stmt->fetch();
        jsonResponse($depoimento ?: ['error' => 'Depoimento não encontrado'], $depoimento ? 200 : 404);
    } else {
        // Listar todos (ou apenas ativos para o frontend)
        $apenasAtivos = isset($_GET['ativos']) && $_GET['ativos'] === 'true';
        
        if ($apenasAtivos) {
            $stmt = $pdo->query("SELECT * FROM depoimentos WHERE ativo = TRUE ORDER BY ordem ASC, criado_em DESC");
        } else {
            $stmt = $pdo->query("SELECT * FROM depoimentos ORDER BY ordem ASC, criado_em DESC");
        }
        
        $depoimentos = $stmt->fetchAll();
        jsonResponse($depoimentos);
    }
}

// POST - Criar novo depoimento
elseif ($method === 'POST') {
    $data = getJsonInput();
    
    $nome = sanitize($data['nome'] ?? '');
    $depoimento = sanitize($data['depoimento'] ?? '');
    $estrelas = intval($data['estrelas'] ?? 5);
    $ativo = isset($data['ativo']) ? (bool)$data['ativo'] : true;
    $ordem = intval($data['ordem'] ?? 0);
    
    if (empty($nome) || empty($depoimento)) {
        jsonResponse(['error' => 'Nome e depoimento são obrigatórios'], 400);
    }
    
    if ($estrelas < 1 || $estrelas > 5) {
        jsonResponse(['error' => 'Estrelas deve ser entre 1 e 5'], 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO depoimentos (nome, depoimento, estrelas, ativo, ordem) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nome, $depoimento, $estrelas, $ativo, $ordem]);
    
    $id = $pdo->lastInsertId();
    
    jsonResponse(['id' => $id, 'message' => 'Depoimento criado com sucesso'], 201);
}

// PUT - Atualizar depoimento
elseif ($method === 'PUT') {
    $data = getJsonInput();
    $id = intval($data['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'ID é obrigatório'], 400);
    }
    
    $nome = sanitize($data['nome'] ?? '');
    $depoimento = sanitize($data['depoimento'] ?? '');
    $estrelas = intval($data['estrelas'] ?? 5);
    $ativo = isset($data['ativo']) ? (bool)$data['ativo'] : true;
    $ordem = intval($data['ordem'] ?? 0);
    
    if (empty($nome) || empty($depoimento)) {
        jsonResponse(['error' => 'Nome e depoimento são obrigatórios'], 400);
    }
    
    if ($estrelas < 1 || $estrelas > 5) {
        jsonResponse(['error' => 'Estrelas deve ser entre 1 e 5'], 400);
    }
    
    $stmt = $pdo->prepare("UPDATE depoimentos SET nome = ?, depoimento = ?, estrelas = ?, ativo = ?, ordem = ? WHERE id = ?");
    $stmt->execute([$nome, $depoimento, $estrelas, $ativo, $ordem, $id]);
    
    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Depoimento não encontrado'], 404);
    }
    
    jsonResponse(['message' => 'Depoimento atualizado com sucesso']);
}

// DELETE - Excluir depoimento
elseif ($method === 'DELETE') {
    $data = getJsonInput();
    $id = intval($data['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'ID é obrigatório'], 400);
    }
    
    $stmt = $pdo->prepare("DELETE FROM depoimentos WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Depoimento não encontrado'], 404);
    }
    
    jsonResponse(['message' => 'Depoimento excluído com sucesso']);
}

else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
