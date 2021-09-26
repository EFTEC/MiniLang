<?php
use eftec\minilang\MiniLang;
// this class is generated!
class ExampleBasicClass extends MiniLang {
	protected $numCode=2; // num of lines of code 
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
				if ($this->dict['var1']=='hello' && $this->dict['comp']['f']==$this->callFunction('false',[])) {
					$_foundIt=true;
					$this->dict['var2']='world';
					
				}
				break;
			case 1:
				$_foundIt=true;
				$this->dict['var3']='world2';
					
				break;
			default:
				$this->errorLog[]='Line '.$lineCode.' is not defined';
		}
		return $_foundIt;
	} // end function Code
} // end class
