<?php
namespace eftec\minilang;


/**
 * It's a mini language parser. It uses the build-in token_get_all function to performance purpose.
 * Class MiniLang
 * @package eftec\minilang
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 2.0 2019-05-25
 * * now function allows parameters fnname(1,2,3)
 * * now set allows operators (+,-,*,/). set field=a1+20+40
 * @link https://github.com/EFTEC/MiniLang
 * @license LGPL v3 (or commercial if it's licensed)
 */
class MiniLang
{
	/**
	 * When operators (if any)
	 * @var array
	 */
	var $where=[];
	/**
	 * Set operators (if any
	 * @var array
	 */
	var $set=[];
	/**
	 * Set operators (if any
	 * @var array
	 */
	var $init=[];
	private $specialCom=[];
	private $areaName=[];
	/** @var array values per the special area */
	var $areaValue=[];
	var $serviceClass=null;
	/** @var object for callbacks */
	private $caller;


	/** @var array */
	private $dict;
	
	private $langCounter=0;

	/**
	 * MiniLang constructor.
	 * @param object $caller
	 * @param array $dict
	 * @param array $specialCom Special commands. it calls a function of the caller.
	 * @param array $areaName It marks special areas that could be called as "<namearea> somevalue"
	 * @param null $serviceClass
	 */
	public function __construct(&$caller,&$dict,array $specialCom=[],$areaName=[],$serviceClass=null)
	{
		$this->specialCom = $specialCom;
		$this->areaName=$areaName;
		$this->serviceClass=$serviceClass;
		$this->dict=&$dict;
		$this->caller=&$caller;
		$this->langCounter=-1;
		$this->where=[];
		$this->set=[];
		$this->init=[];
	}


	public function reset() {


		//$this->areaName=[];
		//$this->areaValue=[];
	}

	/**
	 * @param object $caller
	 */
	public function setCaller(&$caller)
	{
		$this->caller = $caller;
	}

