<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
        $categorias = $stmt->fetchAll();
        jsonResponse($categorias);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $nome = $input['nome'] ?? '';

        if (empty($nome)) {
            jsonResponse(['erro' => 'Nome da categoria é obrigatório'], 400);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
            $stmt->execute([$nome]);
            jsonResponse(['sucesso' => true, 'id' => $pdo->lastInsertId(), 'mensagem' => 'Categoria criada!'], 201);
        } catch (PDOException $e) {
            jsonResponse(['erro' => 'Categoria já existe'], 409);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $nome = $input['nome'] ?? '';

        if (!$id) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório'], 400);
        }
        if (empty($nome)) {
            jsonResponse(['erro' => 'Nome da categoria é obrigatório'], 400);
        }

        try {
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
            if ($stmt->rowCount() === 0) {
                jsonResponse(['erro' => 'Categoria não encontrada'], 404);
            }
            jsonResponse(['sucesso' => true, 'mensagem' => 'Categoria atualizada!']);
        } catch (PDOException $e) {
            jsonResponse(['erro' => 'Já existe uma categoria com este nome'], 409);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$id) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['sucesso' => true, 'mensagem' => 'Categoria excluída!']);
        break;

    default:
        jsonResponse(['erro' => 'Método não permitido'], 405);
}
