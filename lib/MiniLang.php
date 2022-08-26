<?php /** @noinspection MultiAssignmentUsageInspection */
/** @noinspection PhpUnused */
/** @noinspection TypeUnsafeArraySearchInspection */
/** @noinspection TypeUnsafeComparisonInspection */

namespace eftec\minilang;

use Exception;
use RuntimeException;

/**
 * It's a mini language parser. It uses the build-in token_get_all function to performance purpose.
 * Class MiniLang
 *
 * @package  eftec\minilang
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  2.22 2022-08-26
 * @link     https://github.com/EFTEC/MiniLang
 * @license  LGPL v3 (or commercial if it's licensed)
 */
class MiniLang
{
    public const VERSION = '2.22';
    /** @var array When operators (if any) */
    public $where = [];
    /** @var array Set operators (if any) */
    public $set = [];
    /** @var array Set operators (if any) */
    public $else = [];
    /** @var array Init operators (if any) */
    public $init = [];
    /** @var array Loop operators (if any) */
    public $loop = [];
    /** @var string[] */
    public $wherePHP = [];
    /** @var string[] */
    public $setPHP = [];
    /** @var string[] */
    public $elsePHP = [];
    /** @var string[] */
    public $initPHP = [];
    /** @var string[] */
    public $loopPHP = [];
    /** @var string[] */
    public $commentPHP = [];
    /** @var array */
    public $areaName;
    /** @var array values per the special area */
    public $areaValue = [];
    public $serviceClass;
    public $throwError = true;
    public $errorLog = [];
    public $numCode = -1;
    //private $stack;
    /**
     * @var bool <b>If true</b>: then this class is extended by another class that includes the definition of the
     *      tasks<br>
     *           <b>If false</b>: (default value) then the definition of the classes must be evaluated every time
     */
    public $usingClass = false;
    /**
     * @var bool <b>if true</b>: then the variables inside the language are case-sensitive.<br>
     *           It doesn't consider other variables<br>
     *           Example: field1=20 and FIELD1=20 are different<br>
     *           <b>if false</b>: then the variables are not case-sensitive. Howeve, every value stored must be stored
     *           in a lowercase array<br> Example: field1=20 and FIELD1=20 are the same (but the value must be stored
     *           in 'field1')<br>
     */
    public $caseSensitive = true;
    /** @var array */
    protected $dict;
    // for runtime:
    protected $specialCom;
    /** @var object|null for callbacks */
    protected $caller;
    public $debugLine = 0;
    protected $txtCounter = 0;
    protected $langCounter = 0;

