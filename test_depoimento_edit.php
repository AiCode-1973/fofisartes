<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular requisição PUT
$_SERVER['REQUEST_METHOD'] = 'PUT';

// Simular dados JSON
$testData = [
    'id' => 1,
    'nome' => 'Ana Luiza M.',
    'depoimento' => 'Teste de edição do depoimento',
    'estrelas' => 5,
    'ordem' => 1,
    'ativo' => true
];

// Criar stream temporário com os dados
$tempFile = tmpfile();
fwrite($tempFile, json_encode($testData));
rewind($tempFile);

// Redirecionar php://input
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpStream");

class MockPhpStream {
    public $context;
    private static $data;
    
    public static function setData($data) {
        self::$data = $data;
    }
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr(self::$data, 0, $count);
        self::$data = substr(self::$data, $count);
        return $ret;
    }
    
    public function stream_eof() {
        return empty(self::$data);
    }
    
    public function stream_stat() {
        return [];
    }
}

MockPhpStream::setData(json_encode($testData));

echo "=== Testando edição de depoimento ===\n\n";
echo "Dados enviados:\n";
print_r($testData);
echo "\n\n";

ob_start();
include 'api/depoimentos.php';
$output = ob_get_clean();

echo "Resposta da API:\n";
echo $output;
echo "\n";
