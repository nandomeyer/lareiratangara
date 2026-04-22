-- ============================================================
--  BANCO DE DADOS: Lareira Tangará da Serra - MT
--  Execute este script uma única vez para instalar o sistema
-- ============================================================

CREATE DATABASE IF NOT EXISTS lareira_reunioes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lareira_reunioes;

-- Tabela de administradores
CREATE TABLE IF NOT EXISTS admins (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120)  NOT NULL,
    usuario     VARCHAR(60)   NOT NULL UNIQUE,
    senha_hash  VARCHAR(255)  NOT NULL,
    ativo       TINYINT(1)    NOT NULL DEFAULT 1,
    ultimo_login DATETIME     NULL,
    criado_em   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de reuniões
CREATE TABLE IF NOT EXISTS reunioes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    mes         TINYINT       NOT NULL COMMENT '1-12',
    ano         SMALLINT      NOT NULL,
    arquivo     VARCHAR(255)  NOT NULL COMMENT 'Nome do arquivo no disco (sem caminho)',
    downloads   INT           NOT NULL DEFAULT 0,
    admin_id    INT           NULL,
    criado_em   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mes_ano (mes, ano),
    INDEX idx_ano (ano),
    INDEX idx_mes (mes),
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  ADMIN PADRÃO
--  Usuário: admin
--  Senha:   Lareira@2024
--
--  ⚠️  TROQUE A SENHA IMEDIATAMENTE APÓS O PRIMEIRO ACESSO!
--  (Painel Admin > Trocar Senha)
-- ============================================================
INSERT INTO admins (nome, usuario, senha_hash) VALUES (
    'Administrador',
    'admin',
    '$2y$12$nfgAvBIb4FBLHQ7.mGi44eBlJDKPjbXEY.Xk4J5eQ7EJY1Nqo5gma'
    -- Hash bcrypt de: Lareira@2024
);

-- Dados de exemplo (remova em produção ou mantenha para testar)
-- INSERT INTO reunioes (mes, ano, arquivo, downloads, admin_id) VALUES
--     (12, 2024, 'reuniao_2024_12_exemplo.pdf', 835, 1),
--     (11, 2024, 'reuniao_2024_11_exemplo.pdf', 816, 1),
--     (10, 2024, 'reuniao_2024_10_exemplo.pdf', 748, 1);

SELECT 'Banco de dados instalado com sucesso!' AS status;
SELECT 'Login: admin | Senha: Lareira@2024' AS credenciais;
SELECT 'LEMBRE-SE: Troque a senha após o primeiro acesso!' AS aviso;
