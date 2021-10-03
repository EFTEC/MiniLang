<?php
use eftec\minilang\MiniLang;
use ns\example\ExampleBasicClass;

include "../../lib/MiniLang.php";
include "ExampleBasicClass.php";

echo "<h1>Testing the class generates</h1>";

$result=['var1'=>'hello','var3'=>false,'comp'=>['f'=>false],'values'=>['alpha','beta'],'counter'=>0];
echo "<pre>Initial:\n";
var_dump($result);
echo "</pre>";
echo "<br>";
$obj=new ExampleBasicClass(null,$result);
$obj->evalAllLogic(false);
echo "<pre>Final:\n";
var_dump($result);
echo "</pre>";
