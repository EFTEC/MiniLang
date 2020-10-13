<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

$result=['var1'=>'hello'];
$global1="hello";
$mini=new MiniLang(null,$result);
$mini->throwError=false;

$mini->separate('when var1="hello" then var2="world" '); // if var1 is equals "hello" then var2 is set "world"
$mini->separate('when $global1="hello" then $global2="world" '); // if var1 is equals "hello" then var2 is set "world"


echo "<hr>run:<br>";
$mini->evalAllLogic(false);
echo "<hr>result:<br>";
var_dump($result);
echo "<br>";
var_dump($global2);

echo "<hr>Errors:<br>";
var_dump($mini->errorLog);