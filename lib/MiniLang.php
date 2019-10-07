<?php

namespace eftec\minilang;

/**
 * It's a mini language parser. It uses the build-in token_get_all function to performance purpose.
 * Class MiniLang
 *
 * @package  eftec\minilang
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  2.10 2019-10-07
 * @link     https://github.com/EFTEC/MiniLang
 * @license  LGPL v3 (or commercial if it's licensed)
 */
class MiniLang
{
    /**
     * When operators (if any)
     *
     * @var array
     */
    var $where = [];
    /**
     * Set operators (if any)
     *
     * @var array
     */
    var $set = [];
    /**
     * Set operators (if any)
     *
     * @var array
     */
    var $else = [];
    /**
     * Init operators (if any)
     *
     * @var array
     */
    var $init = [];
    private $specialCom = [];
    private $areaName = [];
    /** @var array values per the special area */
    var $areaValue = [];
    var $serviceClass = null;
    /** @var object for callbacks */
    private $caller;

    /** @var array */
    private $dict;
    // for runtime:

    /** @var string current field name */
    private $currFieldName = "";
    private $langCounter = 0;

    /**
     * MiniLang constructor.
     *
     * @param object      $caller
     * @param array       $dict
     * @param array       $specialCom Special commands. it calls a function of the caller.
     * @param array       $areaName   It marks special areas that could be called as "<namearea> somevalue"
     * @param null|object $serviceObject
     */
    public function __construct($caller, &$dict, array $specialCom = [], $areaName = [], $serviceObject = null)
    {
        $this->specialCom = $specialCom;
        $this->areaName = $areaName;
        $this->serviceClass = $serviceObject;
        $this->dict =& $dict;
        $this->caller =& $caller;
        $this->langCounter = -1;
        $this->where = [];
        $this->set = [];
        $this->else = [];
        $this->init = [];

    }

    /**
     * It reset the previous definitions but the variables, service and areas
     */
    public function reset()
    {
        $this->langCounter = -1;
        $this->where = [];
        $this->set = [];
        $this->else = [];
        $this->init = [];

    }

    /**
     * @param object $caller
     */
    public function setCaller($caller)
    {
        $this->caller = $caller;
    }

    /**
     * It sets the whole dictionary. 
     * 
     * @param array $dict This value is passes as reference so it returns the modified values.
     */
    public function setDict(&$dict)
    {
        $this->dict = &$dict;
    }

    /**
     * It returns the value of a index of the dictionary
     * 
     * @param string $name name of the index of the dictionary
     *
     * @return mixed 
     */
    public function getDictEntry($name) {
        return @$this->dict[$name];
    }
    /**
     * It creates a command using a previously separate set
     * @param array $where
     * @param array $set
     * @param array $init
     */
    public function create($where=[],$set=[],$init=[]) {
        $this->langCounter=max(@count($where),@count($set),@count($init))-1;
        $this->where=$where;
        $this->set=$set;
        $this->init=$init;
    }
    
