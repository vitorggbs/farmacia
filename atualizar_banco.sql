USE farmacerta;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
INSERT IGNORE INTO categorias (nome) VALUES ('Medicamentos'),('Higiene'),('Cosméticos'),('Infantil'),('Suplementos'),('Outros');

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS categoria_id INT NULL AFTER farmacia_id;
ALTER TABLE movimentacoes_estoque ADD COLUMN IF NOT EXISTS lote_id INT NULL AFTER usuario_id;
ALTER TABLE entradas_mercadoria ADD COLUMN IF NOT EXISTS lote_id INT NULL AFTER usuario_id;

CREATE TABLE IF NOT EXISTS lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    produto_id INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    validade DATE NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_lote_produto (produto_id, numero_lote)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS itens_venda_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_venda_id INT NOT NULL,
    lote_id INT NOT NULL,
    quantidade INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notas_fiscais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    venda_id INT NOT NULL,
    numero VARCHAR(30), serie VARCHAR(10), chave_acesso VARCHAR(60), protocolo VARCHAR(60),
    status ENUM('pendente','emitida','cancelada','erro') NOT NULL DEFAULT 'pendente',
    observacao VARCHAR(255), criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_fiscal_venda (venda_id)
) ENGINE=InnoDB;
