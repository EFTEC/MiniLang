<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
/** @noinspection NotOptimalIfConditionsInspection */
/** @noinspection TypeUnsafeArraySearchInspection */
/** @noinspection MultiAssignmentUsageInspection */
/** @noinspection PhpRedundantVariableDocTypeInspection */

/** @noinspection TypeUnsafeComparisonInspection */

namespace eftec\minilang;

use RuntimeException;

/**
 * It's a mini language parser. It uses the build-in token_get_all function to performance purpose.
 * Class MiniLang
 *
 * @package  eftec\minilang
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  2.18. 2021-07-25
 * @link     https://github.com/EFTEC/MiniLang
 * @license  LGPL v3 (or commercial if it's licensed)
 */
class MiniLang {
    /** @var array When operators (if any) */
    public $where = [];
    /** @var array Set operators (if any) */
    public $set = [];
    /** @var array Set operators (if any)  */
    public $else = [];
    /** @var array Init operators (if any)  */
    public $init = [];
    /** @var string[] */
    public $whereTxt = [];
    /** @var string[] */
    public $setTxt = [];
    /** @var string[] */
    public $elseTxt = [];
    /** @var string[] */
    public $initTxt = [];

    private $specialCom;
    /** @var array  */
    public $areaName;
    /** @var array values per the special area */
    public $areaValue = [];
    public $serviceClass;
    /** @var object for callbacks */
    private $caller;

    public $throwError=true;
    public $errorLog=[];
    private $debugLine=0;

    /** @var array */
    protected $dict;
    // for runtime:
    private $txtCounter = 0;
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
    public function __construct($caller=null, &$dict=[], $specialCom = [], $areaName = [], $serviceObject = null) {
        $this->specialCom = $specialCom;
        $this->areaName = $areaName;
        $this->serviceClass = $serviceObject;
        $this->dict =& $dict;
        $this->caller =& $caller;
        $this->reset();
    }

    /**
     * It reset the previous definitions but the variables, service and areas
     */
    public function reset() {
        $this->langCounter = -1;
        $this->txtCounter=-1;
        $this->where = [];
        $this->set = [];
        $this->else = [];
        $this->init = [];
        $this->whereTxt = [];
        $this->setTxt = [];
        $this->elseTxt = [];
        $this->initTxt = [];
        $this->errorLog=[];

    }

    /**
     * It sets the object caller.
     *
     * @param object $caller
     */
    public function setCaller($caller) {
        $this->caller = $caller;
    }

    /**
     * It sets the whole dictionary.
     *
     * @param array $dict This value is passes as reference so it returns the modified values.
     */
    public function setDict(&$dict) {
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
        return isset($this->dict[$name]) ? $this->dict[$name] : null;
    }

    /**
     * It creates a command using a previously separate set
     *
     * @param array $where
     * @param array $set
     * @param array $init
     */
    public function create($where = [], $set = [], $init = []) {
        $this->langCounter = max(@count($where), @count($set), @count($init)) - 1;
        $this->where = $where;
        $this->set = $set;
        $this->init = $init;
    }

    /**
     * This function decomposed an array or object into their subelements.<br>
     * Example: $a['a']['b']['c']=123;   _param($a,'a.b.c')=123<br>
     * It is designed to be called inside minilang. Example set $a1=param($a,'a.b.c')
     *
     * @param array|object $a1
     * @param string       $a2 indexes separated by dot.
     *
     * @return array|mixed
     */
    public function _Param($a1, $a2) {
        $arr = explode('.', $a2);
        if (is_object($a1)) {
            $a1 = (array)$a1;
        }
        if (count($arr) < 2) {
            return $a1[$a2];
        } // _Param($a1,'a')
        if (!is_array($a1)) {
            return $a1;
        }
        $v = $a1;
        foreach ($arr as $k) {
            $v = $v[$k];
        }
        return $v;
    }

