<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: huajie <banhuajie@163.com>
// +----------------------------------------------------------------------

namespace Admin\Controller;

/**
 * 模型管理控制器
 * @author huajie <banhuajie@163.com>
 */

class ModelController extends AdminController {

    /**
     * 左侧导航节点定义
     * @author huajie <banhuajie@163.com>
     */
    static protected $nodes = array(
        array(
            'title'     =>  '模型管理',
            'url'       =>  'Model/index',
            'group'     =>  '扩展',
            'operator'  =>  array(
                //权限管理页面的五种按钮
                array('title'=>'新增','url'=>'model/add'),
                array('title'=>'编辑','url'=>'model/edit'),
                array('title'=>'改变状态','url'=>'model/setStatus'),
                array('title'=>'保存数据','url'=>'model/update'),
            ),
        ),
    );

    /**
     * 初始化方法，与AddonsController同步
     * @see AdminController::_initialize()
     * @author huajie <banhuajie@163.com>
     */
    public function _initialize(){
        $this->assign('_extra_menu',array(
                '已装插件后台'=>D('Addons')->getAdminList(),
        ));
        parent::_initialize();
    }

    /**
     * 模型管理首页
     * @author huajie <banhuajie@163.com>
     */
    public function index(){
        $map = array('status'=>array('gt',-1));
        $list = $this->lists('Model',$map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '模型管理';
        $this->display();
    }

    /**
     * 设置一条或者多条数据的状态
     * @author huajie <banhuajie@163.com>
     */
    public function setStatus(){
        /*参数过滤*/
        $ids = I('request.id');
        $status = I('request.status');
        if(empty($ids) || !isset($status)){
            $this->error('请选择要操作的数据');
        }

        /*拼接参数并修改状态*/
        $Model = 'Model';
        $map = array();
        if(is_array($ids)){
            $map['id'] = array('in', implode(',', $ids));
        }elseif (is_numeric($ids)){
            $map['id'] = $ids;
        }
        switch ($status){
            case -1 : $this->delete($Model, $map, array('success'=>'删除成功','error'=>'删除失败'));break;
            case 0  : $this->forbid($Model, $map, array('success'=>'禁用成功','error'=>'禁用失败'));break;
            case 1  : $this->resume($Model, $map, array('success'=>'启用成功','error'=>'启用失败'));break;
            default : $this->error('参数错误');break;
        }
    }


    /**
     * 新增页面初始化
     * @author huajie <banhuajie@163.com>
     */
    public function add(){
    	//获取所有的模型
    	$models = M('Model')->where(array('extend'=>0))->field('id,title')->select();

    	$this->assign('models', $models);
        $this->meta_title = '新增模型';
        $this->display();
    }

    /**
     * 编辑页面初始化
     * @author huajie <banhuajie@163.com>
     */
    public function edit(){
        $id = I('get.id','');
        if(empty($id)){
            $this->error('参数不能为空！');
        }

        /*获取一条记录的详细数据*/
        $Model = M('Model');
        $data = $Model->field(true)->find($id);
        if(!$data){
            $this->error($Model->getError());
        }

        /* 获取模型排序字段 */
        $fields = M('Attribute')->where(array('model_id'=>$data['id']))->field('id,name,title,sort')->order('sort')->select();


        //获取所有的模型
    	$models = M('Model')->where(array('extend'=>0))->field('id,title')->select();

    	$this->assign('models', $models);
    	$this->assign('fields', $fields);
        $this->assign('info', $data);
        $this->meta_title = '编辑模型';
        $this->display();
    }

    /**
     * 更新一条数据
     * @author huajie <banhuajie@163.com>
     */
    public function update(){
        $res = D('Model')->update();

        //更新属性排序
        $fields = I('post.fields');
        $id = I('post.id');
        foreach ($fields as $value){
        	$field = explode(':', $value);
        	M('Attribute')->where(array('id'=>$field[0]))->setField('sort', $field[1]);
        }

        //更新缓存
        $list = S('attribute_list');
        if(isset($list[$id])){
        	unset($list[$id]);
        }
        S('attribute_list', $list);

        if(!$res){
            $this->error(D('Model')->getError());
        }else{
            if($res['id']){
                $this->success('更新成功', U('index'));
            }else{
                $this->success('新增成功', U('index'));
            }
        }
    }

    /**
     * 生成一个模型
     * @author huajie <banhuajie@163.com>
     */
    public function generate(){
    	if(!IS_POST){
    		//获取所有的数据表
    		$tables = D('Model')->getTables();

    		$this->assign('tables', $tables);
    		$this->meta_title = '生成模型';
    		$this->display();
    	}else{
    		$table = I('post.table');
    		empty($table) && $this->error('请选择要生成的数据表！');
			$res = D('Model')->generate($table);
			if($res){
				$this->success('生成模型成功！', U('index'));
			}else{
				$this->error(D('Model')->getError());
			}
    	}
    }

}