    /**
     * MiniLang constructor.
     *
     * @param object      $caller     Who is calling this language. It is used for callbacks.
     * @param array       $dict       The dictionary with the values
     * @param array       $specialCom Special commands. it calls a function of the caller.
     * @param array       $areaName   It marks special areas that could be called as "<namearea> somevalue"
     * @param null|object $serviceObject
     */
    public function __construct($caller = null, &$dict = [], $specialCom = [], $areaName = [], $serviceObject = null)
    {
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
    public function reset(): void
    {
        $this->langCounter = -1;
        $this->txtCounter = -1;
        $this->where = [];
        $this->set = [];
        $this->else = [];
        $this->init = [];
        $this->loop = [];
        $this->wherePHP = [];
        $this->setPHP = [];
        $this->elsePHP = [];
        $this->initPHP = [];
        $this->loopPHP = [];
        $this->commentPHP = [];
        $this->errorLog = [];

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
    public static function getCase($variableName): string
    {
        if ($variableName === strtoupper($variableName)) {
            return 'upper';
        }
        $low = strtolower($variableName);
        if ($variableName === $low) {
            return 'lower';
        }
        if ($variableName === ucfirst($low)) {
            return 'first';
        }
        return 'normal';
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
    public static function unserialize($serializeText, $caller, $serviceClass = null): MiniLang
    {
        /** @var MiniLang $obj */
        /** @noinspection UnserializeExploitsInspection */
        $obj = unserialize($serializeText);
        $obj->caller = $caller;
        $obj->serviceClass = $serviceClass;
        return $obj;
    }

    /**
     * It sets the object caller.
     *
     * @param object|null $caller
     */
    public function setCaller($caller): void
    {
        $this->caller = $caller;
    }

    /**
     * It sets the whole dictionary.
     *
     * @param array $dict This value is passes as reference, so it returns the modified values.
     */
    public function setDict(&$dict): void
    {
        $this->dict = &$dict;
    }

    /**
     * It returns the value of an index of the dictionary<br>
     * <b>Example</b><br>
     * <pre>
     * $this->getDictEntry('customer'); // returns the value of the variable or null
     * $this->getDictEntry('customer.name'); // returns the value of the variable or null
     * $this->getDictEntry('customer.name.subname'); // returns the value of the variable (the limit is 3 sublevels)
     * </pre>
     *
     * @param string $name name of the index of the dictionary
     *
     * @return mixed It returns null if not found
     */
    public function getDictEntry($name)
    {
        if (strpos($name, '.') === false) {
            return $this->dict[$name] ?? null;
        }
        $arr = explode('.', $name, 3);
        if (count($arr) === 2) {
            return isset($this->dict[$arr[0]]) ? $this->dict[$arr[0]][$arr[1]] : null;
        }
        return isset($this->dict[$arr[0]]) ? $this->dict[$arr[0]][$arr[1]][$arr[2]] : null;

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
    public function _Param($a1, $a2)
    {
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
     * It sends an expression to the MiniLang, and it is decomposed in its parts.<br>
     * The script is not executed but parsed. You can obtain the result with $this->wherePHP,$this->setPHP, etc.<br>
     *
     * @param string $miniScript Example: "when $field1>0 then $field1=3 and field2=20"
     * @param int    $numLine    If -1 (default value), then it adds a new separate (automatic number of line).
     *                           If set, then it adds in the number of line.
     *
     * @see \eftec\minilang\MiniLang::serialize To pre-calculate this result and improve the performance.
     */
    public function separate2($miniScript, $numLine = -1): void
    {
        $this->txtCounter = ($numLine < 0) ? $this->txtCounter + 1 : $numLine;
        $this->separate($miniScript);
        $this->wherePHP[$this->txtCounter] = $this->compileTokens('where', $this->txtCounter);
        $this->setPHP[$this->txtCounter] = $this->compileTokens('set', $this->txtCounter);
        $this->initPHP[$this->txtCounter] = $this->compileTokens('init', $this->txtCounter);
        $this->elsePHP[$this->txtCounter] = $this->compileTokens('else', $this->txtCounter);
        $this->loopPHP[$this->txtCounter] = $this->compileTokens('loop', $this->txtCounter);
        if (!$this->loopPHP[$this->txtCounter]) {
            $this->loopPHP[$this->txtCounter] = 'null';
        } else if ($this->loopPHP[$this->txtCounter][-1] === ',') {
            $this->loopPHP[$this->txtCounter] = '[' . $this->loopPHP[$this->txtCounter] . 'null]';
        } else {
            $this->loopPHP[$this->txtCounter] = '[' . $this->loopPHP[$this->txtCounter] . ']';
        }
    }

    /**
     * It sends an expression to the MiniLang, and it is decomposed in its parts. The script is not executed but parsed.
     *
     * @param string $miniScript Example: "when $field1>0 then $field1=3 and field2=20"
     * @param int    $numLine    If -1 (default value), then it adds a new separate (automatic number of line).
     *                           If set, then it adds in the number of line.
     *
     * @see \eftec\minilang\MiniLang::serialize To pre-calculate this result and improve the performance.
     */
    public function separate($miniScript, $numLine = -1): void
    {
        $this->langCounter = ($numLine < 0) ? $this->langCounter + 1 : $numLine;
        $this->where[$this->langCounter] = [];
        $this->set[$this->langCounter] = [];
        $this->else[$this->langCounter] = [];
        $this->init[$this->langCounter] = [];
        $this->loop[$this->langCounter] = [];
        $rToken = token_get_all("<?php " . $miniScript);
        $rToken[] = ''; // avoid last operation
        $count = count($rToken) - 1;
        $first = true;
        $inFunction = false;
        /** @var string $position =['where','set','else','init'][$i]
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
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
                        // los negativos no van aqui
                        $this->addBinOper($first, $position, $inFunction, 'number'
                            , $v[1]);
                        break;
                    case T_ELSE:
                        //adding a new else
                        $position = 'else';
                        $first = true;
                        break;
                    case T_STRING:
                    case T_BREAK:
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
                                case 'loop':
                                    //adding a new set
                                    $position = 'loop';
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
                                        array_splice($rToken, $i + 2, 0, [[T_STRING, substr($rTokenNext[1], 1)]]);
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
                    case 287: // T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG:
                        //var_dump('amp');
                        $this->addOp($position, $first, '&');
                        break;
                    default:
                        //var_dump($v[0]);
                        //var_dump(token_name($v[0]));
                        //var_dump($v);
                        break;
                }
                //var_dump(token_name($v[0]));
                //var_dump($v);
            } else {
                //var_dump("simple:".$v);
                switch ($v) {
                    case '-':
                        if (is_array($rTokenNext)
                            && ($rTokenNext[0] == T_LNUMBER
                                || $rTokenNext[0] == T_DNUMBER)
                        ) {
                            // it's a negative value
                            if ($position !== 'where') {
                                // fixed for a-2 and a=a-2
                                $first = false;
                            }
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
     * It adds a part of a pair of operation.<br>
     * <b>Example:</b><br>
     * <pre>
     * // "field.funname(20)" where value is the first part and 20 is the second part
     * $this->addBinOpen(true,'where',false,'fn','funname');
     * // "fieldname = 20"
     * $this->addBinOpen(true,'where',false,'field','fieldname');
     * </pre>
     *
     * @param bool        $first      if it is the first part or second part of the expression.
     * @param string      $position   =['where','set','else','init'][$i]
     * @param bool        $inFunction If it is inside a function.
     * @param string      $type       =['string','stringp','var','subvar','number','field','subfield','fn','special'][$i]
     * @param string      $name       name of the field
     * @param null|string $ext        extra parameter.
     */
    public function addBinOper(&$first, $position, $inFunction, $type, $name, $ext = null): void
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
            if ($type === 'number' && $name < 0 && $position !== 'where') {
                if($f2 - 1 <= 0 || $expr[$f][$f2 - 1] !== '=') {
                    // value=something-1  => value=something+-1
                    // we exclude value=-1
                    $expr[$f][$f2] = '+';
                    $f2++;
                }
            }
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
    public function addParam($position, $type, $name, $ext = null): void
    {
        $position = (!$position) ? 'init' : $position;
        $numLine = count($this->{$position}[$this->langCounter]) - 1;
        $idx = count($this->{$position}[$this->langCounter][$numLine]) - 1;
        if (!isset($this->{$position}[$this->langCounter][$numLine][$idx])) {
            $this->{$position}[$this->langCounter][$numLine][$idx] = [];
        }
        $this->{$position}[$this->langCounter][$numLine][$idx][] = [$type, $name, $ext];
    }

    /**
     * It adds an operation (such as =,<,+,etc.)
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param bool   $first    If it's true then it is the first value of a binary
     * @param string $opName
     */
    public function addOp($position, &$first, $opName): void
    {
        $position = (!$position) ? 'init' : $position;
        $f = count($this->{$position}[$this->langCounter]) - 1;
        if ($first) {
            $this->{$position}[$this->langCounter][$f][4] = $opName;
            $first = false;
        } else {
            $this->{$position}[$this->langCounter][$f][] = $opName;
        }
    }
    /*
    public function when(): MinLangExp
    {
        $this->stack=new MinLangExp($this);
        return $this->stack;
    }
    */

    /**
     * It adds a logic to the array of the position.
     *
     * @param string $position =['where','set','else','init'][$i]
     * @param bool   $first    If it's true then it is the first value of a binary
     * @param string $name     name of the logic
     */
    protected function addLogic($position, $first, $name): void
    {
        if ($first) {
            $position = (!$position) ? 'init' : $position;
            $this->{$position}[$this->langCounter][] = ['logic', $name];
        } else {
            $error = "Error: Logic operation in the wrong place";
            $this->throwError($error);
        }

    }

    protected function throwError($msg): void
    {
        $this->errorLog[] = $msg . ' Line:' . $this->debugLine;
        if ($this->throwError) {
            throw new RuntimeException($msg . ' Line:' . $this->debugLine);
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
    public function compileTokens($position, $numLine = 0): string
    {
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
                        $this->compileTokenField($item, $p, $code, $position);
                    } else {
                        while (true) {
                            $this->compileTokenField($item, $p, $code, $position);
                            if (!isset($item[$p])) {
                                break;
                            }
                            $this->compileTokenPairOp($position, $item, $p, $code);
                        }
                    }
                    if ($position !== 'where' && $position !== 'loop') {
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
     * It compiles a token (field)
     *
     * @param array    $arrayToken
     * @param int      $startPosition
     * @param string[] $code
     * @param string   $position
     */
    protected function compileTokenField($arrayToken, &$startPosition, &$code, $position): void
    {
        $i1 = $arrayToken[$startPosition];
        $i2 = $arrayToken[$startPosition + 1] ?? null;
        //todo: revisar linea siguiente:
        $i3 = isset($arrayToken[$startPosition + 1]) ? $arrayToken[$startPosition + 2] : null;

        switch ($i1) {
            case 'fn':
                // fn(1),functioname(2),arguments(3 it could be an array)
                if (is_array($i3)) {
                    $codeArg = [];
                    foreach ($i3 as $argToken) {
                        $pArg = 0;
                        $this->compileTokenField($argToken, $pArg, $codeArg, $position);
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
                    if ($position === 'loop' && $startPosition === 1) {
                        // for loop
                        $code[] = "'$i2',";
                    } else if ($i2 == 'break') {
                        $code[] = "return 'break'";
                    } else {
                        $code[] = "\$this->dict['$i2']";
                    }
                } else {
                    switch ($i3) {
                        case '_first':
                            $code[] = "\$this->dict['$i2'][0]";
                            break;
                        case '_last':
                            $code[] = "\end(\$this->dict['$i2'])";
                            break;
                        case '_count':
                            $code[] = "\count(\$this->dict['$i2'])";
                            break;
                        default:
                            $code[] = "\$this->dict['$i2']['$i3']";
                    }

                }
                $startPosition += 3;
                break;
            case 'subfield':
                // field(1),fieldname(2),prop(3)
                if ($i3 === null) {
                    $code[] = "\$this->dict['$i2']";
                } else {
                    switch ($i3) {
                        case '_first':
                            $code[] = "\$this->dict['$i2'][0]";
                            break;
                        case '_count':
                            $code[] = "\count(\$this->dict['$i2'])";
                            break;
                        case '_last':
                            $code[] = "\end(\$this->dict['$i2'])";
                            break;
                        default:
                            $code[] = "\$this->dict['$i2']['$i3']";
                    }
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
                if ($i2 === 0 || $i2 === '0') {
                    // why? it is because field==null is equals than field==0
                    // however, field==null is not equals to field=='0'
                    $code[] = "'$i2'";
                } else {
                    $code[] = $i2;
                }
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
            case 'special':
                $code[] = "\$this->caller->$i2($i3)";
                $startPosition += 3;
                break;
            default:
                $code[] = "???token:[$i1]????";
        }
        // return $code;
    }

    /**
     * It compiles a token (the union between the "pair" token)
     *
     * @param string   $position =['where','set','else','init'][$i]
     * @param array    $arrayToken
     * @param int      $startPosition
     * @param string[] $code
     */
    protected function compileTokenPairOp($position, $arrayToken, &$startPosition, &$code): void
    {
        $i1 = $arrayToken[$startPosition];
        switch ($i1) {
            case '=':

                if (($position === 'where')) {
                    $r = '==';
                } else {
                    $r = ($position === 'loop') ? '' : '=';
                }
                break;
            case '+':
                $r = ($position === 'set' && $startPosition === 4) ? '+=' : '+';
                break;
            case '-':
                $r = ($position === 'set' && $startPosition === 4) ? '-=' : '-';
                break;
            case '&':
                $r = '.';
                break;
            case '<>':
                $r = '!=';
                break;
            default:
                $r = $i1;
                break;
        }
        $startPosition++;
        $code[] = $r;
    }

    /**
     * It evaluates a logic.  It uses the second motor (it generates a php code).
     *
     * @param int $numLine
     *
     * @return bool|string it returns the evaluation of the logic, or it returns the value special (if any).
     */
    public function evalLogic2($numLine = 0)
    {
        $where = $this->wherePHP[$numLine];
        $where = "return ($where);";
        return eval($where);
    }

    /**
     * It evaluates the position "set","init" or "else". It uses the second motor (it generates a php code). It does
     * not consider if WHERE is true or not.
     *
     * @param int    $numLine  number of expression
     * @param string $position =['set','else','init'][$i]
     *
     * @return mixed
     */
    public function evalSet2($numLine = 0, $position = 'set')
    {
        $position .= 'PHP';
        $set = $this->{$position}[$numLine];
        return eval($set);
    }

    /**
     * It generates a PHP class, and it could be evaluated (eval command) or it could be store, so it could be
     * called.<br>
     * $mini->separate2($expr);
     * echo $mini->generateClass2(); // it returns a php class (code)
     *
     * @param string      $className Name of the class to generate
     * @param null|string $namespace The name of the namespace. If null then it doesn't use namespace.
     * @param null|string $filename  The full filename to save the class. If null, then it is not saved
     * @param null|string $header    The header of the class. If null, then it is generated automatically.
     * @return string|bool it returns the class if $filename is null. It returns true or false if it saved the file
     */
    public function generateClass($className = 'RunClass', $namespace = null, $filename = null, $header = null)
    {
        if ($header === null) {
            $header = "<?php\n";
            if ($namespace) {
                $header .= "namespace $namespace;\n";
            }
            $header .= "use eftec\minilang\MiniLang;\n";
        }
        $code = $header;
        $code .= "\n/**\n";
        $code .= "* This class has the motor and definitions of the Mini Language.\n";
        $code .= "*.\n";
        $code .= "* @package $namespace.\n";
        $code .= "* @generated by https://github.com/EFTEC/MiniLang.\n";
        $code .= "* @version " . self::VERSION . " " . date('c') . ".\n";
        $code .= "*/\n";
        $code .= "class $className extends MiniLang {\n";
        $code .= "\tpublic \$numCode=" . $this->langCounter . "; // num of lines of code \n";
        $code .= "\tpublic \$usingClass=true; // if true then we are using a class (this class) \n";

        $this->generateClassWhere($code);
        $this->generateClassLoop($code);
        $this->generateClassSet($code);
        $this->generateClassSet($code, 'else');
        $this->generateClassSet($code, 'init');

        $code .= "} // end class\n";
        if ($filename) {
            try {
                $code = @file_put_contents($filename, $code);
            } catch (Exception $ex) {
                $this->throwError($ex->getMessage());
                $code = false;
            }
        }
        return $code;
    }

    protected function generateClassWhere(&$code): void
    {
        $code .= "\tpublic function whereRun(\$lineCode=0):bool {\n";
        $code .= "\t\tswitch(\$lineCode) {\n";
        // group empties
        $align = "\t\t\t";
        $empties = false;
        for ($i = 0; $i <= $this->txtCounter; $i++) {
            if (!$this->wherePHP[$i]) {
                $empties = true;
                $code .= $align . "case $i:\n";
            }
        }
        if ($empties) {
            $code .= $align . "\treturn true; // nothing to do\n";
        }
        for ($i = 0; $i <= $this->langCounter; $i++) {
            if ($this->wherePHP[$i]) {
                $code .= "\t\t\tcase $i:\n";
                $code .= "\t\t\t\t\$result=" . $this->wherePHP[$i] . ";\n";
                $code .= "\t\t\t\tbreak;\n";
            }
        }
        $code .= "\t\t\tdefault:\n";
        $code .= "\t\t\t\t\$result=false;\n";
        $code .= "\t\t\t\t\$this->throwError('Line '.\$lineCode.' is not defined');\n";
        $code .= "\t\t}\n";
        $code .= "\t\treturn \$result;\n";
        $code .= "\t} // end function WhereRun\n";
    }

    protected function generateClassLoop(&$code): void
    {
        $code .= "\tpublic function loopRun(\$lineCode=0):?array {\n";
        $code .= "\t\tswitch(\$lineCode) {\n";
        // group empties
        $align = "\t\t\t";
        $empties = false;
        for ($i = 0; $i <= $this->txtCounter; $i++) {
            if (!$this->loopPHP[$i]) {
                $empties = true;
                $code .= $align . "case $i:\n";
            }
        }
        if ($empties) {
            $code .= $align . "\treturn true; // nothing to do\n";
        }
        for ($i = 0; $i <= $this->langCounter; $i++) {

            if ($this->loopPHP[$i]) {
                $code .= "\t\t\tcase $i:\n";
                $code .= "\t\t\t\t\$result=" . $this->loopPHP[$i] . ";\n";
                $code .= "\t\t\t\tbreak;\n";
            }
        }
        $code .= "\t\t\tdefault:\n";
        $code .= "\t\t\t\t\$result=null;\n";
        $code .= "\t\t\t\t\$this->throwError('Line '.\$lineCode.' is not defined');\n";
        $code .= "\t\t}\n";
        $code .= "\t\treturn \$result;\n";
        $code .= "\t} // end function loopRun\n";
    }

    protected function generateClassSet(&$code, $type = 'set'): void
    {
        // SetRun *****************
        $code .= "\tpublic function {$type}Run(\$lineCode=0) {\n";
        $code .= "\t\t\$result=null;\n";
        $code .= "\t\tswitch(\$lineCode) {\n";
        $align = "\t\t\t";
        // group empties
        $empties = false;
        for ($i = 0; $i <= $this->txtCounter; $i++) {
            if ($type !== '') {
                $typename = $type . 'PHP';
                $origin = $this->$typename[$i];
            } else {
                $origin = '';
            }
            if (!$origin) {
                $empties = true;
                $code .= $align . "case $i:\n";
            }
        }
        if ($empties) {
            $code .= $align . "\tbreak; // nothing to do\n";
        }
        for ($i = 0; $i <= $this->txtCounter; $i++) {
            if ($type !== '') {
                $typename = $type . 'PHP';
                $origin = $this->$typename[$i];
            } else {
                $origin = '';
            }
            if ($origin) {
                $code .= $align . "case $i:\n";
                $txt = str_replace("\n", "\n" . $align . "\t", $origin);
                $code .= $align . "\t" . $txt;
                $code = rtrim($code) . "\n";
                $code .= $align . "\tbreak;\n";
            }
        }
        $code .= "\t\t\tdefault:\n";
        $code .= "\t\t\t\t\$this->throwError('Line '.\$lineCode.' is not defined for $type');\n";
        $code .= "\t\t}\n";
        $code .= "\t\treturn \$result;\n";
        $code .= "\t} // end function {$type}Run\n";
    }





    /**
     * It evaluates all the expressions.<br>
     * If the position "where" is true, then it processes the "set" position (if any)<br>
     * If the position "where" is false, then it proccess the "else" position (if any)<br>
     *
     * @param bool $stopOnFound If true then it exits if some evaluation returns true.<br>
     *                          If false then it keeps evaluating all the operators<br>
     * @param bool $start       if true then it evaluates the "init" expression if any.
     */
    public function evalAllLogic($stopOnFound = false, $start = false): void
    {
        $upto = ($this->usingClass) ? $this->numCode : $this->langCounter;
        $loopStatus = [];
        $r = 0;
        $loop0 = null;
        $loop1 = null;
        for ($iPosition = 0; $iPosition <= $upto; $iPosition++) {
            try {
                $this->debugLine = $iPosition;
                $loop = $this->evalSet($iPosition, 'loop');
                if ($loop !== null) {
                    $loop0 = $loop[0];
                    $loop1 = $loop[1];
                }
                if ($loop !== null && $loop0 !== 'end') {
                    $keys = array_keys($loop1);
                    if (!isset($loopStatus[$loop0])) {
                        // we create a loop
                        if (count($keys) === 0) {
                            // the loop must be created, however it is empty
                            for ($findiPos = $iPosition; $findiPos <= $upto; $findiPos++) {
                                $loop = $this->evalSet($findiPos, 'loop');
                                if ($loop !== null && $loop[0] == 'end') {
                                    // we jump out of the loop.
                                    $iPosition = $findiPos + 1;
                                    break;
                                }
                            }
                            if ($iPosition > $upto) {
                                break; // for ($iPosition = 0; $iPosition <= $upto; $iPosition++) {
                            }
                        } else {
                            $loopStatus[$loop0] = [$keys, 0, $iPosition];
                            $this->dict[$loop0] = ['_key' => $keys[0], '_value' => $loop1[$keys[0]]];
                        }
                    } else {
                        // we increase the value of the loop
                        $loopStatus[$loop0][1]++;
                        $this->dict[$loop0] = ['_key' => $keys[$loopStatus[$loop0][1]], '_value' => $loop1[$keys[$loopStatus[$loop0][1]]]];
                    }
                }
                if ($start) {
                    $this->evalSet($iPosition, 'init');
                }
                if ($this->evalLogic($iPosition)) {
                    $break = $this->evalSet($iPosition);
                    if ($break === 'break') {
                        for ($findiPos = $iPosition; $findiPos <= $upto; $findiPos++) {
                            $loop = $this->evalSet($findiPos, 'loop');
                            if ($loop !== null && $loop[0] == 'end') {
                                // we jump out of the loop.
                                $iPosition = $findiPos + 1;
                                break;
                            }
                        }
                    }
                    if ($stopOnFound) {
                        break;
                    }
                } else {
                    $this->evalSet($iPosition, 'else');
                }
                if ($loop !== null && $loop0 === 'end') {
                    $r++;
                    if ($r > 99999) {
                        break;
                    }
                    $latestLoop = end($loopStatus);
                    if ($latestLoop[1] < count($latestLoop[0]) - 1) {
                        // we loop
                        $iPosition = $latestLoop[2] - 1;
                    } else {
                        // we remove the last element
                        array_pop($loopStatus);
                    }

                }
            } catch(Exception $ex) {
                throw new RuntimeException('Error in Minilang '.$ex->getMessage().' on line ['.$iPosition.']');
            }
        }
    }

    /**
     * It evaluates the position "set","init" or "else". It does not consider if WHERE is true or not.
     *
     * @param int    $numLine  number of expression
     * @param string $position =['set','else','init'][$i]
     *
     * @return mixed
     */
    public function evalSet($numLine = 0, $position = 'set')
    {
        if ($this->usingClass) {
            if ($position === 'init') {
                $this->initRun($numLine);
                return null;
            }
            if ($position === 'else') {
                $this->elseRun($numLine);
                return null;
            }
            if ($position === 'loop') {
                return $this->loopRun($numLine);
            }
            return $this->setRun($numLine);
        }
        $position = (!$position) ? 'init' : $position;
        $exp = $this->{$position}[$numLine];
        foreach ($exp as $v) {
            if ($v[0] === 'pair') {
                $name = $v[2];
                $ext = $v[3];
                $op = $v[4] ?? null;
                //$field0=$this->getValue($v[1],$v[2],$v[3],$this->caller,$dictionary);
                if (count($v) > 5) {
                    $field1 = $this->getValue($v[5], $v[6], $v[7]);
                } else {
                    $field1 = null;
                }
                $countv = count($v);
                for ($i = 8; $i < $countv; $i += 4) {
                    switch ($v[$i]) {
                        case '+': // if we add numbers then it adds, otherwise it concatenates.
                            $field2 = $this->getValue($v[$i + 1], $v[$i + 2], $v[$i + 3]);
                            if (is_numeric($field1) && is_numeric($field2)) {

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
                        if (is_object($GLOBALS[$name])) {
                            $GLOBALS[$name]->{$ext} = $field1;
                        } else {
                            $GLOBALS[$name][$ext] = $field1;
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
                        $error = "Literal [$v[2]] of the type [$v[1]] is not for set.";
                        $this->throwError($error);
                        break;
                    case 'field':
                        switch ($op) {
                            case '=':
                                $this->dict[$name] = (is_object($field1)) ? clone $field1 : $field1;
                                if ($position === 'loop') {
                                    return [$name, $field1];
                                }
                                break;
                            case '+';

                                $this->dict[$name] += $field1;
                                break;
                            case '-';
                                $this->dict[$name] -= $field1;
                                break;
                            case null:
                                // variable alone
                                if ($position === 'loop') {
                                    return [$name, $field1];
                                }
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
                        $error = "set $v[4] not defined for transaction.";
                        $this->throwError($error);
                        break;
                }
            }
        } // for
        return null;
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function initRun($lineCode)
    {
        $this->throwError('initRun() not defined yet, you must override this method');
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function elseRun($lineCode)
    {
        $this->throwError('elseRun() not defined yet, you must override this method');
    }

    /**
     * @param $lineCode
     * @return mixed
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function loopRun($lineCode)
    {
        $this->throwError('loopRun() not defined yet, you must override this method');
        return null;
    }

    /**
     * @param $lineCode
     * @return mixed
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function setRun($lineCode)
    {
        $this->throwError('setRun() not defined yet, you must override this method');
        return null;
    }

    /**
     * It obtains a value, including a variable, number, string, etc.
     *
     * @param string            $type =['subvar','var','number','string','stringp','field','subfield','fn','special'][$i]
     * @param string            $name name of the value. It is also used for the value of the variable.
     *                                <p> myvar => type=var, name=myvar</p>
     *                                <p> 123 => type=number, name=123</p>
     * @param string|array|null $ext  it is used for subvar, subfield and functions
     *
     * @return bool|int|mixed|string|null
     */
    public function getValue($type, $name, $ext)
    {
        if ($this->caseSensitive) {
            $namel =& $name;
        } else {
            $namel = strtolower($name);
            if (is_string($ext)) {
                $ext = strtolower($ext);
            }
        }
        switch ($type) {
            case 'subvar':
                // $a.field
                $rname = $GLOBALS[$name] ?? null;
                if ($ext[0] === '$') {
                    // $a.$b
                    $subext = substr($ext, 1);
                    $ext = $GLOBALS[$subext] ?? null;
                }
                $r = (is_object($rname)) ? $rname->{$ext} : $rname[$ext];
                break;
            case 'var':
                // $a
                $r = $GLOBALS[$namel] ?? null;
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
                    $this->errorLog[] = "field named [$name] does not exists";
                }
                break;
            case 'subfield':
                // field.sum is equals to sum(field)
                $args = [$this->dict[$namel] ?? null];
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
                $error = "value with type[$type] not defined";
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
    public function getValueP($string)
    {
        return preg_replace_callback('/{{\s?(\w+)\s?}}/u', function ($matches) {
            if (is_array($matches)) {
                $item = substr($matches[0], 2, -2); // removes {{ and }}
                return $this->dict[$item] ?? null;
            }

            $item = substr($matches, 2, -2); // removes {{ and }}
            return $this->dict[$item] ?? null;
        }, $string);
    }

    /**
     * It calls a function predefined by the caller. Example var.myfunction or somevar.value=myfunction(arg,arg)
     *
     * @param $nameFunction
     * @param $args
     *
     * @return mixed (it could return an error if the function fails)
     */
    public function callFunction($nameFunction, $args)
    {
        if (count($args) >= 1) {
            if (is_object($args[0])) {
                // the call is the form nameFunction(somevar) or somevar.nameFunction()
                if (isset($args[0]->{$nameFunction})) {
                    // someobject.field (nameFunction acts as a field name)
                    return $args[0]->{$nameFunction};
                }

                // the first argument is an object
                if (method_exists($args[0], $nameFunction)) {
                    $cp = $args;
                    unset($cp[0]); // it avoids passing the name of the function as argument
                    return $args[0]->{$nameFunction}(...$cp); //(...$cp);
                }

                // but the function is not defined.
                return $this->callFunctionCallerService($nameFunction, $args);
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
        return $this->callFunctionCallerService($nameFunction, $args);
    }

    protected function callFunctionCallerService($nameFunction, $args)
    {
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
        if ($this->serviceClass !== null && method_exists($this->serviceClass, $nameFunction)) {
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
            case 'str_contains':
            case 'contains':
                return $this->contains($args[0], $args[1]);
            case 'endswith':
            case 'str_ends_with':
                return $this->endsWith($args[0], $args[1]);
            case 'startwith':
            case 'str_starts_with':
                return $this->startWith($args[0], $args[1]);
            case 'flip':
                return (isset($args[0]) && $args[0]) ? 0 : 1;
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
                $error = "caller doesn't define field or method dateLastChange";
                $this->throwError($error);
                break;
            case 'fullinterval':
                if (isset($this->caller->dateInit)) {
                    return time() - $this->caller->dateInit;
                }
                if (method_exists($this->caller, 'dateInit')) {
                    return time() - $this->caller->dateInit();
                }
                $error = "caller doesn't define field or method dateInit";
                $this->throwError($error);
                break;
            default:
                $error = "function [$nameFunction] is not defined";
                $this->throwError($error);
                break;
        }

        return false;
    }

    public function contains($haystack, $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }

    public function endsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return !($length > 0) || substr($haystack, -$length) === $needle;
    }

    public function startWith($haystack, $needle): bool
    {
        return strpos($haystack, $needle) === 0;
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
    protected function callFunctionSet($nameFunction, &$args, $setValue): void
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
                $args[] = (is_object($setValue))?clone $setValue : $setValue; // it adds a second parameter
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

    /**
     * It evaluates a logic.
     *
     * @param int $numLine
     *
     * @return bool|string it returns the evaluation of the logic, or it returns the value special (if any).
     */
    public function evalLogic($numLine = 0)
    {
        if ($this->usingClass) {
            return $this->whereRun($numLine);
        }
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
                    default:
                        $error = "comparison $v[4] not defined for eval logic.";
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
                if ($addType === 'and' && !$r) {
                    return false;
                }
            }
        } // for
        return $r;
    }


    public function whereRun($lineCode) : bool
    {
        $this->throwError('whereRun() not defined yet, you must override this method');
        return false;
    }

    /**
     * It serializes the current minilang. It doesn't serialize the caller or service class.<br>
     * This method could be used to speed up the process, especially the function separate()<br>
     * separate() parse the text, and it converts into an array. We could pre-calculate
     * the result to improve the performance.
     *
     * @return string The current object serialized
     *
     * @see \eftec\minilang\MiniLang::separate
     */
    public function serialize(): string
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

    public function runOne($idCode = 0, $init = false): bool
    {
        if ($init) {
            $this->InitRun($idCode);
        }
        $r = $this->WhereRun($idCode);
        if ($r) {
            $this->SetRun($idCode);
        } else {
            $this->ElseRun($idCode);
        }
        return $r;
    }

}
