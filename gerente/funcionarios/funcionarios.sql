CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    cargo VARCHAR(50) NOT NULL DEFAULT 'Caixa',
    data_admissao DATE NOT NULL,
    salario DECIMAL(10,2) DEFAULT 0,
    horario_escala VARCHAR(150) NOT NULL,
    login VARCHAR(60) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    permissao ENUM('balconista') NOT NULL DEFAULT 'balconista',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
