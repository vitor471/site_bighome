<?php
// Conectando este arquivo ao banco de dados
require_once __DIR__ ."/conexao.php";

// função para capturar os dados passados de uma página a outra
// ==================== LISTAGEM DE CATEGORIAS ====================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        $sql = "SELECT idCategoria_produtos AS id, nome FROM categoria_produtos ORDER BY nome";
        $stmt = $pdo->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "option";

        if ($formato === "json") {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "ok" => true,
                "categorias" => $categorias
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Retorno padrão HTML
        header("Content-Type: text/html; charset=utf-8");
        if ($categorias) {
            foreach ($categorias as $cat) {
                $id = (int)$cat["id"];
                $nome = htmlspecialchars($cat["nome"], ENT_QUOTES, "UTF-8");
                echo "<option value=\"$id\">$nome</option>\n";
            }
        } else {
            echo "<option disabled>Nenhuma categoria cadastrada</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        if ($formato === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode([
                "ok" => false,
                "error" => "Erro ao listar categorias",
                "detail" => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar categorias</option>";
        }
        exit;
    }
}



/* ============================ATUALIZAÇÃO DE CATEGORIA=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
    try {
        $id      = (int)($_POST['id'] ?? 0);
        $nome    = trim($_POST['nomecategoria'] ?? '');
        $desconto = (float)($_POST['desconto'] ?? 0);

        if ($id <= 0) {
            redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
                'erro' => 'ID inválido para edição de categoria.'
            ]);
        }

        // validações
        $erros = [];
        if ($nome === '') {
            $erros[] = 'Informe o nome da categoria.';
        } elseif (mb_strlen($nome) > 50) {
            $erros[] = 'Nome da categoria deve ter no máximo 50 caracteres.';
        }

        if ($desconto < 0) {
            $erros[] = 'O desconto não pode ser negativo.';
        }

        if ($erros) {
            redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
                'erro' => implode(' ', $erros)
            ]);
        }

        // UPDATE
        $sql = "UPDATE categoria_produtos
                SET nome = :nome, desconto = :desconto
                WHERE idCategoria_produtos = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':desconto', $desconto, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
            'editar_categoria' => 'ok'
        ]);

    } catch (Throwable $e) {
        redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
            'erro' => 'Erro ao editar categoria: ' . $e->getMessage()
        ]);
    }
}




/* ============================EXCLUSÃO DE CATEGORIA=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
                'erro' => 'ID inválido para exclusão de categoria.'
            ]);
        }

        // Opcional: remover vínculos com produtos antes de excluir a categoria
        $stmtVinc = $pdo->prepare("DELETE FROM categoria_produtos_e_produtos WHERE categoria_produtos_idcategoria_produtos = :id");
        $stmtVinc->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtVinc->execute();

        // Exclui a categoria
        $st = $pdo->prepare("DELETE FROM categoria_produtos WHERE idCategoria_produtos = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
            'excluir_categoria' => 'ok'
        ]);

    } catch (Throwable $e) {
        redirectWith('../paginas_logista/cadastro_produtos_logista.html', [
            'erro' => 'Erro ao excluir categoria: ' . $e->getMessage()
        ]);
    }
}







// códigos de cadastro
try{
// SE O METODO DE ENVIO FOR DIFERENTE DO POST
    if($_SERVER["REQUEST_METHOD"] !== "POST"){
        //VOLTAR À TELA DE CADASTRO E EXIBIR ERRO
        redirecWith("../paginas_logista/cadastro_produtos_logista.html",
           ["erro"=> "Metodo inválido"]);
    }
    // jogando os dados da dentro de váriaveis
    $nome = $_POST["nomecategoria"];
    $desconto = (double)$_POST["desconto"];

     // VALIDANDO OS CAMPOS
// criar uma váriavel para receber os erros de validação
    $erros_validacao=[];
    //se qualquer campo for vazio
    if($nome === "" ){
        $erros_validacao[]="Preencha todos os campos";
    }

    /* Inserir a categoria no banco de dados */
    $sql ="INSERT INTO categoria_produtos (nome,desconto)
     Values (:nome,:desconto)";
     // executando o comando no banco de dados
     $inserir = $pdo->prepare($sql)->execute([
        ":nome" => $nome,
        ":desconto"=> $desconto, 
     ]);
     /* Verificando se foi cadastrado no banco de dados */
     if($inserir){
        redirecWith("../paginas_logista/cadastro_produtos_logista.html",
        ["cadastro" => "ok"]) ;
     }else{
        redirecWith("../paginas_logista/cadastro_produtos_logista.html",["erro" 
        =>"Erro ao cadastrar no banco de dados"]);
     }


}catch(Exception $e){
 redirecWith("../paginas_logista/cadastro_produtos_logista.html",
      ["erro" => "Erro no banco de dados: "
      .$e->getMessage()]);
}


try {
  $sql = "SELECT idCategoriaProduto, nome FROM categorias_produtos ORDER BY nome";
  foreach ($pdo->query($sql) as $row) {
    $id = (int)$row['idCategoriaProduto'];
    $nome = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
    echo "<option value=\"{$id}\">{$nome}</option>\n";
  }
} catch (Throwable $e) {
  http_response_code(500);
  // Pode retornar nada ou uma opção de erro (opcional):
  // echo "<option disabled>Erro ao carregar</option>";
}





?>