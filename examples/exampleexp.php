<?php

use eftec\minilang\MiniLang;

$p = new ReflectionParameter('str_replace', 1);
var_dump($p);
die(1);

include "../lib/MiniLang.php";

class DummyClassExample {
	var $values=[];
	public function test() {
		return 1;
	}
}
function myfn($v1,$v2,$v3) {
    return $v1.$v2.$v3;
}

$caller=new DummyClassExample();
$caller->values=['field1'=>1,'field2'=>0,'field3'=>123,'countries'=>['us'=>'usa','ca'=>'canada']];

$mini=new MiniLang($caller,$caller->values);
$n=2;
$arr=[10,20,30,40,'a'=>666];

$mini->when()
    ->compare('a','=','b')
    ->and()
    ->compare('a','=',['123','+','a'])
    ->then()
    ->set('a','=',20)
    ->end();

echo "<pre>";
var_dump($mini);
echo "</pre>";
die(1);

//$mini->separate('when true() then field2b=$arr');
$mini->separate('when true() and field1=555 and field3.ff=field4.ff2
then field1=xyz and fieldsum=123+456 and field3=321+789 and field4="xx" + "yy" and $field5=myfn(1,2,3) and $field6=a1.myfn(4,5) ');
$mini->separate("when field1=2 then field2=4");
//$mini->exp()->when('field1','=',2)->then('field2','=',4);


// pair fn true null: true()
// logic and : and
// pair field field1 1 null = number 555 : field1=555
// pair subfield field3 ff = subfield field4 ff2 field3.ff=field4.ff2
// pair var field3 null : $field3
// + number 1 null number 2 null = 1+2
// + number 1 null number 2 null number 3 null = 1+2+3
// fn function20 [ [number,1,null],[number,2,null],[number,3,null] ] = function(20,1,2,3)


echo "<br>where:<br>";
echo "<pre>";
//var_dump($mini->compileTokens('where'));
//var_dump($mini->compileTokens('set'));
echo "\n\nwhere:\n";
//var_dump($mini->where);
echo "\n\nset:\n";
//var_dump($mini->set);
echo "</pre>";
echo "<hr>";
//var_dump($mini->set);
//
//die(1);

if ($mini->evalLogic()) {
	$mini->evalSet();
}


//var_dump($caller->values['field2']);


$mini->evalAllLogic(true);
var_dump($caller->values);