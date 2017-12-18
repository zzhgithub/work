<?php

namespace app\index\controller;

use \think\Controller;
use think\Request;
use think\View;
use app\index\model\Donate;

class DonateCtl extends Controller
{

    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
    }

    /**
     * 捐款列表
     * @param Request $request
     * @return string|\think\response\Json
     */
    public function donateList(Request $request)
    {
        $donate = new Donate();
        $list = $donate->where(['state' => 1])->order('id desc')->paginate(5);
        $items = $list->items();
        $response = new \stdClass();
        if ($request->isAjax()) {
            $response->code = 400;
            $response->msg = '';
            $response->data = array();
            if (!empty($items)) {
                $response->code = 0;
                $response->data = $items;
            }
            return json($response);
        }
        $this->assign('curPage', 1);
        $this->assign('list', $items);
        return $this->view->fetch('donate/list');
    }

    /**
     * 捐款详情
     * @param $id
     * @return string
     */
    public function donateDetail($id)
    {
        $id = intval($id);
        if (!$id) {
            $this->redirect('/');
        }
        $detail = Donate::get(['id' => $id, 'state' => 1]);
        if (empty($detail)){
            $this->redirect('/');
        }
        $this->assign('detail',$detail);
        return $this->view->fetch('donate/detail');
    }

    public function donateDo($id){
        $id = intval($id);
        if (!$id) {
            $this->redirect('/');
        }
        $donate = new Donate();
        $detail = $donate->where(['id' => $id, 'state' => 1])->field('id,name,des,img')->find();
        if (empty($detail)){
            $this->redirect('/');
        }
        $this->assign('detail',$detail);
        return $this->view->fetch('donate/pay');
    }

}
