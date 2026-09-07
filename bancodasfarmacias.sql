CREATE DATABASE IF NOT EXISTS farmacerta
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE farmacerta;

CREATE TABLE farmacias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(20),
    telefone VARCHAR(20),
    endereco VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(60) NOT NULL UNIQUE,
    senha VARCHAR(100) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(11),
    telefone VARCHAR(20),
    email VARCHAR(150),
    endereco VARCHAR(255),
    data_nascimento DATE,
    data_admissao DATE,
    salario DECIMAL(10, 2) DEFAULT 0,
    horario_escala VARCHAR(150),
    login VARCHAR(60) NOT NULL UNIQUE,
    senha VARCHAR(100) NOT NULL,
    cargo ENUM('gerente', 'balconista') NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
);

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 5,
    prateleira VARCHAR(50) NOT NULL,
    imagem VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
);

CREATE TABLE lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    produto_id INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL,
    validade DATE NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cliente VARCHAR(150),
    cpf_cliente VARCHAR(11),
    valor_total DECIMAL(10, 2) NOT NULL,
    valor_recebido DECIMAL(10, 2) NOT NULL,
    troco DECIMAL(10, 2) NOT NULL DEFAULT 0,
    forma_pagamento ENUM('dinheiro', 'pix', 'debito', 'credito') NOT NULL,
    data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE itens_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
    quantidade INT NOT NULL,
    observacao VARCHAR(255),
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

INSERT INTO administradores (nome, login, senha) VALUES
('Administrador do Sistema', 'admin', '123456');

INSERT INTO farmacias (nome, cnpj, telefone, endereco) VALUES
('Farmacia 1', '00.000.000/0001-01', '(84) 0000-0000', 'Endereco da Farmacia 1'),
('Farmacia 2', '00.000.000/0002-02', '(84) 1111-1111', 'Endereco da Farmacia 2');

INSERT INTO usuarios (farmacia_id, nome, cpf, login, senha, cargo) VALUES
(1, 'Gerente da Farmacia 1', NULL, 'gerente', '123456', 'gerente'),
(1, 'Balconista da Farmacia 1', '00000000000', 'balconista', '123456', 'balconista'),
(2, 'Gerente da Farmacia 2', NULL, 'gerente2', '123456', 'gerente'),
(2, 'Balconista da Farmacia 2', '11111111111', 'balconista2', '123456', 'balconista');
