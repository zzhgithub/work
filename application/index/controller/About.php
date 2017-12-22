<?php
namespace app\index\controller;

use app\index\model\Config;
use \think\Controller;
use think\Request;

class About extends Controller
{

    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function index()
    {
        $about = Config::get(1);
        $this->assign('about',$about);
        return $this->fetch('about/index');
    }
}
