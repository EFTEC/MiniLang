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
$mini->separate('when true() and field1=555 and field3.ff=field4.ff 
then fieldsum=123+456 and field3=321+789 and field4="xx" + "yy" and $field5=myfn(1,2,3) and $field6=a1.myfn(4,5) ');
$mini->separate("when field1=2 then field2=4");


echo "<br>where:<br>";
echo "<pre>";
var_dump($mini->compileTokens('where'));
var_dump($mini->compileTokens('set'));
echo "\n\nwhere:\n";
var_dump($mini->where);
echo "\n\nset:\n";
var_dump($mini->set);
echo "</pre>";
echo "<hr>";
//var_dump($mini->set);
//
//die(1);

if ($mini->evalLogic()) {
	$mini->evalSet();
}


//var_dump($caller->values['field2']);


$mini->evalAllLogic();
var_dump($caller->values);