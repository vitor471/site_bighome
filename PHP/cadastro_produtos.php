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
 $img2   = readImageToBlob($_FILES["imagemmarca2"] ?? null);
 $img3   = readImageToBlob($_FILES["imagemmarca3"] ?? null);

// VALIDANDO OS CAMPOS
  $erros_validacao = [];
  if ($nome === "" || $descricao === "" || $quantidade = 0 || $preco = 0 || $preco === ""
  || $marcas_idmarcas = 0) {
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
values (:nome,:descricao
"



}catch(Exception $e){
 redirecWith("../paginas_logista/cadastro_produtos_logista.html",
      ["erro" => "Erro no banco de dados: "
      .$e->getMessage()]);
}