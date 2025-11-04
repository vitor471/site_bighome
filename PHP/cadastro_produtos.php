<?php
require_once __DIR__ . "/conexao.php";

// ---------------- Funções auxiliares ----------------
function redirectWith(string $url, array $params = []): void {
    if (!empty($params)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }
    header("Location: $url");
    exit;
}

function readImageToBlob(?array $file): ?string {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $content = file_get_contents($file['tmp_name']);
    return $content === false ? null : $content;
}

function json_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok($data = []) {
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===================== LISTAR POR CATEGORIA ===================== //
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['listar_por_categoria'])) {
  // aceita idCategoria, idcategoria ou categoria_id
  $catId = (int)($_GET['idCategoria'] ?? $_GET['idcategoria'] ?? $_GET['categoria_id'] ?? 0);
  if ($catId <= 0) json_err('idCategoria inválido');

  try {
    $sql = "SELECT
              p.idProdutos,
              p.nome,
              p.descricao,
              p.quantidade,
              p.preco,
              p.preco_promocional,
              m.nome AS marca,
              c.nome AS categoria,
              MIN(i.idImagem_produtos) AS id_img,
              (SELECT i2.foto
                 FROM imagens_produtos i2
                 JOIN imagens_produtos_e_produtos ip2 ON ip2.imagens_produtos_idimagem_produtos = i2.idImagem_produtos
                WHERE ip2.produtos_idprodutos = p.idProdutos
                ORDER BY i2.idImagem_produtos ASC
                LIMIT 1) AS imagem,
              (SELECT i2.descricao
                 FROM imagens_produtos i2
                 JOIN imagens_produtos_e_produtos ip2 ON ip2.imagens_produtos_idimagem_produtos = i2.idImagem_produtos
                WHERE ip2.produtos_idprodutos = p.idProdutos
                ORDER BY i2.idImagem_produtos ASC
                LIMIT 1) AS texto_alternativo
            FROM produtos p
            LEFT JOIN marcas m ON m.idMarcas = p.marcas_idmarcas
            INNER JOIN categoria_produtos_e_produtos cp ON cp.produtos_idprodutos = p.idProdutos
            INNER JOIN categoria_produtos c ON c.idCategoria_produtos = cp.categoria_produtos_idcategoria_produtos
            LEFT JOIN imagens_produtos_e_produtos ip ON ip.produtos_idprodutos = p.idProdutos
            LEFT JOIN imagens_produtos i ON i.idImagem_produtos = ip.imagens_produtos_idimagem_produtos
            WHERE cp.categoria_produtos_idcategoria_produtos = :catId
            GROUP BY p.idProdutos
            ORDER BY p.idProdutos DESC";

    $st = $pdo->prepare($sql);
    $st->bindValue(':catId', $catId, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $produtos = array_map(function($r) {
      return [
        'idProdutos'        => (int)$r['idProdutos'],
        'nome'              => $r['nome'],
        'descricao'         => $r['descricao'],
        'quantidade'        => (int)$r['quantidade'],
        'preco'             => (float)$r['preco'],
        'preco_promocional' => isset($r['preco_promocional']) ? (float)$r['preco_promocional'] : null,
        'marca'             => $r['marca'] ?? null,
        'categoria'         => $r['categoria'] ?? null,
        'imagem'            => $r['imagem'] ? base64_encode($r['imagem']) : null,
        'texto_alternativo' => $r['texto_alternativo'] ?? null
      ];
    }, $rows);

    json_ok(['count' => count($produtos), 'produtos' => $produtos]);
  } catch (Throwable $e) {
    json_err('Falha ao listar produtos por categoria: ' . $e->getMessage(), 500);
  }
}

// ---------------- FLUXO GET (LISTAGEM GERAL) ----------------
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = "SELECT 
                    p.idProdutos AS id,
                    p.nome,
                    p.descricao,
                    p.quantidade,
                    p.preco,
                    p.preco_promocional,
                    p.codigo,
                    m.nome AS marca,
                    c.nome AS categoria,
                    i.foto AS imagem
                FROM produtos p
                LEFT JOIN marcas m ON p.marcas_idmarcas = m.idMarcas
                LEFT JOIN categoria_produtos_e_produtos cp ON cp.produtos_idprodutos = p.idProdutos
                LEFT JOIN categoria_produtos c ON c.idCategoria_produtos = cp.categoria_produtos_idcategoria_produtos
                LEFT JOIN imagens_produtos_e_produtos ip ON ip.produtos_idprodutos = p.idProdutos
                LEFT JOIN imagens_produtos i ON i.idImagem_produtos = ip.imagens_produtos_idimagem_produtos
                GROUP BY p.idProdutos
                ORDER BY p.idProdutos DESC";

        $stmt = $pdo->query($sql);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $saida = array_map(function ($p) {
            return [
                "id" => (int)$p["id"],
                "nome" => $p["nome"],
                "descricao" => $p["descricao"],
                "quantidade" => (int)$p["quantidade"],
                "preco" => (float)$p["preco"],
                "preco_promocional" => $p["preco_promocional"] !== null ? (float)$p["preco_promocional"] : null,
                "codigo" => $p["codigo"],
                "marca" => $p["marca"] ?? "—",
                "categoria" => $p["categoria"] ?? "—",
                "imagem" => $p["imagem"] ? base64_encode($p["imagem"]) : null
            ];
        }, $produtos ?: []);

        echo json_encode(["ok" => true, "produtos" => $saida], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["ok" => false, "erro" => $e->getMessage()]);
        exit;
    }
}

// ---------------- FLUXO POST (CADASTRAR PRODUTO) ----------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $nome = trim($_POST["nome"] ?? "");
        $descricao = trim($_POST["descricao"] ?? "");
        $quantidade = (int)($_POST["quantidade"] ?? 0);
        $preco = (float)($_POST["preco"] ?? 0);
        $tamanho = trim($_POST["tamanho"] ?? "");
        $codigo = trim($_POST["codigo"] ?? "");
        $preco_promocional = (float)($_POST["preco_promocional"] ?? 0);
        $marcas_idmarcas = (int)($_POST["marcas_idmarcas"] ?? 1);
        $categorias = $_POST["categoriaprodutos"] ?? [];

        // Imagens
        $img1 = readImageToBlob($_FILES["imgproduto1"] ?? null);
        $img2 = readImageToBlob($_FILES["imgproduto2"] ?? null);
        $img3 = readImageToBlob($_FILES["imgproduto3"] ?? null);

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

        // Vincular categorias
        if (!empty($categorias)) {
            $sqlCat = "INSERT INTO categoria_produtos_e_produtos 
                       (categoria_produtos_idcategoria_produtos, produtos_idprodutos)
                       VALUES (:cat_id, :prod_id)";
            $stmtCat = $pdo->prepare($sqlCat);
            foreach ($categorias as $catId) {
                $stmtCat->execute([
                    ":cat_id" => (int)$catId,
                    ":prod_id" => $idProduto
                ]);
            }
        }

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

            // Vincular imagens ao produto e marca (tabela existente)
            $sqlVinc = "INSERT INTO imagens_produtos_e_produtos
                        (produtos_idprodutos, imagens_produtos_idimagem_produtos, marcas_idmarcas)
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
        redirectWith("../paginas_logista/cadastro_produtos_logista.html", [
            "sucesso" => "Produto cadastrado e vinculado com sucesso!"
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        redirectWith("../paginas_logista/cadastro_produtos_logista.html", [
            "erro" => "Erro no banco: " . $e->getMessage()
        ]);
    }
}

// ---------------- Fallback (nenhum método compatível) ----------------
json_err('Requisição inválida', 405);