    /**
     * It sends an expression to the MiniLang and it is decomposed in its parts. The script is not executed but parsed.
     *
     * @param string $miniScript Example: "when $field1>0 then $field1=3 and field2=20"
     *
     * @see \eftec\minilang\MiniLang::serialize To pre-calculate this result and improve the performance.
     */
    public function separate($miniScript)
    {
        $this->langCounter++;

        $this->where[$this->langCounter] = [];
        $this->set[$this->langCounter] = [];
        $this->else[$this->langCounter] = [];
        $this->init[$this->langCounter] = [];
        $rToken = token_get_all("<?php " . $miniScript);

        $rToken[] = ''; // avoid last operation
        $count = count($rToken) - 1;
        $first = true;
        $inFunction = false;
        /** @var  string $position =['where','set','else','init'][$i] */
        $position = 'init';
        for ($i = 0; $i < $count; $i++) {
            $v = $rToken[$i];
            $rTokenNext = $rToken[$i + 1];
            if (is_array($v)) {
                switch ($v[0]) {
                    case T_CONSTANT_ENCAPSED_STRING:
                        $txt = substr($v[1], 1, -1);
                        if (strpos($txt, '{{') === false) {
                            $this->addBinOper($first, $position, $inFunction, 'string'
                                , substr($v[1], 1, -1), null);
                        } else {
                            $this->addBinOper($first, $position, $inFunction, 'stringp'
                                , substr($v[1], 1, -1), null);
                        }
                        break;
                    case T_VARIABLE:
                        if (is_array($rTokenNext)) {
                            // fix for $aaa.2 
                            if ($rTokenNext[0] == T_DNUMBER && substr($rTokenNext[1], 0, 1) == '.') {
                                $rToken[$i + 2] = [T_STRING, substr($rTokenNext[1], 1)];
                                $rTokenNext = '.';
                            }
                        }
                        if (is_string($rTokenNext) && $rTokenNext == '.') {
                            // $var.vvv
                            $this->addBinOper($first, $position, $inFunction, 'subvar'
                                , substr($v[1], 1), $rToken[$i + 2][1]);
                            $i += 2;
                        } else {
                            // $var
                            $this->addBinOper($first, $position, $inFunction, 'var', substr($v[1], 1), null);
                        }
                        break;
                    case T_LNUMBER:
                    case T_DNUMBER:
                        $this->addBinOper($first, $position, $inFunction, 'number'
                            , $v[1], null);
                        break;
                    case T_ELSE:
                        //adding a new else
                        $position = 'else';
                        $first = true;
                        break;
                    case T_STRING:
                        if (in_array($v[1], $this->areaName)) {
                            // its an area. <area> <somvalue>
                            if (count($rToken) > $i + 2) {
                                $tk = $rToken[$i + 2];
                                switch ($tk[0]) {
                                    case T_VARIABLE:
                                        $this->areaValue[$v[1]] = ['var', $tk[1], null];
                                        break;
                                    case T_STRING:
                                        $this->areaValue[$v[1]] = ['field', $tk[1], null];
                                        break;
                                    case T_DNUMBER:
                                    case T_LNUMBER:
                                        $this->areaValue[$v[1]] = $tk[1];
                                        break;
                                }
                            }
                            $i += 2;
                        } else {
                            switch ($v[1]) {
                                case 'init':
                                    //adding a new init
                                    $position = 'init';
                                    $first = true;
                                    break;
                                case 'where':
                                case 'when':
                                    // adding a new when
                                    $position = 'where';
                                    $first = true;
                                    break;
                                case 'then':
                                case 'set':
                                    //adding a new set
                                    $position = 'set';
                                    $first = true;
                                    break;
                                case 'else':

                                    //adding a new else
                                    $position = 'else';
                                    $first = true;
                                    break;
                                default:
                                    if (is_array($rTokenNext)) {
                                        // fix for $aaa.2 
                                        if ($rTokenNext[0] == T_DNUMBER && substr($rTokenNext[1], 0, 1) == '.') {
                                            $rToken[$i + 2] = [T_STRING, substr($rTokenNext[1], 1)];
                                            $rTokenNext = '.';
                                        }
                                    }
                                    if (is_string($rTokenNext)) {
                                        if ($rTokenNext == '.') {
                                            if (@$rToken[$i + 3] != '(') {
                                                // field.vvv
                                         
                                                $this->addBinOper($first, $position, $inFunction, 'subfield', $v[1],
                                                    $rToken[$i + 2][1]);
                                                $i += 2;
                                            } else {
                                                // $v[1].$rToken[$i+2][1]
                                                // field.vvv(arg,arg) = vvv(field,arg,arg)
                                                $this->addBinOper($first, $position, $inFunction, 'fn'
                                                    , $rToken[$i + 2][1], null);
                                                $inFunction = true;
                                                $this->addParam($position, 'field', $v[1]);
                                                $i += 3;
                                            }
                                        } elseif ($rTokenNext == '(') {
                                            // function()

                                            $this->addBinOper($first, $position, $inFunction, 'fn', $v[1], null);
                                            $inFunction = true;
                                            $i += 1;
                                        } else {
                                            // field
                                            if (in_array($v[1], $this->specialCom)) {
                                                $this->addBinOper($first, $position, $inFunction, 'special', $v[1],
                                                    null);
                                                $first = true;
                                            } else {
                                                $this->addBinOper($first, $position, $inFunction, 'field', $v[1], null);
                                            }

                                        }
                                    } else {
                                        // field
                                        if (in_array($v[1], $this->specialCom)) {
                                            $this->addBinOper($first, $position, $inFunction, 'special', $v[1], null);
                                            $first = true;
                                        } else {
                                            $this->addBinOper($first, $position, $inFunction, 'field', $v[1], null);
                                        }
                                    }
                                    break;
                            }
                        }
                        break;
                    case T_IS_EQUAL:
                        $this->addOp($position, $first, '=');
                        break;
                    case T_IS_GREATER_OR_EQUAL:
                        $this->addOp($position, $first, '>=');
                        break;
                    case T_IS_SMALLER_OR_EQUAL:
                        $this->addOp($position, $first, '<=');
                        break;
                    case T_IS_NOT_EQUAL:
                        $this->addOp($position, $first, '<>');
                        break;
                    case T_LOGICAL_AND:
                    case T_BOOLEAN_AND:
                        if ($position != 'where') {
                            $first = true;
                        } else {
                            $this->addLogic($position, $first, 'and');
                        }
                        break;
                    case T_BOOLEAN_OR:
                    case T_LOGICAL_OR:
                        $this->addLogic($position, $first, 'or');
                        break;
                }
            } else {
                switch ($v) {
                    case '-':
                        if (is_array($rTokenNext)
                            && ($rTokenNext[0] == T_LNUMBER
                                || $rTokenNext[0] == T_DNUMBER)
                        ) {
                            // it's a negative value
                            $this->addBinOper($first, $position, $inFunction, 'number', -$rTokenNext[1], null);
                            $i++;
                        } else {
                            // its a minus
                            $this->addOp($position, $first, $v);
                        }
                        break;
                    case ')':
                        $inFunction = false;
                        break;
                    case ',':
                        if (!$inFunction) {
                            if ($position != 'where') {
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
                        $this->addOp($position, $first, $v);
                        break;
                }
            }
        }
    }

    /**
     * It evaluates a logic
     *
     * @param int $idx
     *
     * @return bool|string it returns the evaluation of the logic or it returns the value special (if any).
     */
    public function evalLogic($idx = 0)
    {
        $prev = true;
        $r = false;
        $addType = '';
        if (count($this->where[$idx]) === 0) {
            return true;
        } // no where = true
        foreach ($this->where[$idx] as $k => $v) {
            if ($v[0] === 'pair') {
                if ($v[1] == 'special') {

                    if (count($v) >= 7) {
                        return $this->caller->{$v[2]}($v[6]);
                    } else {
                        return $this->caller->{$v[2]}();
                    }
                }

                $field0 = $this->getValue($v[1], $v[2], $v[3]);
                if (count($v) <= 4) {
                    return $field0 ? true : false;
                }
                if (count($v) >= 8) {
                    $field1 = $this->getValue($v[5], $v[6], $v[7]);
                } else {
                    $field1 = null;
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
                        $r = $prev && $r;
                        break;
                    case 'or':
                        $r = $prev || $r;
                        break;
                    case '':
                        break;
                }
                $prev = $r;
            } else {
                // logic
                $addType = $v[1];
            }
        } // for
        return $r;
    }

    /**
     * It evaluates all logic and sets if the logic is true
     *
     * @param bool $stopOnFound exit if some evaluation matches
     * @param bool $start       if true then it evaluates the "init" expression.
     */
    public function evalAllLogic($stopOnFound = true, $start = false)
    {
        for ($i = 0; $i <= $this->langCounter; $i++) {
            if ($start) {
                $this->evalSet($i, 'init');
            }
            if ($this->evalLogic($i)) {
                $this->evalSet($i);
                if ($stopOnFound) {
                    break;
                }
            } else {
                $this->evalSet($i, 'else');
            }
        }
    }

    /**
     * It sets a value or values. It does not consider if WHERE is true or not.
     *
     * @param int    $idx      number of expression
     * @param string $position =['set','else','init'][$i]
     *
     * @return void
     */
    public function evalSet($idx = 0, $position = 'set')
    {
        $position = (!$position) ? 'init' : $position;
        $exp = $this->{$position}[$idx];
        foreach ($exp as $k => $v) {
            if ($v[0] === 'pair') {
                $name = $v[2];
                $ext = $v[3];
                $op = @$v[4];
                //$field0=$this->getValue($v[1],$v[2],$v[3],$this->caller,$dictionary);
                if (count($v) > 5) {
                    $field1 = $this->getValue($v[5], $v[6], $v[7]);
                } else {
                    $field1 = null;
                }
                for ($i = 8; $i < count($v); $i += 4) {
                    switch ($v[$i]) {
                        case '+': // if we add numbers then it adds, otherwise it concatenates.
                            $field2 = $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            if (is_numeric($field1) && is_numeric($field2)) {
                                $field1 += $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            } else {
                                $field1 .= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            }
                            break;
                        case '-':
                            $field1 -= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            break;
                        case '*':
                            $field1 *= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            break;
                        case '/':
                            $field1 /= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            break;
                    }
                }
                if ($field1 === '___FLIP___') {
                    $field0 = $this->getValue($v[1], $v[2], $v[3]);
                    $field1 = (!$field0) ? 1 : 0;
                }
                switch ($v[1]) {
                    case 'subvar':
                        // $a.field
                        $rname = @$GLOBALS[$name];
                        if (is_object($rname)) {
                            $rname->{$ext} = $field1;
                        } else {
                            $rname[$ext] = $field1;
                        }
                        break;
                    case 'var':
                        // $a
                        switch ($op) {
                            case '=':
                                $GLOBALS[$name] = $field1;
                                break;
                            case '+';
                                $GLOBALS[$name] += $field1;
                                break;
                            case '-';
                                $GLOBALS[$name] -= $field1;
                                break;
                        }
                        break;
                    case 'number':
                    case 'string':
                    case 'stringp':
                        trigger_error("Literal [{$v[2]}] of the type [{$v[1]}] is not for set.");
                        break;
                    case 'field':
                        switch ($op) {
                            case '=':
                                $this->dict[$name] = $field1;
                                break;
                            case '+';
                                $this->dict[$name] += $field1;
                                break;
                            case '-';
                                $this->dict[$name] -= $field1;
                                break;
                        }
                        break;
                    case 'subfield':
                        // field.value=
                        // field.value()=
                        $args = [&$this->dict[$name]];
                        $this->callFunctionSet($ext, $args, $field1);
                        break;
                    case 'fn':
                        // function name($this->caller,$somevar);
                        $args = [];
                        if ($ext !== null) {
                            foreach ($ext as $e) {
                                $args[] = $this->getValue($e[0], $e[1], $e[2]);
                            }
                        }
                        $this->callFunctionSet($name, $args, $field1);
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
     *
     * @param $nameFunction
     * @param $args
     *
     * @return mixed (it could returns an error if the function fails)
     */
    private function callFunction($nameFunction, $args)
    {
        if (count($args) >=1) {
            if (is_object($args[0])) {
                // the call is the form nameFunction(somevar) or somevar.nameFunction()
                if (isset($args[0]->{$nameFunction})) {
                    // someobject.field (nameFunction acts as a field name)
                    return $args[0]->{$nameFunction};
                } else {
                    $cp = $args;
                    unset($cp[0]); // it avoids to pass the name of the function as argument
                    return $args[0]->{$nameFunction}(...$cp); //(...$cp);
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
            if (method_exists($this->caller, $nameFunction)) {
                return call_user_func_array(array($this->caller, $nameFunction), $args);
            }
            if (isset($this->caller->{$nameFunction})) {
                return $this->caller->{$nameFunction};
            }
        } else {
            if (is_array($this->caller)) {
                if (isset($this->caller[$nameFunction])) {
                    return $this->caller[$nameFunction];
                }
            }
        }
        if (method_exists($this->serviceClass, $nameFunction)) {
            return call_user_func_array(array($this->serviceClass, $nameFunction), $args);
        } else {
            if (function_exists($nameFunction)) {
                return call_user_func($nameFunction,$args);
            }
            trigger_error("function [$nameFunction] is not defined");
            return false;
        }
    }

    /**
     * Example: field2.value=20  namefunction=value,setvalue=20,args
     * 
     * @param string $nameFunction
     * @param        $args
     * @param        $setValue
     *
     * @return void
     */
    private function callFunctionSet($nameFunction, &$args, $setValue)
    {
        if (count($args) >= 1) {
            if (is_object($args[0])) {

                // the call is the form nameFunction(somevar)=1 or somevar.nameFunction()=1
                if (isset($args[0]->{$nameFunction})) {
                    // someobject.field (nameFunction acts as a field name
                    $args[0]->{$nameFunction} = $setValue;
                    return;
                }
                if (method_exists($args[0], $nameFunction)) {
                    // someobject.function
                    $cp = $args;
                    unset($cp[0]); // it avoids to pass the function as argument
                    $args[0]->$nameFunction(...$cp); // = $setValue;
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
            if (method_exists($this->caller, $nameFunction)) {
                $args[] = $setValue; // it adds a second parameter
                call_user_func_array(array($this->caller, $nameFunction), $args);
                return;

            } elseif (isset($this->caller->{$nameFunction})) {
                $this->caller->{$nameFunction} = $setValue;
                return;
            }
        } else {
            if (is_array($this->caller)) {
                if (isset($this->caller[$nameFunction])) {
                    $this->caller[$nameFunction] = $setValue;
                    return;
                }
            }
        }
        if ($this->serviceClass !== null) {

            call_user_func_array(array($this->serviceClass, $nameFunction), $args);
        }
    }

    /**
     * It obtains a value.
     *
     * @param string       $type =['subvar','var','number','string','stringp','field','subfield','fn','special'][$i]
     * @param string       $name name of the value. It is also used for the value of the variable.
     *                           <p> myvar => type=var, name=myvar</p>
     *                           <p> 123 => type=number, name=123</p>
     * @param string|array $ext  it is used for subvar, subfield and functions
     *
     * @return bool|int|mixed|string|null
     */
    public function getValue($type, $name, $ext)
    {
        $r = 0;
        switch ($type) {
            case 'subvar':
                // $a.field
                $rname = @$GLOBALS[$name];
                if (substr($ext, 0, 1) === '$') {
                    // $a.$b
                    $ext = @$GLOBALS[substr($ext, 1)];
                }
                $r = (is_object($rname)) ? $rname->{$ext} : $rname[$ext];
                break;
            case 'var':
                // $a
                $r = @$GLOBALS[$name];
                break;
            case 'number':
                // 20
                $r = $name;
                break;
            case 'string':
                // 'aaa',"aaa"
                $r = $name;
                break;
            case 'stringp':
                // 'aaa',"aaa"
                $r = $this->getValueP($name);

                break;
            case 'field':
                $r = @$this->dict[$name];
                break;
            case 'subfield':
                // field.sum is equals to sum(field)
                $args = [@$this->dict[$name]];
                $r = $this->callFunction($ext, $args);
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
                        if (method_exists($this->caller, 'dateLastChange')) {
                            return time() - $this->caller->dateLastChange();
                        }
                        trigger_error("caller doesn't define field or method dateLastChange");
                        break;
                    case 'fullinterval':
                        if (isset($this->caller->dateInit)) {
                            return time() - $this->caller->dateInit;
                        }
                        if (method_exists($this->caller, 'dateInit')) {
                            return time() - $this->caller->dateInit();
                        }
                        trigger_error("caller doesn't define field or method dateInit");
                        break;
                    default:
                        $args = [];
                        if ($ext) {
                            foreach ($ext as $e) {
                                $args[] = $this->getValue($e[0], $e[1], $e[2]);
                            }
                        }
                        return $this->callFunction($name, $args);
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
        return preg_replace_callback('/{\{\s?(\w+)\s?\}\}/u', function ($matches) {
            if (is_array($matches)) {
                $item = substr($matches[0], 2, strlen($matches[0]) - 4); // removes {{ and }}
                return @$this->dict[$item];
            } else {
                $item = substr($matches, 2, strlen($matches) - 4); // removes {{ and }}
                return @$this->dict[$item];
            }
        }, $string);
    }

    /**
     * It adds a part of a pair of operation.
     * <p>"value=20" where value is the first part and 20 is the second part<p>
     *
     * @param bool        $first    if it is the first part or second part of the expression.
     * @param string      $position =['where','set','else','init'][$i]
     * @param bool        $inFunction
     * @param string      $type     =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
     * @param string      $name     name of the field
     * @param null|string $ext      extra parameter.
     */
    private function addBinOper(&$first, $position, $inFunction, $type, $name, $ext = null)
    {
        if ($inFunction) {
            $this->addParam($position, $type, $name, $ext);
            return;
        }
        $posexpr = (!$position) ? 'init' : $position;
        if ($first) {
            $this->{$posexpr}[$this->langCounter][] = ['pair', $type, $name, $ext];
        } else {
            $expr =& $this->{$posexpr}[$this->langCounter];
            $f = count($expr) - 1;
            $f2 = count($expr[$f]);
            $expr[$f][$f2] = $type;
            $expr[$f][$f2 + 1] = $name;
            $expr[$f][$f2 + 2] = $ext;
            if ($position == 'where') {
                $first = true;
            }
        }
    }

    /**
     * Add params of a function
     *
     * @param string      $position =['where','set','else','init'][$i]
     * @param string      $type     =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
     * @param string      $name     name of the field
     * @param null|string $ext      extra parameter.
     */
    private function addParam($position, $type, $name, $ext = null)
    {
        $position = (!$position) ? 'init' : $position;
        $f = count($this->{$position}[$this->langCounter]) - 1;
        $idx = count($this->{$position}[$this->langCounter][$f]) - 1;
        if (!isset($this->{$position}[$this->langCounter][$f][$idx])) {
            $this->{$position}[$this->langCounter][$f][$idx] = [];
        }
        $this->{$position}[$this->langCounter][$f][$idx][] = [$type, $name, $ext];
    }

    /**
     * It adds an operation (such as =,<,+,etc.)
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param bool   $first    If it's true then it is the first value of a binary
     * @param string $opName
     */
    private function addOp($position, &$first, $opName)
    {
        $position = (!$position) ? 'init' : $position;
        if ($first) {
            $f = count($this->{$position}[$this->langCounter]) - 1;
            $this->{$position}[$this->langCounter][$f][4] = $opName;
            $first = false;
        } else {
            $f = count($this->{$position}[$this->langCounter]) - 1;
            $this->{$position}[$this->langCounter][$f][] = $opName;
        }
    }

    /**
     * It adds a logic
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param bool   $first    If it's true then it is the first value of a binary
     * @param string $name     name of the logic
     */
    private function addLogic($position, &$first, $name)
    {
        if ($first) {
            $position = (!$position) ? 'init' : $position;
            $this->{$position}[$this->langCounter][] = ['logic', $name];
        } else {
            trigger_error("Error: Logic operation in the wrong place");
        }

    }

    /**
     * It serializes the current minilang. It doesn't serialize the caller or service class.<br>
     * This method could be used to speed up the process, especially the function separate()<br>
     * separate() parse the text and it converts into an array. We could pre-calculate
     * the result to improve the performance.
     *
     * @return string The current object serialized
     *
     * @see \eftec\minilang\MiniLang::separate
     */
    public function serialize()
    {
        $tmpCaller = $this->caller;
        $tmpService = $this->serviceClass;
        $this->caller = null;
        $this->serviceClass = null;
        $result = serialize($this);
        $this->caller = $tmpCaller;
        $this->serviceClass = $tmpService;
        return $result;
    }

    /**
     * Unserialize an object serialized by the method serialize()
     *
     * @param string $serializeText
     * @param object $caller
     * @param object $serviceClass
     *
     * @return MiniLang
     */
    public static function unserialize($serializeText, $caller, $serviceClass = null)
    {
        /** @var MiniLang $obj */
        $obj = unserialize($serializeText);
        $obj->caller = $caller;
        $obj->serviceClass = $serviceClass;
        return $obj;
    }

}