<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";
$variables=['field1'=>12345,'field2'=>0]; // we define regular variables
$callback=new stdClass();

$mini=new MiniLang($callback,$variables);
$mini->separate("fieldtxt='123 {{field1}} {{field1}}' when field1>0 then field2+2"); // we prepare the language

echo "\n";
var_dump(end($mini->where));
echo "\n";
var_dump(end($mini->set));
echo "\n";
var_dump(end($mini->init));


$mini->evalAllLogic(false,true); // we set the variables and run the languageand run the language
var_dump($variables);