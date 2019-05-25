<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";


$mini=new MiniLang();
$mini->separate("when true=true then field1=timer()"); // we prepare the language
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the language
var_dump($variables);