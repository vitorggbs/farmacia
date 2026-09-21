CREATE DATABASE IF NOT EXISTS farmacerta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmacerta;
-- Script consolidado e não destrutivo: não contém DROP, TRUNCATE ou DELETE.
-- Ele cria somente as tabelas que ainda não existem.

CREATE TABLE IF NOT EXISTS farmacias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(20), telefone VARCHAR(20), endereco VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(60) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tentativas_login (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(60) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tentativas_login (login, ip, criado_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL, cpf VARCHAR(11), telefone VARCHAR(20), email VARCHAR(150), endereco VARCHAR(255),
    data_nascimento DATE, data_admissao DATE, salario DECIMAL(10,2) DEFAULT 0, horario_escala VARCHAR(150),
    login VARCHAR(60) NOT NULL UNIQUE, senha VARCHAR(255) NOT NULL,
    cargo ENUM('gerente','balconista') NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    nome VARCHAR(80) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unico_nome_categoria (farmacia_id, nome),
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    categoria_id INT NULL,
    nome VARCHAR(150) NOT NULL, descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 5,
    prateleira VARCHAR(50) NOT NULL,
    imagem VARCHAR(255), ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    produto_id INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    validade DATE NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_lote_produto (produto_id, numero_lote),
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL, cpf VARCHAR(11), telefone VARCHAR(20), email VARCHAR(150),
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unico_cliente_cpf (farmacia_id, cpf),
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL, usuario_id INT NOT NULL,
    cliente VARCHAR(150), cpf_cliente VARCHAR(11),
    valor_total DECIMAL(10,2) NOT NULL, valor_recebido DECIMAL(10,2) NOT NULL, troco DECIMAL(10,2) NOT NULL DEFAULT 0,
    forma_pagamento ENUM('dinheiro','pix','debito','credito') NOT NULL,
    status ENUM('concluida','cancelada') NOT NULL DEFAULT 'concluida',
    data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelada_em DATETIME NULL, cancelada_por INT NULL, motivo_cancelamento VARCHAR(255) NULL,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cancelada_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS itens_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL, produto_id INT NOT NULL, quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL, subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS itens_venda_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_venda_id INT NOT NULL, lote_id INT NOT NULL, quantidade INT NOT NULL,
    FOREIGN KEY (item_venda_id) REFERENCES itens_venda(id) ON DELETE CASCADE,
    FOREIGN KEY (lote_id) REFERENCES lotes(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL, produto_id INT NOT NULL, usuario_id INT NOT NULL,
    lote_id INT NULL,
    tipo ENUM('entrada','saida','ajuste') NOT NULL,
    quantidade INT NOT NULL, observacao VARCHAR(255), criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL, cnpj VARCHAR(20), telefone VARCHAR(20), email VARCHAR(150), endereco VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_mercadoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL, fornecedor_id INT NOT NULL, produto_id INT NOT NULL, usuario_id INT NOT NULL,
    lote_id INT NULL, quantidade INT NOT NULL, custo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    numero_nota VARCHAR(60), criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notas_fiscais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NOT NULL, venda_id INT NOT NULL,
    numero VARCHAR(30), serie VARCHAR(10), chave_acesso VARCHAR(60), protocolo VARCHAR(60),
    status ENUM('pendente','emitida','cancelada','erro') NOT NULL DEFAULT 'pendente',
    observacao VARCHAR(255), criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_fiscal_venda (venda_id),
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id),
    FOREIGN KEY (venda_id) REFERENCES vendas(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT NULL, usuario_id INT NULL, admin_id INT NULL,
    perfil VARCHAR(30) NOT NULL, acao VARCHAR(60) NOT NULL, entidade VARCHAR(60) NOT NULL,
    entidade_id INT NULL, descricao VARCHAR(255), criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES administradores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Nenhuma credencial ou farmácia de demonstração é criada.
-- Execute criar_admin.php pela linha de comando e cadastre as unidades pela interface.
