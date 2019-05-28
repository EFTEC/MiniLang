<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";
$variables=[]; // local variables
$callback=new stdClass();
$field1=1; // global variable
$mini=new MiniLang($callback,$variables);
$mini->separate('when $field1>0 then $field1=3'); // we prepare the language

$mini->evalAllLogic(); // we set the variables and run the languageand run the language
var_dump($field1);