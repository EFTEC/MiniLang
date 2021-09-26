<?php
use eftec\minilang\MiniLang;
use ns\example\ExampleBasicClass;

include "../../lib/MiniLang.php";
include "ExampleBasicClass.php";

echo "<h1>Testing the class generates</h1>";

$result=['var1'=>'hello','var3'=>false,'comp'=>['f'=>false]];
var_dump($result);
echo "<br>";
$obj=new ExampleBasicClass(null,$result);
$obj->evalAllLogic();
var_dump($result);