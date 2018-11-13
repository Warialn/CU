<?php
namespace Home\Controller;
use Think\Controller;
class InterfaceController extends CommonController {
    public function cu_eu(){
      $result = M('setting_eu')->select()[0];
      $this->assign('data',$result);
      self::log('Web', 'CU与EU参数查询', 5);
      Layout('Layout/layout');
      $this->display();
    }
    public function eu_addoredit(){
      $res = M('setting_eu')->select()[0];
      $data = $_GET;
      $id = $data['id'];
      $data['id']=1;
      if($res){        
        $result = M('setting_eu')->where(array('id'=>$id))->save($data);
        if($result){
          self::log('Web', '修改EU参数', 5);
          $this->ajaxReturn(array('status'=>'success'));
        }
      }else{
        $result = M('setting_eu')->add($data);
        if($result){
          self::log('Web', '添加EU参数', 5);
          $this->ajaxReturn(array('status'=>'ad_success','info'=>'添加成功！'));
        }

      }
     

    }
    public function cu_smms(){
      $result = M('setting_smms')->select()[0];
      $this->assign('data',$result);
      self::log('Web', 'CU与SMMS参数查询', 5);
      Layout('Layout/layout');
      $this->display();
    }
    public function smms_addoredit(){
      $res = M('setting_smms')->select()[0];
      $data = $_GET;
      $id = $data['id'];
      $data['id'] =1;
      if($res){        
        $result = M('setting_smms')->where(array('id'=>$id))->save($data);
        if($result){
          self::log('Web', '修改SMMS参数', 5);
          $this->ajaxReturn(array('status'=>'success'));
        }
      }else{
        $result = M('setting_smms')->add($data);
        if($result){
          self::log('Web', '添加SMMS参数', 5);
          $this->ajaxReturn(array('status'=>'ad_success','info'=>'添加成功！'));
        }

      }

    }
    public function cu_du(){
      $result = M('setting_du')->select()[0];
      $this->assign('data',$result);
      self::log('Web', 'CU与DU参数查询', 5);
      Layout('Layout/layout');
      $this->display();
    }
    public function du_addoredit(){
      $res = M('setting_du')->select()[0];
      $data = $_GET;
      $id = $data['id'];
      $data['id']=1;
      $data['aes_method'] = 0;
      if($res != ""){        
        $result = M('setting_du')->where(array('id'=>$id))->save($data);
        if($result){
          self::log('Web', '修改DU参数', 5);
          $this->ajaxReturn(array('status'=>'success'));
        }
      }else{
        $result = M('setting_du')->add($data);
        if($result){
          self::log('Web', '添加DU参数', 5);
          $this->ajaxReturn(array('status'=>'ad_success','info'=>'添加成功！'));
        }else{
          self::log('Web', '添加DU参数', 3);
          $this->ajaxReturn(array('status'=>'error','info'=>'添加失败！'));
        }

      }
    }
    public function setting_cu(){
      $result =  M('setting_cu')->select()[0];
      $this   -> assign('data',$result);
      self::log('Web', 'CU系统配置查询', 5);
      Layout('Layout/layout');
      $this->display();

    }
    public function do_cuaddoredit(){
      $res = M('setting_cu')->select()[0];
      $data = $_GET;
      $id = $data['id'];
      if($res != ""){        
        $result = M('setting_cu')->where(array('id'=>$id))->save($data);
        if($result){
          self::log('Web', '修改CU参数', 5);
          $this->ajaxReturn(array('status'=>'success'));
        }
      }else{
        $result = M('setting_cu')->add($data);
        if($result){
          self::log('Web', '添加CU参数', 5);
          $this->ajaxReturn(array('status'=>'ad_success','info'=>'添加成功！'));
        }else{
          self::log('Web', '添加CU参数', 3);
          $this->ajaxReturn(array('status'=>'error','info'=>'添加失败！'));
        }

      }
    }
    public function inner(){
      if($_GET){
        if(I('house_id')!=''){
          $house_id = I('house_id');
          $where['house_id'] = I('house_id');
        }
      }
      $result = M('device_info')->where($where)->select();
      foreach($result as $key=>$value ){
        $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
        $result[$key]['status']=$this->info_value('status',$value['online']);
      }
      $this->assign('data',$result);
      $this->assign('house_name',$house_id);
      $rooms = M('basic_house')->where(array('status'=>array('neq','3')))->select();
      $this->assign('rooms',$rooms);
      self::log('Web', 'EU设备管理查询', 5);
      Layout('Layout/layout');
      $this->display();
    }
    public function inner_addoredit(){
      $data = $_GET;     
      $result = M('device_info')->add($data);
      if($result){
        self::log('Web', '添加EU设备', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }else{
        self::log('Web', '添加EU设备', 3);
        $this->ajaxReturn(array('status'=>'error','info'=>'添加失败！'));
      }
    }
    public function inner_del(){
      if($_GET){
        $id = I('get.id');
        $res = M('device_info')->where(array('id'=>$id))->delete();
        if($res){
          self::log('Web', '删除EU设备', 5);
          $this->ajaxReturn(array('status'=>'success'));
        }
      }
    }
    public function info_value($col,$value){
      $c = [  
          'status'  => function($v){
              $cc = [
                '0'                                   => '离线',
                '1'                                   => '在线',

              ];
              return isset($cc[$v]) ? $cc[$v] : $v;
          }, 
             
      ];
      return isset($c[$col]) ? $c[$col]($value) : $value;
    }
}