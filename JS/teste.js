// variavel: e uma caixa que serve para armazenar infomações ou dados.


//pode mudar o valor dentro do codigo
let nomevariavel = 1; //inteiro
let nomevariavel2 = "joão vitor"; //varchar
let nomevariavel3 = 2.7 //double
let nomevariavel4 = true ; // boolean

// variavel constante, que não altera o valor dela 
const nome= "joao vitor";

//operação matematicas

let soma = 3+5;// 8
let subtracao= 5-3;// 2
let mutiplicacao =3*5;// 15
let divisao = 10/2;//5

//juntar textos

let primeironome = "joão vitor";
let sobrenome = "pinheiro";
let nomecompleto = primeironome + sobrenome;

//criar função

//função ela imprime o olá mundo
//função sem parametro: que não recebe dados dentro do()
function imprimirmsg(){
    //console e utilizado para mostrar textos 
    console.log("olá mundo");
    console.log(primeironome+"olá mundo");
}
//fução com parametros
187,76
function somarvalores(valor1,valor2){
    let soma = valor1+valor2;
    console.log("o resultado da soma é:"+soma);
}
function imc(altura,peso,nomepessoa){
    let resultado = (altura/peso) * altura;
    console.log(nomepessoa+"o seu imc é :" +resultado);
}

//condicional
/* É uma ação que é executada com base em um ou mais criterio
- se chover irei ao cinema, se fizer sol irei a paria 

-hoje choveu! (ir ao cinema)
-hoje fez sol! (ir praia)

se fizer sol e eu tiver dinheiro, irei á praia,
senão ficarei em casa.
-Fez sol e tenho dinheiro ()
-fez sol mas não tenho dinheiro()
-choveu mas eu tenho dinheiro()
*/

let n1 =15;
let n2 =45;
//if - é o (se) else - é o (senão)

//se n1 for ig
if(n1=10){
    console.log("irei à praia!")
}else{
    console.log("fico em casa!");
}
//se n1 for maior que 10
if(n1>10){
    console.log("irei à praia!")
}else{
    console.log("fico em casa!");
}
//se n1 for maior que 10 e n2 for menor que 40
if(n1>10 & n2<40){
    console.log("irei à praia!")
}else{
    console.log("fico em casa!"); //ele fica e casa
}