<?php
$host = "localhost"; // servidor do banco
$db   = "bighome"; // nome do banco de dados
$user = "root";    // usuário do mySQl
$pass = "";  // senha do MySQL (ajuste se houver)

try {
    //estabelecendo conexao
    $pdo = new PDO("mysql:host=$host;dbname=$db;
    charset=utf8mb4", $user, $pass);
    // verificando se deu certo ou nao
    $pdo->setattribute(PDO:: ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION);
    //imprimindo mensagem caso tenha dado certo
    echo "conexao bem-sucedida!"; // (opcional para teste)
} catch (PDOException $e) {
    //caso de erro, ele executa o catch e imprime a mensagem 
    die("erro ao conectar ao banco de dados. "
    . $e->getmessage());
}

?>