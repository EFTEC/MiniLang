<?php
use eftec\minilang\MiniLang;

/**
* This class has the motor and definitions of the Mini Language.
*.
* @package .
* @generated by https://github.com/EFTEC/MiniLang.
* @version 2.21 2022-08-13T19:40:34-04:00.
*/
class ExampleBasicClass2 extends MiniLang {
	public $numCode=3; // num of lines of code 
	public $usingClass=true; // if true then we are using a class (this class) 
	public function whereRun($lineCode=0):bool {
		switch($lineCode) {
			case 3:
				return true; // nothing to do
			case 0:
				$result=$this->dict['field1']['id']>'0' && $this->dict['field1']['id']<10;
				break;
			case 1:
				$result=$this->dict['field1']['id']>=10 && $this->dict['field1']['id']<20;
				break;
			case 2:
				$result=$this->dict['field1']['id']>=20 && $this->dict['field1']['id']<30;
				break;
			default:
				$result=false;
				$this->throwError('Line '.$lineCode.' is not defined');
		}
		return $result;
	} // end function WhereRun
	public function loopRun($lineCode=0):?array {
		switch($lineCode) {
			case 0:
				$result=null;
				break;
			case 1:
				$result=null;
				break;
			case 2:
				$result=null;
				break;
			case 3:
				$result=null;
				break;
			default:
				$result=null;
				$this->throwError('Line '.$lineCode.' is not defined');
		}
		return $result;
	} // end function loopRun
	public function setRun($lineCode=0) {
		$result=null;
		switch($lineCode) {
			case 0:
				$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
				$this->callFunction('processcaller',[$this->dict['field3']]);
				$this->callFunction('processservice',[$this->dict['field3']]);
				break;
			case 1:
				$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
				$this->callFunction('processcaller',[$this->dict['field3']]);
				$this->callFunction('processservice',[$this->dict['field3']]);
				break;
			case 2:
				$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
				$this->callFunction('processcaller',[$this->dict['field3']]);
				$this->callFunction('processservice',[$this->dict['field3']]);
				break;
			case 3:
				$this->dict['field2']['value']=$this->callFunction('param',[$GLOBALS['a'],'a.b.c']);
				$this->callFunction('processcaller',[$this->dict['field3']]);
				$this->callFunction('processservice',[$this->dict['field3']]);
				break;
			default:
				$this->throwError('Line '.$lineCode.' is not defined for set');
		}
		return $result;
	} // end function setRun
	public function elseRun($lineCode=0) {
		$result=null;
		switch($lineCode) {
			case 0:
			case 1:
			case 2:
			case 3:
				break; // nothing to do
			default:
				$this->throwError('Line '.$lineCode.' is not defined for else');
		}
		return $result;
	} // end function elseRun
	public function initRun($lineCode=0) {
		$result=null;
		switch($lineCode) {
			case 0:
			case 1:
			case 2:
			case 3:
				break; // nothing to do
			default:
				$this->throwError('Line '.$lineCode.' is not defined for init');
		}
		return $result;
	} // end function initRun
} // end class
