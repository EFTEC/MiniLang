<?php
use eftec\minilang\MiniLang;

include "../../lib/MiniLang.php";

echo "<h1>Generating the class</h1>";


$mini=new MiniLang(null);
$mini->throwError=false;

$mini->separate2('when var1="hello" and comp.f=false() then var2="world" '); // if var1 is equals "hello" then var2 is set "world"
$mini->separate2('then var3="world2" ');
//$mini->separate('when $global1="hello" then $global2="world" and var3=false() '); // if var1 is equals "hello" then var2 is set "world"
file_put_contents('ExampleBasicClass.php'
    ,"<?php\n".
    "use eftec\minilang\MiniLang;\n".
    "// this class is generated!\n".
    $mini->generateClass2('ExampleBasicClass',false));
echo "<br>ExampleBasicClass.php generated";