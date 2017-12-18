<?php
namespace app\index\controller;
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * @author mutou <br>
 * @version 1.0.0
 * @description product <br>
 * @date 2017/12/18 <br>
 */
use \think\Controller;
use \think\Request;
use \app\index\model\Product;
use \think\View;

class ProductCtl extends Controller{
    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
    }

    public function productList(Request $request){
        $product = new Product();
        $list = $product->where(['state' => 1])->order('id desc')->field('id,name,img,price,store')->paginate(4);
        $items = $list->items();
        if ($request->isAjax()) {
            if (!empty($items)) {
                return self::response(0,'success',$items);
            }
            return self::response(400);
        }
        $this->assign('curPage', 1);
        $this->assign('list', $items);
        return $this->view->fetch('product/list');
    }

    private static function response($code, $msg='', $data = [])
    {
        $response = new \stdClass();
        $response->code = $code;
        $response->data = $data;
        $response->msg = $msg;
        return json($response);
    }
}

 