<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";
$variables=['field1'=>0,'field2'=>0
    ,'fruits'=>['k1'=>'apple','k2'=>'pear','k3'=>'banana']
    ,'counters'=>[1,2,3,4]]; // we define regular variables
class SomeClass {
    public function printme($value,$value2) {
        echo "<br>value: #$value2 $value<br>";
    }
}

$callback=new SomeClass();

$mini->when('variable','=','20')->and('variable2','>',20)->set('variable3','=',50);



$mini=new MiniLang($callback,$variables);
$mini->separate("loop fruit=fruits");
$mini->separate("loop counter=counters");
$mini->separate("when field2=0 then field1=field1+1 and printme(fruit,counter)"); // we prepare the language
$mini->separate("loop end");
$mini->separate("loop end");
echo "<pre>";
echo "\n";
var_dump($mini->loop);
echo "\n";
var_dump($mini->where);
echo "\n";
var_dump($mini->set);
echo "\n";
var_dump($mini->init);



$mini->evalAllLogic(false,true); // we set the variables and run the languageand run the language
var_dump($variables);
echo "</pre>";