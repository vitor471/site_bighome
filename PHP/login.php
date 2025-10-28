<?php
// PHP/login.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/conexao.php";

// Recebe dados via JSON ou POST
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$login = isset($data['cpf']) ? trim($data['cpf']) : ''; // CPF ou usuário
$senha = isset($data['senha']) ? trim($data['senha']) : '';

if ($login === '' || $senha === '') {
    echo json_encode(['ok' => false, 'msg' => 'Informe CPF/usuário e senha.']);
    exit;
}

// Remove qualquer caractere não numérico do CPF
$cpfDigits = preg_replace('/\D+/', '', $login);

// === 1. Autentica como Cliente ===
try {
    $sql = "SELECT idCliente, nome, senha FROM cliente WHERE cpf = :cpf LIMIT 1";
    $st  = $pdo->prepare($sql);
    $st->bindValue(':cpf', $cpfDigits);
    $st->execute();
    $cli = $st->fetch(PDO::FETCH_ASSOC);

    if ($cli) {
        // Se estiver usando hash SHA2 no banco:
        // $hash = hash('sha256', $senha);
        // if ($cli['senha'] !== $hash) throw new Exception();

        if ($cli['senha'] === $senha) { // plain text
            $_SESSION['auth']      = true;
            $_SESSION['user_type'] = 'cliente';
            $_SESSION['user_id']   = (int)$cli['idCliente'];
            $_SESSION['nome']      = $cli['nome'];
            echo json_encode(['ok' => true, 'redirect' => '../paginas_cliente/telahome.html']);
            exit;
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Senha incorreta.']);
            exit;
        }
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro ao verificar cliente.']);
    exit;
}

// === 2. Autentica como Empresa ===
try {
    $sql = "SELECT idEmpresa, nome_fantasia, senha, cnpj_cpf, usuario FROM empresa
            WHERE (usuario = :u OR cnpj_cpf = :u) LIMIT 1";
    $st  = $pdo->prepare($sql);
    $st->bindValue(':u', $login);
    $st->execute();
    $emp = $st->fetch(PDO::FETCH_ASSOC);

    if ($emp) {
        // Se estiver usando hash SHA2 no banco:
        // $hash = hash('sha256', $senha);
        // if ($emp['senha'] !== $hash) throw new Exception();

        if ($emp['senha'] === $senha) { // plain text
            $_SESSION['auth']      = true;
            $_SESSION['user_type'] = 'empresa';
            $_SESSION['user_id']   = (int)$emp['idEmpresa'];
            $_SESSION['nome']      = $emp['nome_fantasia'];
            echo json_encode(['ok' => true, 'redirect' => '../paginas_logista/home_lojista.html']);
            exit;
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Senha incorreta.']);
            exit;
        }
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro ao verificar empresa.']);
    exit;
}

// === 3. Falha geral ===
echo json_encode(['ok' => false, 'msg' => 'Credenciais inválidas.']);
