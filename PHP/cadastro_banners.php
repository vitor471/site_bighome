<?php

// Conectando este arquivo ao banco de dados
require_once __DIR__ . "/conexao.php";

// Função para redirecionamento com parâmetros
function redirecWith($url, $params = [])
{
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        // Consulta banners com o nome da categoria
        $sqlListar = "SELECT 
                        b.idBanners AS id,
                        b.descricao,
                        b.link,
                        b.data_validade,
                        b.imagem,
                        c.nome AS categoria_nome
                      FROM banners b
                      LEFT JOIN categoria_produtos c ON b.categoriasprodutos_id = c.idCategoria_produtos
                      ORDER BY b.data_validade DESC";

        $stmt = $pdo->query($sqlListar);
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "option";

        if ($formato === "json") {
            // Normaliza dados
            $saida = array_map(function ($item) {
                return [
                    "id" => (int)$item["id"],
                    "descricao" => $item["descricao"],
                    "link" => $item["link"],
                    "data_validade" => $item["data_validade"],
                    "categoria_nome" => $item["categoria_nome"],
                    "imagem" => $item["imagem"] ? base64_encode($item["imagem"]) : null
                ];
            }, $banners);

            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "banners" => $saida], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Retorno padrão (HTML <option>) mostrando o nome da categoria
        header("Content-Type: text/html; charset=utf-8");
        foreach ($banners as $banner) {
            $id = (int)$banner["id"];
            $descricao = htmlspecialchars($banner["descricao"], ENT_QUOTES, "UTF-8");
            $categoria = htmlspecialchars($banner["categoria_nome"] ?? 'Sem categoria', ENT_QUOTES, "UTF-8");
            echo "<option value=\"$id\">$descricao - $categoria</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        if (isset($_GET["format"]) && strtolower($_GET["format"]) === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode([
                "ok" => false,
                "error" => "Erro ao listar banners",
                "detail" => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar banners</option>";
        }
        exit;
    }
}





/*  ============================ATUALIZAÇÃO=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
  try {
    $id        = (int)($_POST['id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $dataVal   = trim($_POST['data_validade'] ?? '');
    $link      = trim($_POST['link'] ?? '');
    $categoriaprodutos_id = $_POST['categoriaprodutos_id'] ?? null;
   $categoria = ($categoriaprodutos_id === '' || $categoriaprodutos_id === null)
    ? null
    : (int)$categoriaprodutos_id;

    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'ID inválido para edição.']);
    }

    // Lê (se houver) nova imagem
    $imgBlob = read_image_to_blob($_FILES['foto'] ?? null);

    // validações mínimas (iguais ao cadastro)
    $erros = [];
    if ($descricao === '') { $erros[] = 'Informe a descrição.'; }
    elseif (mb_strlen($descricao) > 45) { $erros[] = 'Descrição deve ter no máximo 45 caracteres.'; }

    $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
    if (!($dt && $dt->format('Y-m-d') === $dataVal)) { $erros[] = 'Data de validade inválida (use YYYY-MM-DD).'; }

    if ($link !== '' && mb_strlen($link) > 45) { $erros[] = 'Link deve ter no máximo 45 caracteres.'; }

    if ($erros) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => implode(' ', $erros)]);
    }

    // Monta UPDATE dinâmico (atualiza imagem só se uma nova foi enviada)
    $setSql = "descricao = :desc, data_validade = :dt, link = :lnk, CategoriasProdutos_id = :cat";
    if ($imgBlob !== null) {
      $setSql = "imagem = :img, " . $setSql;
    }

    $sql = "UPDATE Banners
              SET $setSql
            WHERE idBanners = :id";

    $st = $pdo->prepare($sql);

    if ($imgBlob !== null) {
      $st->bindValue(':img', $imgBlob, PDO::PARAM_LOB);
    }

    $st->bindValue(':desc', $descricao, PDO::PARAM_STR);
    $st->bindValue(':dt',   $dataVal,   PDO::PARAM_STR);

    if ($link === '') {
      $st->bindValue(':lnk', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':lnk', $link, PDO::PARAM_STR);
    }

    if ($categoria === null) {
      $st->bindValue(':cat', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':cat', $categoria, PDO::PARAM_INT);
    }

    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['editar_banner' => 'ok']);

  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao editar: ' . $e->getMessage()]);
  }
}





/*  ============================EXCLUSÃO=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
  try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'ID inválido para exclusão.']);
    }

    $st = $pdo->prepare("DELETE FROM Banners WHERE idBanners = :id");
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['excluir_banner' => 'ok']);

  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao excluir: ' . $e->getMessage()]);
  }
}


















// ==================== CADASTRAR BANNER ====================
try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Método inválido"]);
    }

    // Lê dados do formulário
    $descricao = $_POST["descricao"] ?? "";
    $link = $_POST["link"] ?? "";
    $data_validade = $_POST["data_validade"] ?? null;
    $categoriaprodutos_id = $_POST["categoriaprodutos_id"] ?? null;
    $imagem = $_FILES["imagem"]["tmp_name"] ?? null;

    $erros_validacao = [];

    if ($descricao === "" || !$imagem || !$data_validade) {
        $erros_validacao[] = "Preencha todos os campos obrigatórios e selecione uma imagem";
    }

    // Lê o conteúdo do arquivo de imagem
    $imagem_blob = file_get_contents($imagem);

    // Insere no banco
    $sql = "INSERT INTO banners (descricao, link, data_validade, categoriasprodutos_id, imagem)
            VALUES (:descricao, :link, :data_validade, :categoriaprodutos_id, :imagem)";
    $stmt = $pdo->prepare($sql);
    $inserir = $stmt->execute([
        ":descricao" => $descricao,
        ":link" => $link,
        ":data_validade" => $data_validade,
        ":categoriaprodutos_id" => $categoriaprodutos_id,
        ":imagem" => $imagem_blob
    ]);

    if ($inserir) {
        redirecWith("../paginas_logista/promocoes_logista.html", ["cadastro" => "ok"]);
    } else {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
    }

} catch (\Exception $e) {
    redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}

?>