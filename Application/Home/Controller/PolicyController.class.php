<?php
namespace Home\Controller;
use Think\Controller;
use Home\Common\Export;
use Home\Common\Import;
use Home\Model\Policy;
class PolicyController extends CommonController {

  //基础数据查询
  public function basicsearch(){
    if(I('get.n')=='room'){
        $this -> basic_room_info(I('get.house_id'));
       
    }elseif(I('get.n')=='user'){
        $this -> basic_user_info(I('get.userid'));
    }elseif($_GET && empty(I('get.n'))){
        if(I('commandid')!=''){
          $commandid = I('commandid');
          $where['commandid'] = $commandid;
        }
        /*if(I('house_name')!=''){
          $house_id = I('house_name');
          $where['house_id'] = $house_id;
        }*/
        if(I('type')!=''){
          $type = I('type');
          $where['type'] = $type;
        }
        if(I('report')!=''){
          $report = I('report');
          $where['report'] = $report;
        } 
    }
    $count = M('policy_basic_data')->where($where)->count('DISTINCT commandid');// 查询满足要求的总记录数
    $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
    $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
    $result=M('policy_basic_data')->where($where)->group('commandid')->order('timestamp desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    //dump($result);die;
    foreach ($result as $key => $value) {
      if($value['house_id'] == ''){
        $result[$key]['house_name'] ="全部机房";
      }elseif($value['house_id'] == '-1'){
        $result[$key]['house_name'] = "-";
      }else{
        $result[$key]['house_name'] = "查看详情";
      }
       if($value['user_id'] == ''){
        $result[$key]['user_name'] ="全部用户";
      }elseif($value['user_id'] == '-1'){
        $result[$key]['user_name'] = "-";
      }else{
        $result[$key]['user_name'] = "查看详情";

      }
      $result[$key]['report']=$this->info_value('report',$value['report']);
      $result[$key]['type']=$this->info_value('basic_data_type',$value['type']);
      $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
    }
    $rooms = M('basic_house')->select();
    $show = $Page->show();
    $this->assign('show', $show);
    $this->assign('commandid',$commandid);
    //$this->assign('house_name',$house_id);
    $this->assign('type',$type);
    $this->assign('report',$report);
    $this->assign('rooms',$rooms);
    $this->assign('res',$result);
    self::log('Web', '基础数据查询', 5);
    Layout('Layout/layout');
    $this->display();
  }
  public function basic_room_info($house_id){
    if($house_id!=''){
      //$house_id = I('house_id');
      $house_ids = explode(',',$house_id);
    }elseif($house_id==''){
      $house_s = M('basic_house')->select();
      foreach ($house_s as $key => $value) {
        $house_ids[] = $value['house_id'];
        # code...
      }
    }
    
    foreach ($house_ids as $key => $value) {
      $house_names[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value))->select()[0]['house_name'];
      if(!isset($house_names[$key]['house_name'])){
        $house_names[$key]['house_name'] = $value;
      }
    }

    $this->ajaxReturn(array('data'=>$house_names));

  }
  public function basic_user_info($userid){
    if($userid!=''){
      //$userid = I('userid');
      $userids = explode(',',$userid);
    }elseif($userid==''){
      $userid_s = M('basic_user')->select();
      foreach ($userid_s as $key => $value) {
        $userids[] = $value['user_id']; 
      }

    }    
    foreach ($userids as $key => $value) {
      $user_names[$key]['unitname'] = M('basic_user')->where(array('user_id'=>$value))->select()[0]['unitname'];
    }
    $this->ajaxReturn(array('data'=>$user_names));

  }
  //基础数据核验
  public function basic_check(){
    if(I('get.type')=='house'){
        $this -> house_return(I('get.return_id'));die;
    }elseif(I('get.type')=='user'){
        $this -> user_return(I('get.return_id'));die;
    }elseif($_GET && empty(I('get.type'))){
      if(I('is_return')!=''){
        $is_return = I('is_return');
        $where['is_return'] = $is_return;
      }
      if(I('operation_type')!=''){
        $operation_type = I('operation_type');
        $where['operation_type'] =$operation_type;
      }
      if(I('operation_user')!=''){
        $operation_user = I('operation_user');
        $where['operation_user'] = $operation_user;
      }
      if(I('operation_time') !=''){
        $operation_time = I('operation_time');
        $time = explode(' - ',$operation_time);
        $where['operation_time'] = array('between',array(strtotime($time[0]),strtotime($time[1])));
      }
      if(I('return_stamp')!=''){
        $return_stamp = I('return_stamp');
        $return_time = explode(' - ',$return_stamp);
        $where['return_stamp'] = array('between',array(strtotime($return_time[0]),strtotime($return_time[1])));
      }
      
    }
    $count = M('policy_basic_return')->where($where)->count();// 查询满足要求的总记录数
    $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
    $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");

    $result = M('policy_basic_return')->where($where)->order('return_stamp desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    //dump(M()->getLastSql());die;
    foreach ($result as $key => $value) {
      //dump($value);
      $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
      $result[$key]['return_code']=$this->info_value('return_code',$value['return_code']);
      $result[$key]['is_return'] = $value['is_return']==1?'有':'无';
      /*$ret_user_res = M('policy_return_userdata')->where(array('basic_return_info_id'=>$value['id']))->select();
      $ret_house_res = M('policy_return_housedata')->where(array('basic_return_info_id'=>$value['id']))->select();

      if($ret_user_res || $ret_house_res ){
        $result[$key]['is_return'] = '有';
      }else{
        $result[$key]['is_return'] = '无';
      }*/
      //dump($result[$key]);die;
    }
    $show = $Page->show();
    $this->assign('show', $show);
    $this->assign('is_return',$is_return);
    $this->assign('operation_type',$operation_type);
    $this->assign('operation_time',$operation_time);
    $this->assign('operation_user',$operation_user);
    $this->assign('return_stamp',$return_stamp);
    $this->assign('res',$result);
    self::log('Web', '基础数据核验处理查询', 5);
    Layout('Layout/layout');
    $this->display();

  }
  public function user_return($return_id){
    //$return_id = I('return_id');
    if($return_id){
      $where['basic_return_info_id'] = $return_id;        
    }
    if(I('user_id')){
      $user_id = I('user_id');
      $where['user_id'] = $user_id;
    }
    if(I('service_id')){
      $service_id =I('service_id');
      $where['service_id'] = $service_id;
    }
    if(I('service_domain_id')!=''){
      $service_domain_id = I('service_domain_id');
      $where['service_domain_id'] = $service_domain_id;
    }
    if(I('service_hh_id')!=''){
      $service_hh_id =I('service_hh_id');
      $where['service_hh_id'] = $service_hh_id;
    }
    if(I('hh_id')!=''){
      $hh_id =I('hh_id');
      $where['hh_id'] = $hh_id;
    }
    $result = M('policy_return_userdata')->where($where)->select();
    foreach ($result as $key => $value) {
      $result[$key]['domain_name'] = M('basic_domain')->where(array('domain_id'=>$value['service_domain_id']))->select()[0]['domain_name'];
    }
    $this->assign('res',$result);
    $this->assign('user_id',$user_id);
    $this->assign('service_id',$service_id);
    $this->assign('service_domain_id',$service_domain_id);
    $this->assign('service_hh_id',$service_hh_id);
    $this->assign('hh_id',$hh_id);
    Layout('Layout/layout');
    $this->display('user_return');
  }
  public function house_return($return_id){
    if(I('house_name')!=''){
      $house_id = I('house_name');
      $where['house_id'] = $house_id;
    }
    if(I('gateway_id')){
      $gateway_id = I('gateway_id');
      $where['gateway_id'] = $gateway_id;
    }
    if(I('ip_seg_id')){
      $ip_seg_id =I('ip_seg_id');
      $where['ip_seg_id'] = $ip_seg_id;
    }
    if(I('frame_info_id')){
      $frame_info_id = I('frame_info_id');
      $where['frame_info_id'] = $frame_info_id;
    }
    //$return_id = I('return_id');
    if($return_id){
      $where['basic_return_info_id'] = $return_id;
      
    }
    $result = M('policy_return_housedata')->where($where)->select();
    foreach ($result as $key => $value) {
      $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
    }
    $rooms = M('basic_house')->select();
    $this->assign('rooms',$rooms);
    $this->assign('house_name',$house_id);
    $this->assign('gateway_id',$gateway_id);
    $this->assign('ip_seg_id',$ip_seg_id);
    $this->assign('frame_info_id',$frame_info_id);
    $this->assign('res',$result);
    Layout('Layout/layout');
    $this->display('house_return');
  }
  //基础信息核验处理
  public function basic_check_operation(){
    if($_GET){
      $id = I('id');
      $data['operation_type'] = 1;
      $data['operation_user'] = $_SESSION['username'];
      $data['operation_time'] = time();
      $res = M('policy_basic_return')->where(array('id'=>$id))->save($data);
      if($res){
        self::log('Web', '基础信息核验处置成功', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }else{
        self::log('Web', '基础信息核验处置失败', 3);
        $this->ajaxReturn(array('status'=>'error','info'=>'处理失败'));
      }

    }

  }

  //违法信息安全管理
  public function illegal_info(){
    ini_set("display_errors","On");
    error_reporting(E_ALL);
    set_time_limit (0);
    ini_set('memory_limit','-1');
    if(IS_POST){
      $where['commandid'] = array('in',I('post.commandid'));
      $result = M('policy_isms')->where($where)->group('commandid')->select();
      foreach ($result as $key => $value) {
        $comm_num = M('policy_isms')->where(array('commandid'=>$value['commandid']))->select();
          if(count($comm_num)>1){
            $check_fail = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>3))->select();
            $check_success = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>2))->select();
            $check_ing = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>1))->select();
            $check_no_access = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>4))->select();
            $check_no_ack = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>8))->select();
            $check_del_ing = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>5))->select();
            $check_del_fail = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>7))->select();
            $check_del_suc = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>6))->select();
            if($check_ing){
              $result[$key]['new_status']= "下发中";
            }elseif($check_del_ing){
              $result[$key]['new_status']= "取消中";
            }else{
              if(count($check_no_ack) == count($comm_num)){
                $result[$key]['new_status']= "未回复ack";
              }elseif(count($check_no_access) == count($comm_num)){
                $result[$key]['new_status']= "接口不通";
              }else{
                if(count($check_success) == count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['new_status'] = "下发成功";
                }elseif(count($check_success) != count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['new_status'] = "下发失败";
                }elseif(count($check_del_suc) == count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['new_status'] = "取消成功";
                }elseif(count($check_del_suc) != count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['new_status'] = "取消失败";
                }
              }
            }
          }else{
            $result[$key]['new_status'] = $this->info_value('status',$value['status']);
          }
      }
      if($result){
        $this->ajaxReturn($result);
      }
      
      /*if(!in_array(1,$status)){
          $this -> ajaxReturn(['status'=>'success']);
      }else{
          $this -> ajaxReturn(['status'=>'error']);
      }*/
    }else{
        if(I('get.n')=='room'){
            $this -> room_info(I('get.commandid'));
        }elseif(I('get.n')=='rule'){
            $this -> rule_info(I('get.commandid'));
        }elseif($_GET && empty(I('get.n'))){

          if(I('commandid')!=''){
            $commandid = I('commandid');
            $where['commandid'] = $commandid;
          }
          
          if(I('status')!=''){
            $status = I('status');
            if($status == '1'){
              //下发中状态的commandid
              $ing_res = M('policy_isms')->where(array('status'=>1))->select();
              foreach ($ing_res as $key => $value) {
                $ing_comm[]= $value['commandid']; 
              }
            }elseif($status == '3'){
              //下发失败的commandid
            
              $fail_res = M('policy_isms')->where(array('status'=>3))->select();
              foreach ($fail_res as $key => $value) {
                $fail_comm[]= $value['commandid']; 
              } 
            }elseif($status == '4'){
            
              //接口不通的commandid     
              $no_access = M('policy_isms')->where(array('status'=>4))->select();
              foreach ($no_access as $key => $value) {
                $no_access_s = M('policy_isms')->where(array('commandid'=>$value['commandid']))->count();
                $no_num = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>"4"))->count();
                if($no_access_s == 1){
                  $no_access_comm[]= $value['commandid']; 
                }elseif($no_access_s>1){
                  if($no_access_s == $no_num){
                    $no_access_comm[]= $value['commandid'];
                  }
                }
              } 
            } elseif($status == '5'){
              //取消中的commandid
              $del_ing = M('policy_isms')->where(array('status'=>5))->select();
              foreach ($del_ing as $key => $value) {
                $del_ing_comm[]= $value['commandid']; 
              }
            }elseif($status == '6'){
              //取消成功的commandid
              $del_success = M('policy_isms')->where(array('status'=>6))->select();
              foreach ($del_success as $key => $value) {
                $del_success_s = M('policy_isms')->where(array('commandid'=>$value['commandid']))->count();
                $del_num = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>"6"))->count();
                if($del_success_s==1){
                  $del_success_comm[]= $value['commandid'];
                }elseif($del_success_s>1){
                  if($del_success_s == $del_num){
                    $del_success_comm[]= $value['commandid'];
                  }
                }
                
              }
            } elseif($status == '7'){
              //取消失败的commandid
              $del_fail = M('policy_isms')->where(array('status'=>7))->select();
              foreach ($del_fail as $key => $value) {
                $del_fail_comm[]= $value['commandid']; 
              }
            }elseif($status == 8){
              //未回复ack的commandid
              $no_ack = M('policy_isms')->where(array('status'=>8))->field('commandid,status')->select();
              foreach ($no_ack as $key => $value) {
                $no_ack_s = M('policy_isms')->where(array('commandid'=>$value['commandid']))->count();
                $no_ack_num = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>8))->count();
                if($no_ack_s==1){
                  $no_ack_comm[] = $value['commandid'];
                }elseif($no_ack_s>1){
                  if($no_ack_s== $no_ack_num){
                    $no_ack_comm[] = $value['commandid'];
                  }
                }
                
              }
            }elseif($status == 2){
              //下发成功的commandid
              $success = M('policy_isms')->where(array('status'=>2))->field('commandid,status')->select();

              foreach ($success as $key => $value) {

                $success_s = M('policy_isms')->where(array('commandid'=>$value['commandid']))->count();
                $success_num = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>2))->count();
                if($success_s > 1){
                  if($success_s == $success_num){
                    $success_comm[] = $value['commandid'];
                  }
                    
                }else{
                  $success_comm[] = $value['commandid'];
                }

              }
            }
            if(I('commandid') == ''){
              switch ($status) {
                case 1:
                  if($ing_comm!=''){
                    $where['commandid'] = array('in',$ing_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 2:
                  if($success_comm!=''){
                    $where['commandid'] = array('in',$success_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 3:
                  if($fail_comm!=''){
                    $where['commandid'] = array('in',$fail_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 4:
                  if($no_access_comm!=''){
                    $where['commandid'] = array('in',$no_access_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 5:
                  if($del_ing_comm!=''){
                    $where['commandid'] = array('in',$del_ing_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 6:
                  if($del_success_comm!=''){
                    $where['commandid'] = array('in',$del_success_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 7:
                  if($del_fail_comm!=''){
                    $where['commandid'] = array('in',$del_fail_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  
                  break;
                case 8:
                  if($no_ack_comm!=''){
                    $where['commandid'] = array('in',$no_ack_comm);
                  }else{
                    $where['commandid'] = NULL;
                  }
                  break;
              }
            }elseif(I('commandid')!=''){
              switch ($status) {
                case 1:
                  if($ing_comm!=''){
                    $ing_comm = implode(',',$ing_comm);
                    $where['_string'] = " commandid in ".$ing_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 2:
                  if($success_comm!=''){
                    $success_comm = "(".implode(',',$success_comm).")";
                    $where['_string'] = " commandid in ".$success_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 3:
                  if($fail_comm!=''){
                    $fail_comm = "(".implode(',',$fail_comm).")";
                    $where['_string'] = " commandid in ".$fail_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 4:
                  if($no_access_comm!=''){
                    $no_access_comm = "(".implode(',',$no_access_comm).")";
                    $where['_string'] = " commandid in ".$no_access_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 5:
                  if($del_ing_comm!=''){
                    $del_ing_comm = "(".implode(',',$del_ing_comm).")";
                    $where['_string'] = " commandid in ".$del_ing_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 6:
                  if($del_success_comm!=''){
                    $del_success_comm = "(".implode(',',$del_success_comm).")";
                    $where['_string'] = " commandid in ".$del_success_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 7:
                  if($del_fail_comm!=''){
                    $del_fail_comm = "(".implode(',',$del_fail_comm).")";
                    $where['_string'] = " commandid in ".$del_fail_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 8:
                  if($no_ack_comm!=''){
                    $no_ack_comm = "(".implode(',',$no_ack_comm).")";
                    $where['_string'] = " commandid in ".$no_ack_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  break;
              }
            }
          }
          if(I('house_name')!=''){
            $house_id = I('house_name');
            $where['house_id'] = $house_id;
          }
          if(I('type')!=''){
            $type = I('type');
            $where['type'] = $type;
          }
          if(I('operationtype')!=''){
            $operationtype = I('operationtype');
            $where['operationtype'] = $operationtype;
          }
          if(I('timestamp')!=''){
            $timestamp =I('timestamp');
            $time = explode(' - ',$timestamp);
            $where['timestamp'] = array('between',array(strtotime($time[0]),strtotime($time[1])));
          }
          if(I('source')!=""){
            $source = I('source');
            $where['source'] = $source;
          }
        }
        //dump($where);die;
        $count = M('policy_isms')->where($where)->count('DISTINCT commandid');// 查询满足要求的总记录数
        $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $result=M('policy_isms')->where($where)->order('timestamp desc')->limit($Page->firstRow . ',' . $Page->listRows)->group('commandid')->select();
        //dump(M()->getLastSql());die;
        foreach ($result as $key => $value) {
          $comm_num = M('policy_isms')->where(array('commandid'=>$value['commandid']))->count();
          if($comm_num>1){
            $check_fail = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>3))->count();
            $check_success = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>2))->count();
            $check_ing = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>1))->count();
            $check_no_access = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>4))->count();
            $check_no_ack = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>8))->count();
            $check_del_ing = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>5))->count();
            $check_del_fail = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>7))->count();
            $check_del_suc = M('policy_isms')->where(array('commandid'=>$value['commandid'],'status'=>6))->count();
            if($check_ing>0){
              $result[$key]['status']= "下发中";
            }elseif($check_del_ing>0){
              $result[$key]['status']= "取消中";
            }else{
              if($check_no_ack == $comm_num){
                $result[$key]['status']= "未回复ack";
              }elseif($check_no_access == $comm_num){
                $result[$key]['status']= "接口不通";
              }else{
                if($check_success == $comm_num && $result[$key]['operationtype'] == '0'){
                  $result[$key]['status'] = "下发成功";
                }elseif($check_success != $comm_num && $result[$key]['operationtype'] == '0'){
                  $result[$key]['status'] = "下发失败";
                }elseif($check_del_suc == $comm_num && $result[$key]['operationtype'] == '1'){
                  $result[$key]['status'] = "取消成功";
                }elseif($check_del_suc != $comm_num && $result[$key]['operationtype'] == '1'){
                  $result[$key]['status'] = "取消失败";
                }else{
                  $result[$key]['status'] = $this->info_value('status',$value['status']);
                }
              }
              
            }
            $result[$key]['type'] = $this->info_value('isms_type',$value['type']);
            $result[$key]['house_name'] = '查看详情';
            $result[$key]['source'] = $this->info_value('source',$value['source']);
            $result[$key]['operationtype'] = $this->info_value('operationtype',$value['operationtype']);
            $result[$key]['log'] = $this->info_value('log_flag',$value['log_flag']);
            $result[$key]['report'] = $this->info_value('report_flag',$value['report_flag']);
          }else{
            $result[$key]['type'] = $this->info_value('isms_type',$value['type']);
            $result[$key]['status'] = $this->info_value('status',$value['status']);
            $result[$key]['source'] = $this->info_value('source',$value['source']);
            $result[$key]['operationtype'] = $this->info_value('operationtype',$value['operationtype']);
            $result[$key]['log'] = $this->info_value('log_flag',$value['log_flag']);
            $result[$key]['report'] = $this->info_value('report_flag',$value['report_flag']);
            $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
          }

        }
        //dump($result);die;
        $this->assign('flag',$flag);
        $rooms = M('basic_house')->select();
        $show = $Page->show();
        $this->assign('show', $show);
        $this->assign('commandid',$commandid);
        $this->assign('house_name',$house_id);
        $this->assign('type',$type);
        $this->assign('operationtype',$operationtype);
        $this->assign('status',$status);
        $this->assign('timestamp',$timestamp);
        $this->assign('source',$source);
        $this->assign('rooms',$rooms);
        $this->assign('result',$result);
        self::log('Web', '违法信息安全管理查询', 5);
        Layout('Layout/layout');
        $this->display();
    }
    
  }
  public function room_info($commandid){
      $comm_nu = M('policy_isms')->where(array('commandid'=>$commandid))->select();
      foreach ($comm_nu as $key => $value) {
        $comm_nu[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
        $comm_nu[$key]['status'] = $this->info_value('status',$value['status']); 
      }
      $this->ajaxReturn(array('data'=>$comm_nu));
  }
  public function rule_info($commandid){
      $result = M('isms_rule')->where(array('commandid'=>$commandid))->select();
      $array =array();
      foreach ($result as $key => $value) {
        if($value['subtype'] == '4' || $value['subtype'] == '5'){
          $result[$key]['value_start'] = $value['value_start']." ~ ".$value['value_end'];
        }
        if($value['subtype'] == '8'){
          $result[$key]['value_start']=$this->info_value('value_start',$value['value_start']);
        }
        if($value['subtype'] == '3'){
          if($value['keyword_range'] !=""){
            $res = explode(',',$value['keyword_range']);
            foreach ($res as $ke => $valu) {
              $result[$key]['keywordrange'] .= $this->info_value('keyword_range',$valu).",";
            }
            $result[$key]['keywordrange'] = substr($result[$key]['keywordrange'],0,-1);
          }else{
            $result[$key]['keywordrange'] = "";
          } 
        }else{
          if($value['keyword_range'] == ''){
            $result[$key]['keywordrange'] = "";
          }
        }
        $result[$key]['subtype'] = $this->info_value('subtype',$value['subtype']);       
      }
      $this->ajaxReturn(array('data'=>$result));
  }
  public function info_add(){  
    if($_GET){
        $number = I('get.number');
        $illegal_info = I('get.data1');
        if($illegal_info && $illegal_info['type'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'指令类型不能为空！'));
        }
        if(empty($illegal_info['house_id'])){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房不能为空'));
        }elseif($illegal_info['house_id'][0] == 'all'){
          $house = M('basic_house')->select();
          foreach ($house as $key => $value) {
            $houses[$key] = $value['house_id']; 
          }
          $illegal_info['house_id'] = $houses;
        }
        if($illegal_info['effect_time'] == "" || $illegal_info['expired_time'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'生效时间和过期时间不能为空'));
        }elseif(strtotime($illegal_info['effect_time']) >= strtotime($illegal_info['expired_time'])){
          $this->ajaxReturn(array('status'=>'error','info'=>'过期时间不能小于生效时间！'));
        }
        $rule_info = I('get.data2');
        /*if($rule_info == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'请填写规则！'));
        }*/
        if($number ==''&& $rule_info!='' && $illegal_info!='' ){
            $max_commandid = M('cu_commandid')->order('id desc')->limit(0,1)->select()[0]['commandid'];
            if($max_commandid){
              $illegal_info['commandid'] =$max_commandid+1;       
            }else{
              $illegal_info['commandid'] = 1;
            }
            $illegal_info['owner'] = $_SESSION['username'];
            $illegal_info['rule'] = $rule_info;
            $illegal_info['operationtype'] = 0;
            $illegal_info['effect_time'] = strtotime($illegal_info['effect_time']);
            $illegal_info['expired_time'] = strtotime($illegal_info['expired_time']);
            $illegal_info['level'] = $this->judge_level($rule_info,$illegal_info['type']);
            dump($illegal_info['level']);die;
            $illegal_info['timeStamp'] = time();
            $illegal_info['idc_id'] = M('basic_idc')->select()[0]['idc_id'];
            $data['commandid'] = $illegal_info['commandid'];
            M('cu_commandid')->add($data);       
            $res = $this->info_build_xml($illegal_info);
           
        }else{
          $data = $illegal_info;
          //dump($number);die;
          foreach ($number as $key => $val) {
            if($val != 0){
              for($i=1;$i<=$val;$i++){
                $max_commandid = M('cu_commandid')->order('id desc')->limit(0,1)->select()[0]['commandid'];
                if($max_commandid){
                 $commandid = $max_commandid;       
                }else{
                  $commandid = 0;
                }
                $data['rule'] = $this->judge_rule($key,$data['type']);
                
                $illegal_info['level'] = $this->judge_level($data['rule'],$data['type']);
                $data['commandid'] = $commandid+1;
                M('cu_commandid')->add($data);
                $data['operationtype'] = 0; 
                $data['effect_time'] = strtotime($data['effect_time']);
                $data['expired_time'] = strtotime($data['expired_time']);
                $data['idc_id'] = M('basic_idc')->select()[0]['idc_id'];
                $data['owner'] = $_SESSION['username'];
                //$inf[] =$data; 
                //dump($data);die;
                $res=$this->info_build_xml($data); 
            }            

          }
              
        }
      } 
      //dump($inf);die;
      if($res){
        self::log('Web', '下发信安策略', 5);
        $this->ajaxReturn(array('status'=>'success')); 
      }
    }else{
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        Layout('Layout/layout');
        $this->display('illegal_info_add');
    }          
  }
 
  //上传并分割csv文件
  public function before_upload(){
    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize   =     0 ;// 设置附件上传大小
    $upload->exts      =     array('csv');// 设置附件上传类型
    $upload->rootPath  =     './Uploads/excel/'; // 设置附件上传根目录
    $upload->savePath  =     ''; // 设置附件上传（子）目录
    $upload->saveName = 'time';
    $upload->autoSub  = false;

    // 上传文件
    $info   =   $upload->upload();
    if(!$info) {// 上传错误提示错误信息
         $this->error($upload->getError());
    }
    $count = I('count');
    $rootPath = C('DOWN_PATH');
    $filePath = 'excel/';
    if($info['file']['savename']){
        $cmd = "cd ". $rootPath . $filePath . "\n";
        $cmd .= "split -a  1 -l 10000 " . $info['file']['savename']." -d zp_";
        //dump($cmd);die;
        $result = system($cmd);
        if ($result)
        {
            $status = true;
        }
        $cmd = "cd ".$rootPath.$filePath."\n";
        $cmd .= "rm -rf ".$info['file']['savename'];
        $res = system($cmd);
    }
    //dump($rootPath . $filePath.'zp_'.$count);die;
    if(file_exists($rootPath . $filePath.'zp_'.$count)){
      $this->ajaxReturn(array('status'=>'3','count'=>$count));
    }else{
      $this->ajaxReturn(array('status'=>'1'));
    }

  }
  public function info_upload(){
    
    $count = I('count');
    $rootPath = C('DOWN_PATH');
    $cmd = "cd ".$rootPath."excel/"."\n";
    $cmd.= 'mv zp_'.$count." zp_".$count.".csv"."\n";
    $cmd .= "chmod -R 777 zp_".$count.".csv";
    system($cmd);
    $filename = "zp_".$count.".csv";

    $file = fopen($rootPath."excel/".$filename,'r');
    while(!feof($file)){
      $dat[] = fgetcsv($file);
    }
    $dat = eval('return ' . iconv('EUC-CN', 'utf-8', var_export($dat, true)) . ';');
    foreach ($dat as $key => $value) {
      if (!$value) {
        unset($dat[$key]);
      }
    }
    fclose($file);
    //dump($data);die;
    $config = Policy::col();
    $error_msg = "";
    $status = 'success';
    $top_data = array(
      '0' =>  "指令ID",
      '1' =>  "指令类型",
      '2' =>  "机房名称",
      '3' =>  "生效时间",
      '4' =>  "过期时间",
      '5' =>  "操作类型",
      '6' =>  "域名",
      '7' =>  "URL",
      '8' =>  "关键字",
      '9' =>  "源IP地址",
      '10' =>  "目的IP地址",
      '11' => "源端口",
      '12' => "目的端口",
      '13' => "传输层协议",
    );
    if($count == 0){
      //文件被切割后只有第一个存在标题，需要去掉标题
      unset($dat[0]);
    }
    //转化成需要的格式
    foreach ($dat as $key => $value) {
      foreach ($value as $k => $val) {
        $result[$key-1][$top_data[$k]] = $val;
      }
      
    }
    if(!empty($result)){ 
        foreach ($result as $key => $value) { 
            $data = array();
            foreach ($value as $k => $v) {
                if(is_null($v)){
                    continue;
                }
                if (isset($config[$k])) {
                    if ($v!='') {
                      //转换成 字段名 =>value的形式
                      $data[$config[$k]] = Policy::col2value($k, $v);
                      //dump([$config[$k]]);die;
                    }
                }        
            }
            $data['effect_time'] = strtotime($data['effect_time']);
            $data['expired_time'] = strtotime($data['expired_time']);
            if($result['status'] === false) {
                $status = 'failed';
                $error_msg .= $res['message']."(excel:".($key + 1).")     ";
            }else{
              //dump($data);die;
              $rule = $data;
              //去掉不是规则的data数组中的元素
              unset($rule['type']);
              unset($rule['house_id']);
              unset($rule['effect_time']);
              unset($rule['expired_time']); 
              unset($rule['operationtype']);
              unset($rule['commandid']);
              foreach ($rule as $key => $value) {
                if($value !=''){
                  if($key == '关键字'){
                    $data['rule'][] = array('subtype'=>$key,'valueStart'=>str_replace('，', ',', $value),'keywordRange'=>'0,1,2');
                  }else{
                    //将规则统一归到$data['rule']里面
                    $data['rule'][] = array('subtype'=>$key,'valueStart'=>$value);
                  }
                }
              }
              $data['level'] = $this->judge_level($data['rule'],$data['type']);
              //去掉规则汉字的元素  因为规则已被归到$data['rule']里面
              unset($data['域名']);
              unset($data['URL']);
              unset($data['关键字']);
              unset($data['源IP地址']);
              unset($data['目的IP地址']);
              unset($data['源端口']);
              unset($data['目的端口']);
              unset($data['传输层协议']);
              $data['idc_id'] = M('basic_idc')->select()[0]['idc_id'];
              $data['owner'] = $_SESSION['username'];
              $this->info_build_xml($data);
              //停顿一毫秒，太快反应不过来会丢文件  
              usleep(100);            
            } 
        }
        //循环完了该文件里的内容就删掉，防止与下次导入发生重合
        $cmd = "cd ".$rootPath."excel/"."\n";
        $cmd .= "rm zp_".$count.".csv";
        system($cmd);

        if(file_exists($rootPath."excel/".'zp_'.($count+1))){
          $this->ajaxReturn(['status' => '3','count'=>$count+1]);
        }else{
          $this->ajaxReturn(['status' => $status]);
        }
        
    }else{
        $status='error';
        $error_msg ='没有导入任何信息！';
        $this->ajaxReturn(['status' => $status,'message' => $error_msg]);
    }
  }
  //批量下发策略时  判断规则
  public function judge_rule($key,$type){
    $data['type'] = $type;
    if($key == 'mdip'){
        $data['rule']= array('0'=>array('subtype'=>'目的IP地址','valueStart'=>'2.1.2.1-2.1.2.255'));
      }elseif($key == 'gjz'){
        $data['rule']= array('0'=>array('subtype'=>'关键字','valueStart'=>'关键字测试','keywordRange'=>'0,1,2'));

      }elseif($key == 'yip'){
        $data['rule']= array('0'=>array('subtype'=>'源IP地址','valueStart'=>'1.1.1.1-1.1.1.200'));

      }elseif($key == 'ym'){
        if($data['type'] == 1){
          $data['rule']= array('0'=>array('subtype'=>'域名','valueStart'=>'www.subnetmonitor.index'.$i.".com"));
        }elseif($data['type'] == 2){
          $data['rule']= array('0'=>array('subtype'=>'域名','valueStart'=>'www.subnetblock.index'.$i.".com"));
        }
      }elseif($key == "ydk"){
        $data['rule']= array('0'=>array('subtype'=>'源端口','valueStart'=>'90'));

      }elseif($key == 'mddk'){
        $data['rule']= array('0'=>array('subtype'=>'目的端口','valueStart'=>'11'));

      }elseif($key == "cscxy"){
        $data['rule']= array('0'=>array('subtype'=>'传输层协议','valueStart'=>rand('TCP协议','UDP协议')));

      }elseif($key == 'url'){
        if($data['type'] == 1){
          $data['rule']= array('0'=>array('subtype'=>'URL','valueStart'=>'www.monitor.com/index'.$i.'html'));
        }elseif($data['type'] == 2){
          $data['rule']= array('0'=>array('subtype'=>'URL','valueStart'=>'www.block.com/index'.$i.'html'));
        }                  
      }
      return $data['rule'];
  }
  //判断规则优先级
  public function judge_level($data,$type){
    foreach ($data as $key => $value) {
      $subtype[] = $value['subtype'];
      if($value['subtype'] == '传输层协议'){
        $subtype['valueStart'] = $value['valueStart'];
      }
    }
    if($type == 1){
      if(in_array('目的IP地址', $subtype)){
        $data['level'] = '111000000001';
      }
      if(in_array('目的端口', $subtype)){
        $data['level'] = '111000000010';
      }
      if(in_array('传输层协议', $subtype)){
        if($subtype['valueStart'] == 'TCP协议'){
          $data['level'] = '111000000100';
        }else{
          $data['level'] = '111000001000';
        }
      }
      if(in_array('源IP地址', $subtype)){
        $data['level'] = '111000010000';
      }
      if(in_array('源端口', $subtype)){
        $data['level'] = '111000100000';
      }
      if(in_array('域名', $subtype)){
        $data['level'] = '111001000000';
      }
      if(in_array('URL', $subtype)){
        $data['level'] = '111010000000';
      }
      if(in_array('关键字', $subtype)){
        $data['level'] = '111100000000';
      }
    }elseif($type == 2){
      if(in_array('目的IP地址', $subtype)){
        $data['level'] = '110000000001';
      }
      if(in_array('目的端口', $subtype)){
        $data['level'] = '110000000010';
      }
      if(in_array('传输层协议', $subtype)){
        if($subtype['valueStart'] == 'TCP协议'){
          $data['level'] = '110000000100';
        }else{
          $data['level'] = '110000001000';
        }
      }
      if(in_array('源IP地址', $subtype)){
        $data['level'] = '110000010000';
      }
      if(in_array('源端口', $subtype)){
        $data['level'] = '110000100000';
      }
      if(in_array('域名', $subtype)){
        $data['level'] = '110001000000';
      }
      if(in_array('URL', $subtype)){
        $data['level'] = '110010000000';
      }
      if(in_array('关键字', $subtype)){
        $data['level'] = '110100000000';
      }
    }
    return $data['level'];
  }
  public function info_del(){
    if(I('id')){
      $where['id'] = I('id');
      $data = M('policy_isms')->where($where)->select()[0];
      $commandid = $data['commandid'];
      $data['rule'] = M('isms_rule')->where(array('commandid'=>$commandid))->select();
      $symbol = array(
                      '0'=>array('search'=>'&','replace'=>'&amp;'),
                      '1'=>array('search'=>'<','replace'=>'&lt;'),
                      '2'=>array('search'=>'>','replace'=>'&gt;'),
                      '3'=>array('search'=>'"','replace'=>'&quot;'),
                      '4'=>array('search'=>"'",'replace'=>'&apos;'),
                    );
      foreach($data['rule'] as $key =>$value){
        foreach ($symbol as $ke => $valu) {
          $data['rule'][$key]['value_start']=str_replace($valu['search'],$valu['replace'],$data['rule'][$key]['value_start']);
        }
        $data['rule'][$key]['valueStart'] = $data['rule'][$key]['value_start'];
        
        if($value['value_end']!=''){
          $data['rule'][$key]['valueEnd']=$value['value_end'];
        }
        if($value['keyword_range'] !=''){
          $kewords = explode(',',$value['keyword_range']);
          foreach ($kewords as $k => $val) {
            $data['rule'][$key]['keywordRange'] .=  $this->info_value('keyword_range',$value['keyword_range'])." ,";
          }
          $data['rule'][$key]['keywordRange'] = rtrim($data['rule'][$key]['keywordRange'],','); 
        }        
      }
      $house = M('policy_isms')->where(array('commandid'=>$commandid))->select();
      foreach ($house as $key => $value) {
         $house[$key] = $value['house_id']  ; 
      }
      $data['house_id'] = $house;
      $data['operationtype'] = 1;
      $res= $this->info_build_xml($data);
      if($res){
        self::log('Web', '取消信安策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }
  }
  public function info_realdel(){
    if(I('id')){
      $where['id'] = I('id');
      $result = M('policy_isms')->where($where)->select();
      $res = M('policy_isms')->where(array('commandid'=>$result[0]['commandid']))->delete();
      M('isms_rule')->where(array('commandid'=>$result[0]['commandid']))->delete();

      if($res){
        self::log('Web', '删除信安策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }
  }
  public function info_build_xml($data){
    $value=$data;
    $xml =  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>"; 
    $xml .="
    <command>
      <commandId>".$value['commandid']."</commandId>
      <type>".$value['type']."</type>"."\n";
      foreach ($data['rule'] as $key => $val) {
        if($val['subtype'] == '源IP地址' || $val['subtype'] == '目的IP地址'){
          $valus = explode('-',$val['valueStart']);
          $val['valueStart'] = $valus[0];
          $val['valueEnd'] = $valus[1];
        }

        $xml .= "      <rule>
        <subtype>".$this->info_value('subtype_value',$val['subtype'])."</subtype>
        <valueStart>".$this->info_value('valuestart_value',$val['valueStart'])."</valueStart>"."\n";
         if(!empty($val['valueEnd'])){
          $xml .= "        <valueEnd>".$val['valueEnd']."</valueEnd>"."\n";
          
         }
         
         if(!empty($val['keywordRange']) ){
          $val['keywordRange'] = explode(',',$val['keywordRange']);
          foreach ($val['keywordRange'] as $ke => $valuee) {
            
            $xml .= "        <keywordRange>".$this->info_value('keywordvalue',$valuee)."</keywordRange>"."\n";
            # code...
          }            

         }
          $xml .= "      </rule>"."\n";         
      }

      $xml.="      <action>
            <reason>no</reason>
            <log>1</log>
            <report>1</report>
      </action>
      <time>
          <effectTime>".date('Y-m-d H:i:s',$value['effect_time'])."</effectTime>
          <expiredTime>".date('Y-m-d H:i:s',$value['expired_time'])."</expiredTime>
      </time>
      <range>
          <idcId>".$value['idc_id']."</idcId>"."\n";
      foreach ($data['house_id'] as $key => $valu) {
        $xml.="          <houseId>".$valu."</houseId>"."\n";
      }        
      $xml.="      </range>
      <privilege>
          <owner>".$value['owner']."</owner>
          <visible>1</visible>
      </privilege>
      <operationType>".$value['operationtype']."</operationType>
      <level>".$value['level']."</level>
      <timeStamp>".date('Y-m-d H:i:s')."</timeStamp>
    </command>";
    $file = "./byzoro/policy_scan/".$value['commandid'].".xml"; 
    //$file = "E:/php/WWW/CU/Public/illegal_info/".$value['commandid'].".xml";
    $res = file_put_contents($file, $xml, LOCK_EX);
    return $res;     
  }
  
  //违法违规网站
  public function illegal_net(){
    if(IS_POST){
      $where['commandid'] = array('in',I('post.commandid'));
      $result = M('policy_black_list')->where($where)->group('commandid')->select();
      foreach ($result as $key => $value) {
        $comm_num = M('policy_black_list')->where(array('commandid'=>$value['commandid']))->select();
          if(count($comm_num)>1){
            $check_fail = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>3))->select();
            $check_success = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>2))->select();
            $check_ing = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>1))->select();
            $check_no_access = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>4))->select();
            $check_no_ack = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>8))->select();
            $check_del_ing = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>5))->select();
            $check_del_fail = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>7))->select();
            $check_del_suc = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>6))->select();
            if($check_ing){
              $result[$key]['new_status']= "下发中";
            }elseif($check_del_ing){
              $result[$key]['new_status']= "取消中";
            }else{
              if(count($check_no_ack) == count($comm_num)){
                $result[$key]['new_status']= "未回复ack";
              }elseif(count($check_no_access) == count($comm_num)){
                $result[$key]['new_status']= "接口不通";
              }else{
                if(count($check_success) == count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['new_status'] = "下发成功";
                }elseif(count($check_success) != count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['new_status'] = "下发失败";
                }elseif(count($check_del_suc) == count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['new_status'] = "取消成功";
                }elseif(count($check_del_suc) != count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['new_status'] = "取消失败";
                }
              }
            }
          }else{
            $result[$key]['new_status'] = $this->info_value('status',$value['status']);
          }
      }
      if($result){
        $this->ajaxReturn($result);
      }
    }else{
        if(I('get.n')=='room'){
            $this ->illegal_net_room(I('get.commandid'));
        }elseif($_GET && empty(I('get.n'))){
          if(I('commandid')!=''){
            $commandid = I('commandid');
            $where['commandid'] =$commandid;
          }
          if(I('status')!=''){
            $status = I('status');
            //下发中状态的commandid
            $ing_res = M('policy_black_list')->where(array('status'=>1))->select();
            foreach ($ing_res as $key => $value) {
              $ing_comm[]= $value['commandid']; 
            }
            //下发失败的commandid
            
            $fail_res = M('policy_black_list')->where(array('status'=>3))->select();
            foreach ($fail_res as $key => $value) {
              $fail_comm[]= $value['commandid']; 
            }  
            //接口不通的commandid     
            $no_access = M('policy_black_list')->where(array('status'=>4))->select();
            foreach ($no_access as $key => $value) {
              $no_access_s = M('policy_black_list')->where(array('commandid'=>$value['commandid']))->select();
              $no_num = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>"4"))->select();
              if(count($no_access_s) == count($no_num)){
                $no_access_comm[]= $value['commandid']; 
              }
            }
            //取消中的commandid
            $del_ing = M('policy_black_list')->where(array('status'=>5))->select();
            foreach ($del_ing as $key => $value) {
              $del_ing_comm[]= $value['commandid']; 
            }
            //取消成功的commandid
            $del_success = M('policy_black_list')->where(array('status'=>6))->select();
            foreach ($del_success as $key => $value) {
              $del_success_s = M('policy_black_list')->where(array('commandid'=>$value['commandid']))->select();
              $del_num = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>"6"))->select();
              if(count($del_success_s) == count($del_num)){
                $del_success_comm[]= $value['commandid'];
              }
            }
            //取消失败的commandid
            $del_fail = M('policy_black_list')->where(array('status'=>7))->select();
            foreach ($del_fail as $key => $value) {
              $del_fail_comm[]= $value['commandid']; 
            }
            //未回复ack的commandid
            $no_ack = M('policy_black_list')->where(array('status'=>8))->select();
            foreach ($no_ack as $key => $value) {
              $no_ack_s = M('policy_black_list')->where(array('commandid'=>$value['commandid']))->select();
              $no_ack_num = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>"8"))->select();
              if(count($no_ack_s) == count($no_ack_num)){
                $no_ack_comm[]= $value['commandid']; 
              }
            }
            //下发成功的commandid
            $success = M('policy_black_list')->where(array('status'=>2))->select();
            foreach ($success as $key => $value) {
              $success_s = M('policy_black_list')->where(array('commandid'=>$value['commandid']))->select();
              $success_num = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>"2"))->select();
              if(count($success_s) == count($success_num)){
                //$success_comm .= $value['commandid'].',';
                $success_comm[] = $value['commandid'];  
              }
            }
            //dump($success);die;
            if($commandid == ''){
              switch ($status) {
                case 1:
                  if($ing_comm!=''){
                    $where['commandid'] = array('in',$ing_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 2:
                  if($success_comm!=''){
                    $where['commandid'] = array('in',$success_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 3:
                  if($fail_comm!=''){
                    $where['commandid'] = array('in',$fail_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 4:
                  if($no_access_comm!=''){
                    $where['commandid'] = array('in',$no_access_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 5:
                  if($del_ing_comm!=''){
                    $where['commandid'] = array('in',$del_ing_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 6:
                  if($del_success_comm!=''){
                    $where['commandid'] = array('in',$del_success_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 7:
                  if($del_fail_comm!=''){
                    $where['commandid'] = array('in',$del_fail_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 8:
                  if($no_ack_comm!=''){
                    $where['commandid'] = array('in',$no_ack_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  break;
              }
            }elseif($commandid!=''){
              switch ($status) {
                case 1:
                  if($ing_comm!=''){
                    $ing_comm = implode(',',$ing_comm);
                    $where['_string'] = " commandid in ".$ing_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 2:
                  if($success_comm!=''){
                    $success_comm = "(".implode(',',$success_comm).")";
                    $where['_string'] = " commandid in ".$success_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 3:
                  if($fail_comm!=''){
                    $fail_comm = "(".implode(',',$fail_comm).")";
                    $where['_string'] = " commandid in ".$fail_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 4:
                  if($no_access_comm!=''){
                    $no_access_comm = "(".implode(',',$no_access_comm).")";
                    $where['_string'] = " commandid in ".$no_access_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 5:
                  if($del_ing_comm!=''){
                    $del_ing_comm = "(".implode(',',$del_ing_comm).")";
                    $where['_string'] = " commandid in ".$del_ing_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 6:
                  if($del_success_comm!=''){
                    $del_success_comm = "(".implode(',',$del_success_comm).")";
                    $where['_string'] = " commandid in ".$del_success_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 7:
                  if($del_fail_comm!=''){
                    $del_fail_comm = "(".implode(',',$del_fail_comm).")";
                    $where['_string'] = " commandid in ".$del_fail_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 8:
                  if($no_ack_comm!=''){
                    $no_ack_comm = "(".implode(',',$no_ack_comm).")";
                    $where['_string'] = " commandid in ".$no_ack_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  break;
              }
            }
          }
          if(I('operationtype')!=''){
            $operationtype = I('operationtype');
            $where['operationtype'] =$operationtype;
          }
          if(I('timestamp')!=''){
            $timestamp =I('timestamp');
            $time = explode(' - ',$timestamp);
            $where['timestamp'] = array('between',array(strtotime($time[0]),strtotime($time[1])));
          }
          if(I('source')!=""){
            $source = I('source');
            $where['source'] = $source;
          }
          if(I('type1')!=""){
            $type1 = I('type1');
            $where['type'] = $type1;
          }
        }
        $count = M('policy_black_list')->where($where)->count('DISTINCT commandid');// 查询满足要求的总记录数
        $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $result=M('policy_black_list')->where($where)->group('commandid')->order('timestamp desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($result as $key => $value) {
          $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
          $comm_num = M('policy_black_list')->where(array('commandid'=>$value['commandid']))->select();
          if(count($comm_num)>1){
            $check_fail = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>3))->select();
            $check_success = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>2))->select();
            $check_ing = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>1))->select();
            $check_no_access = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>4))->select();
            $check_no_ack = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>8))->select();
            $check_del_ing = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>5))->select();
            $check_del_fail = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>7))->select();
            $check_del_suc = M('policy_black_list')->where(array('commandid'=>$value['commandid'],'status'=>6))->select();
           if($check_ing){
              $result[$key]['status']= "下发中";
            }elseif($check_del_ing){
              $result[$key]['status']= "取消中";
            }else{
              if(count($check_no_ack) == count($comm_num)){
                $result[$key]['status']= "未回复ack";
              }elseif(count($check_no_access) == count($comm_num)){
                $result[$key]['status']= "接口不通";
              }else{
                if(count($check_success) == count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['status'] = "下发成功";
                }elseif(count($check_success) != count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['status'] = "下发失败";
                }elseif(count($check_del_suc) == count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['status'] = "取消成功";
                }elseif(count($check_del_suc) != count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['status'] = "取消失败";
                }else{
                  $result[$key]['status'] = $this->info_value('status',$value['status']);
                }
              }
              
            }
            $result[$key]['type'] = $this->info_value('black_list_type',$value['type']);
            $result[$key]['house_name'] = '查看详情';
            $result[$key]['source'] = $this->info_value('source',$value['source']);
            $result[$key]['operationtype'] = $this->info_value('operationtype',$value['operationtype']);
          }else{
            $result[$key]['type'] = $this->info_value('black_list_type',$value['type']);
            $result[$key]['status'] = $this->info_value('status',$value['status']);
            $result[$key]['source'] = $this->info_value('source',$value['source']);
            $result[$key]['operationtype'] = $this->info_value('operationtype',$value['operationtype']);
            $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
          }
          # code...
        }
        $show = $Page->show();
        $this->assign('show', $show);
        $this->assign('res',$result);
        $this->assign('commandid',$commandid);
        $this->assign('status',$status);
        $this->assign('operationtype',$operationtype);
        $this->assign('timestamp',$timestamp);
        $this->assign('source',$source);
        $this->assign('type1',$type1);
        self::log('Web', '违法网站列表查询', 5);
        Layout('Layout/layout');
        $this->display();
    }
  }
  public function illegal_net_room($commandid){
    
      $comm_nu = M('policy_black_list')->where(array('commandid'=>$commandid))->select();
      foreach ($comm_nu as $key => $value) {
        $comm_nu[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
        $comm_nu[$key]['status'] = $this->info_value('status',$value['status']); 

      }
      $this->ajaxReturn(array('data'=>$comm_nu));
  }
  public function illegal_net_add(){
    if($_GET){
      if(I('type') !=''){
        $data['type'] = I('type');
      }else{
        $this->ajaxReturn(array('status'=>'error','info'=>'规则类型不能为空！'));
      }
      if(I('contents')!=''){
        if(strlen(I('contents')) >128){
          $this->ajaxReturn(array('status'=>'error','info'=>'规则内容不能超过128位！'));
        }else{
          $data['contents'] = I('contents');
        }
      }else{
        $this->ajaxReturn(array('status'=>'error','info'=>'规则内容不能为空！'));
      }
      $max_commandid = M('cu_commandid')->order('id desc')->limit(0,1)->select()[0]['commandid'];
      if($max_commandid){
        $data['commandid'] = $max_commandid+1;       
      }else{
        $data['commandid'] = 1;
      }
      $data['operationtype'] = 0;
      $dat['commandid'] = $data['commandid'];
      M('cu_commandid')->add($dat); 
      $data['idc_id'] = M('basic_idc')->select()[0]['idc_id'];
      if($data['type']==1){
        $data['level']='100001000000';
      }elseif($data['type']==2){
        $data['level']='100000000001';
      }
      $data['owner'] = $_SESSION['username'];
      
      $res=$this->illegal_net_buildxml($data);
      if($res){
        self::log('Web', '下发违法违规网站策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }
  }
  public function illegal_net_del(){
    if(I('id')){
      $where['id'] = I('id');
      $data = M('policy_black_list')->where($where)->select()[0];
      $commandid =$data['commandid'];
      $house = M('policy_black_list')->where(array('commandid'=>$commandid))->select();
      foreach ($house as $key => $value) {
         $house[$key] = $value['house_id']; 
      }
      $data['house_id'] = $house;
      $data['operationtype'] = 1;
      $res=$this->illegal_net_buildxml($data);
      if($res){
        self::log('Web', '取消违法违规网站策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }
  }
  public function illegal_net_realdel(){
    if(I('id')){
      $where['id'] = I('id');
      $result = M('policy_black_list')->where($where)->select();
      $res = M('policy_black_list')->where(array('commandid'=>$result[0]['commandid']))->delete();
      if($res){
        self::log('Web', '删除违法违规网站策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }
    }
  }
  public function illegal_net_buildxml($data){
    $value=$data; 
    $xml =  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>";
    $xml .= "<blacklist>
                <commandId>".$value['commandid']."</commandId>
                <idcId>".$value['idc_id']."</idcId>
                <operationType>".$value['operationtype']."</operationType>
                <type>".$value['type']."</type>
                <contents>".$value['contents']."</contents>
                <level>".$value['level']."</level>
                <timeStamp>".date('Y-m-d H:i:s')."</timeStamp>
                <owner>".$value['owner']."</owner>
            </blacklist>";
            $file = "./byzoro/policy_scan/".$value['commandid'].".xml"; 
    //$file = "E:/xampp/htdocs/Feasims/Public/illegal_info/".$value['commandid'].".xml";
    file_put_contents($file, $xml, LOCK_EX);
    return $xml;
  }

  //免过滤网站
  public function no_filter(){
    if(IS_POST){
        $where['commandid'] = array('in',I('post.commandid'));
      $result = M('policy_no_filter')->where($where)->group('commandid')->select();
      foreach ($result as $key => $value) {
        $comm_num = M('policy_no_filter')->where(array('commandid'=>$value['commandid']))->select();
          if(count($comm_num)>1){
            $check_fail = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>3))->select();
            $check_success = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>2))->select();
            $check_ing = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>1))->select();
            $check_no_access = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>4))->select();
            $check_no_ack = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>8))->select();
            $check_del_ing = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>5))->select();
            $check_del_fail = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>7))->select();
            $check_del_suc = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>6))->select();
            if($check_ing){
              $result[$key]['new_status']= "下发中";
            }elseif($check_del_ing){
              $result[$key]['new_status']= "取消中";
            }else{
              if(count($check_no_ack) == count($comm_num)){
                $result[$key]['new_status']= "未回复ack";
              }elseif(count($check_no_access) == count($comm_num)){
                $result[$key]['new_status']= "接口不通";
              }else{
                if(count($check_success) == count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['new_status'] = "下发成功";
                }elseif(count($check_success) != count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['new_status'] = "下发失败";
                }elseif(count($check_del_suc) == count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['new_status'] = "取消成功";
                }elseif(count($check_del_suc) != count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['new_status'] = "取消失败";
                }else{
                  $result[$key]['new_status'] = $this->info_value('status',$value['status']);
                }
              }
            }
          }else{
            $result[$key]['new_status'] = $this->info_value('status',$value['status']);
          }
      }
      if($result){
        $this->ajaxReturn($result);
      }
    }else{
        if(I('get.type')=='room'){
          $this -> no_filter_room(I('commandid'));
        }elseif($_GET && empty(I('get.type'))){
          if(I('commandid')!=''){
            $commandid = I('commandid');
            $where['commandid'] =$commandid;
          }
          if(I('status')!=''){
            $status = I('status');
            //下发中状态的commandid
            $ing_res = M('policy_no_filter')->where(array('status'=>1))->select();
            foreach ($ing_res as $key => $value) {
              $ing_comm[]= $value['commandid']; 
            }
            //下发失败的commandid
            
            $fail_res = M('policy_no_filter')->where(array('status'=>3))->select();
            foreach ($fail_res as $key => $value) {
              $fail_comm[]= $value['commandid']; 
            }  
            //接口不通的commandid     
            $no_access = M('policy_no_filter')->where(array('status'=>4))->select();
            foreach ($no_access as $key => $value) {
              $no_access_s = M('policy_no_filter')->where(array('commandid'=>$value['commandid']))->select();
              $no_num = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>"4"))->select();
              if(count($no_access_s) == count($no_num)){
                $no_access_comm[]= $value['commandid']; 
              }
            }
            //取消中的commandid
            $del_ing = M('policy_no_filter')->where(array('status'=>5))->select();
            foreach ($del_ing as $key => $value) {
              $del_ing_comm[]= $value['commandid']; 
            }
            //取消成功的commandid
            $del_success = M('policy_no_filter')->where(array('status'=>6))->select();
            foreach ($del_success as $key => $value) {
              $del_success_s = M('policy_no_filter')->where(array('commandid'=>$value['commandid']))->select();
              $del_num = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>"6"))->select();
              if(count($del_success_s) == count($del_num)){
                $del_success_comm[]= $value['commandid'];
              }
            }
            //取消失败的commandid
            $del_fail = M('policy_no_filter')->where(array('status'=>7))->select();
            foreach ($del_fail as $key => $value) {
              $del_fail_comm[]= $value['commandid']; 
            }
            //未回复ack的commandid
            $no_ack = M('policy_no_filter')->where(array('status'=>8))->select();
            foreach ($no_ack as $key => $value) {
              $no_ack_s = M('policy_no_filter')->where(array('commandid'=>$value['commandid']))->select();
              $no_ack_num = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>"8"))->select();
              if(count($no_ack_s) == count($no_ack_num)){
                $no_ack_comm[]= $value['commandid']; 
              }
            }
            //下发成功的commandid
            $success = M('policy_no_filter')->where(array('status'=>2))->select();
            foreach ($success as $key => $value) {
              $success_s = M('policy_no_filter')->where(array('commandid'=>$value['commandid']))->select();
              $success_num = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>"2"))->select();
              if(count($success_s) == count($success_num)){
                //$success_comm .= $value['commandid'].',';
                $success_comm[] = $value['commandid'];  
              }
            }
            //dump($success);die;
            if($commandid == ''){
              switch ($status) {
                case 1:
                  if($ing_comm!=''){
                    $where['commandid'] = array('in',$ing_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 2:
                  if($success_comm!=''){
                    $where['commandid'] = array('in',$success_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 3:
                  if($fail_comm!=''){
                    $where['commandid'] = array('in',$fail_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 4:
                  if($no_access_comm!=''){
                    $where['commandid'] = array('in',$no_access_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 5:
                  if($del_ing_comm!=''){
                    $where['commandid'] = array('in',$del_ing_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 6:
                  if($del_success_comm!=''){
                    $where['commandid'] = array('in',$del_success_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 7:
                  if($del_fail_comm!=''){
                    $where['commandid'] = array('in',$del_fail_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  
                  break;
                case 8:
                  if($no_ack_comm!=''){
                    $where['commandid'] = array('in',$no_ack_comm);
                  }else{
                    $where['commandid'] = '';
                  }
                  break;
              }
            }elseif($commandid!=''){
              switch ($status) {
                case 1:
                  if($ing_comm!=''){
                    $ing_comm = implode(',',$ing_comm);
                    $where['_string'] = " commandid in ".$ing_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 2:
                  if($success_comm!=''){
                    $success_comm = "(".implode(',',$success_comm).")";
                    $where['_string'] = " commandid in ".$success_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 3:
                  if($fail_comm!=''){
                    $fail_comm = "(".implode(',',$fail_comm).")";
                    $where['_string'] = " commandid in ".$fail_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 4:
                  if($no_access_comm!=''){
                    $no_access_comm = "(".implode(',',$no_access_comm).")";
                    $where['_string'] = " commandid in ".$no_access_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 5:
                  if($del_ing_comm!=''){
                    $del_ing_comm = "(".implode(',',$del_ing_comm).")";
                    $where['_string'] = " commandid in ".$del_ing_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 6:
                  if($del_success_comm!=''){
                    $del_success_comm = "(".implode(',',$del_success_comm).")";
                    $where['_string'] = " commandid in ".$del_success_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 7:
                  if($del_fail_comm!=''){
                    $del_fail_comm = "(".implode(',',$del_fail_comm).")";
                    $where['_string'] = " commandid in ".$del_fail_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  
                  break;
                case 8:
                  if($no_ack_comm!=''){
                    $no_ack_comm = "(".implode(',',$no_ack_comm).")";
                    $where['_string'] = " commandid in ".$no_ack_comm." AND commandid = ".$commandid;
                  }else{
                    $where['commandid'] = $commandid;
                  }
                  break;
              }
            }
          }
          if(I('operationtype')!=''){
            $operationtype = I('operationtype');
            $where['operationtype'] =$operationtype;
          }
          if(I('timestamp')!=''){
            $timestamp =I('timestamp');
            $time = explode(' - ',$timestamp);
            $where['timestamp'] = array('between',array(strtotime($time[0]),strtotime($time[1])));
          }
          if(I('source')!=""){
            $source = I('source');
            $where['source'] = $source;
          }
          if(I('type1')!=""){
            $type1 = I('type1');
            $where['type'] = $type1;
          }
        }
        //dump($where);die;
        $count = M('policy_no_filter')->where($where)->count('DISTINCT commandid');// 查询满足要求的总记录数
        $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $result=M('policy_no_filter')->where($where)->group('commandid')->order('timestamp desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($result as $key => $value) {
          $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
          $comm_num = M('policy_no_filter')->where(array('commandid'=>$value['commandid']))->select();
          if(count($comm_num)>1){
            $check_fail = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>3))->select();
            $check_ing = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>1))->select();
            $check_no_access = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>4))->select();
            $check_success = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>2))->select();
            $check_no_ack = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>8))->select();
            $check_del_ing = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>5))->select();
            $check_del_fail = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>7))->select();
            $check_del_suc = M('policy_no_filter')->where(array('commandid'=>$value['commandid'],'status'=>6))->select();
            if($check_ing){
              $result[$key]['status']= "下发中";
            }elseif($check_del_ing){
              $result[$key]['status']= "取消中";
            }else{
              if(count($check_no_ack) == count($comm_num)){
                $result[$key]['status']= "未回复ack";
              }elseif(count($check_no_access) == count($comm_num)){
                $result[$key]['status']= "接口不通";
              }else{
                if(count($check_success) == count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['status'] = "下发成功";
                }elseif(count($check_success) != count($comm_num) && $result[$key]['operationtype'] == '0'){
                  $result[$key]['status'] = "下发失败";
                }elseif(count($check_del_suc) == count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['status'] = "取消成功";
                }elseif(count($check_del_suc) != count($comm_num) && $result[$key]['operationtype'] == '1'){
                  $result[$key]['status'] = "取消失败";
                }else{
                  $result[$key]['status'] = $this->info_value('status',$value['status']);
                }
              }
            }
            $result[$key]['type'] = $this->info_value('black_list_type',$value['type']);
            $result[$key]['house_name'] = '查看详情';
            $result[$key]['source'] = $this->info_value('source',$value['source']);
            $result[$key]['operationtype'] = $this->info_value('operationtype',$value['operationtype']);
          }else{
            $result[$key]['type'] = $this->info_value('black_list_type',$value['type']);
            $result[$key]['status'] = $this->info_value('status',$value['status']);
            $result[$key]['source'] = $this->info_value('source',$value['source']);
            $result[$key]['operationtype'] = $this->info_value('operationtype',$value['operationtype']);
            $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
          }
        }
        $show = $Page->show();
        $this->assign('show', $show);
        $this->assign('res',$result);
        $this->assign('commandid',$commandid);
        $this->assign('status',$status);
        $this->assign('type1',$type1);
        $this->assign('operationtype',$operationtype);
        $this->assign('timestamp',$timestamp);
        $this->assign('source',$source);
        self::Log('Web', '免过滤网站列表', 5);
        Layout('Layout/layout');
        $this->display();
      }
  }
  public function no_filter_room($commandid){
      $commandid = I('commandid');
      $comm_nu = M('policy_no_filter')->where(array('commandid'=>$commandid))->select();
      foreach ($comm_nu as $key => $value) {
        $comm_nu[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
        $comm_nu[$key]['status'] = $this->info_value('status',$value['status']); 
      }
      $this->ajaxReturn(array('data'=>$comm_nu));
  }
  public function no_filter_add(){
     if($_GET){
      if(I('type') !=''){
        $data['type'] = I('type');
      }else{
        $this->ajaxReturn(array('status'=>'error','info'=>'规则类型不能为空！'));
      }
      if(I('contents')!=''){
        if(strlen(I('contents')) >128){
          $this->ajaxReturn(array('status'=>'error','info'=>'规则内容不能超过128位！'));
        }else{
          $data['contents'] = I('contents');
        }
        
      }else{
        $this->ajaxReturn(array('status'=>'error','info'=>'规则内容不能为空！'));
      }
      $max_commandid = M('cu_commandid')->order('id desc')->limit(0,1)->select()[0]['commandid'];
      if($max_commandid){
        $data['commandid'] =$max_commandid+1;       
      }else{
        $data['commandid'] = 1;
      }
      $data['operationtype'] = 0;
      $dat['commandid'] = $data['commandid'];
      M('cu_commandid')->add($dat); 
      $data['idc_id'] = M('basic_idc')->select()[0]['idc_id'];
      if($data['type']==1){
        $data['level']='101001000000';
      }elseif($data['type']==2){
        $data['level']='101000000001';
      }
      $data['owner'] = $_SESSION['username'];
      $res=$this->no_filter_buildxml($data);
      if($res){
        self::log('Web', '下发免过滤网站策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }

  }
  public function no_filter_del(){
    if(I('id')){
      $where['id'] = I('id');
      $data = M('policy_no_filter')->where($where)->select()[0];
      $commandid =$data['commandid'];
      $house = M('policy_no_filter')->where(array('commandid'=>$commandid))->select();
      foreach ($house as $key => $value) {
         $house[$key] = $value['house_id']; 
      }
      $data['house_id'] = $house;
      $data['operationtype'] = 1;
      $res=$this->no_filter_buildxml($data);
      if($res){
        self::log('Web', '取消免过滤网站策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }
  }
  public function no_filter_realdel(){
    if(I('id')){
       $where['id'] = I('id');
      $result = M('policy_no_filter')->where($where)->select();
      $res = M('policy_no_filter')->where(array('commandid'=>$result[0]['commandid']))->delete();
      if($res){
        self::log('Web', '删除免过滤网站策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }
    }
  }
  public function no_filter_buildxml($data){
    $value=$data; 
    $xml =  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>";
    $xml .= "<noFilter>
                <commandId>".$value['commandid']."</commandId>
                <idcId>".$value['idc_id']."</idcId>
                <operationType>".$value['operationtype']."</operationType>
                <type>".$value['type']."</type>
                <contents>".$value['contents']."</contents>
                <level>".$value['level']."</level>
                <timeStamp>".date('Y-m-d H:i:s')."</timeStamp>
                <owner>".$value['owner']."</owner>
            </noFilter>";
            $file = "./byzoro/policy_scan/".$value['commandid'].".xml"; 
    //$file = "E:/xampp/htdocs/Feasims/Public/illegal_info/".$value['commandid'].".xml";
    file_put_contents($file, $xml, LOCK_EX);
    return $xml;

  }
  public function access_log(){
    if(I('get.type')=='rule'){
      $this -> access_log_rule_info(I('commandid'));
    }elseif($_GET && empty(I('get.type'))){
      if(I('commandid')!=""){
        $commandid = I('commandid');
        $where['commandid'] =I('commandid');
      }
      if(I('house_name')!=""){
        $house_name = I('house_name');
        $where['house_id'] = I('house_name');
      }
    }
    $count = M('policy_access_query')->where($where)->count('DISTINCT commandid');// 查询满足要求的总记录数
    $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
    $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
    $result=M('policy_access_query')->where($where)->group('commandid')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    foreach ($result as $key => $value) {
      $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
      $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
      $result[$key]['status']=$this->info_value('access_log_status',$value['status']);
      $result[$key]['result']=$this->info_value('access_log_result',$value['result']);
      $result[$key]['protocol']=$this->info_value('value_start',$value['protocol']);
      
    }
    $show = $Page->show();
    $rooms = M('basic_house')->select();
    $this->assign('commandid',$commandid);
    $this->assign('house_name',$house_name);
    $this->assign('rooms',$rooms);   
    $this->assign('show', $show);
    $this->assign('res',$result);
    self::Log('Web', '访问日志查询指令', 5);
    Layout('Layout/layout');
    $this->display();
  }
  public function access_log_rule_info($commandid){
      $commandid = I('commandid');
      $where['commandid'] = $commandid;
      $result = M('policy_access_query')->group('commandid,house_id')->where($where)->select();
      foreach ($result as $key => $value) {
        $result[$key]['protocol']=$this->info_value('value_start',$value['protocol']);
      }
      $this->ajaxReturn(array('data'=>$result));
  }
  public function access_log_add(){
    if($_GET){
      $data = $_GET;
      $max_commandid = M('cu_commandid')->order('id desc')->limit(0,1)->select()[0]['commandid'];
      if($max_commandid){
        $data['commandid'] =$max_commandid+1;       
      }else{
        $data['commandid'] = 1;
      }
      $dat['commandid'] = $data['commandid'];
      M('cu_commandid')->add($dat); 
      $data['idc_id'] = M('basic_idc')->select()[0]['idc_id'];
      if($data['startTime'] == ''){
        $this->ajaxReturn(array('status'=>'error','info'=>'查询起始时间不能为空！'));
      }
      $data['src_start_ip'] = explode('-',$data['srcIp'])[0];
      $data['src_end_ip'] = explode('-',$data['srcIp'])[1];
      $data['dest_start_ip'] = explode('-',$data['destIp'])[0];
      $data['dest_end_ip'] = explode('-',$data['destIp'])[1];
      $res=$this->access_log_buildxml($data);
      if($res){
        self::log('Web', '下发访问日志查询策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }

    }
  }
  public function access_log_buildxml($data){
    $value=$data; 
    $xml =  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>";
    $xml .= "<logQuery>
                <commandId>".$value['commandid']."</commandId>
                <idcId>".$value['idc_id']."</idcId>
                <commandInfo>
                    <houseId>".$value['houseId']."</houseId>
                    <startTime>".$value['startTime']."</startTime>"."\n";
                    if($value['endTime']!=""){
                      $xml .="                    <endTime>".$value['endTime']."</endTime>"."\n";
                    }
                    if($value['src_start_ip'] !="" || $value['src_end_ip'] !=''){
                      $xml .="                    <srcIp>"."\n";
                      if($value['src_start_ip']!=""){
                        $xml .="                       <startIp>".$value['src_start_ip']."</startIp>"."\n";
                      }
                      if($value['src_end_ip'] !=""){
                        $xml .= "                       <endIp>".$value['src_end_ip']."</endIp>"."\n";
                      }            
                      $xml .="                    </srcIp>"."\n";
                    }
                    if($value['dest_start_ip'] !="" || $value['dest_end_ip'] !=''){
                      $xml .="                    <destIp>"."\n";
                      if($value['dest_start_ip']!=""){
                        $xml .="                       <startIp>".$value['dest_start_ip']."</startIp>"."\n";
                      }
                      if($value['dest_end_ip'] !=""){
                        $xml .= "                       <endIp>".$value['dest_end_ip']."</endIp>"."\n";
                      }            
                      $xml .="                    </destIp>"."\n";
                    }
                    if($value['srcPort'] !=''){
                      $xml .=  "                    <srcPort>".$value['srcPort']."</srcPort>"."\n";
                    }
                    if($value['dstPort'] !=''){
                      $xml .="                    <dstPort>".$value['dstPort']."</dstPort>"."\n";
                    }
                    if($value['ProtocolType']!=''){
                      $xml .="                    <ProtocolType>".$value['ProtocolType']."</ProtocolType>"."\n";
                    }
                    if($value['url']!=''){
                      $xml .= "                    <url>".$value['url']."</url>"."\n";
                    }
           
                $xml.="                </commandInfo>
                <timeStamp>".date('Y-m-d H:i:s')."</timeStamp>
            </logQuery>";
            $file = "./byzoro/policy_scan/".$value['commandid'].".xml"; 
    //$file = "E:/xampp/htdocs/Feasims/Public/illegal_info/".$value['commandid'].".xml";
    file_put_contents($file, $xml, LOCK_EX);
    return $xml;

  }
  public function active_resource(){
    if($_GET){
      if(I('commandid')!=''){
        $commandid = I('commandid');
        $where['commandid'] = $commandid;
      }
      if(I('type')!=''){
        $type = I('type');
        $where['type'] = $type;
      }
      if(I('contents')!=''){
        $contents = I('contents');
        $where['content'] = $contents;
      }
    }
    $result=M('policy_active_view')->where($where)->select();
    foreach ($result as $key => $value) {
      $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
      $result[$key]['status']=$this->info_value('status',$value['status']);
      $result[$key]['type']=$this->info_value('active_type',$value['type']);
      # code...
    }
    $this->assign('res',$result);
    $this->assign('commandid',$commandid);
    $this->assign('type',$type);
    $this->assign('contents',$contents);
    self::Log('Web','活跃资源访问量查询',5);
    Layout('Layout/layout');
    $this->display();
  }

  public function illegal_manage(){
    Layout('Layout/layout');
    $this->display();
  }
  public function code_table(){
    Layout('Layout/layout');
    $this->display();
  }
  public function active_time(){
    $result = M('policy_active_period')->select();
    foreach ($result as $key => $value) {
      $result[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$value['house_id']))->select()[0]['house_name'];
    }
    $this  -> assign('res',$result);
    $rooms =  M('basic_house')->select();
    $this  -> assign('rooms',$rooms);
    self::Log('Web','活跃资源周期',5);
    Layout('Layout/layout');
    $this->display();
  }
  public function active_time_add(){
    if($_GET){
      $data['houseId']=I('get.houseId');
      $data['reportTime'] = I('get.reportTime');
      $max_commandid = M('cu_commandid')->order('id desc')->limit(0,1)->select()[0]['commandid'];
      if($max_commandid){
        $data['commandid'] = $max_commandid+1;       
      }else{
        $data['commandid'] =1;
      }
      $data['prov_id']  = M('setting_eu')->select()[0]['prov_id'];
      $dat['commandid'] = $data['commandid'];
      M('cu_commandid') ->add($dat);
      $res = $this->active_time_buildxml($data);
      if($res){
        self::log('Web', '下发活跃资源周期策略', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }
    }
  }
  public function active_time_buildxml($data){
    $value=$data;
    $xml =  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>";
    $xml .= "<findDomainCycle>
                <provID>".$value['prov_id']."</provID>
                <houseId>".$value['houseId']."</houseId>
                <reportTime>".$value['reportTime']."</reportTime>               
            </findDomainCycle>";
            $file = "./byzoro/policy_scan/".$value['commandid'].".xml"; 
    //$file = "E:/xampp/htdocs/Feasims/Public/illegal_info/".$value['commandid'].".xml";
    file_put_contents($file, $xml, LOCK_EX);
    return $xml;

  }
  public function timediff($timediff){
        //计算天数
    $days = intval($timediff/86400);
    //计算小时数
    $remain = $timediff%86400;
    $hours = intval($remain/3600);
    //计算分钟数
    $remain = $remain%3600;
    $mins = intval($remain/60);
    //计算秒数
    $secs = $remain%60;
    $res = $days."天".$hours."小时".$mins."分钟";
    return $res;
  }
  //参数管理
  public function info_value($col,$value){
    $c = [
        'subtype' => function($v){
            $cc = [
              '1'                                   => '域名',
              '2'                                   => 'URL',
              '3'                                   => '关键字',
              '4'                                   => '源IP地址',
              '5'                                   => '目的IP地址',
              '6'                                   => '源端口',
              '7'                                   => '目的端口',
              '8'                                   => '传输层协议',
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'subtype_value' => function($v){
            $cc = [
              '域名'                                =>'1',
              'URL'                                 =>'2',
              '关键字'                              =>'3',
              '源IP地址'                            =>'4',
              '目的IP地址'                          =>'5',
              '源端口'                              =>'6',
              '目的端口'                            =>'7',
              '传输层协议'                          =>'8',
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },  
        'keyword_range' => function($v){
            $cc = [
              '0'                                   => '正文标题及正文本身',
              '1'                                   => '附件文件题目',
              '2'                                   => '附件正文',
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },  
        'keywordvalue' => function($v){
            $cc = [
              '正文标题及正文本身 '                  =>'0',
              '附件文件题目 '                        =>'1',
              '附件正文'                            =>'2',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'ack' => function($v){
            $cc = [
              '0'                                   => '下发成功',
              '1'                                   => '下发失败',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'isms_type' => function($v){
            $cc = [
              '1'                                   => '监测',
              '2'                                   => '过滤',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'report'  => function($v){
            $cc = [
              '0'                                   => '未上报',
              '1'                                   => '已上报',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'basic_data_type'  => function($v){
            $cc = [
              '0'                                   => '查询基础数据记录',
              '1'                                   => '保留',
              '2'                                   => '保留',
              '3'                                   => '查询基础数据监测异常',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },  
        'black_list_type'  => function($v){
            $cc = [
              '1'                                   => '域名',
              '2'                                   => 'IP',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'active_type'  => function($v){
            $cc = [
              '1'                                   => '顶级域名',
              '2'                                   => '子域名',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'valuestart_value'  => function($v){
            $cc = [
              'TCP协议'                             => '1',
              'UDP协议'                             => '2',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'value_start'  => function($v){
            $cc = [
              '1'                                   => 'TCP协议',
              '2'                                   => 'UDP协议',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'status'  => function($v){
            $cc = [
              '1'                                   => '下发中',
              '2'                                   => '下发成功',
              '3'                                   => '下发失败',
              '4'                                   => '接口不通',
              '5'                                   => '取消中',
              '6'                                   => '取消成功',
              '7'                                   => '取消失败',
              '8'                                   => '未回复ack',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'access_log_status'  => function($v){
            $cc = [
              '1'                                   => '下发中',
              '2'                                   => '下发成功',
              '3'                                   => '查询条件错误',
              '4'                                   => 'DU解析错误',
              '5'                                   => 'DU系统繁忙',
              '6'                                   => '其他错误',
              '7'                                   => '下发失败',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'access_log_result'  => function($v){
            $cc = [
              '1'                                   => '等待DU返回结果',
              '2'                                   => 'DU返回查询结果',
              '3'                                   => 'DU未查询到结果',
              '4'                                   => 'DU上传失败',
              '5'                                   => 'DU返回其他错误',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        }, 
        'operationtype'  => function($v){
            $cc = [
              '0'                                   => '新增',
              '1'                                   => '删除',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'log_flag'  => function($v){
            $cc = [
              '0'                                   => '不记录日志',
              '1'                                   => '记录日志',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'report_flag'  => function($v){
            $cc = [
              '0'                                   => '不上传日志',
              '1'                                   => '上传日志',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'source'  => function($v){
            $cc = [
              '0'                                   => 'SMMS',
              '1'                                   => '本地配置',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'return_code'  => function($v){
            $cc = [
              '0'                                   => '上报数据通过核验',
              '1'                                   => '上报数据与既有数据记录冲突',
              '2'                                   => '上报数据内容不完整',
              '3'                                   => '上报数据内容错误',
              '4'                                   => '其他原因退回',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },    
    ];
    return isset($c[$col]) ? $c[$col]($value) : $value;
  }
  public function colvalue($col){
     $c = [
          'ym'                                   => '1',
          'url'                                  => '2',
          'gjz'                                  => '3',
          'yip'                                  => '4',
          'mdip'                                 => '5',
          'ydk'                                  => '6',
          'mddk'                                 => '7',
          'cscxy'                                => '8',
      ];

      return $col === null ? array_keys($c) : (isset($c[$col]) ? $c[$col] : "");

  }

public function test(){
  // $ids = trim(array_reduce($results1, 'retrive_ids'), ',');
  $a=M('policy_isms')->select();
  $new = trim(array_reduce($a, function(&$new,$v2){
    return $new .",". $v2['id'];

  }),',');
  dump($new);die;
}

}