<?php

use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

$mini=new MiniLang([],[]);
$mini->separate('when level=0 then level=1 and position=0'); // inicializa.
$mini->separate("when level=1 and findnext(p1,p2)=true then level=2");
var_dump($mini->logic);
//$mini->separate("when level=2 and position.findnext()>0 then level=2");
//$mini->separate("when field1=1 then field1.readnext()>0 and field2=2");

class SpiderClass {
	var $values=[];
	var $position=0;
	public function test() {
		return 1;
	}
	public function readnext($fields=null) {
		echo "hello world $fields";
	}
	public function findnext($p1,$p2) {
		echo "find position $p1 $p2<br>";
	}
	public function nextposition($findme) {
		return -1;
	}
	public function downloadfile($filename) {
		return "";
	}
	public function loadpage($page) {
		return "";
	}
	
}
$caller=new SpiderClass();
$caller->values=['p1'=>1,'p2'=>2,'field1'=>1,'level'=>0,'position'=>0];

$mini->evalAllLogic($caller,$caller->values);
