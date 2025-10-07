<?php
require_once __DIR__ . "/conexao.php";

// Função de redirecionamento com parâmetros
function redirectWith(string $url, array $params = []): void {
  if (!empty($params)) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
  }
  header("Location: $url");
  exit;
}

// Função para ler imagens como blob
function readImageToBlob(?array $file): ?string {
  if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
  $content = file_get_contents($file['tmp_name']);
  return $content === false ? null : $content;
}

try {
  // Valida método
  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectWith("../paginas_logista/cadastro_produtos_logista.html", ["erro" => "Método inválido."]);
  }

  // Captura dados
  $nome = trim($_POST["nome"] ?? "");
  $descricao = trim($_POST["descricao"] ?? "");
  $quantidade = (int)($_POST["quantidade"] ?? 0);
  $preco = (float)($_POST["preco"] ?? 0);
  $tamanho = trim($_POST["tamanho"] ?? "");
  
  $codigo = (int)($_POST["codigo"] ?? 0);
  $preco_promocional = (float)($_POST["preco_promocional"] ?? 0);
  $marcas_idmarcas = 1;

  $img1 = readImageToBlob($_FILES["imgproduto1"] ?? null);
  $img2 = readImageToBlob($_FILES["imgproduto2"] ?? null);
  $img3 = readImageToBlob($_FILES["imgproduto3"] ?? null);

  // Transação
  $pdo->beginTransaction();

  // Inserir produto
  $sql = "INSERT INTO produtos (nome, descricao, quantidade, preco, tamanho, codigo, preco_promocional, marcas_idmarcas)
          VALUES (:nome, :descricao, :quantidade, :preco, :tamanho, :codigo, :preco_promocional, :marcas_idmarcas)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ":nome" => $nome,
    ":descricao" => $descricao,
    ":quantidade" => $quantidade,
    ":preco" => $preco,
    ":tamanho" => $tamanho,
    ":codigo" => $codigo,
    ":preco_promocional" => $preco_promocional,
    ":marcas_idmarcas" => $marcas_idmarcas
  ]);

  $idProduto = (int)$pdo->lastInsertId();

  // Inserir imagens se existirem
  if ($img1 || $img2 || $img3) {
    $sqlImg = "INSERT INTO imagens_produtos (foto) VALUES (:foto)";
    $stmtImg = $pdo->prepare($sqlImg);

    $idsImg = [];
    foreach ([$img1, $img2, $img3] as $img) {
      if ($img) {
        $stmtImg->bindValue(":foto", $img, PDO::PARAM_LOB);
        $stmtImg->execute();
        $idsImg[] = (int)$pdo->lastInsertId();
      }
    }

    // Vincular imagens ao produto
    $sqlVinc = "INSERT INTO imagens_produtos_e_produtos 
                (produtos_idProdutos, imagens_produtos_idImagem_produtos, marcas_idMarcas)
                VALUES (:idprod, :idimg, :idmarca)";
    $stmtVinc = $pdo->prepare($sqlVinc);
    foreach ($idsImg as $idImg) {
      $stmtVinc->execute([
        ":idprod" => $idProduto,
        ":idimg" => $idImg,
        ":idmarca" => $marcas_idmarcas
      ]);
    }
  }

  $pdo->commit();
  redirectWith("../paginas_logista/cadastro_produtos_logista.html", ["sucesso" => "Produto cadastrado com sucesso!"]);

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  redirectWith("../paginas_logista/cadastro_produtos_logista.html", ["erro" => "Erro no banco: " . $e->getMessage()]);
}