	/**
	 * @param array $dict
	 */
	public function setDict(&$dict)
	{
		$this->dict = &$dict;
	}
	/**
	 * @param $text
	 */
	public function separate($text) {
		$this->reset();
		$this->langCounter++;

		$this->where[$this->langCounter]=[];
		$this->set[$this->langCounter]=[];
		$rToken=token_get_all("<?php ".$text);
		/*echo "<pre>";
		var_dump($rToken);
		echo "</pre>";*/
		//die(1);
		$rToken[]=''; // avoid last operation
		$count=count($rToken)-1;
		$first=true;
		$inFunction=false;
		/** @var  string $position=['where','set','init'][$i] */
		$position='init';
		for($i=0;$i<$count;$i++) {
			$v=$rToken[$i];
			if(is_array($v)) {
				switch ($v[0]) {
					case T_CONSTANT_ENCAPSED_STRING:
						$txt=substr($v[1],1,-1);
						if (strpos($txt,'{{')===false) {
							$this->addBinOper($first,$position,$inFunction,'string'
								,substr($v[1],1,-1),null);
						} else {
							$this->addBinOper($first,$position,$inFunction,'stringp'
								,substr($v[1],1,-1),null);
						}
						break;
					case T_VARIABLE:
						if (is_string($rToken[$i+1]) && $rToken[$i+1]=='.') {
							// $var.vvv
							$this->addBinOper($first,$position,$inFunction,'subvar'
								,substr($v[1],1),$rToken[$i+2][1]);
							$i+=2;
						} else {
							// $var
							$this->addBinOper($first,$position,$inFunction,'var'
								,substr($v[1],1),null);
						}
						break;
					case T_LNUMBER:
					case T_DNUMBER:
						$this->addBinOper($first,$position,$inFunction, 'number'
							, $v[1], null);
						break;
					case T_STRING:
						if (in_array($v[1],$this->areaName)) {
							// its an area. <area> <somvalue>
							if (count($rToken)>$i+2) {
								$tk=$rToken[$i + 2];

								switch ($tk[0]) {
									case T_VARIABLE:
										$this->areaValue[$v[1]]=['var',$tk[1],null];
										break;
									case T_STRING:
										$this->areaValue[$v[1]]=['field',$tk[1],null];
										break;
									case T_LNUMBER:
										$this->areaValue[$v[1]]=$tk[1];
										break;
								}
							}
							$i+=2;
						} else {
							switch ($v[1]) {
								case 'init':
									//adding a new init
									$position='init';
									$first=true;
									break;
								case 'where':
								case 'when':
									// adding a new when
									$position='where';
									$first=true;
									break;
								case 'then':
								case 'set':
									//adding a new set
									$position='set';
									$first=true;
									break;
								default:
									if (is_string($rToken[$i + 1])) {
										if ($rToken[$i + 1] == '.') {
											// field.vvv
											$this->addBinOper($first,$position,$inFunction, 'subfield', $v[1], $rToken[$i + 2][1]);
											$i += 2;
										} elseif ($rToken[$i + 1] == '(') {
											// function()
											$this->addBinOper($first,$position,$inFunction, 'fn', $v[1], null);
											$inFunction=true;
											$i+=1;
										} else {
											// field
											if (in_array($v[1], $this->specialCom)) {
												$this->addBinOper($first,$position,$inFunction, 'special', $v[1], null);
												$first = true;
											} else {
												$this->addBinOper($first,$position,$inFunction, 'field', $v[1], null);
											}

										}
									} else {
										// field
										if (in_array($v[1], $this->specialCom)) {
											$this->addBinOper($first,$position,$inFunction, 'special', $v[1], null);
											$first = true;
										} else {
											$this->addBinOper($first,$position,$inFunction, 'field', $v[1], null);
										}
									}
									break;
							}
						}
						break;
					case T_IS_EQUAL:
						$this->addOp($position,$first,'=');
						break;
					case T_IS_GREATER_OR_EQUAL:
						$this->addOp($position,$first,'>=');
						break;
					case T_IS_SMALLER_OR_EQUAL:
						$this->addOp($position,$first,'<=');
						break;
					case T_IS_NOT_EQUAL:
						$this->addOp($position,$first,'<>');
						break;
					case T_LOGICAL_AND:
					case T_BOOLEAN_AND:
						if ($position!='where') {
							$first=true;
						} else {
							$this->addLogic($position, $first, 'and');
						}
						break;
					case T_BOOLEAN_OR:
					case T_LOGICAL_OR:
						$this->addLogic($position,$first,'or');
						break;
				}
			} else {
				switch ($v) {
					case '-':
						if (is_array($rToken[$i+1]) && ($rToken[$i+1][0]==T_LNUMBER || $rToken[$i+1][0]==T_DNUMBER )) {
							// it's a negative value
							$this->addBinOper($first,$position,$inFunction, 'number', -$rToken[$i+1][1], null);
							$i++;
						} else {
							// its a minus
							$this->addOp($position, $first, $v);
						}
						break;
					case ')':
						$inFunction=false;
						break;
					case ',':
						if (!$inFunction) {
							if ($position!='where') {
								$first = true;
							} else {
								$this->addLogic($position, $first, ',');
							}
						}
						break;
					case '=':
					case '+':
					case '*':
					case '/':
					case '<':
					case '>':
						$this->addOp($position,$first,$v);
						break;
				}
			}
		}
	}

