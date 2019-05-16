<?php

use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

$mini=new MiniLang([],[]);

$mini->separate("when field1=1 then field2=2");


class DummyClassExample {
	var $values=[];
	public function test() {
		return 1;
	}
}
$caller=new DummyClassExample();
$caller->values=['field1'=>1,'field2'=>0];

if ($mini->evalLogic($caller,$caller->values)) {
	$mini->evalSet($caller,$caller->values);
}



var_dump($caller->values['field2']);

$result=['field1'=>1,'field2'=>0];
$callback=new stdClass();
$mini->evalAllLogic($callback,$result);
var_dump($result);