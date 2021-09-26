<?php


use somenamespace\ExampleBasicClass2;

include "../../lib/MiniLang.php";
include "ExampleBasicClass2.php";
echo "<h1>Testing use of arrays using a generated class</h1>";

class ClassCaller {
	public function Processcaller($arg) {
		echo "Caller: setting the variable {$arg['id']}<br>";
	}
}
class ClassService {
	public function ProcessService($arg) {
		echo "Service: setting the variable {$arg['id']}<br>";
	}

}
$a['a']['b']['c']=123;

$variables=['field1'=>['id'=>1,'value'=>3]
	,'field2'=>['id'=>2,'value'=>'']
	,'field3'=>['id'=>3,'value'=>'']
    ,'arrnum'=>['hello','world','abc'=>'123']];
$callback=new ClassCaller();
$mini=new ExampleBasicClass2($callback,$variables,[],[],new ClassService());

$mini->RunAll();

echo "<pre>";
var_dump($variables);
echo "</pre>";