	/**

	 * @param int $idx
	 * @return bool|string it returns the evaluation of the logic or it returns the value special (if any).
	 */
	public function evalLogic($idx=0) {
		$prev=true;
		$r=false;
		$addType='';
		foreach($this->where[$idx] as $k=> $v) {
			if($v[0]==='pair') {
				if ($v[1]=='special') {

					if (count($v)>=7) {
						return $this->caller->{$v[2]}($v[6]);
					} else {
						return $this->caller->{$v[2]}();
					}
				}
				$field0=$this->getValue($v[1],$v[2],$v[3]);
				if (count($v)>=8) {
					$field1 = $this->getValue($v[5], $v[6], $v[7]);
				} else {
					$field1=null;
				}
				switch ($v[4]) {
					case '=':
						$r = ($field0 == $field1);
						break;
					case '<>':
						$r = ($field0 != $field1);
						break;
					case '<':
						$r = ($field0 < $field1);
						break;
					case '<=':
						$r = ($field0 <= $field1);
						break;
					case '>':
						$r = ($field0 > $field1);
						break;
					case '>=':
						$r = ($field0 >= $field1);
						break;
					case 'contain':
						$r = (strpos($field0, $field1) !== false);
						break;
					default:
						trigger_error("comparison {$v[4]} not defined for eval logic.");
				}
				switch ($addType) {
					case 'and':
						$r=$prev && $r;
						break;
					case 'or':
						$r=$prev || $r;
						break;
					case '':
						break;
				}
				$prev=$r;
			} else {
				// logic
				$addType=$v[1];
			}
		} // for
		return $r;
	}

	/**
	 * It evaluates all logic and sets if the logic is true
	 * @param bool $stopOnFound exit if some evaluation matches
	 * @param bool $start if true then it evaluates the "init" expression.
	 */
	public function evalAllLogic($stopOnFound=true,$start=false) {
		for($i=0; $i<=$this->langCounter; $i++) {
			if ($start) {
				$this->evalSet($i,'init');
			}
			if ($this->evalLogic($i)) {
			
				$this->evalSet($i);
			
				if ($stopOnFound) break;
			}
		}
	}

	/**
	 * It sets a value.
	 * @param int $idx
	 * @param string $position=['set','init][$i]
	 * @return void
	 */
	public function evalSet($idx=0,$position='set') {
		$exp=($position=='set')? $this->set[$idx] : $this->init[$idx];
		
		foreach($exp as $k=>$v) {
			if($v[0]==='pair') {
				$name=$v[2];
				$ext=$v[3];
				$op=@$v[4];
				//$field0=$this->getValue($v[1],$v[2],$v[3],$this->caller,$dictionary);
				if (count($v)>5) {
					$field1 = $this->getValue($v[5], $v[6], $v[7]);
				} else {
					$field1=null;
				}
				for($i=8;$i<count($v);$i+=4) {
					switch ($v[$i]) {
						case '+': // if we add numbers then it adds, otherwise it concatenates.
							$field2=$this->getValue($v[$i+1], $v[$i+2], $v[$i+3]);
							if (is_numeric($field1) && is_numeric($field2)) {
								$field1 += $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
							} else {
								$field1 .= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
							}
							break;
						case '-':
							$field1 -= $this->getValue($v[$i+1], $v[$i+2], $v[$i+3]);
							break;
						case '*':
							$field1 *= $this->getValue($v[$i+1], $v[$i+2], $v[$i+3]);
							break;
						case '/':
							$field1 /= $this->getValue($v[$i+1], $v[$i+2], $v[$i+3]);
							break;
					}
				}
				if ($field1==='___FLIP___') {
					$field0=$this->getValue($v[1],$v[2],$v[3]);
					$field1=(!$field0)?1:0;
				}
				switch ($v[1]) {
					case 'subvar':
						// $a.field
						$rname=@$GLOBALS[$name];
						if (is_object($rname)) {
							$rname->{$ext}=$field1;
						} else {
							$rname[$ext]=$field1;
						}
						break;
					case 'var':
						// $a
						switch ($op) {
							case '=':
								$GLOBALS[$name]=$field1;
								break;
							case '+';
								$GLOBALS[$name]+=$field1;
								break;
							case '-';
								$GLOBALS[$name]-=$field1;
								break;
						}
						break;
					case 'number':
					case 'string':
					case 'stringp':
						trigger_error("comparison {$v[4]} not defined for transaction.");
						break;
					case 'field':
						switch ($op) {
							case '=':
								$this->dict[$name]=$field1;
								break;
							case '+';
								$this->dict[$name]+=$field1;
								break;
							case '-';
								$this->dict[$name]-=$field1;
								break;
						}
						break;
					case 'subfield':
						$args=[$this->dict[$name]];
						$this->callFunctionSet($ext,$args,$field1);
						break;
					case 'fn':
						// function name($this->caller,$somevar);
						$args=[];
						if ($ext!==null) {
							foreach ($ext as $e) {
								$args[] = $this->getValue($e[0], $e[1], $e[2]);
							}
						}
						$this->callFunctionSet($name,$args,$field1);
						break;
					default:
						trigger_error("set {$v[4]} not defined for transaction.");
						break;
				}
			}
		} // for
	}
	/**
	 * It calls a function predefined by the caller. Example var.myfunction or somevar.value=myfunction(arg,arg)
	 * @param $nameFunction
	 * @param $args
	 * @return mixed (it could returns an error if the function fails)
	 */
	private function callFunction($nameFunction, $args) {
		if (count($args)===1 ) {
			if (is_object($args[0])) {
				// the call is the form nameFunction(somevar) or somevar.nameFunction()
				if (isset($args[0]->{$nameFunction})) {
					// someobject.field (nameFunction acts as a field name)
					return $args[0]->{$nameFunction};
				}
			}
			if (is_array($args[0])) {
				// the call is the form nameFunction(somevar) or somevar.nameFunction()
				if (isset($args[0][$nameFunction])) {
					// someobject.field (nameFunction acts as a field name)
					return $args[0][$nameFunction];
				}
			}
		}
		if (is_object($this->caller)) {
			if(method_exists($this->caller,$nameFunction)) {
				return call_user_func_array(array($this->caller,$nameFunction),$args);
			}
			if (isset($this->caller->{$nameFunction})) {
				return $this->caller->{$nameFunction};
			}
		} else {
			if(is_array($this->caller)) {
				if(isset($this->caller[$nameFunction])) {
					return $this->caller[$nameFunction];
				}
			}
		}
		if(method_exists($this->serviceClass,$nameFunction)) {
			return call_user_func_array(array($this->serviceClass, $nameFunction), $args);
		} else {
			trigger_error("function [$nameFunction] is not defined");
			return false;
		}
	}

