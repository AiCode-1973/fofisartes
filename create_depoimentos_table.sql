-- Tabela para depoimentos de clientes
CREATE TABLE IF NOT EXISTS depoimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    depoimento TEXT NOT NULL,
    estrelas INT NOT NULL DEFAULT 5,
    ativo BOOLEAN DEFAULT TRUE,
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir depoimentos existentes do site
INSERT INTO depoimentos (nome, depoimento, estrelas, ativo, ordem) VALUES
('Ana Luiza M.', 'Fiquei encantada com o topo de bolo. Ficou exatamente como eu imaginei, cada detalhe perfeito!', 5, TRUE, 1),
('Carlos Eduardo S.', 'Qualidade impecável! Os convites ficaram lindos e a entrega foi super rápida. Recomendo!', 5, TRUE, 2),
('Mariana Costa', 'Atendimento nota 10! Me ajudaram a escolher os melhores materiais para meu evento.', 5, TRUE, 3);