    /**
     * It sends an expression to the MiniLang and it is decomposed in its parts. The script is not executed but parsed.
     *
     * @param string $miniScript Example: "when $field1>0 then $field1=3 and field2=20"
     * @param int    $numLine    If -1 (default value), then it adds a new separate (automatic number of line).
     *                           If set, then it adds in the number of line.
     *
     * @see \eftec\minilang\MiniLang::serialize To pre-calculate this result and improve the performance.
     */
    public function separate($miniScript, $numLine = -1) {
        $this->langCounter = ($numLine < 0) ? $this->langCounter + 1 : $numLine;
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
                                , substr($v[1], 1, -1));
                        } else {
                            $this->addBinOper($first, $position, $inFunction, 'stringp'
                                , substr($v[1], 1, -1));
                        }
                        break;
                    case T_VARIABLE:
                        // fix for $aaa.2
                        if (is_array($rTokenNext) && $rTokenNext[0] == T_DNUMBER && $rTokenNext[1][0] === '.') {
                            $rToken[$i + 2] = [T_STRING, substr($rTokenNext[1], 1)];
                            $rTokenNext = '.';
                        }
                        if ($rTokenNext === '.') {
                            if (isset($rToken[$i + 3]) && $rToken[$i + 3] !== '(') {
                                // $var.vvv
                                $this->addBinOper($first, $position, $inFunction, 'subvar'
                                    , substr($v[1], 1), $rToken[$i + 2][1]);
                                $i += 2;
                            } else {
                                // $field.vvv(arg,arg) = vvv($field,arg,arg)

                                $this->addBinOper($first, $position, $inFunction, 'fn'
                                    , $rToken[$i + 2][1]);
                                $inFunction = true;
                                //  $this->addParam($position, 'field', $v[1]);
                                //$this->addParam($position, 'var', $v[1]);
                                $this->addParam($position, 'var', substr($v[1], 1));
                                $i += 3;
                            }
                        } else {
                            // $var
                            $this->addBinOper($first, $position, $inFunction, 'var', substr($v[1], 1));
                        }
                        break;
                    case T_LNUMBER:
                    case T_DNUMBER:
                        $this->addBinOper($first, $position, $inFunction, 'number'
                            , $v[1]);
                        break;
                    case T_ELSE:
                        //adding a new else
                        $position = 'else';
                        $first = true;
                        break;
                    case T_STRING:
                        if (in_array($v[1], $this->areaName, true)) {
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
                            // its a variable or a custom area.
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
                                    // it is a variable insided of MiniLang (i.e. variablename)
                                    if (is_array($rTokenNext) && ($rTokenNext[0] == T_DNUMBER || $rTokenNext[0] == T_EVAL)
                                        && $rTokenNext[1][0] === '.'
                                    ) {
                                        // variable.2
                                        array_splice( $rToken, $i+2, 0, [[T_STRING, substr($rTokenNext[1], 1)]] );
                                        $count++;
                                        $rTokenNext = '.';
                                    }
                                    if (is_string($rTokenNext)) {
                                        if ($rTokenNext === '.') {
                                            if (isset($rToken[$i + 3]) && $rToken[$i + 3] !== '(') {
                                                // field.vvv
                                                $this->addBinOper($first, $position, $inFunction, 'subfield', $v[1],
                                                    $rToken[$i + 2][1]);
                                                $i += 2;
                                            } else {
                                                // field.vvv(arg,arg) = vvv(field,arg,arg)
                                                $this->addBinOper($first, $position, $inFunction, 'fn'
                                                    , $rToken[$i + 2][1]);
                                                $inFunction = true;
                                                $this->addParam($position, 'field', $v[1]);
                                                $i += 3;
                                            }
                                        } elseif ($rTokenNext === '(') {
                                            // function()
                                            if ($v[1] === 'flip') {
                                                // $pr is pair(0),field(1),field8(2),null(3),=(4)
                                                $pr = end($this->{$position}[$this->langCounter]);
                                                $this->addBinOper($first, $position, $inFunction, 'fn', $v[1]);
                                                // we add a first parameter
                                                $this->addParam($position, $pr[1], $pr[2], $pr[3]);
                                            } else {
                                                $this->addBinOper($first, $position, $inFunction, 'fn', $v[1]);
                                            }

                                            $inFunction = true;
                                            ++$i;
                                        } elseif (in_array($v[1], $this->specialCom)) {
                                            $this->addBinOper($first, $position, $inFunction, 'special', $v[1]);
                                            $first = true;
                                        } else {
                                            // simple field, example: field = something  ("field" is $v[1]),
                                            // the "= something" is not processed yet.
                                            $this->addBinOper($first, $position, $inFunction, 'field', $v[1]);
                                        }
                                    } elseif (in_array($v[1], $this->specialCom)) {
                                        $this->addBinOper($first, $position, $inFunction, 'special', $v[1]);
                                        $first = true;
                                    } else {
                                        $this->addBinOper($first, $position, $inFunction, 'field', $v[1]);
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
                        if ($position !== 'where') {
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
                            $this->addBinOper($first, $position, $inFunction, 'number', -$rTokenNext[1]);
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
                            if ($position !== 'where') {
                                $first = true;
                            } else {
                                $this->addLogic($position, $first, ',');
                            }
                        }
                        break;
                    case '=':
                    case '+':
                    case '*':
                    case '&':
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
     * It sends an expression to the MiniLang, and it is decomposed in its parts.<br>
     * The script is not executed but parsed. You can obtain the result with $this->whereTxt,$this->setTxt, etc.<br>
     *
     * @param string $miniScript Example: "when $field1>0 then $field1=3 and field2=20"
     * @param int    $numLine    If -1 (default value), then it adds a new separate (automatic number of line).
     *                           If set, then it adds in the number of line.
     *
     * @see \eftec\minilang\MiniLang::serialize To pre-calculate this result and improve the performance.
     */
    public function separate2($miniScript, $numLine = -1) {
        $this->txtCounter = ($numLine < 0) ? $this->txtCounter + 1 : $numLine;
        $this->separate($miniScript);
        $this->whereTxt[$this->txtCounter] = $this->compileTokens('where', $this->txtCounter);
        $this->setTxt[$this->txtCounter] = $this->compileTokens('set', $this->txtCounter);
        $this->initTxt[$this->txtCounter] = $this->compileTokens('init', $this->txtCounter);
        $this->elseTxt[$this->txtCounter] = $this->compileTokens('else', $this->txtCounter);
    }

    /**
     * It evaluates a logic.  It uses the second motor (it generates a php code).
     *
     * @param int $numLine
     *
     * @return bool|string it returns the evaluation of the logic, or it returns the value special (if any).
     */
    public function evalLogic2($numLine = 0) {
        $where = $this->whereTxt[$numLine];
        $where = "return ($where);";
        return eval($where);
    }

    /**
     * It evaluates the position "set","init" or "else". It uses the second motor (it generates a php code). It does not consider if WHERE is true or not.
     *
     * @param int    $numLine  number of expression
     * @param string $position =['set','else','init'][$i]
     *
     * @return void
     */
    public function evalSet2($numLine = 0, $position = 'set') {
        $position .= 'Txt';
        $set = $this->{$position}[$numLine];
        return eval($set);
    }

    /**
     * It returns a php code based in the expression obtained by separate2()
     *
     * @param int  $line        number of line
     * @param bool $stopOnFound if "when" is true, then it only executes a single command
     * @param bool $start       if true then it includes the position "init"
     * @param int  $tabs        Number of tabs (for the identation)
     *
     * @return string a php code.
     * @noinspection PhpUnusedParameterInspection
     */
    public function getCode2($line = 0, $stopOnFound = false, $start = false, $tabs = 0) {
        $code = '';
        $align = str_repeat("\t", $tabs);
        if ($start && $this->initTxt[$line]) {
            $code .= $align . $this->initTxt[$line]."\n";
        }
        if($this->whereTxt[$line]) {
            $align2=$align."\t";
            $code .= $align . 'if (' . $this->whereTxt[$line] . ") {\n";
        } else {
            $align2=$align;
        }
        $code .= $align2 . "\$_foundIt=true;\n";
        $code .= $align2 . str_replace("\n", "\n" . $align . "\t", $this->setTxt[$line]) . "\n";
        if ($this->elseTxt[$line] && $this->whereTxt[$line]) {
            $code .= $align . "} else {\n";
            $code .= $align2 . str_replace("\n", "\n" . $align, $this->elseTxt[$line]) . "\n";
        }
        if($this->whereTxt[$line]) {
            $code .= $align . "}\n";
        }
        return $code;
    }

    /**
     * It generates a PHP class, and it could be evaluated (eval command) or it could be store so it could be called.<br>
     * $mini->separate2($expr);
     * echo $mini->generateClass2(); // it returns a php class (code)
     *
     * @param string $className   Name of the class to generate
     * @param bool   $stopOnFound If true, then it stops the execution if one "when" is fulfilled.
     * @param bool   $start       if true then it includes the "start" position
     *
     * @return string
     */
    public function generateClass2($className = 'RunClass', $stopOnFound = true, $start = false) {
        $code = "class $className extends MiniLang {\n";
        $code .= "\tprotected \$numCode=".($this->langCounter+1)."; // num of lines of code \n";
        $code .= "\tpublic function RunAll(\$stopOnFound=true) {\n";
        $code .= "\t\tfor(\$i=0;\$i<\$this->numCode;\$i++) {\n";
        $code .= "\t\t\t\$r=\$this->Code(\$i);\n";
        $code .= "\t\t\tif(\$r && \$stopOnFound) break;\n";
        $code .= "\t\t}\n";
        $code .= "\t}\n";
        $code .= "\tpublic function Code(\$lineCode=0) {\n";
        $code .= "\t\t\$_foundIt=false;\n";
        $code .= "\t\tswitch(\$lineCode) {\n";
        for ($i = 0; $i <= $this->langCounter; $i++) {
            $code .= "\t\t\tcase $i:\n";
            $code .=$this->getCode2($i, $stopOnFound, $start, 4);
            $code .= "\t\t\t\tbreak;\n";
        }
        $code .= "\t\t\tdefault:\n";
        $code .= "\t\t\t\t\$this->errorLog[]='Line '.\$lineCode.' is not defined';\n";
        $code .= "\t\t}\n";
        $code .= "\t\treturn \$_foundIt;\n";
        $code .= "\t} // end function Code\n";
        $code .= "} // end class\n";
        return $code;
    }

    /**
     * It evaluates all the expressions (using the second motor).<br>
     * If the position "where" is true, then it processes the "set" position (if any)<br>
     * If the position "where" is false, then it proccess the "else" position (if any)<br>
     *
     * @param bool $stopOnFound exit if some evaluation matches
     * @param bool $start       if true then it evaluates the "init" expression.
     */
    public function evalAllLogic2($stopOnFound = true, $start = false) {
        $_foundIt = false;
        for ($i = 0; $i <= $this->langCounter; $i++) {
            eval($this->getCode2($i, $stopOnFound, $start));
            if ($stopOnFound && $_foundIt) {
                break;
            }
        }
    }

    /**
     * It evaluates a logic.
     *
     * @param int $numLine
     *
     * @return bool|string it returns the evaluation of the logic or it returns the value special (if any).
     */
    public function evalLogic($numLine = 0) {
        $prev = true;
        $r = false;
        $addType = '';
        if (count($this->where[$numLine]) === 0) {
            return true;
        } // no where = true
        foreach ($this->where[$numLine] as $v) {
            if ($v[0] === 'pair') {
                if ($v[1] === 'special') {
                    if (count($v) >= 7) {
                        return $this->caller->{$v[2]}($v[6]);
                    }

                    return $this->caller->{$v[2]}();
                }

                $field0 = $this->getValue($v[1], $v[2], $v[3]);
                if (count($v) <= 4) {
                    return (bool)$field0;
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
                        $error="comparison $v[4] not defined for eval logic.";
                        $this->throwError($error);
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
                $addType = $v[1];
                if($addType==='and' && !$r) {
                    return false;
                }
            }
        } // for
        return $r;
    }

    /**
     * It evaluates all the expressions.<br>
     * If the position "where" is true, then it processes the "set" position (if any)<br>
     * If the position "where" is false, then it proccess the "else" position (if any)<br>
     *
     * @param bool $stopOnFound exit if some evaluation matches
     * @param bool $start       if true then it evaluates the "init" expression.
     */
    public function evalAllLogic($stopOnFound = true, $start = false) {
        for ($i = 0; $i <= $this->langCounter; $i++) {
            $this->debugLine=$i;
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
     * It evaluates the position "set","init" or "else". It does not consider if WHERE is true or not.
     *
     * @param int    $numLine  number of expression
     * @param string $position =['set','else','init'][$i]
     *
     * @return void
     */
    public function evalSet($numLine = 0, $position = 'set') {
        $position = (!$position) ? 'init' : $position;
        $exp = $this->{$position}[$numLine];
        foreach ($exp as $v) {
            if ($v[0] === 'pair') {
                $name = $v[2];
                $ext = $v[3];
                $op = isset($v[4])?$v[4]:null;
                //$field0=$this->getValue($v[1],$v[2],$v[3],$this->caller,$dictionary);
                if (count($v) > 5) {
                    $field1 = $this->getValue($v[5], $v[6], $v[7]);
                } else {
                    $field1 = null;
                }
                $countv=count($v);
                for ($i = 8; $i < $countv; $i += 4) {
                    switch ($v[$i]) {
                        case '+': // if we add numbers then it adds, otherwise it concatenates.
                            $field2 = $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            if (is_numeric($field1) && is_numeric($field2)) {
                                /** @noinspection AdditionOperationOnArraysInspection */
                                $field1 += $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            } else {
                                $field1 .= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            }
                            break;
                        case '&':
                            $field1 .= $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
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
                        $rname = isset($GLOBALS[$name]) ? $GLOBALS[$name] : null;
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
                                /** @noinspection AdditionOperationOnArraysInspection */
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
                        $error="Literal [$v[2]] of the type [$v[1]] is not for set.";
                        $this->throwError($error);
                        break;
                    case 'field':
                        switch ($op) {
                            case '=':
                                $this->dict[$name] = $field1;
                                break;
                            case '+';
                                /** @noinspection AdditionOperationOnArraysInspection */
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
                        $error="set $v[4] not defined for transaction.";
                        $this->throwError($error);
                        break;
                }
            }
        } // for
    }
    private function throwError($msg) {
        $this->errorLog[]=$msg.' Line:'.$this->debugLine;
        if($this->throwError) {
            throw new RuntimeException($msg.' Line:'.$this->debugLine);
        }
    }

    /**
     * It calls a function predefined by the caller. Example var.myfunction or somevar.value=myfunction(arg,arg)
     *
     * @param $nameFunction
     * @param $args
     *
     * @return mixed (it could return an error if the function fails)
     */
    public function callFunction($nameFunction, $args) {
        if (count($args) >= 1) {
            if (is_object($args[0])) {
                // the call is the form nameFunction(somevar) or somevar.nameFunction()
                if (isset($args[0]->{$nameFunction})) {
                    // someobject.field (nameFunction acts as a field name)
                    return $args[0]->{$nameFunction};
                }

                // the first argument is an object
                if(method_exists($args[0], $nameFunction)) {
                    $cp = $args;
                    unset($cp[0]); // it avoids to pass the name of the function as argument
                    return $args[0]->{$nameFunction}(...$cp); //(...$cp);
                }

                // but the function is not defined.
                return $this->callFunctionCallerService($nameFunction,$args);
            }
            // the call is the form nameFunction(somevar) or somevar.nameFunction()
            if (is_array($args[0])) {
                // someobject.field (nameFunction acts as a field name)
                switch ($nameFunction) {
                    case '_count':
                        return count($args[0]);
                    case '_first':
                        return reset($args[0]);
                    case '_last':
                        return end($args[0]);
                    default:
                        if (isset($args[0][$nameFunction])) {
                            return $args[0][$nameFunction];
                        }
                }
            }
        }
        return $this->callFunctionCallerService($nameFunction,$args);
    }
    private function callFunctionCallerService($nameFunction,$args) {
        if (is_object($this->caller)) {
            if (method_exists($this->caller, $nameFunction)) {
                return call_user_func_array(array($this->caller, $nameFunction), $args);
            }
            if (isset($this->caller->{$nameFunction})) {
                return $this->caller->{$nameFunction};
            }
        } elseif (is_array($this->caller)) {
            if (isset($this->caller[$nameFunction])) {
                return $this->caller[$nameFunction];
            }
        }
        if ($this->serviceClass!==null && method_exists($this->serviceClass, $nameFunction)) {
            return call_user_func_array(array($this->serviceClass, $nameFunction), $args);
        }

        if (method_exists($this, '_' . $nameFunction)) {
            return call_user_func_array(array($this, '_' . $nameFunction), $args);
        }

        if (function_exists($nameFunction)) {
            return call_user_func_array($nameFunction, $args);
        }
        switch ($nameFunction) {
            case 'null':
                return null;
            case 'true':
                return true;
            case 'false':
                return false;
            case 'on':
                return 1;
            case 'off':
                return 0;
            case 'undef':
                return -1;
            case 'flip':
                return (isset($args[0]) && $args[0]) ? 0:1;
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
                $error="caller doesn't define field or method dateLastChange";
                $this->throwError($error);
                break;
            case 'fullinterval':
                if (isset($this->caller->dateInit)) {
                    return time() - $this->caller->dateInit;
                }
                if (method_exists($this->caller, 'dateInit')) {
                    return time() - $this->caller->dateInit();
                }
                $error="caller doesn't define field or method dateInit";
                $this->throwError($error);
                break;
            default:
                $error="function [$nameFunction] is not defined";
                $this->throwError($error);
                break;
        }

        return false;
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
    private function callFunctionSet($nameFunction, &$args, $setValue) {
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
                    unset($cp[0]); // it avoids passing the function as argument
                    $args[0]->$nameFunction(...$cp); // = $setValue;
                    return;
                }
            }
            // the call is the form nameFunction(somevar)=1 or somevar.nameFunction()=1
            if (is_array($args[0]) && isset($args[0][$nameFunction])) {
                // someobject.field (nameFunction acts as a field name
                $args[0][$nameFunction] = $setValue;
                return;
            }
        }
        if (is_object($this->caller)) {
            if (method_exists($this->caller, $nameFunction)) {
                $args[] = $setValue; // it adds a second parameter
                call_user_func_array(array($this->caller, $nameFunction), $args);
                return;

            }

            if (isset($this->caller->{$nameFunction})) {
                $this->caller->{$nameFunction} = $setValue;
                return;
            }
        } elseif (is_array($this->caller)) {
            if (isset($this->caller[$nameFunction])) {
                $this->caller[$nameFunction] = $setValue;
                return;
            }
        }
        if ($this->serviceClass !== null) {

            call_user_func_array(array($this->serviceClass, $nameFunction), $args);
        }
    }
    public $caseSensitive=true;
    /**
     * It obtains a value.
     *
     * @param string       $type =['subvar','var','number','string','stringp','field','subfield','fn','special'][$i]
     * @param string       $name name of the value. It is also used for the value of the variable.
     *                           <p> myvar => type=var, name=myvar</p>
     *                           <p> 123 => type=number, name=123</p>
     * @param string|array|null $ext  it is used for subvar, subfield and functions
     *
     * @return bool|int|mixed|string|null
     */
    public function getValue($type, $name, $ext) {
        if($this->caseSensitive) {
            $namel=&$name;
        } else {
            $namel=strtolower($name);
            if(is_string($ext)) {
                $ext = strtolower($ext);
            }
        }
        switch ($type) {
            case 'subvar':
                // $a.field
                $rname = isset($GLOBALS[$name]) ? $GLOBALS[$name] : null;
                if ($ext[0] === '$') {
                    // $a.$b
                    $subext=substr($ext, 1);
                    $ext = isset($GLOBALS[$subext]) ? $GLOBALS[$subext] : null;
                }
                $r = (is_object($rname)) ? $rname->{$ext} : $rname[$ext];
                break;
            case 'var':
                // $a
                $r = isset($GLOBALS[$namel]) ? $GLOBALS[$namel] : null;
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
                if (isset($this->dict[$namel])) {
                    $r = $this->dict[$namel];
                } else {
                    $r = null;
                    $this->errorLog[]="field named [$name] does not exists";
                }
                break;
            case 'subfield':
                // field.sum is equals to sum(field)
                $args = [isset($this->dict[$namel])? $this->dict[$namel] : null];
                $r = $this->callFunction($ext, $args);
                break;
            case 'fn':
                $args = [];
                if ($ext) {
                    foreach ($ext as $e) {
                        $args[] = $this->getValue($e[0], $e[1], $e[2]);
                    }
                }
                return $this->callFunction($name, $args);
            case 'special':
                return $name;
            default:
                $error="value with type[$type] not defined";
                $this->throwError($error);
                return null;
        }
        return $r;
    }

    /**
     * Evaluates a string when the string contains a substring<br>
     * getValueP("it is an example {{variable}}"); // variable=hello. it returns: it is an example hello
     *
     * @param $string
     *
     * @return string|string[]|null
     */
    public function getValueP($string) {
        return preg_replace_callback('/{{\s?(\w+)\s?}}/u', function ($matches) {
            if (is_array($matches)) {
                $item = substr($matches[0], 2, -2); // removes {{ and }}
                return isset($this->dict[$item]) ? $this->dict[$item] : null;
            }

            $item = substr($matches, 2, -2); // removes {{ and }}
            return isset($this->dict[$item]) ? $this->dict[$item] : null;
        }, $string);
    }

    /**
     * It adds a part of a pair of operation.<br>
     * <b>Example:</b><br>
     * <pre>
     * // "field.funname(20)" where value is the first part and 20 is the second part
     * $this->addBinOpen(true,'where',false,'fn','funname');
     * // "fieldname = 20"
     * $this->addBinOpen(true,'where',false,'field','fieldname');
     * </pre>
     *
     * @param bool        $first    if it is the first part or second part of the expression.
     * @param string      $position =['where','set','else','init'][$i]
     * @param bool        $inFunction If it is inside a function.
     * @param string      $type     =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
     * @param string      $name     name of the field
     * @param null|string $ext      extra parameter.
     */
    public function addBinOper(&$first, $position, $inFunction, $type, $name, $ext = null) {
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
            if ($position === 'where') {
                $first = true;
            }
        }
    }

    /**
     * Add params of a function (binary operation).
     *
     * @param string      $position =['where','set','else','init'][$i]
     * @param string      $type     =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
     * @param string      $name     name of the field
     * @param null|string $ext      extra parameter.
     */
    public function addParam($position, $type, $name, $ext = null) {
        $position = (!$position) ? 'init' : $position;
        $numLine = count($this->{$position}[$this->langCounter]) - 1;
        $idx = count($this->{$position}[$this->langCounter][$numLine]) - 1;
        if (!isset($this->{$position}[$this->langCounter][$numLine][$idx])) {
            $this->{$position}[$this->langCounter][$numLine][$idx] = [];
        }
        $this->{$position}[$this->langCounter][$numLine][$idx][] = [$type, $name, $ext];
    }

    /**
     * It gets the case of a variable<br>
     * <b>Example:</b><br>
     * <pre>
     * MiniLang::getCase('Hello'); // returns "first" (the first letter is upper, the rest is lower)
     * </pre>
     * @param string $variableName
     * @return string=['upper','lower','first','normal][$i]
     */
    public static function getCase($variableName) {
        if ($variableName===strtoupper($variableName)) {
            return 'upper';
        }
        $low=strtolower($variableName);
        if ($variableName===$low) {
            return 'lower';
        }
        if ($variableName===ucfirst($low)) {
            return 'first';
        }
        return 'normal';
    }

    /**
     * It adds an operation (such as =,<,+,etc.)
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param bool   $first    If it's true then it is the first value of a binary
     * @param string $opName
     */
    public function addOp($position, &$first, $opName) {
        $position = (!$position) ? 'init' : $position;
        $f = count($this->{$position}[$this->langCounter]) - 1;
        if ($first) {
            $this->{$position}[$this->langCounter][$f][4] = $opName;
            $first = false;
        } else {
            $this->{$position}[$this->langCounter][$f][] = $opName;
        }
    }

    /**
     * It compiles a list of token previously obtained by separate() or separate2()
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param int    $numLine
     *
     * @return string
     */
    public function compileTokens($position, $numLine = 0) {
        $code = [];
        foreach ($this->{$position}[$numLine] as $item) {
            $p = 0;
            $type = $item[$p];
            $i1 = $item[$p + 1];
            //$i2 = isset($item[$p + 2]) ? $item[$p + 2] : null;
            //$i3 = isset($item[$p + 3]) ? $item[$p + 3] : null;
            $p = 1;
            switch ($type) {
                case 'pair':
                    if (count($item) <= 4) {
                        $this->compileTokenField($item, $p, $code);
                    } else {
                        while (true) {
                            $this->compileTokenField($item, $p, $code);
                            if (!isset($item[$p])) {
                                break;
                            }
                            $this->compileTokenPairOp($position, $item, $p, $code);
                        }
                    }
                    if ($position !== 'where') {
                        $code[] = ";\n";
                    }
                    break;
                case 'logic':
                    switch ($i1) {
                        case 'and':
                            $code[] = ' && ';
                            break;
                        case 'or':
                            $code[] = ' || ';
                            break;
                        default:
                            $code[] = '??logic:$u1???';
                            break;
                    }

                    break;
            }
        }
        return implode('', $code);
    }

    /**
     * It compile a token (the union between the "pair" token)
     *
     * @param string   $position =['where','set','else','init'][$i]
     * @param array    $arrayToken
     * @param int      $startPosition
     * @param string[] $code
     */
    private function compileTokenPairOp($position, $arrayToken, &$startPosition, &$code) {
        $i1 = $arrayToken[$startPosition];
        switch ($i1) {
            case '=':
                $r = ($position === 'where') ? '==' : '=';
                break;
            case '&':
                $r = '.';
                break;
            default:
                $r = $i1;
                break;
        }
        $startPosition++;
        $code[] = $r;
    }

    /**
     * It compiles a token (field)
     *
     * @param array    $arrayToken
     * @param int      $startPosition
     * @param string[] $code
     */
    private function compileTokenField($arrayToken, &$startPosition, &$code) {
        $i1 = $arrayToken[$startPosition];
        $i2 = isset($arrayToken[$startPosition + 1]) ? $arrayToken[$startPosition + 1] : null;
        $i3 = isset($arrayToken[$startPosition + 1]) ? $arrayToken[$startPosition + 2] : null;
        switch ($i1) {
            case 'fn':
                // fn(1),functioname(2),arguments(3 it could be array)
                if (is_array($i3)) {
                    $codeArg = [];
                    foreach ($i3 as $argToken) {
                        $pArg = 0;
                        $this->compileTokenField($argToken, $pArg, $codeArg);
                    }
                    $argTxt = implode(',', $codeArg);
                    /** @see \eftec\minilang\MiniLang::callFunction */
                    if ($i2 === 'flip') {
                        $code[] = $codeArg[0] . "=\$this->callFunction('$i2',[$argTxt])";
                    } else {
                        $code[] = "\$this->callFunction('$i2',[$argTxt])";
                    }

                } else {
                    //$i3=($i3===null)?'':',['.$i3.']';
                    /** @see \eftec\minilang\MiniLang::callFunction */
                    $code[] = "\$this->callFunction('$i2',[$i3])";
                }
                $startPosition += 3;
                break;
            case 'field':
                // field(1),fieldname(2),prop(3)
                if ($i3 === null) {
                    $code[] = "\$this->dict['$i2']";
                } else {
                    $code[] = "\$this->dict['$i2']['$i3']";
                }
                $startPosition += 3;
                break;
            case 'subfield':
                // field(1),fieldname(2),prop(3)
                if ($i3 === null) {
                    $code[] = "\$this->dict['$i2']";
                } else {
                    $code[] = "\$this->dict['$i2']['$i3']";
                }
                $startPosition += 3;
                break;
            case 'var':
            case 'subvar':
                // $field(1),fieldname(2),prop(3)
                if ($i3 === null) {
                    $code[] = "\$GLOBALS['$i2']";
                } else {
                    $code[] = "\$GLOBALS['$i2']['$i3']";
                }
                $startPosition += 3;
                break;
            case 'number':
                // number(1).value(2).null(3)
                $code[] = $i2;
                $startPosition += 3;
                break;
            case 'string':
                // number(1).value(2).null(3)
                $code[] = "'$i2'";
                $startPosition += 3;
                break;
            case 'stringp':
                $code[] = "\$this->getValueP('$i2')";
                $startPosition += 3;
                break;
            default:
                $code[] = "???token:[$i1]????";
        }
        // return $code;
    }

    /**
     * It adds a logic to the array of the position.
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param bool   $first    If it's true then it is the first value of a binary
     * @param string $name     name of the logic
     */
    private function addLogic($position, $first, $name) {
        if ($first) {
            $position = (!$position) ? 'init' : $position;
            $this->{$position}[$this->langCounter][] = ['logic', $name];
        } else {
            $error="Error: Logic operation in the wrong place";
            $this->throwError($error);
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
    public function serialize() {
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
    public static function unserialize($serializeText, $caller, $serviceClass = null) {
        /** @var MiniLang $obj */
        $obj = unserialize($serializeText);
        $obj->caller = $caller;
        $obj->serviceClass = $serviceClass;
        return $obj;
    }

}