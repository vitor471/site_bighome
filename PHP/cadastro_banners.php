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

// ==================== LISTAR BANNERS ====================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        // Consulta banners
        $sqlListar = "SELECT idBanners AS id, descricao, link, data_validade, categoriasprodutos_id AS categoria_id, imagem
                      FROM banners
                      ORDER BY data_validade DESC";

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
                    "categoria_id" => $item["categoria_id"],
                    "imagem" => base64_encode($item["imagem"]) // converte imagem para base64
                ];
            }, $banners);

            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "banners" => $saida], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Retorno padrão (HTML <option>) caso necessário
        header("Content-Type: text/html; charset=utf-8");
        foreach ($banners as $banner) {
            $id = (int)$banner["id"];
            $descricao = htmlspecialchars($banner["descricao"], ENT_QUOTES, "UTF-8");
            echo "<option value=\"$id\">$descricao</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        if (isset($_GET["format"]) && strtolower($_GET["format"]) === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode(["ok" => false, "error" => "Erro ao listar banners", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar banners</option>";
        }
        exit;
    }
}

// ==================== CADASTRAR BANNER ====================
try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_logista/banners.html", ["erro" => "Método inválido"]);
    }

    // Lê dados do formulário
    $descricao = $_POST["descricao"] ?? "";
    $link = $_POST["link"] ?? "";
    $data_validade = $_POST["data_validade"] ?? null;
    $categoria_id = $_POST["categoria_id"] ?? null;
    $imagem = $_FILES["imagem"]["tmp_name"] ?? null;

    $erros_validacao = [];

    if ($descricao === "" || !$imagem || !$data_validade) {
        $erros_validacao[] = "Preencha todos os campos obrigatórios e selecione uma imagem";
    }

    // Lê o conteúdo do arquivo de imagem
    $imagem_blob = file_get_contents($imagem);

    // Insere no banco
    $sql = "INSERT INTO banners (descricao, link, data_validade, categoriasprodutos_id, imagem)
            VALUES (:descricao, :link, :data_validade, :categoria_id, :imagem)";
    $stmt = $pdo->prepare($sql);
    $inserir = $stmt->execute([
        ":descricao" => $descricao,
        ":link" => $link,
        ":data_validade" => $data_validade,
        ":categoria_id" => $categoria_id,
        ":imagem" => $imagem_blob
    ]);

    if ($inserir) {
        redirecWith("../paginas_logista/banners.html", ["cadastro" => "ok"]);
    } else {
        redirecWith("../paginas_logista/banners.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
    }

} catch (\Exception $e) {
    redirecWith("../paginas_logista/banners.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}

?>