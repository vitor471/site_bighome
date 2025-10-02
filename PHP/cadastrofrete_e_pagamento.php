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

try{
    //se o metodo de envio for diferente do post
    if($)





    //validação
    $erros_validacao=[];
    //se qualquer campo for vazio
    if($bairro === "" || $valor === "" ){
        $erros_validacao[]="preencha todos os campos";
    }
    /* Inserir o Cliente no banco de dados */
    $sql ="INSERT INTO 
    Cliente (nome,cpf,telefone,email,senha)
     Values (:nome,:cpf,:telefone,:email,:senha)";
     // executando o comando no banco de dados
     $inserir = $pdo->prepare($sql)->execute([
        "bairro" => $bairro,
        "valor" => $valor,
        "prazo" => $prazo,
     ]);
     /* Verificando se foi cadastrado no banco de dados */
     if($inserir){
        redirecWith("../paginas/login.html",
        ["cadastro" => "ok"]) ;
     }else{
        redirecWith("../paginas/",["erro" 
        =>"Erro ao cadastrar no banco de dados"]);
     }





}




?>