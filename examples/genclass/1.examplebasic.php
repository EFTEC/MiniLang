<?php
use eftec\minilang\MiniLang;

include "../../lib/MiniLang.php";

echo "<h1>Generating the class</h1>";


$mini=new MiniLang(null);
$mini->throwError=false;

$mini->separate2('when var1="hello" and comp.f=false() then var2="world" '); // if var1 is equals "hello" then var2 is set "world"
$mini->separate2('then var3="world2" ');

$r=$mini->generateClass('ExampleBasicClass','ns\example','ExampleBasicClass.php');
if(!$r) {
    echo "unable to save file<br>";
    var_dump($mini->errorLog);
}


echo "<br>ExampleBasicClass.php generated";