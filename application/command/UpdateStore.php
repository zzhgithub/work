<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class ${NAME}   <br>
 * @author mutou <br>
 * @version 1.0.0
 * @description todo <br>
 * @date 2017/12/27 <br>
 */

namespace app\command;

use \app\index\model\Order;
use app\index\model\OrderItem;
use \think\console\Command;
use \think\console\Input;
use \think\console\Output;

class UpdateStore extends Command
{
    protected function configure()
    {
        $this->setName('updateStore')->setDescription('Update store while order was not paied orderd before 30 mins');
    }

    protected function execute(Input $input, Output $output)
    {
        /* 永不超时 */
        ini_set('max_execution_time', 0);
        $prefix = config("database.prefix");
        $fp = fopen("lock.txt", "w+");
        if(!flock($fp,LOCK_EX | LOCK_NB)){
            echo "系统繁忙，请稍后再试";
            return false;
        }

        $orderObj = null;
        $orderObj = new Order();
        $deadlineTime = date('Y-m-d H:i:s', (time() - 1800));
        $orders = $orderObj->where(['is_paied' => 0, 'is_update' => 0])->where('create_time', '<', $deadlineTime)->field('order_no')->select();
        if ($orders){
            $res = null;
            foreach ($orders as $order){
                $orderItemObj = null;
                $orderItemObj = new OrderItem();
                $res = $orderItemObj->alias('a')->join($prefix.'product b','a.pro_id = b.id','LEFT')->where(['a.order_no'=>$order->order_no])->update(['`b`.`store`' => ['exp', '`b`.`store`+ `a`.`count`']]);
                $msg = $res ? '库存更新成功' : '库存更新失败';
                file_put_contents('data/log/update_store/' . date('Y-m-d') . '-updateStore.log', date('Y-m-d H:i:s') . ':-----> 订单：' . $order->order_no . '; '. $msg . PHP_EOL . '------------------------------------------' . PHP_EOL,FILE_APPEND);
                // 更新订单表
                $order->save(['is_update' => 1],['order_no' => $order->order_no]);
            }
        }
        flock($fp,LOCK_UN);     //释放锁
        fclose($fp);
    }
}