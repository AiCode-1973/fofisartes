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
        $nome = sanitizeInput($input['nome'] ?? '');

        if (empty($nome)) {
            jsonResponse(['erro' => 'Nome da categoria é obrigatório'], 400);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
            $stmt->execute([$nome]);
            jsonResponse(['sucesso' => true, 'id' => $pdo->lastInsertId(), 'mensagem' => 'Categoria criada com sucesso!'], 201);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                jsonResponse(['erro' => 'Categoria já existe'], 409);
            }
            jsonResponse(['erro' => 'Erro ao criar categoria: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $nome = sanitizeInput($input['nome'] ?? '');

        if (!$id || !is_numeric($id)) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório e deve ser numérico'], 400);
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
            jsonResponse(['sucesso' => true, 'mensagem' => 'Categoria atualizada com sucesso!']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                jsonResponse(['erro' => 'Já existe uma categoria com este nome'], 409);
            }
            jsonResponse(['erro' => 'Erro ao atualizar categoria: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$id || !is_numeric($id)) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório e deve ser numérico'], 400);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                jsonResponse(['erro' => 'Categoria não encontrada'], 404);
            }
            jsonResponse(['sucesso' => true, 'mensagem' => 'Categoria excluída com sucesso!']);
        } catch (PDOException $e) {
            jsonResponse(['erro' => 'Erro ao excluir categoria: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['erro' => 'Método não permitido'], 405);
}
