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
$caller->values=['field1'=>1,'field2'=>0,'field3'=>123,'countries'=>['us'=>'usa','ca'=>'canada']];

$mini=new MiniLang($caller,$caller->values);
$n=2;
$arr=[10,20,30,40,'a'=>666];

//$mini->separate('when true() then field2b=$arr');
$mini->separate('when true() then field3=countries.us');
$mini->separate("when field1=2 then field2=4");


if ($mini->evalLogic()) {
	$mini->evalSet();
}


//var_dump($caller->values['field2']);


$mini->evalAllLogic();
var_dump($caller->values);