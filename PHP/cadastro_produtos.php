<?php
// Conectando este arquivo ao banco de dados
require_once __DIR__ ."/conexao.php";

// função para capturar os dados passados de uma página a outra
function redirecWith($url,$params=[]){
// verifica se os os paramentros não vieram vazios
 if(!empty($params)){
// separar os parametros em espaços diferentes
$qs= http_build_query($params);
$sep = (strpos($url,'?') === false) ? '?': '&';
$url .= $sep . $qs;
}
// joga a url para o cabeçalho no navegador
header("Location:  $url");
// fecha o script
exit;
}
/* Lê arquivo de upload como blob (ou null) */
function readImageToBlob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $content = file_get_contents($file['tmp_name']);
  return $content === false ? null : $content;
}

try{
    // SE O METODO DE ENVIO FOR DIFERENTE DO POST
  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirecWith("../paginas_logista/cadastro_produtos_logista.html",
      ["erro_marca" => "Método inválido"]);
  }
//criar as variaveis
 $nome = $_POST["nome"];
 $descricao =  $_POST["descricao"];
 $quantidade =(int) $_POST["quantidade"];
 $preco =(double) $_POST["preco"];
 $tamanho = $_POST["tamanho"];
 $cor = $_POST["cor"];
 $codigo = (int)$_POST["codigo"];
 $preco_promocional = (double)$_POST["preco_promocional"];
 $marcas_idmarcas = 1; 

 //criar as variaveis das imagens
 $img1   = readImageToBlob($_FILES["imagemmarca1"] ?? null);
 $img2   = readImageToBlob($_FILES["imagemmarca2"]  ?? null);
 $img3   = readImageToBlob($_FILES["imagemmarca3"] ?? null);


// VALIDANDO OS CAMPOS
  $erros_validacao = [];
  if ($nome === "" || $descricao === "" || $quantidade <= 0 || $preco <= 0 || $preco === ""
  || $marcas_idmarcas <= 0) {
    $erros_validacao[] = "Preencha os campos obrigatorios.";
  }
  // se houver erros, volta para a tela com a mensagem
  if (!empty($erros_validacao)) {
    redirecWith("../paginas_logista/cadastro_produtos_logista.html",
      ["erro" => implode(" ", $erros_validacao)]);
  }
//é utilizado para fazer vinculos de transa
$pdo ->begintransaction();

//fazer o comando de inserir dentro da tabela de produtos
$sqlprodutos ="insert into produtos(nome,descricao,quantidade,
preco,tamanho,cor,codigo,preco_promocional,marcas_idmarcas)
values (:nome,:descricao,:quantidade,:preco,:tamanho,:cor,:codigo,:preco_promocional,:marcas_idmarcas)";

$stmprodutos = $pdo->prepare($sqlprodutos);

$inserirProdutos = $stmprodutos-execute([
  ":nome" => $nome,
  ":descricao"=> $descricao,
  ":quantidade" => $quantidade,
  ":preco" => $preco,
  ":tamanho" => $tamanho,
  ":cor" => $cor,
  ":codigo" => $codigo,
  ":preco_promocional"=> $preco_promocional,
  ":marcas_idmarcas" => $marcas_idmarcas,
]);

if($inserirprodutos) {
  $pdo ->rollback();
  redirecWith("../paginas_logista/cadastro_produtos_logista.html",
  ["erro"=>"falha ao cadastrar produtos"]);
}

$idproduto=(int)$pdo->lastinsertid();

//cadastro de imagens

$sqlimagens ="insert into imagem_produtos(foto) values 
(:imagem1),(:imagem2),(:imagem3)";

//PREPARA O COMANDO SQL PARA SER EXECUTADO
$stmimagens=$pdo -> prepare(sqlimagens);
//BIND COMO LOB QUANDO HOUVER CONTEÚDO; SE NULL, O PDO ENVIA NULL CORRETAMENTE
if ($img1 !== null) {
  $stmimagens->bindparam(':imagem1', $img1, PDO::PARAM_LOB);
}else{
  $stmimagens-> bindvalue(':imagem1', null, PDO::PARAM_NULL);
}
if ($img1 !== null) {
  $stmimagens->bindparam(':imagem2', $img2, PDO::PARAM_LOB);
}else{
  $stmimagens-> bindvalue(':imagem2', null, PDO::PARAM_NULL);
}
if ($img1 !== null) {
  $stmimagens->bindparam(':imagem3', $img3, PDO::PARAM_LOB);
}else{
  $stmimagens-> bindvalue(':imagem3', null, PDO::PARAM_NULL);
}
 
$inseririmages=$stmimagens->execute();

//VERIFICAR SE O INSERIR DE IMAGENS DEU ERRADO
if(!$inseririmagens){
  $pdo ->rollback();
  redirecWith("../paginas_logista/cadastro_produtos_logista.html",
  ["erro"=>"faha ao cadastrar imagens"]);
}
//CASO TENHA DADO CERTO, CAPTRE O ID DA IMAGEM CADASTRADA
$idimg = (int)$pdo->lastinsertid();

//VINCULAR A IMAGEM COM O PRODUTO
$sqlVincularprodimg ="insert into produtos_has_imagem_produtos
(produtos_idprodutos,imagem_produtos_idimagem_produtos)
values
(:idpro,:idimg)";

$stmvincularprodimg = $pdo->prepare($sqlVincularprodimg);

$inserirvincularprodimg = $stmvincularprodimg->execute([
  ":idpro"=> $idproduto,
  ":idimg"=> $idimg,
]);

if (!$inserirVincularProdimg) {
  $pdo->rollback();
  redirecWith("../paginas_logista/cadastro_produtos_logista.html",
  ["erro"=> "falha ao vincular produto com imagem."]);
}

































$stmimagens=$pdo -> prepare(sqlimagens);

$inseririmages=$stmimagens->execute([
  ":imagem1"=> $img1,
  ":imagem2"=> $img2,
  ":imagem3"=> $img3,
]);





}catch(Exception $e){
 redirecWith("../paginas_logista/cadastro_produtos_logista.html",
      ["erro" => "Erro no banco de dados: 
      .$e->getMessage()]);
}