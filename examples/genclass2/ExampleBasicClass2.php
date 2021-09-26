<?php
use eftec\minilang\MiniLang;
// this class is generated!
class ExampleBasicClass2 extends MiniLang {
	protected $numCode=3; // num of lines of code 
	public function RunAll($stopOnFound=true) {
		for($i=0;$i<$this->numCode;$i++) {
			$r=$this->Code($i);
			if($r && $stopOnFound) break;
		}
	}
	public function Code($lineCode=0) {
		$_foundIt=false;
		switch($lineCode) {
			case 0:
				if ($this->dict['field1']['id']>0 && $this->dict['field1']['id']<10) {
					$_foundIt=true;
					$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
					$this->callFunction('processcaller',[$this->dict['field3']]);
					$this->callFunction('processservice',[$this->dict['field3']]);
					
				}
				break;
			case 1:
				if ($this->dict['field1']['id']>=10 && $this->dict['field1']['id']<20) {
					$_foundIt=true;
					$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
					$this->callFunction('processcaller',[$this->dict['field3']]);
					$this->callFunction('processservice',[$this->dict['field3']]);
					
				}
				break;
			case 2:
				if ($this->dict['field1']['id']>=20 && $this->dict['field1']['id']<30) {
					$_foundIt=true;
					$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
					$this->callFunction('processcaller',[$this->dict['field3']]);
					$this->callFunction('processservice',[$this->dict['field3']]);
					
				}
				break;
			default:
				$this->errorLog[]='Line '.$lineCode.' is not defined';
		}
		return $_foundIt;
	} // end function Code
} // end class
