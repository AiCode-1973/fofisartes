<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $produto = $stmt->fetch();
            if ($produto) {
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
            jsonResponse($produtos);
        }
        break;

    case 'POST':
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $valor = $_POST['valor'] ?? 0;

        if (empty($nome) || empty($categoria) || empty($valor)) {
            jsonResponse(['erro' => 'Nome, categoria e valor são obrigatórios'], 400);
        }

        $imagem = '';
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                jsonResponse(['erro' => 'Formato de imagem não permitido. Use: jpg, png, gif ou webp'], 400);
            }

            $filename = uniqid('prod_') . '.' . $ext;
            $destino = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $imagem = 'uploads/' . $filename;
            } else {
                jsonResponse(['erro' => 'Erro ao fazer upload da imagem'], 500);
            }
        }

        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, categoria, valor, imagem) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $categoria, floatval($valor), $imagem]);

        jsonResponse(['sucesso' => true, 'id' => $pdo->lastInsertId(), 'mensagem' => 'Produto cadastrado com sucesso!'], 201);
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$id) {
            jsonResponse(['erro' => 'ID do produto é obrigatório'], 400);
        }

        $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch();

        if ($produto) {
            if (!empty($produto['imagem'])) {
                $imgPath = __DIR__ . '/../' . $produto['imagem'];
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['sucesso' => true, 'mensagem' => 'Produto excluído com sucesso!']);
        } else {
            jsonResponse(['erro' => 'Produto não encontrado'], 404);
        }
        break;

    default:
        jsonResponse(['erro' => 'Método não permitido'], 405);
}
