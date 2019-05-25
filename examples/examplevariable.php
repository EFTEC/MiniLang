<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

$mini=new MiniLang();
$mini->separate("when field1>0 then field2=3"); // we prepare the language
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the languageand run the language
var_dump($variables);