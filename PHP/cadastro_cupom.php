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

/*  ============================EXCLUSÃO=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
  try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/banners_logista.html', ['erro_banner' => 'ID inválido para exclusão.']);
    }

    $st = $pdo->prepare("DELETE FROM Banners WHERE idBanners = :id");
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['excluir_banner' => 'ok']);

  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao excluir: ' . $e->getMessage()]);
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
