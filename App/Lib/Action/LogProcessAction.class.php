<?php
/**
 * 应用管理 控制器类
 * @author 蔡繁荣
 * @version  1.0.6 build 20171018
 */
class LogProcessAction extends CommonAction{

    public function _initialize(){
        parent::_initialize();

        $this->addPath('应用');
    }
    

    public function index(){
        $name = $this->getActionName();
        $model = D($name);

        // 请求参数
        $keyword    = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $is_actived = (isset($_GET['is_actived']) && $_GET['is_actived']!=='') ? intval($_GET['is_actived']) : ''; // 01类型
        $is_deleted = (isset($_GET['is_deleted']) && $_GET['is_deleted']!=='') ? intval($_GET['is_deleted']) : ''; // 01类型
        $order_by   = $_GET['order_by'] ? $_GET['order_by'] : 'id';
        $direction  = $_GET['direction'] ? $_GET['direction'] : 'desc';

        $cond = array();
        if($keyword != ''){
            $where['filename']    = array('like', '%'.$keyword.'%');
            $where['_logic']  = 'or';
            $cond['_complex'] = $where;
            $this->assign('keyword', $keyword);
        }


        $count = $model->where($cond)->count();
        
        import('@.ORG.Util.Page');
        $page = new Page($count, 15);
        $page->setConfig('prev' , '&laquo;');
        $page->setConfig('next' , '&raquo;');

        $max_cost_time = 0;
        if($count > 0){
            $list = $model->where($cond)->order($order_by.' '.$direction)->limit($page->firstRow, $page->listRows)->select();
            foreach ($list as $key => $rs) {
                if($rs['cost_time'] > $max_cost_time){
                    $max_cost_time = $rs['cost_time'];
                }
            }
        }else{
            $list = array();
        }

        $this->assign('max_cost_time', $max_cost_time);

        $this->assign('list', $list);
        $this->assign('page', $page->shows());
        $this->assign('order_by', $order_by);
        $this->assign('direction', $direction);
        $this->display();
    }


}

?>