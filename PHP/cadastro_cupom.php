<?php
require_once __DIR__ . "/conexao.php";

function redirectWith(string $url, array $params = []): void {
  if (!empty($params)) {
    $qs = http_build_query($params);
    $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
  }
  header("Location: $url");
  exit;
}

/* ==================== LISTAR CUPONS ==================== */
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
  try {
    $sqlListar = "SELECT idCupom AS id, nome, valor, data_validade, quantidade
                  FROM Cupom
                  ORDER BY data_validade DESC";
    $stmt = $pdo->query($sqlListar);
    $cupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "option";

    if ($formato === "json") {
      // Normaliza tipos numéricos e datas
      $saida = array_map(function ($c) {
        return [
          "id"            => (int)$c["id"],
          "nome"          => $c["nome"],
          "valor"         => (double)$c["valor"],
          "data_validade" => $c["data_validade"],
          "quantidade"    => (int)$c["quantidade"],
        ];
      }, $cupons);

      header("Content-Type: application/json; charset=utf-8");
      echo json_encode(["ok" => true, "cupons" => $saida], JSON_UNESCAPED_UNICODE);
      exit;
    }


  } catch (Throwable $e) {
    if (isset($_GET["format"]) && strtolower($_GET["format"]) === "json") {
      header("Content-Type: application/json; charset=utf-8", true, 500);
      echo json_encode(["ok" => false, "error" => "Erro ao listar cupons", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } 
    exit;
  }
}






/*  ============================ ATUALIZAÇÃO DE CUPOM ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
  try {
    $id          = (int)($_POST['id'] ?? 0);
    $nome        = trim($_POST['nome'] ?? '');
    $valor       = trim($_POST['valor'] ?? '');
    $dataVal     = trim($_POST['data_validade'] ?? '');
    $quantidade  = (int)($_POST['quantidade'] ?? 0);

    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_cupom' => 'ID inválido para edição.']);
    }

    // ==================== VALIDAÇÕES ====================
    $erros = [];

    if ($nome === '') {
      $erros[] = 'Informe o nome do cupom.';
    } elseif (mb_strlen($nome) > 50) {
      $erros[] = 'O nome do cupom deve ter no máximo 50 caracteres.';
    }

    if ($valor === '' || !is_numeric($valor) || $valor <= 0) {
      $erros[] = 'Informe um valor válido para o cupom.';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
    if (!($dt && $dt->format('Y-m-d') === $dataVal)) {
      $erros[] = 'Data de validade inválida (use o formato YYYY-MM-DD).';
    }

    if ($quantidade <= 0) {
      $erros[] = 'Informe uma quantidade válida (maior que zero).';
    }

    if ($erros) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_cupom' => implode(' ', $erros)]);
    }

    // ==================== ATUALIZAÇÃO NO BANCO ====================
    $sql = "UPDATE cupom
            SET nome = :nome,
                valor = :valor,
                data_validade = :dataVal,
                quantidade = :quantidade
            WHERE idCupom = :id";

    $st = $pdo->prepare($sql);
    $st->bindValue(':nome', $nome, PDO::PARAM_STR);
    $st->bindValue(':valor', $valor, PDO::PARAM_STR);
    $st->bindValue(':dataVal', $dataVal, PDO::PARAM_STR);
    $st->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
    $st->bindValue(':id', $id, PDO::PARAM_INT);

    $st->execute();

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['editar_cupom' => 'ok']);

  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', [
      'erro_cupom' => 'Erro ao editar: ' . $e->getMessage()
    ]);
  }
}


/*  ============================ EXCLUSÃO DE CUPOM ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
  try {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_cupom' => 'ID inválido para exclusão.']);
    }

    $st = $pdo->prepare("DELETE FROM cupom WHERE idCupom = :id");
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['excluir_cupom' => 'ok']);

  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', [
      'erro_cupom' => 'Erro ao excluir: ' . $e->getMessage()
    ]);
  }
}




/* ==================== CADASTRAR CUPOM ==================== */
try {
  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectWith("../paginas_logista/promocoes_logista.html", ["erro" => "Método inválido"]);
  }

  $nome = trim($_POST["nome"] ?? "");
  $valor = (float)($_POST["valor"] ?? 0);
  $data_validade = $_POST["data_validade"] ?? "";
  $quantidade = (int)($_POST["quantidade"] ?? 0);

  $erros = [];
  if ($nome === "" || $valor <= 0 || $quantidade <= 0) {
    $erros[] = "Preencha todos os campos obrigatórios corretamente.";
  }

  if (!empty($erros)) {
    redirectWith("../paginas_logista/promocoes_logista.html", ["erro" => implode(", ", $erros)]);
  }

  $sql = "INSERT INTO Cupom (nome, valor, data_validade, quantidade)
          VALUES (:nome, :valor, :data_validade, :quantidade)";
  $stmt = $pdo->prepare($sql);
  $ok = $stmt->execute([
    ":nome" => $nome,
    ":valor" => $valor,
    ":data_validade" => $data_validade,
    ":quantidade" => $quantidade
  ]);

  if ($ok) {
    redirectWith("../paginas_logista/promocoes_logista.html", ["cadastro" => "ok"]);
  } else {
    redirectWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro ao cadastrar cupom."]);
  }

} catch (Throwable $e) {
  redirectWith("../paginas_logista/promocoes_logista.html", [
    "erro" => "Erro no banco de dados: " . $e->getMessage()
  ]);
}
?>
