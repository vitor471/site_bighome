<?php
require_once __DIR__ . "/conexao.php";

function redirecWith($url, $params = []) {
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_cliente/cadastro.html", ["erro" => "Método inválido"]);
    }

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $telefone = preg_replace('/\D/', '', $_POST["telefone"] ?? "");
    $cpf = preg_replace('/\D/', '', $_POST["cpf"] ?? ""); // <<< remove pontos e traços
    $confirmarsenha = $_POST["confirmar"] ?? "";

    // ======= VALIDAÇÕES =======
    $erros_validacao = [];

    if ($nome === "" || $email === "" || $senha === "" || $telefone === "" || $cpf === "" || $confirmarsenha === "") {
        $erros_validacao[] = "Preencha todos os campos";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros_validacao[] = "E-mail inválido";
    }
    if ($senha !== $confirmarsenha) {
        $erros_validacao[] = "As senhas não conferem";
    }
    if (strlen($senha) < 8) {
        $erros_validacao[] = "Senha deve ter pelo menos 8 caracteres";
    }
    if (strlen($telefone) < 11) {
        $erros_validacao[] = "Telefone incorreto";
    }
    if (strlen($cpf) != 11) {
        $erros_validacao[] = "CPF inválido";
    }

    if ($erros_validacao) {
        redirecWith("../paginas_cliente/cadastro.html", ["erro" => $erros_validacao[0]]);
    }

    // ======= VERIFICAR DUPLICIDADE DE CPF =======
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Cliente WHERE cpf = :cpf");
    $stmt->execute([':cpf' => $cpf]);
    if ($stmt->fetchColumn() > 0) {
        redirecWith('../paginas_cliente/cadastro.html', ["erro" => "CPF já está cadastrado"]);
    }

    // ======= INSERIR CLIENTE =======
    $sql = "INSERT INTO Cliente (nome, cpf, telefone, email, senha)
            VALUES (:nome, :cpf, :telefone, :email, :senha)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ":nome" => $nome,
        ":cpf" => $cpf,
        ":telefone" => $telefone,
        ":email" => $email,
        ":senha" => password_hash($senha, PASSWORD_DEFAULT) // mais seguro
    ]);

    if ($ok) {
        redirecWith("../paginas_cliente/login.html", ["cadastro" => "ok"]);
    } else {
        redirecWith("../paginas_cliente/cadastro.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
    }

} catch (PDOException $e) {
    // Verifica se é duplicata (erro 1062)
    if (str_contains($e->getMessage(), '1062')) {
        redirecWith("../paginas_cliente/cadastro.html", ["erro" => "CPF já cadastrado no sistema"]);
    } else {
        redirecWith("../paginas_cliente/cadastro.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
    }
}
?>
