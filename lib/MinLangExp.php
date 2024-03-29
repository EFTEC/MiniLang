<?php

namespace eftec\minilang;
/**
 * Use future.
 */
class MinLangExp
{
    /** @var MiniLang|null */
    public ?MiniLang $parent = null;

    /**
     * @param MiniLang $parent
     */
    public function __construct(MiniLang $parent)
    {
        $this->parent = $parent;
    }
    public function when($value1,$comparison,$value2): void
    {
        $f=false;
        $this->parent->addOp('where',$f,$comparison);
        $this->parent->addBinOper($f,'where',false,'string',$value1);
    }
    public function compare($value1,$comparison,$value2): MinLangExp
    {
        return $this;
    }
    public function and(): MinLangExp
    {
        return $this;
    }
    public function set($value1,$comparison,$value2): MinLangExp
    {
        return $this;
    }
    public function then(): MinLangExp
    {
        return $this;
    }
    public function end(): MinLangExp
    {
        return $this;
    }



}
