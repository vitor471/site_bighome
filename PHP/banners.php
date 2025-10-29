<?php
// cadastro_banners.php
require_once __DIR__ . '/conexao.php';

/* ---------------------- FUNÇÕES ---------------------- */
function redirect_with(string $url, array $params = []): void {
  if ($params) {
    $qs  = http_build_query($params);
    $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
  }
  header("Location: $url");
  exit;
}

// detecta se é chamada via fetch (Ajax)
function is_fetch_request(): bool {
  return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch';
}

function read_image_to_blob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $bin = file_get_contents($file['tmp_name']);
  return $bin === false ? null : $bin;
}

/*  ===================== LISTAGEM ========================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['listar'])) {
  header('Content-Type: application/json; charset=utf-8');

  try {
    $sql = "SELECT
              b.idBanners              AS id,
              b.imagem                 AS imagem,
              b.data_validade          AS data_validade,
              b.descricao              AS descricao,
              b.link                   AS link,
              b.categoriasprodutos_id  AS categoria_id,
              c.nome                   AS categoria_nome
            FROM banners b
            LEFT JOIN categoria_produtos c
              ON c.idCategoria_produtos = b.categoriasprodutos_id
            ORDER BY b.idBanners DESC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $banners = array_map(function ($r) {
      return [
        'id'             => (int)$r['id'],
        'descricao'      => $r['descricao'],
        'data_validade'  => $r['data_validade'],
        'link'           => $r['link'] !== '' ? $r['link'] : null,
        'categoria_id'   => $r['categoria_id'] !== null ? (int)$r['categoria_id'] : null,
        'categoria_nome' => $r['categoria_nome'] ?? null,
        'imagem'         => !empty($r['imagem']) ? base64_encode($r['imagem']) : null,
      ];
    }, $rows);

    echo json_encode(['ok' => true, 'count' => count($banners), 'banners' => $banners], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro ao listar banners', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/* ==================== ATUALIZAR ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
  try {
    $id        = (int)($_POST['id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $dataVal   = trim($_POST['data'] ?? '');
    $link      = trim($_POST['link'] ?? '');
    $categoria = $_POST['categoriab'] ?? null;
    $categoria = ($categoria === '' || $categoria === null) ? null : (int)$categoria;
    $imgBlob   = read_image_to_blob($_FILES['foto'] ?? null);

    $erros = [];
    if ($id <= 0) $erros[] = 'ID inválido para edição.';
    if ($descricao === '') $erros[] = 'Informe a descrição.';
    elseif (mb_strlen($descricao) > 50) $erros[] = 'Descrição deve ter no máximo 50 caracteres.';
    $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
    if (!($dt && $dt->format('Y-m-d') === $dataVal)) $erros[] = 'Data de validade inválida (use YYYY-MM-DD).';
    if ($link !== '' && mb_strlen($link) > 45) $erros[] = 'Link deve ter no máximo 45 caracteres.';

    if ($erros) {
      if (is_fetch_request()) {
        echo json_encode(['ok' => false, 'erros' => $erros], JSON_UNESCAPED_UNICODE);
        exit;
      }
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => implode(' ', $erros)]);
    }

    $setSql = "descricao = :desc, data_validade = :dt, link = :lnk, categoriasprodutos_id = :cat";
    if ($imgBlob !== null) $setSql = "imagem = :img, " . $setSql;

    $sql = "UPDATE banners SET $setSql WHERE idBanners = :id";
    $st = $pdo->prepare($sql);

    if ($imgBlob !== null) $st->bindValue(':img', $imgBlob, PDO::PARAM_LOB);
    $st->bindValue(':desc', $descricao);
    $st->bindValue(':dt', $dataVal);
    $st->bindValue(':lnk', $link === '' ? null : $link, $link === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $st->bindValue(':cat', $categoria, $categoria === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    if (is_fetch_request()) {
      echo json_encode(['ok' => true, 'mensagem' => 'Banner atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['editar_banner' => 'ok']);
  } catch (Throwable $e) {
    if (is_fetch_request()) {
      echo json_encode(['ok' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
      exit;
    }
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao editar: ' . $e->getMessage()]);
  }
}

/* ==================== EXCLUIR ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
  try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido para exclusão.');

    $st = $pdo->prepare("DELETE FROM banners WHERE idBanners = :id");
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    if (is_fetch_request()) {
      echo json_encode(['ok' => true, 'mensagem' => 'Banner excluído com sucesso.'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['excluir_banner' => 'ok']);
  } catch (Throwable $e) {
    if (is_fetch_request()) {
      echo json_encode(['ok' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
      exit;
    }
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao excluir: ' . $e->getMessage()]);
  }
}

/* ==================== CADASTRO ==================== */
try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_fetch_request()) {
      echo json_encode(['ok' => false, 'erro' => 'Método inválido.']);
      exit;
    }
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Método inválido']);
  }

  $descricao = trim($_POST['descricao'] ?? '');
  $dataVal   = trim($_POST['data'] ?? '');
  $link      = trim($_POST['link'] ?? '');
  $categoria = $_POST['categoriab'] ?? null;
  $categoria = ($categoria === '' || $categoria === null) ? null : (int)$categoria;
  $imgBlob   = read_image_to_blob($_FILES['foto'] ?? null);

  $erros = [];
  if ($descricao === '') $erros[] = 'Informe a descrição.';
  elseif (mb_strlen($descricao) > 50) $erros[] = 'Descrição deve ter no máximo 50 caracteres.';
  $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
  if (!($dt && $dt->format('Y-m-d') === $dataVal)) $erros[] = 'Data de validade inválida (use YYYY-MM-DD).';
  if ($link !== '' && mb_strlen($link) > 45) $erros[] = 'Link deve ter no máximo 45 caracteres.';
  if ($imgBlob === null) $erros[] = 'Envie a imagem do banner.';

  if ($erros) {
    if (is_fetch_request()) {
      echo json_encode(['ok' => false, 'erros' => $erros], JSON_UNESCAPED_UNICODE);
      exit;
    }
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => implode(' ', $erros)]);
  }

  $sql = "INSERT INTO banners (imagem, data_validade, descricao, link, categoriasprodutos_id)
          VALUES (:img, :dt, :desc, :lnk, :cat)";
  $st  = $pdo->prepare($sql);
  $st->bindValue(':img', $imgBlob, PDO::PARAM_LOB);
  $st->bindValue(':dt', $dataVal);
  $st->bindValue(':desc', $descricao);
  $st->bindValue(':lnk', $link === '' ? null : $link, $link === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
  $st->bindValue(':cat', $categoria, $categoria === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
  $st->execute();

  if (is_fetch_request()) {
    echo json_encode(['ok' => true, 'mensagem' => 'Banner cadastrado com sucesso.'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['cadastro_banner' => 'ok']);

} catch (Throwable $e) {
  if (is_fetch_request()) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
  redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>
