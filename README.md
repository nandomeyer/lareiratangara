# 🔥 Lareira Tangará da Serra — Sistema de Reuniões
## Guia de Instalação Completo

---

## 📁 Estrutura de Arquivos

```
lareira/
├── index.php           → Página pública (listagem de reuniões)
├── download.php        → Download seguro (sem expor caminho físico)
├── config.php          → Configurações do sistema (edite aqui!)
├── db.php              → Conexão com banco de dados
├── install.sql         → Script SQL para criar tabelas
├── .htaccess           → Segurança Apache
├── storage/
│   ├── .htaccess       → Bloqueia acesso direto aos PDFs
│   └── pdfs/           → Aqui ficam os PDFs (criado automaticamente)
└── admin/
    ├── login.php        → Tela de login
    ├── dashboard.php    → Painel principal
    ├── upload.php       → Upload de nova reunião
    ├── excluir.php      → Excluir reunião
    ├── trocar-senha.php → Trocar senha
    ├── logout.php       → Logout
    └── auth.php         → Verificação de sessão
```

---

## 🚀 Passo a Passo de Instalação

### 1. Configurar banco de dados
```bash
# Acesse o MySQL e execute:
mysql -u root -p < install.sql
```

### 2. Editar config.php
```php
define('SECRET_KEY', 'MUDE_PARA_UMA_CHAVE_ALEATORIA_E_LONGA');
define('DB_HOST', 'localhost');
define('DB_NAME', 'lareira_reunioes');
define('DB_USER', 'seu_usuario_mysql');
define('DB_PASS', 'sua_senha_mysql');

// Caminho FORA da pasta pública (recomendado)
define('UPLOAD_DIR', '/var/www/storage/lareira_pdfs/');
// OU dentro da pasta do projeto (já protegido pelo .htaccess):
define('UPLOAD_DIR', __DIR__ . '/storage/pdfs/');
```

### 3. Permissões de pasta
```bash
chmod 755 storage/
chmod 755 storage/pdfs/
# O PHP precisa de permissão de escrita nesta pasta
chown www-data:www-data storage/ -R
```

### 4. Primeiro acesso (Admin)
- Acesse: `seusite.com.br/admin/login.php`
- Usuário: `admin`
- Senha: `Lareira@2024`
- **⚠️ Troque a senha imediatamente após entrar!**

---

## 🔐 Segurança Implementada

| Feature | Descrição |
|---|---|
| **Download seguro** | PDFs servidos via PHP com token HMAC-SHA256 |
| **Sem exposição de caminho** | Usuário nunca vê o caminho físico do arquivo |
| **CSRF protection** | Tokens em todos os formulários admin |
| **Senha com bcrypt** | Hash seguro com custo 12 |
| **Sessão com timeout** | Admin desloga automaticamente em 1h |
| **Validação de MIME** | Upload verifica tipo real do arquivo (não só extensão) |
| **Nome aleatório** | Arquivos salvos com nome hash imprevisível |
| **.htaccess** | Bloqueia acesso direto à pasta storage |
| **Brute-force delay** | Login com sleep(1) em tentativas falhas |

---

## 🌐 Funcionalidades do Site Público

- ✅ Listagem de reuniões por ano e mês (filtros clicáveis)
- ✅ Último arquivo em destaque
- ✅ Contador de downloads
- ✅ Paginação
- ✅ Design responsivo (mobile-friendly)

## ⚙️ Funcionalidades do Administrativo

- ✅ Login seguro com sessão
- ✅ Dashboard com estatísticas
- ✅ Upload de PDF (com drag & drop)
- ✅ Exclusão de reuniões
- ✅ Troca de senha
- ✅ Múltiplos administradores (via banco)

---

## 💡 Dicas

- Para adicionar mais admins, insira diretamente no banco:
  ```sql
  INSERT INTO admins (nome, usuario, senha_hash)
  VALUES ('Maria', 'maria', '$2y$12$...');
  -- Gere o hash com: echo password_hash('senha', PASSWORD_BCRYPT);
  ```

- Para **NGINX**, use as mesmas regras de segurança adaptadas:
  ```nginx
  location /storage/ { deny all; }
  ```

---

*Sistema desenvolvido para Lareira Tangará da Serra - MT*