	/**
	 * @param $nameFunction
	 * @param $args
	 * @param $setValue
	 * @return void
	 */
	private function callFunctionSet($nameFunction, $args, $setValue) {
		if (count($args)===1 ) {
			if (is_object($args[0])) {
				// the call is the form nameFunction(somevar)=1 or somevar.nameFunction()=1
				if (isset($args[0]->{$nameFunction})) {
					// someobject.field (nameFunction acts as a field name
					$args[0]->{$nameFunction} = $setValue;
					return;
				}
			}
			if (is_array($args[0])) {
				// the call is the form nameFunction(somevar)=1 or somevar.nameFunction()=1
				if (isset($args[0][$nameFunction])) {
					// someobject.field (nameFunction acts as a field name
					$args[0][$nameFunction] = $setValue;
					return;
				}
			}
		}
		if (is_object($this->caller)) {
			if(method_exists($this->caller,$nameFunction)) {
				$args[]=$setValue; // it adds a second parameter
				call_user_func_array(array($this->caller,$nameFunction),$args);
				return;

			} elseif (isset($this->caller->{$nameFunction})) {
				$this->caller->{$nameFunction}=$setValue;
				return;
			}
		} else {
			if(is_array($this->caller)) {
				if(isset($this->caller[$nameFunction])) {
					$this->caller[$nameFunction]=$setValue;
					return;
				}
			}
		}
		call_user_func_array(array($this->serviceClass,$nameFunction),$args);
	}

