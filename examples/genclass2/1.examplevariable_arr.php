<?php
use eftec\minilang\MiniLang;

include "../../lib/MiniLang.php";
echo "<h1>Testing use of arrays using a generated class</h1>";


$mini=new MiniLang();

$mini->separate2("when field1.id>0 and field1.id<10 then 
                field2.value=\$a.param('a.b.c')
				and processcaller(field3) 
				and processservice(field3)");
$mini->separate2("when field1.id>=10 and field1.id<20 then 
                field2.value=\$a.param('a.b.c')
				and processcaller(field3) 
				and processservice(field3)");
$mini->separate2("when field1.id>=20 and field1.id<30 then 
                field2.value=\$a.param('a.b.c')
				and processcaller(field3) 
				and processservice(field3)");
$mini->separate2("then 
                field2.value=\$a.param('a.b.c')
				and processcaller(field3) 
				and processservice(field3)");

$mini->generateClass('ExampleBasicClass2','','ExampleBasicClass2.php');

echo "<b>Class ExampleBasicClass2 generated</b> ";