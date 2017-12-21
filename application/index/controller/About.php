<?php
namespace app\index\controller;

use app\index\model\Act;
use app\index\model\Banner;
use app\index\model\Product;
use \think\Controller;
use think\Request;
use think\View;

class About extends Controller
{

    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function index(Request $request)
    {

        return $this->fetch('about/index');
    }
}
