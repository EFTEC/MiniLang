<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini=new MiniLang($callback,$variables);
$mini->separate("when true=true then field1=timer()"); // we prepare the language

$mini->evalAllLogic(true); // we set the variables and run the language
var_dump($variables);