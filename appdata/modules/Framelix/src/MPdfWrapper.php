<?php

namespace Framelix\Framelix;

use Mpdf\Mpdf;

use function call_user_func_array;

class MPdfWrapper extends Mpdf
{
    private mixed $onBeforeAddPage = null;
    private mixed $onAfterAddPage = null;

    /**
     * Called before a new page is generated
     * Warning: Avoid directly writing pdf content in this function, is will probably cause a recursion
     * @param callable $callable
     * @return void
     */
    public function onBeforeAddPage(callable $callable): void
    {
        $this->onBeforeAddPage = $callable;
    }

    /**
     * Called after a new page is generated
     * Warning: Avoid directly writing pdf content in this function, is will probably cause a recursion
     * @param callable $callable
     * @return void
     */
    public function onAfterAddPage(callable $callable): void
    {
        $this->onAfterAddPage = $callable;
    }

    public function AddPage(
        $orientation = '',
        $condition = '',
        $resetpagenum = '',
        $pagenumstyle = '',
        $suppress = '',
        $mgl = '',
        $mgr = '',
        $mgt = '',
        $mgb = '',
        $mgh = '',
        $mgf = '',
        $ohname = '',
        $ehname = '',
        $ofname = '',
        $efname = '',
        $ohvalue = 0,
        $ehvalue = 0,
        $ofvalue = 0,
        $efvalue = 0,
        $pagesel = '',
        $newformat = ''
    ) {
        if ($this->onBeforeAddPage) {
            call_user_func_array($this->onBeforeAddPage, []);
        }
        $result = parent::AddPage($orientation, $condition, $resetpagenum, $pagenumstyle, $suppress, $mgl, $mgr, $mgt,
            $mgb, $mgh, $mgf, $ohname, $ehname, $ofname, $efname, $ohvalue, $ehvalue, $ofvalue, $efvalue, $pagesel,
            $newformat);

        if ($this->onAfterAddPage) {
            call_user_func_array($this->onAfterAddPage, []);
        }
        return $result;
    }
}