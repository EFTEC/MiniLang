<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

class MyModel {
	var $id=1;
	var $value="";
	
	public function __construct($id=0, $value="")
	{
		$this->id = $id;
		$this->value = $value;
	}
}
class ClassCaller {
	public function Processcaller($arg) {
		echo "Caller: setting the variable {$arg->id}<br>";
	}
}
class ClassService {
	public function ProcessService($arg) {
		echo "Service: setting the variable {$arg->id}<br>";
	}
}

$mini=new MiniLang([],[],new ClassService());
$mini->separate("when field1.id>0 then 
				field2.value=3 
				and field3.processcaller 
				and processcaller(field3) 
				and processservice(field3)"); // we prepare the language

$variables=['field1'=>new MyModel(1,"hi")
			,'field2'=>new MyModel(2,'')
			,'field3'=>new MyModel(3,'')]; // we define regular variables
$callback=new ClassCaller();
$mini->evalAllLogic($callback,$variables,false); // we set the variables and run the languageand run the language
echo "<pre>";
var_dump($variables);
echo "</pre>";