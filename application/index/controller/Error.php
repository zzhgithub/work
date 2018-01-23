<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class Error <br>
 * @package app\index\controller <br>
 * @author mutou <br>
 * @version 1.0.0
 * @date 23/01/2018 <br>
 */
namespace app\index\controller;
use \think\Controller;
use \think\Request;

class Error extends Controller
{
    public function index(Request $request)
    {
        return $this->_empty();
    }

    public function _empty(){
        $this->redirect('/');
    }
}