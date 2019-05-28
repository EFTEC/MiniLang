<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";


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
$variables=['field1'=>['id'=>1,'value'=>3]
	,'field2'=>['id'=>2,'value'=>'']
	,'field3'=>['id'=>3,'value'=>'']];
$callback=new ClassCaller();
$mini=new MiniLang($callback,$variables,[],[],new ClassService());
$mini->separate("when field1.id>0 then 
				field2.value=3 
				and field3.processcaller 
				and processcaller(field3) 
				and processservice(field3)"); 


$mini->evalAllLogic(false);
var_dump($variables);