	/**
	 * It obtains a value.
	 * @param string $type=['subvar','var','number','string','stringp','field','subfield','fn','special'][$i]
	 * @param string $name name of the value. It is also used for the value of the variable. 
	 * <p> myvar => type=var, name=myvar</p>
	 * <p> 123 => type=number, name=123</p>
	 * @param string|array $ext it is used for subvar, subfield and functions
	 * @return bool|int|mixed|string|null
	 */
	public function getValue($type,$name,$ext) {
		$r=0;
		switch ($type) {
			case 'subvar':
				// $a.field
				$rname=@$GLOBALS[$name];
				$r=(is_object($rname))?$rname->{$ext}:$rname[$ext];
				break;
			case 'var':
				// $a
				$r=@$GLOBALS[$name];
				break;
			case 'number':
				// 20
				$r=$name;
				break;
			case 'string':
				// 'aaa',"aaa"
				$r=$name;
				break;
			case 'stringp':
				// 'aaa',"aaa"
				$r=$this->getValueP($name);

				break;
			case 'field':
				$r=@$this->dict[$name];
				break;
			case 'subfield':
				// field.sum is equals to sum(field)
				$args=[@$this->dict[$name]];
				$r=$this->callFunction($ext,$args);
				break;
			case 'fn':
				switch ($name) {
					case 'null':
						return null;
					case 'false':
						return false;
					case 'true':
						return true;
					case 'on':
						return 1;
					case 'off':
						return 0;
					case 'undef':
						return -1;
					case 'flip':
						return "___FLIP___"; // value must be flipped (only for set).
					case 'now':
					case 'timer':
						return time();
					case 'interval':
						if (isset($this->caller->dateLastChange)) {
							return time() - $this->caller->dateLastChange;
						}
						if (method_exists($this->caller,'dateLastChange')) {
							return time() - $this->caller->dateLastChange();
						}
						trigger_error("caller doesn't define field or method dateLastChange");
						break;
					case 'fullinterval':
						if (isset($this->caller->dateInit)) {
							return time() - $this->caller->dateInit;
						}
						if (method_exists($this->caller,'dateInit')) {
							return time() - $this->caller->dateInit();
						}
						trigger_error("caller doesn't define field or method dateInit");
						break;
					default:
						$args=[];
						foreach($ext as $e) {
							$args[]=$this->getValue($e[0],$e[1],$e[2]);
						}
						return $this->callFunction($name,$args);
				}
				break;
			case 'special':
				return $name;
				break;
			default:
				trigger_error("value with type[$type] not defined");
				return null;
		}
		return $r;
	}
	public function getValueP($string)
	{
		return preg_replace_callback('/\{\{\s?(\w+)\s?\}\}/u', function($matches) {
			if(is_array($matches)) {
				$item=substr($matches[0],2,strlen($matches[0])-4); // removes {{ and }}
				return @$this->dict[$item];
			} else {
				$item=substr($matches,2,strlen($matches)-4); // removes {{ and }}
				return @$this->dict[$item];
			}
		}, $string);
	}

	/**
	 * It adds part of a pair of operation.
	 * @param bool $first if it is the first part or second part of the expression.
	 * @param string $position=['where','set','init'][$i]
	 * @param bool $inFunction
	 * @param string $type =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
	 * @param string $name name of the field
	 * @param null|string $ext extra parameter.
	 */
	private function addBinOper(&$first, $position, $inFunction, $type, $name, $ext=null) {
		if ($inFunction) {
			$this->addParam($position,$type, $name, $ext);
			return;
		}
		if ($first) {
			switch ($position) {
				case 'where':
					$this->where[$this->langCounter][] = ['pair', $type, $name, $ext];
					break;
				case 'set':
					$this->set[$this->langCounter][] = ['pair', $type, $name, $ext];
					break;
				case '':
				case 'init':
					$this->init[$this->langCounter][] = ['pair', $type, $name, $ext];
					break;
			}
		} else {

			switch ($position) {
				case 'where':
					$f=count($this->where[$this->langCounter])-1;
					$f2=count($this->where[$this->langCounter][$f]);
					$this->where[$this->langCounter][$f][$f2]=$type;
					$this->where[$this->langCounter][$f][$f2+1]=$name;
					$this->where[$this->langCounter][$f][$f2+2]=$ext;
					$first=true;
					break;
				case 'set':
					$f=count($this->set[$this->langCounter])-1;
					$f2=count($this->set[$this->langCounter][$f]);
					$this->set[$this->langCounter][$f][$f2]=$type;
					$this->set[$this->langCounter][$f][$f2+1]=$name;
					$this->set[$this->langCounter][$f][$f2+2]=$ext;
					break;
				case '':
				case 'init':
					$f=count($this->init[$this->langCounter])-1;
					$f2=count($this->init[$this->langCounter][$f]);
					$this->init[$this->langCounter][$f][$f2]=$type;
					$this->init[$this->langCounter][$f][$f2+1]=$name;
					$this->init[$this->langCounter][$f][$f2+2]=$ext;
					break;
			}
		}
	}

