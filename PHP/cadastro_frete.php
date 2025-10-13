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
    // SE O METODO DE ENVIO FOR DIFERENTE DO POST
    if($_SERVER["REQUEST_METHOD"] !== "POST"){
        //VOLTAR À TELA DE CADASTRO E EXIBIR ERRO
        redirecWith("../paginas_logista/frete_pagamento_logista.html",
           ["erro"=> "Metodo inválido"]);
    }
    // variaveis
    $cidade = $_POST["cidade"];
    $valor = (double)$_POST["valor"];
    $transportadora = $_POST["transportadora"];

    // validação
    $erros_validacao=[];
    //se qualquer campo for vazio
    if($cidade === "" || $valor ==="" ){
        $erros_validacao[]="Preencha todos os campos";
    }

/* Inserir o frete no banco de dados */
    $sql ="INSERT INTO 
    Frete (cidade,valor,transportadora)
     Values (:cidade,:valor,:transportadora)";
     // executando o comando no banco de dados
     $inserir = $pdo->prepare($sql)->execute([
        ":cidade" => $cidade,
        ":valor"=> $valor,
        ":transportadora"=> $transportadora,     
     ]);

     /* Verificando se foi cadastrado no banco de dados */
     if($inserir){
        redirecWith("../paginas_logista/frete_pagamento_logista.html",
        ["cadastro" => "ok"]) ;
     }else{
        redirecWith("../paginas_logista/frete_pagamento_logista.html"
        ,["erro" =>"Erro ao cadastrar no banco
         de dados"]);
     }
}catch(\Exception $e){
redirecWith("../paginas_logista/frete_pagamento_logista.html",
      ["erro" => "Erro no banco de dados: "
      .$e->getMessage()]);
}


?>