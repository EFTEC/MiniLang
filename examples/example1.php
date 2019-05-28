<?php

use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

class DummyClassExample {
	var $values=[];
	public function test() {
		return 1;
	}
}
$caller=new DummyClassExample();
$caller->values=['field1'=>1,'field2'=>0];

$mini=new MiniLang($caller,$caller->values);

$mini->separate("when field1=1 then field2=2");
$mini->separate("when field1=2 then field2=4");


if ($mini->evalLogic()) {
	$mini->evalSet();
}


var_dump($caller->values['field2']);


$mini->evalAllLogic();
var_dump($caller->values);