	/**
	 * Add params of a function
	 * @param string $position=['where','set','init'][$i]
	 * @param string $type =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
	 * @param string $name name of the field
	 * @param null|string $ext extra parameter.
	 */
	private function addParam($position,$type, $name, $ext=null) {
		switch ($position) {
			case 'where':
				$f = count($this->where[$this->langCounter]) - 1;
				$idx = count($this->where[$this->langCounter][$f]) - 1;
				if (!isset($this->where[$this->langCounter][$f][$idx])) {
					$this->where[$this->langCounter][$f][$idx] = [];

				}
				$this->where[$this->langCounter][$f][$idx][] = [$type, $name, $ext];
				break;
			case 'set':
				$f = count($this->set[$this->langCounter]) - 1;
				$idx = count($this->set[$this->langCounter][$f]) - 1;
				if (!isset($this->set[$this->langCounter][$f][$idx])) {
					$this->set[$this->langCounter][$f][$idx] = [];

				}
				$this->set[$this->langCounter][$f][$idx][] = [$type, $name, $ext];
				break;
			case '':
			case 'init':
				$f = count($this->init[$this->langCounter]) - 1;
				$idx = count($this->init[$this->langCounter][$f]) - 1;
				if (!isset($this->init[$this->langCounter][$f][$idx])) {
					$this->init[$this->langCounter][$f][$idx] = [];
	
				}
				$this->init[$this->langCounter][$f][$idx][] = [$type, $name, $ext];
				break;
		}
	}

	/**
	 * It adds an operation (such as =,<,+,etc.)
	 * @param string $position=['where','set','init'][$i]
	 * @param bool $first If it's true then it is the first value of a binary
	 * @param string $opName
	 */
	private function addOp($position, &$first, $opName) {
		switch ($position) {
			case 'where':
				if ($first) {
					$f = count($this->where[$this->langCounter]) - 1;
					$this->where[$this->langCounter][$f][4] = $opName;
					$first = false;
				} else {
					$f = count($this->where[$this->langCounter]) - 1;
					$this->where[$this->langCounter][$f][] = $opName;
				}
				break;
			case 'set':
				if ($first) {
					$f = count($this->set[$this->langCounter]) - 1;
					$this->set[$this->langCounter][$f][4] = $opName;
					$first = false;
				} else {
					$f = count($this->set[$this->langCounter]) - 1;
					$this->set[$this->langCounter][$f][] = $opName;
				}
				break;
			case '':
			case 'init':
				if ($first) {
					$f = count($this->init[$this->langCounter]) - 1;
					$this->init[$this->langCounter][$f][4] = $opName;
					$first = false;
				} else {
					$f = count($this->init[$this->langCounter]) - 1;
					$this->init[$this->langCounter][$f][] = $opName;
				}
				break;
		}
		
	}

	/**
	 * It adds a logic
	 * @param string $position=['where','set','init'][$i]
	 * @param bool $first If it's true then it is the first value of a binary
	 * @param string $name name of the logic
	 */
	private function addLogic($position, &$first, $name) {
		if ($first) {
			switch ($position) {
				case 'where':
					$this->where[$this->langCounter][] = ['logic', $name];
					break;
				case 'set':
					$this->set[$this->langCounter][] = ['logic', $name];
					break;
				case '':
				case 'init':
					$this->init[$this->langCounter][] = ['logic', $name];
					break;
			}
		} else {
			trigger_error("Error: Logic operation in the wrong place");
		}
		
	}
}