<?php
namespace Home\Controller;
//use Think\Controller;
class BasicinfoController extends CommonController {
    //经营单位信息
    public function company(){
      if($_GET){
        if($idc_name = I('get.idc_name')){
          $idc_name1 = str_replace('&amp;', '&', $idc_name);
          $idc_name2 = str_replace('&quot;', '"', $idc_name1);
          $idc_name3 = str_replace('&lt;', '<', $idc_name2);
          $idc_name = str_replace('&gt;', '>', $idc_name3);
          $where['idc_name'] =$idc_name;
        }
        if($idc_corp = I('get.idc_corp')){
           $where['idc_corp'] =$idc_corp;
        }       
      }
      $where['idc_status'] =array('neq',3);
      $count = M('basic_idc')->where($where)->count();// 查询满足要求的总记录数
      $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
      $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
      $result = M('basic_idc')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
      foreach ($result as $key => $value) {
        $result[$key]['idc_officer_idtype'] = $this->info_value('id_type',$value['idc_officer_idtype']);
        $result[$key]['idc_emergency_idtype'] = $this->info_value('id_type',$value['idc_emergency_idtype']);
      }
      $show = $Page->show();
      $this->assign('show', $show);
      $this->assign('idc_name',$idc_name);
      $this->assign('idc_corp',$idc_corp);
      $this->assign('result',$result);
      self::log('Web', 'IDC/ISP经营单位信息查询', 5);
      Layout('Layout/layout');
      $this->display();
    }        
    public function company_add(){ 
        if($_GET){
            $data =$_GET;
            $data['idc_status'] = "1";
            $result = M('basic_idc')->select();
            if($result){
              $this->ajaxReturn(array('status'=>'error','info'=>'经营单位只能有一个，不允许添加多个！'));
            }else{
              $res = M('basic_idc')->add($data);
              if($res){
                  self::log('Web', '添加经营单位成功', 5);
                  $this->ajaxReturn(['status'=>'success']);
              }else{
                  self::log('Web', '添加经营单位失败', 3);
                  $this->ajaxReturn(['status'=>'false']);
              }
            }
            
        }else{
            Layout('Layout/layout');
            $this->display();
        }
        
    }
    public function company_edit(){
      if(count(I('get.'))>1){
          $data = $_GET;
          $id = I('id');
          $result =M('basic_idc')->where(array('id'=>$id))->select();
          $house_res = M('basic_house')->where(array('idc_id'=>$result[0]['idc_id'],'status'=>array('neq',3)))->select();

          if($house_res && $data['idc_id'] !=$result[0]['idc_id']){
            $this->ajaxReturn(array('status'=>'false','info'=>'该经营单位已经绑定机房，IDC/ISP许可证号不允许修改！'));
          }
          if($result[0]['idc_status'] == "1"){
            $data['idc_status'] = "1";
          }else{
            $data['idc_status'] = $result[0]['idc_status'];
            if($data != $result[0]){
              $data['idc_status'] = "2";
            }          
          }
          $res = M('basic_idc')->where(array('id'=>$id))->save($data);
          if($res!==false){
            self::log('Web', '编辑经营单位成功', 5);
            $this->ajaxReturn(array('status'=>'success'));
          }else{
            self::log('Web', '编辑经营单位失败', 3);
            $this->ajaxReturn(array('status'=>'error'));
          }
      }else{
          $id = I('get.id');
          $result = M('basic_idc')->where(array('id'=>$id))->select()[0];
          $this->assign('data',$result);
          self::log('Web', '编辑经营单位', 5);
          Layout('Layout/layout');
          $this->display();
      }
    }
    public function company_del(){
      if($_GET){
        $id = I('get.id');
        $result = M('basic_idc')->where(array('id'=>$id))->select();
        $house_res = M('basic_house')->where(array('idc_id'=>$result[0]['idc_id'],'status'=>array('neq',3)))->select();
        $user_res = M('basic_user')->where(array('idc_id'=>$result[0]['idc_id'],'status'=>array('neq',3)))->select();
        if($house_res || $user_res){
          $this->ajaxReturn(array('status'=>'error','info'=>'该经营单位信息下有机房或用户占用！请先删除机房或者用户信息！'));
        }else{
          if($result[0]['idc_status'] == "1" || $result[0]['idc_status'] == "2"){
            $res = M('basic_idc')->where(array('id'=>$id))->delete();
          }else{
            $data['idc_status'] = "3";
            $res = M('basic_idc')->where(array('id'=>$id))->save($data);
          }
        }     
      }
      if($res){
        self::log('Web', '删除经营单位成功', 5);
        $this->ajaxReturn(array('status'=>'success'));
      }else{
          self::log('Web', '删除经营单位失败', 5);
          $this->ajaxReturn(array('status'=>'error'));
      }
    }
    //机房管理
    public function room(){
      if(I('get.n')==1){
          $this -> ip_info(I('get.house_id'));
      }elseif(I('get.n')==2){
          $this -> frame_info(I('get.house_id'));
      }elseif(I('get.n')==3){
          $this -> gateway_info(I('get.house_id'));
      }elseif(empty(I('get.n')) && $_GET){
        if(I('house_name')!=""){
          $house_name = I('house_name');
          $where['a.house_id'] = $house_name;
        }
        if(I('house_type')!=""){
          $house_type = I('house_type');
          $where['a.house_type'] = $house_type;
        }
        if(I('idc_name')!=""){
          $idc_name = I('idc_name');
          $where['basic_idc.idc_name'] = $idc_name;
        }
        if(I('province')!=""){
          $province =I('province');
          $where['a.province'] = M('city')->where(array('id'=>$province))->find()['gbt_num'];
        }
        if(I('city')!=""){
          $city = I('city');
          $where['a.city'] = M('city')->where(array('id'=>$city))->find()['gbt_num'];;
        }
        if(I('county')!=""){
          $county = I('county');
          $where['a.county'] = M('city')->where(array('id'=>$county))->find()['gbt_num'];;
        }
      }
      $where['a.status'] = array('neq','3');
      $where['basic_idc.idc_status'] =array('neq','3');
      $count = M('basic_house')->JOIN("as a left join basic_idc on basic_idc.idc_id = a.idc_id")->where($where)->count();// 查询满足要求的总记录数
      $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
      $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
      $result = M('basic_house')->where($where)->JOIN("as a left join basic_idc on basic_idc.idc_id = a.idc_id")
      ->field('a.*,basic_idc.idc_status,basic_idc.idc_name')
      ->limit($Page->firstRow . ',' . $Page->listRows)->select();
      //dump($result);die;
      foreach ($result as $key => $value) {
        $result[$key]['house_type'] = $this->info_value('house_type',$value['house_type']);
        $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
        $result[$key]['province'] = M('city')->where(array('gbt_num'=>$value['province']))->select()[0]['name'];
        $result[$key]['city'] = M('city')->where(array('gbt_num'=>$value['city']))->select()[0]['name'];
        $result[$key]['county'] = M('city')->where(array('gbt_num'=>$value['county']))->select()[0]['name'];# code...
        $result[$key]['house_officer_idtype'] = $this->info_value('id_type',$value['house_officer_idtype']);
      }
      $parent_id['parent_id'] = 1;
      $region = M('city')->where($parent_id)->select();
      $rooms = M('basic_house')->where(array('status'=>array('neq',3)))->select();
      $show = $Page->show();
      $this->assign('show', $show);
      $this->assign('house_name',$house_name);
      $this->assign('house_type',$house_type);
      $this->assign('idc_name',$idc_name);
      $this->assign('province',$province);
      $this->assign('city',$city);
      $this->assign('county',$county);
      $this->assign('rooms',$rooms);
      $this->assign('region',$region);
      $this->assign('res',$result);
      self::log('Web', '机房管理查询', 5);
      Layout('Layout/layout');
      $this->display();
    }
    public function ip_info($house_id){
      //if(I('house_id')!=''){
        //$house_id = I('house_id');
        $result = M('basic_ipseg')->where(array('house_id'=>$house_id,'status'=>array('neq',3)))->select();
        foreach ($result as $key => $value) {
          $result[$key]['type'] = $this->info_value('type',$value['type']);
          if($value['id_type'] != NULL){
            $result[$key]['id_type'] = $this->info_value('id_type',$value['id_type']); 
          }else{
            $result[$key]['id_type'] ='';
          }
          
          $result[$key]['usertime'] = date('Y-m-d',$value['usertime']);
        }
      //}
        $this->ajaxReturn(array('data'=>$result));
    }
    public function frame_info($house_id){
      //if(I('house_id')!=''){
        //$house_id = I('house_id');
        $result = M('basic_frame')->where(array('house_id'=>$house_id,'status'=>array('neq',3)))->select();
        foreach ($result as $key => $value) {
          $result[$key]['usertype'] = $this->info_value('usertype',$value['usertype']);
          $result[$key]['distribution'] = $this->info_value('distribution',$value['distribution']);
          $result[$key]['occupancy'] = $this->info_value('occupancy',$value['occupancy']);
          $result[$key]['is_special'] = $this->info_value('is_special',$value['is_special']);
        }
      //}
        $this->ajaxReturn(array('data'=>$result));
    }
    public function gateway_info($house_id){
      //if(I('house_id')!=''){
        //$house_id = I('house_id');
        $result = M('basic_gateway')->where(array('house_id'=>$house_id,'status'=>array('neq',3)))->select();
        foreach ($result as $key => $value) {
          $result[$key]['linktype'] = $this->info_value('linktype',$value['linktype']);
        }
      //}
        $this->ajaxReturn(array('data'=>$result));
    }
    public function room_add(){
      $parent_id['parent_id'] = 1;
      $region = M('city')->where($parent_id)->select();     
      if($_GET){
        $data = array_merge(I('get.data1'),I('get.data2'));
        
        foreach ($data as $key => $value) {
          if($value == ''){
            $data[$key] = NULL;
          }
        }
        $data['province'] = M('city')->where(array('id'=>$data['province']))->find()['gbt_num'];
        $data['city'] = M('city')->where(array('id'=>$data['city']))->find()['gbt_num'];
        $data['county'] = M('city')->where(array('id'=>$data['county']))->find()['gbt_num'];
        $house_id_check = M('basic_house')->where(array('house_id'=>$data['house_id'],'status'=>array('neq',3)))->select();
        $house_name_check = M('basic_house')->where(array('house_name'=>$data['house_name'],'status'=>array('neq',3)))->select();
        if($data['house_id'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房编号不能为空！'));
        }elseif(strlen($data['house_id'])>32){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房编号不能超过32位！'));
        }
        if($data['house_name'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房名称不能为空！'));
        }elseif(strlen($data['house_name']) >128){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房名称不能超过128位！'));
        }
        if($data['idc_id'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'经营单位不能为空！'));
        }
        if($data['province'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'省份不能为空！'));
        }elseif(strlen($data['province']) >6){
          $this->ajaxReturn(array('status'=>'error','info'=>'省份ID不能超过6位！'));
        }
        if($data['city'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'城市不能为空！'));
        }elseif(strlen($data['province']) >6){
          $this->ajaxReturn(array('status'=>'error','info'=>'城市ID不能超过6位！'));
        }
        if($data['house_addr'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房地址不能为空！'));
        }elseif(strlen($data['house_addr']) >128){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房地址不能超过128位！'));
        }
        if($data['house_zip'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'邮编不能为空！'));
        }elseif(strlen($data['house_zip']) != 6){
          $this->ajaxReturn(array('status'=>'error','info'=>'请填写6位邮编！'));
        }
        if($data['house_officer_name'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人姓名不能为空！'));
        }elseif(strlen($data['house_officer_name']) >32){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人姓名不能超过32位！'));

        }
        if($data['house_officer_idtype'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人证件类型不能为空！'));
        }
        if($data['house_officer_id'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人证件号不能为空！'));
        }elseif(strlen($data['house_officer_idty']) >32){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人证件号不能超过32位！'));

        }
        if($data['house_officer_tel'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人固定电话不能为空！'));
        }elseif(strlen($data['house_officer_tel']) > 32){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人固定电话不能超过32位！'));
        }
        if($data['house_officer_mobile'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人移动电话不能为空！'));
        }elseif(strlen($data['house_officer_mobile']) > 32){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人移动电话不能超过32位！'));
        }
        if($data['house_officer_email'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人邮箱地址不能为空！'));
        }elseif(strlen($data['house_officer_email']) > 64){
          $this->ajaxReturn(array('status'=>'error','info'=>'网络安全负责人邮箱地址不能超过64位！'));
        }
        if(empty(I('get.data3')) || empty(I('get.data4')) || empty(I('get.data5'))){
          $this->ajaxReturn(array('status'=>'error','info'=>'机架信息，IP段信息，链路信息不能为空！'));
        }
        
        if($house_id_check){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房编号已存在，请重新填写！'));
        }
        if($house_name_check){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房名称已存在，请重新填写！'));
        }
        $data['status'] = "1"; 
        //dump($data);die;
        $res =  M('basic_house')->add($data);
        foreach (I('get.data3') as $key => $value) {
          if($value['id_type'] == ''){
            $value['id_type'] = NULL;
          }
          
          $value['house_id'] = $data['house_id'];
          $value['usertime'] = strtotime($value['usertime']);
          $value['type']     = $this->info_value('type_value',$value['type']);
          $value['status']   = "1";
          $value['id_type']     = $this->info_value('id_type_value',$value['id_type']);
          M('basic_ipseg')->add($value);
          # code...
        }
        foreach (I('get.data4') as $key => $value) {
          foreach ($value as $k => $val) {
            if($val == ''){
              $value[$k] = NULL;
            }
          }
          $is_special = $value['is_special'];
          if($is_special == '否'){
            $value['is_special'] = 1;
            $value['house_id'] = $data['house_id'];
            $value['usertype'] = $this->info_value('usertype_value',$value['usertype']);
            $value['distribution'] = $this->info_value('distribution_value',$value['distribution']);
            $value['occupancy'] = $this->info_value('occupancy_value',$value['occupancy']);
            $value['status']   = "1";
          }elseif($is_special == '是'){
            $value['is_special'] = 2;
            $value['house_id'] = $data['house_id'];
            $value['frame_name'] = '0';
            $value['usertype'] = '0';
            $value['distribution'] = '0';
            $value['occupancy'] = '0';
            $value['status']   = "1";
          }
          
          M('basic_frame')->add($value);
          # code...
        }
        foreach (I('get.data5') as $key => $value) {
          foreach ($value as $k => $val) {
            if($val == ''){
              $value[$k] = NULL;
            }
          }
          $value['house_id'] = $data['house_id'];
          $value['linktype'] = $this->info_value('linktype_value',$value['linktype']);
          $value['status']   = "1";
          M('basic_gateway')->add($value);
          # code...
        }
        if($res){
          self::log('Web', '添加机房信息成功', 5);
          $this->ajaxReturn(array('status'=>'success'));
        }else{
          self::log('Web', '添加机房信息失败', 3);
          $this->ajaxReturn(array('status'=>'false'));
        }       
      }
      $basic_idc = M('basic_idc')->select();
      $this->assign('basic_idc',$basic_idc);
      $this->assign('region',$region);
      Layout('Layout/layout');
      $this->display();
    }
    /*public function room_edit(){
      if($_GET){
        $id = I('get.id');
        $where['id'] = $id;
        $where['status'] =array('neq',"3");
        $result = M('basic_house')->where($where)->select()[0];
        $ip_info = M('basic_ipseg')->where(array('house_id'=>$result['house_id'],'status'=>array('neq',"3")))->select();
        foreach ($ip_info as $key => $value) {
          $ip_info[$key]['usertime'] = date('Y-m-d',$value['usertime']);
          # code...
        }
        $frame = M('basic_frame')->where(array('house_id'=>$result['house_id'],'status'=>array('neq',"3")))->select();
        $gateway = M('basic_gateway')->where(array('house_id'=>$result['house_id'],'status'=>array('neq',"3")))->select();
      }
      $basic_idc = M('basic_idc')->select();
      $this->assign('basic_idc',$basic_idc);
      $this->assign('data',$result);
      $parent_id['parent_id'] = 1;
      $region = M('city')->where($parent_id)->select();
      $this->assign('id',$id);
      $this->assign('region',$region);
      $this->assign('ip_data',$ip_info);
      $this->assign('frame_data',$frame);
      $this->assign('gateway_data',$gateway);
      Layout('Layout/layout');
      $this->display();
    }*/
    public function room_edit(){
      if(!I('get.type')){
          $id = I('get.id');
          $where['id'] = $id;
          $where['status'] =array('neq',"3");
          $result = M('basic_house')->where($where)->select()[0];
          
          $result['province'] = M('city')->where(array('gbt_num'=>$result['province']))->find()['id'];
          $result['city'] = M('city')->where(array('gbt_num'=>$result['city']))->find()['id'];
          $result['county'] = M('city')->where(array('gbt_num'=>$result['county']))->find()['id'];
          $ip_info = M('basic_ipseg')->where(array('house_id'=>$result['house_id'],'status'=>array('neq',"3")))->select();
          foreach ($ip_info as $key => $value) {
            $ip_info[$key]['usertime'] = date('Y-m-d',$value['usertime']);
            # code...
          }
          $frame = M('basic_frame')->where(array('house_id'=>$result['house_id'],'status'=>array('neq',"3")))->select();
          
          $gateway = M('basic_gateway')->where(array('house_id'=>$result['house_id'],'status'=>array('neq',"3")))->select();
          $basic_idc = M('basic_idc')->select();
          $this->assign('basic_idc',$basic_idc);
          $this->assign('data',$result);
          $parent_id['parent_id'] = 1;
          $region = M('city')->where($parent_id)->select();
          $this->assign('id',$id);
          $this->assign('region',$region);
          $this->assign('ip_data',$ip_info);
          $this->assign('frame_data',$frame);
          $this->assign('gateway_data',$gateway);
          Layout('Layout/layout');
          $this->display();
      }else{
          $type = I('get.type');
          switch ($type) {
            case 'ip':
              $this -> room_edit_ip_del(I('get.id'));
              break;
            case 'frame':
              $this -> room_edit_frame_del(I('get.id'));
              break;
            case 'gateway':
              $this -> room_edit_gateway_del(I('get.id'));
              break;
            default:
              $this -> do_room_edit(I('get.data1'),I('get.data2'),I('get.data3'),I('get.data4'),I('get.data5'));
              break;
          }
       }   
    }
    public function do_room_edit($data1,$data2,$data3,$data4,$data5){
        $data = array_merge($data1,$data2);
        
        if($data['county'] == ''){
          $data['county'] = NULL;
        }
        $data['province'] = M('city')->where(array('id'=>$data['province']))->find()['gbt_num'];
        $data['city'] = M('city')->where(array('id'=>$data['city']))->find()['gbt_num'];
        $data['county'] = M('city')->where(array('id'=>$data['county']))->find()['gbt_num'];
        $result = M('basic_house')->where(array('id'=>$data['id']))->select()[0];
        if($result['status'] == "1"){
          $data['status'] = "1";
        }else{
          $data['status'] = $result['status'];
          if($data != $result){
            $data['status'] = "2";
          }
        }
        $house_id_check = M('basic_house')->where(array('house_id'=>$data['house_id'],'id'=>array('neq',$data['id']),'status'=>array('neq',3)))->select();
        $house_name_check = M('basic_house')->where(array('house_name'=>$data['house_name'],'id'=>array('neq',$data['id']),'status'=>array('neq',3)))->select();
        if($data['house_id'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房编号不能为空！'));
        }
        if($data['house_name'] == ''){
          $this->ajaxReturn(array('status'=>'error','info'=>'机房名称不能为空！'));
        }
        if($house_id_check){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房编号已存在，请重新填写！'));
        }
        if($house_name_check){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房名称已存在，请重新填写！'));
        }
        $res =  M('basic_house')->where(array('id'=>$data['id']))->save($data);
        foreach ($data3 as $key => $value) {
          if($value['id_type'] == ""){
            $value['id_type'] = NULL;
          }
          $value['house_id'] = $data['house_id'];
          $value['usertime'] = strtotime($value['usertime']);
          if($value['id']){           
            $ip_result = M('basic_ipseg')->where(array('id'=>$value['id']))->select();
            if($ip_result[0]['status'] == "1"){
              $value['status'] = "1";
            }else{
              $value['status'] = $ip_result[0]['status'];
              if($ip_result[0] !=$value){
                $value['status'] = "2";
              }             
            }         
            M('basic_ipseg')->where(array('id'=>$value['id']))->save($value);
          }else{
            $value['status'] = "1";
            M('basic_ipseg')->add($value);
          }          
        }
        foreach ($data4 as $key => $value) {
          $value['house_id'] = $data['house_id'];

          if($value['id']){
            $frame_result = M('basic_frame')->where(array('id'=>$value['id']))->select();
            $is_special = $value['is_special'];
            if($is_special == '2'){
              $value['house_id'] = $data['house_id'];
              $value['usertype'] = '0';
              $value['frame_name'] = '0';
              $value['distribution'] = '0';
              $value['occupancy'] = '0';

            }else{
              $value['is_special'] = 1;
            }
            if($frame_result[0]['status'] == "1"){
              $value['status'] = "1";
            }else{
              $value['status'] = $frame_result[0]['status'];
              if($frame_result[0] != $value){
                $value['status'] = "2";
              }
            }
            
            M('basic_frame')->where(array('id'=>$value['id']))->save($value);
          }else{
            $value['status'] = "1";
            $is_special = $value['is_special'];
            if($is_special == '2'){
              $value['house_id'] = $data['house_id'];
              $value['usertype'] = '0';
              $value['frame_name'] = '0';
              $value['distribution'] = '0';
              $value['occupancy'] = '0';

            }else{
              $value['is_special'] = 1;
            }
            M('basic_frame')->add($value);
          }
          
        }
        foreach ($data5 as $key => $value) {
          $value['house_id'] = $data['house_id'];
          if($value['id']){
            $gateway_result = M('basic_gateway')->where(array('id'=>$value['id']))->select();
            if($gateway_result[0]['status'] == "1"){
              $value['status'] = "1";
            }else{
              $value['status'] = $gateway_result[0]['status'];
              if($gateway_result[0] != $value){
                $value['status'] = "2";
              }          
            }
            M('basic_gateway')->where(array('id'=>$value['id']))->save($value);
          }else{
            $value['status'] = "1";
            M('basic_gateway')->add($value);
          }         
          # code...
        }
        self::log('Web', '编辑机房信息', 5);
        $this->ajaxReturn(array('status'=>'success'));             
    
    }
    public function room_edit_ip_del($id){

        $result = M('basic_ipseg')->where(array('id'=>$id))->select();
        $check_count = M('basic_ipseg')->where(array('house_id'=>$result[0]['house_id'],'status'=>array('neq',3)))->count();
        if($check_count==1){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房下现只有一个IP段，不允许删除！'));
        }
        if($result[0]['status'] == "1"){
          M('basic_ipseg') -> where(array('id'=>$id))->delete();
        }else{
          $data['status'] = "3";
          M('basic_ipseg') -> where(array('id'=>$id))->save($data);
        }
    }
    public function room_edit_frame_del($id){
        $result = M('basic_frame')->where(array('id'=>$id))->select();
        $check_count = M('basic_frame')->where(array('house_id'=>$result[0]['house_id'],'status'=>array('neq',3)))->count();
        if($check_count==1){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房下现只有一个机架信息，不允许删除！'));
        }
        if($result[0]['status'] == "1"){
          M('basic_frame')->where(array('id'=>$id))->delete();
        }else{
          $data['status'] = "3";
          M('basic_frame')->where(array('id'=>$id))->save($data);
        }
    }
    public function room_edit_gateway_del($id){
        $result = M('basic_gateway')->where(array('id'=>$id))->select();
        $check_count = M('basic_gateway')->where(array('house_id'=>$result[0]['house_id'],'status'=>array('neq',3)))->count();
        if($check_count==1){
          $this->ajaxReturn(array('status'=>'error','info'=>'该机房下现只有一个链路信息，不允许删除！'));
        }
        if($result[0]['status'] == "1"){
          M('basic_gateway')->where(array('id'=>$id))->delete();
        }else{
          $data['status'] = "3";
          M('basic_gateway')->where(array('id'=>$id))->save($data);
        }
    }
    public function room_del(){
      if($_GET){
        $id = I('get.id');
        $result = M('basic_house')->where(array('id'=>$id))->select();
        $user_ser_result = M('basic_ispuser_house')->where(array('house_id'=>$result[0]['house_id'],'status'=>array('neq',3)))->select();
        $user_oth_result = M('basic_otheruser_house')->where(array('house_id'=>$result[0]['house_id'],'status'=>array('neq',3)))->select();
        if(!empty($user_ser_result) or !empty($user_oth_result) ){
          $this->ajaxReturn(array('status'=>'warnning','info'=>'该机房已被用户占用，不允许删除！'));
        }
        $ip_result = M('basic_ipseg')->where(array('house_id'=>$result[0]['house_id']))->select();
        $frame_result = M('basic_frame')->where(array('house_id'=>$result[0]['house_id']))->select();
        $gateway_result = M('basic_gateway')->where(array('house_id'=>$result[0]['house_id']))->select();

        if($result[0]['status'] == "1"){
          M('basic_house')->where(array('id'=>$id))->delete();
        }else{
          $data['status'] = "3";
          M('basic_house')->where(array('id'=>$id))->save($data);
        }
        if($ip_result){
          foreach ($ip_result as $key => $value) {
            if($value['status'] == "1" ){
              M('basic_ipseg')->where(array('id'=>$value['id']))->delete();
            }else{
              $data1['status'] = "3";
              M('basic_ipseg')->where(array('id'=>$value['id']))->save($data1);
            }
          }
        }
        if($frame_result){
          foreach ($frame_result as $key => $value) {
            if($value['status'] == "1" ){
              M('basic_frame')->where(array('id'=>$value['id']))->delete();
            }else{
              $data1['status'] = "3";
              M('basic_frame')->where(array('id'=>$value['id']))->save($data1);
            }
          }
        }
        if($gateway_result){
          foreach ($gateway_result as $key => $value) {
            if($value['status'] == "1" ){
              M('basic_gateway')->where(array('id'=>$value['id']))->delete();
            }else{
              $data1['status'] = "3";
              M('basic_gateway')->where(array('id'=>$value['id']))->save($data1);
            }
          }
        }
      }
      self::log('Web', '删除机房信息成功', 5);              
      $this->ajaxReturn(array('status'=>'success'));
 
    }
   /* public function room_I_O(){
      Layout('Layout/layout');
      $this->display();
    }
    public function room_IP(){
      Layout('Layout/layout');
      $this->display();
    }*/

    //用户管理
    public function user(){
      if($_GET){
        if(I('get.nature')!=""){
          $nature = I('get.nature');
          $where['nature'] = $nature;
        }
        if(I('get.unitname') !=""){
          $unitname = I('get.unitname');
          $where['unitname'] = $unitname;
        }
        if(I('get.id_type') !=""){
          $id_type = I('get.id_type');
          $where['id_type'] = $id_type;
        }
        if(I('get.idnumber')!=""){
          $idnumber = I('get.idnumber');
          $where['idnumber'] = $idnumber;
        }
        if(I('get.officer_name')!=""){
          $officer_name =I('get.officer_name');
          $where['officer_name'] = $officer_name;
        }
      }
      $where['status'] = array('neq',"3");
      $count = M('basic_user')->where($where)->count();
      $Page  = new \Think\Page($count, 25);// 实例化分页类 传入总记录数
      $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
      $result = M('basic_user')->where($where)->select();
      foreach ($result as $key => $value) {
        $result[$key]['nature'] = $this->info_value('nature',$value['nature']);
        $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
        $result[$key]['id_type'] = $this->info_value('id_type',$value['id_type']);
        $result[$key]['officer_idtype'] = $this->info_value('id_type',$value['officer_idtype']);
        $result[$key]['unitnature'] = $this->info_value('unitnature',$value['unitnature']);
        # code...
      }
      $show = $Page->show();
      $this->assign('show', $show);
      $this->assign('nature',$nature);
      $this->assign('unitname',$unitname);
      $this->assign('id_type',$id_type);
      $this->assign('idnumber',$idnumber);
      $this->assign('officer_name',$officer_name);
      $this->assign('res',$result);
      self::log('Web', '基础信息用户信息查询', 5);
      Layout('Layout/layout');
      $this->display();
    }
    /*//用户添加
    public function user_add(){

      $basic_idc = M('basic_idc')->select();
      $rooms = M('basic_house')->select();
      $frame = M('basic_frame')->select();
      $this->assign('frame',$frame);
      $this->assign('rooms',$rooms);
      $this->assign('basic_idc',$basic_idc);
      Layout('Layout/layout');
      $this->display();
    }*/
    //互联网用户/其他用户添加
    public function user_add(){
        if(IS_POST){
            switch (I('post.type')) {
                case 1:
                    $this -> server_user_add(I('post.data1'),I('post.data2'),I('post.data3'));
                    break;
                case 2:
                    $this -> other_user_add(I('post.data1'),I('post.data2'),I('post.data3'));
                    break;
            }
        }else{
            if(I('get.house_id')){
                $where['house_id'] = I('get.house_id');
                $frame = M('basic_frame')->field('id,frame_name')->where($where)->select();
                $this -> ajaxReturn($frame);
            }else{
                $basic_idc = M('basic_idc')->select();
                $rooms = M('basic_house')->where(array('status'=>array('neq',3)))->select();
                $this->assign('rooms',$rooms);
                $this->assign('basic_idc',$basic_idc);
                Layout('Layout/layout');
                $this->display();
            }
        }
    }
     //互联网用户的添加
    public function server_user_add($data1,$data2,$data3){ 
        $data = array_merge($data1,$data2);
       
        $data['zip_code'] = $data['zip_code'];
        $data['status'] = 1;
        $data['register_time'] = strtotime($data['register_time']);
        $data['service_reg_time'] = 0;
        
        $res = M('basic_user')->add($data);
        if($res){
          $user_id = M('basic_user')->limit(0,1)->order('user_id desc')->select()[0]['user_id'];
          foreach ($data3 as $key => $value) {
            if($value['regtype'] ==''){
              $value['regtype'] = NULL;
            }
            $data4[$key]['user_id'] = $user_id;
            $data4[$key]['regtype'] = $value['regtype'];
            $data4[$key]['set_mode'] = $value['set_mode'];
            $data4[$key]['regid'] = $value['regid'];
            $data4[$key]['service_content'] = implode(',', $value['service_content']);
            $data4[$key]['status'] = "1";
            $data4[$key]['business'] = $value['business'];
            M('basic_service')->add($data4[$key]);
            $service_id = M('basic_service')->limit(0,1)->order('service_id desc')->select()[0]['service_id'];
            /*if($value['doman_name'] !=""){               
                foreach($value['doman_name'] as $kee=>$vall){
                  $data7['domain_name'] = $vall;
                  $data7['service_id'] = $service_id;
                  $data7['status'] = "1";
                  M('basic_domain')->add($data7);
                }
            }*/
            if(!empty($value['room_list'])){
              foreach ($value['room_list'] as $k => $val) {
                if($val['frame_info_id'] ==''){
                  $val['frame_info_id']=NULL;
                }
                if($val['band_width'] ==''){
                  $val['band_width']=NULL;
                }
                $data5[$k]['service_id'] = $service_id;
                $data5[$k]['house_id'] = $val['house_id'];
                $data5[$k]['band_width'] = empty($val['band_width'])?null:$val['band_width'];
                $data5[$k]['distribute_time'] = strtotime($val['distribute_time']);
                $data5[$k]['frame_info_id'] = $val['frame_info_id'];
                $data5[$k]['status'] = "1";
                
                $max_hhid = M('cu_hhid')->order('id desc')->limit(0,1)->select()[0]['hhid'];
                
                if($max_hhid){
                  $data5[$k]['hhid'] = $max_hhid+1;       
                }else{
                  $data5[$k]['hhid'] = 1;
                }
                $dat['hhid'] = $data5[$k]['hhid'];

                M('cu_hhid')->add($dat);
                M('basic_ispuser_house')->add($data5[$k]);
                if(!empty($val['Host'])){
                  foreach ($val['Host'] as $k1 => $v1) {
                    if($v1['virtual_host_state'] == ""){
                      $v1['virtual_host_state'] =NULL;
                    }
                    $data7[$k1]['hhid'] = $dat['hhid'];
                    $data7[$k1]['virtual_host_state'] = $v1['virtual_host_state'];
                    $data7[$k1]['virtual_host_addr'] = $v1['virtual_host_addr'];
                    $data7[$k1]['virtual_host_management_addr'] = $v1['virtual_host_management_addr'];
                    $data7[$k1]['virtual_host_name'] = $v1['virtual_host_name']; 
                    $data7[$k1]['virtual_host_type'] = $v1['virtual_host_type'];
                    $data7[$k1]['status'] = 1;
                    M('basic_virtualhost')->add($data7[$k1]);
                  }
                }
                if(!empty($val['IP'])){
                  foreach ($val['IP'] as $ke => $valu) {
                    if($valu['domain_name'] !=''){
                      $data7['domain_name'] = $valu['domain_name'];
                      $data7['service_id'] = $service_id;
                      $data7['status'] = "1";
                      M('basic_domain')->add($data7);
                      $data6[$ke]['domain_id'] = M('basic_domain')->order('domain_id desc')->limit(0,1)->find()['domain_id'];
                    }
                    
                    $data6[$ke]['hhid'] = $dat['hhid'];
                    $data6[$ke]['internet_start_ip'] = $valu['internet_start_ip'];
                    $data6[$ke]['internet_end_ip'] = $valu['internet_end_ip'];
                    $data6[$ke]['net_start_ip'] = $valu['net_start_ip'];
                    $data6[$ke]['net_end_ip'] = $valu['net_end_ip']; 
                    $data6[$ke]['status'] = "1";
                    //dump($data6);die;
                    M('basic_iptrans')->add($data6[$ke]);
                    
                  }
                }
              }
            }
          }
        }
        self::log('Web', '添加提供互联网用户', 5);
        $this->ajaxReturn(array('status'=>'success'));
      
    }
    //其他用户添加
    public function other_user_add($data1,$data2,$data3){
        $data = array_merge($data1,$data2);
        $data['status'] = "1";
        $data['register_time'] = strtotime($data['register_time']);
        $data['service_reg_time'] =$data['register_time'];
        $res = M('basic_user')->add($data);
        if($res){
          $user_id = M('basic_user')->limit(0,1)->order('user_id desc')->select()[0]['user_id'];
          foreach ($data3 as $key => $value) {
            if($value['band_width'] == ''){
              $value['band_width'] =NULL;
            }
            $data4[$key]['user_id'] = $user_id;
            $data4[$key]['house_id'] = $value['house_id'];
            $data4[$key]['band_width'] = empty($value['band_width'])?null:$value['band_width'];
            $data4[$key]['distribute_time'] = strtotime($value['distribute_time']);
            $data4[$key]['status'] = "1";
            $max_hhid = M('cu_hhid')->order('id desc')->limit(0,1)->select()[0]['hhid'];
            if($max_hhid){
              $data4[$key]['hhid'] = $max_hhid+1;       
            }else{
              $data4[$key]['hhid'] = 1;
            }
            $dat['hhid'] = $data4[$key]['hhid'];
            M('cu_hhid')->add($dat);
            M('basic_otheruser_house')->add($data4[$key]);
            if(!empty($value['Ip'])){
              foreach ($value['Ip'] as $ke => $valu) {
                if($valu['is_special'] == '2'){
                  $data5[$ke]['start_ip'] = $valu['startip']."(专线)";
                  $data5[$ke]['end_ip'] = $valu['endip']."(专线)";
                }elseif($valu['is_special'] == '1'){
                  $data5[$ke]['start_ip'] = $valu['startip'];
                  $data5[$ke]['end_ip'] = $valu['endip'];
                }

                $data5[$ke]['is_special'] = $valu['is_special'];
                $data5[$ke]['hhid'] = $data4[$key]['hhid'];
                $data5[$ke]['status'] = "1";
                M('basic_otheruser_ipseg')->add($data5[$ke]);
              }
            }   
          }
        }
        self::log('Web', '添加其他用户', 5);
        $this->ajaxReturn(array('status'=>'success'));
    }
    public function user_edit(){
      //dump($_POST);die;
        if(IS_POST){
            switch (I('post.type')) {
                case 12:
                  $this -> do_user_edit_server(I('post.data1'),I('post.data2'),I('post.data3'));
                  break;
                case 13:
                  $this -> server_edit_room_del((int)I('post.id'));
                  break;
                case 14:
                  $this -> server_edit_service_del((int)I('post.id'));
                  break;
                case 15:
                  $this -> server_edit_domain_del((int)I('post.id'));
                  break;
                case 16:
                  $this -> server_edit_ip_del((int)I('post.id'));
                  break;
                case 17:
                  $this -> server_edit_host_del((int)I('post.id'));
                  break;
                case 22:
                  $this -> do_user_edit_other(I('post.data1'),I('post.data2'),I('post.data3'));
                  break;
                case 23:
                  $this -> other_edit_basic_del((int)I('post.id'));
                  break;
                case 24:
                  $this -> other_edit_ip_del((int)I('post.id'));
                  break;
            }
        }else{
            switch (I('get.type')) {
              case 11:
                $this -> user_edit_server((int)I('get.id'));
                break;
              case 21:
                $this -> user_edit_other((int)I('get.id'));
                break;
              case 31:
                $where['house_id'] = (int)I('get.house_id');
                $frame = M('basic_frame')->field('id,frame_name')->where($where)->select();
                $this -> ajaxReturn($frame);
                break;
            }
        }
    }
    //提供互联网用户编辑展示
    public function user_edit_server($id){
        $where['user_id']=$id;
        $result = M('basic_user')->where($where)->select()[0];
        $server = M('basic_service')->where(array('user_id'=>$id,'status'=>array('neq',"3")))->select();
        foreach ($server as $key => $value) {
          $server[$key]['service_content'] = explode(',', $value['service_content']);
          
          $server[$key]['title'] = "应用服务信息".($key+1);
         /* $domain = M('basic_domain')->where(array('service_id'=>$value['service_id'],'status'=>array('neq',"3")))->select();*/
          $roomlist = M('basic_ispuser_house')->where(array('service_id'=>$value['service_id'],'status'=>array('neq',"3")))->select();
          //$server[$key]['domain'] = $domain;
          $server[$key]['roomlist'] = $roomlist;
          foreach ($server[$key]['roomlist'] as $k => $val) {
            $server[$key]['roomlist'][$k]['room_title'] = "占用机房列表-".($k+1);
            $server[$key]['roomlist'][$k]['datep'] = "datetimepicker".($k+1);
            $ip = M('basic_iptrans')->where(array('hhid'=>$val['hhid']))->select();
            foreach ($ip as $ki => $vali) {
              $domain = M('basic_domain')->where(array('domain_id'=>$vali['domain_id']))->find()['domain_name'];
              $ip[$ki]['domain_name'] =$domain;
            }
            $server[$key]['roomlist'][$k]['ip'] = $ip; 
            $host = M('basic_virtualhost')->where(array('hhid'=>$val['hhid']))->select();
            $server[$key]['roomlist'][$k]['host'] = $host;            
          }
        }
        $basic_idc = M('basic_idc')->select();
        $rooms = M('basic_house')->where(array('status'=>array('neq',3)))->select();
        $this->assign('rooms',$rooms);
        //$frame = M('basic_frame')->select();
        //$this->assign('frame',$frame);
        $this->assign('basic_idc',$basic_idc);
        $this->assign('data',$result);
        $this->assign('server',$server);
        //self::log('Web', '编辑互联网用户', 5);
        Layout('Layout/layout');
        $this->display('user_edit_server');
    }
    //提供互联网用户编辑的时候服务信息删除
    public function server_edit_service_del($id){
        $result = M('basic_service')->where(array('service_id'=>$id))->select()[0];
        if($result['status'] == "1"){
            M('basic_service')->where(array('service_id'=>$id))->delete();
        }elseif($result['status'] == "0" || $result['status'] == "2"){
            $data['status'] = "3";
            M('basic_service')->where(array('service_id'=>$id))->save($data);
        }
    }
    //提供互联网用户编辑的时候占用机房信息删除
    public function server_edit_room_del($id){
        $result = M('basic_ispuser_house')->where(array('hhid'=>$id))->select()[0];
        if($result['status'] == "1"){
            M('basic_ispuser_house')->where(array('hhid'=>$id))->delete();
        }elseif($result['status'] == "0"  || $result['status'] == "2"){
            $data['status'] = "3";
            M('basic_ispuser_house')->where(array('hhid'=>$id))->save($data);
        }
    }
    //提供互联网用户编辑的时候IP信息删除
    public function server_edit_ip_del($id){
        $result = M('basic_iptrans')->where(array('id'=>$id))->select()[0];
        $domain = M('basic_domain')->where(array('domain_id'=>$result['domain_id']))->select()[0];
        if($result['status'] == "1" ){
          if($domain['status'] == '1'){
            M('basic_domain')->where(array('domain_id'=>$domain['domain_id']))->delete();
          }elseif($domain['status'] == "0"  || $domain['status'] == "2"){
            $data['status'] = "3";
            M('basic_domain')->where(array('domain_id'=>$domain['domain_id']))->save($data);
          }
          M('basic_iptrans')->where(array('id'=>$id))->delete();
          
        }elseif($result['status'] == "0"  || $result['status'] == "2"){
          if($domain['status'] == '1'){
            M('basic_domain')->where(array('domain_id'=>$domain['domain_id']))->delete();
          }elseif($domain['status'] == "0"  || $domain['status'] == "2"){
            $data['status'] = "3";
            M('basic_domain')->where(array('domain_id'=>$domain['domain_id']))->save($data);
          }
          $data['status'] = "3";
          M('basic_iptrans')->where(array('id'=>$id))->save($data);
          
          
        }
    }
    //提供互联网用户编辑的时候虚拟机信息删除
    public function server_edit_host_del($id){
        $result = M('basic_virtualhost')->where(array('id'=>$id))->select()[0];
        if($result['status'] == "1" ){
          M('basic_virtualhost')->where(array('id'=>$id))->delete();
        }elseif($result['status'] == "0"  || $result['status'] == "2"){
          $data['status'] = "3";
          M('basic_virtualhost')->where(array('id'=>$id))->save($data);
        }
    }
    //提供互联网用户编辑的时候域名信息删除
    public function server_edit_domain_del($id){
        $result = M('basic_domain')->where(array('domain_id'=>$id))->select()[0];
        if($result['status'] == "1"){
          M('basic_domain')->where(array('domain_id'=>$id))->delete();
        }elseif($result['status'] == "0" || $result['status'] == "2"){
          $data['status'] = "3";
          M('basic_domain')->where(array('domain_id'=>$id))->save($data);
        }
    }
    
    //提供互联网用户编辑
    public function do_user_edit_server($data1,$data2,$data3){
      //dump($data1);dump($data2);dump($data3);die;
        $data = array_merge($data1,$data2);
        
        $data['register_time'] = strtotime($data['register_time']);
        $data['service_reg_time'] = '0';
        $where['user_id'] =$data['user_id'];
        $where['status'] = array('neq',"3");
        $result = M('basic_user')->where($where)->select()[0];
        if($result['status'] == "1"){
          $data['status'] = "1";
        }elseif($result['status'] == "2" || $result['status'] == "0"){          
          $data['status'] = $result['status'];
          if($result != $data){
            $data['status'] = "2";
          }
        }
        M('basic_user')->where($where)->save($data);
        if(!empty($data3)){
          foreach ($data3 as $key => $value) {
            if($value['regtype'] == ''){
              $value['regtype'] = NULL;
            }
            if($value['service_id'] !== ""){
              $server[$key] = M('basic_service')->where(array('service_id'=>$value['service_id']))->select()[0];
              $data4[$key]['service_id'] = $value['service_id'];
              $data4[$key]['service_content'] = implode(',', $value['service_content']);
              $data4[$key]['regtype'] = $value['regtype'];
              $data4[$key]['set_mode'] = $value['set_mode'];
              $data4[$key]['regid'] = $value['regid'];
              $data4[$key]['user_id'] = $data['user_id'];
              $data4[$key]['business'] = $value['business'];
              if($server[$key]['status'] == "1"){
                $data4[$key]['status'] = "1";
              }elseif($server[$key]['status']=="2" || $server[$key]['status']=="0"){
                $data4[$key]['status'] = $server[$key]['status'];
                
                if($server[$key] != $data4[$key]){
                  $data4[$key]['status'] = "2";
                }
              }
              M('basic_service')->where(array('service_id'=>$value['service_id']))->save($data4[$key]);
              /*if(!empty($value['doman_name'])){
                foreach($value['doman_name'] as $kee=>$vall){
                  if($vall['domain_id'] !==""){
                    $domain[$kee] = M('basic_domain')->where(array('domain_id'=>$vall['domain_id']))->select()[0];
                    if($domain[$kee]['status'] == "1"){
                      $data8[$kee]['status'] = "1";
                    }elseif($domain[$kee]['status'] == "2" || $domain[$kee]['status'] == "0"){
                      $data8[$kee]['status'] = "2";
                    }
                    $data8[$kee]['domain_name'] = $vall['domain_name'];
                    $data8[$kee]['service_id'] = $server[$key]['service_id'];
                    M('basic_domain')->where(array('domain_id'=>$vall['domain_id']))->save($data8[$kee]);
                  }else{
                    $data8[$kee]['domain_name'] = $vall['domain_name'];
                    $data8[$kee]['service_id'] = $server[$key]['service_id'];
                    $data8[$kee]['status'] = "1";
                    M('basic_domain')->add($data8[$kee]);
                  }             
                }
              }*/
              if(!empty($value['room_list'])){
                foreach ($value['room_list'] as $ke => $valu) {
                  if($valu['band_width'] ==''){
                    $valu['band_width'] =NULL;
                  }
                  if($valu['frame_info_id'] ==''){
                    $valu['frame_info_id'] =NULL;
                  }
                  if($valu['id']!=""){
                    $house[$ke] = M('basic_ispuser_house')->where(array('id'=>$valu['id']))->select()[0];
                    $data5[$ke]['id'] = $valu['id'];
                    $data5[$ke]['service_id'] = $server[$key]['service_id'];
                    $data5[$ke]['house_id'] = $valu['house_id'];
                    $data5[$ke]['distribute_time'] = strtotime($valu['distribute_time']);
                    $data5[$ke]['band_width'] =empty($valu['band_width'])?null:$valu['band_width'];
                    $data5[$ke]['frame_info_id'] = $valu['frame_info_id'];
                    $data5[$ke]['hhid'] = $house[$ke]['hhid'];
                    if($house[$ke]['status'] == "1"){
                      $data5[$ke]['status'] = "1";
                    }elseif($house[$ke]['status']=="2" || $house[$ke]['status'] == "0"){
                      $data5[$ke]['status'] = $house[$ke]['status'];
                      if($data5[$ke] != $house[$ke]){
                        $data5[$ke]['status'] = "2";
                      }
                    }
                   
                    M('basic_ispuser_house')->where(array('id'=>$valu['id']))->save($data5[$ke]);
                    if(!empty($valu['Host'])){
                      foreach ($valu['Host'] as $k1 => $v1) {
                        if($v1['virtual_host_state'] == ''){
                          $v1['virtual_host_state'] = NULL;
                        }
                        if($v1['id'] !=""){
                          $host[$k1] = M('basic_virtualhost')->where(array('id'=>$v1['id']))->select()[0];
                          $data7[$k1]['virtual_host_state'] = $v1['virtual_host_state'];
                          $data7[$k1]['virtual_host_addr'] = $v1['virtual_host_addr'];
                          $data7[$k1]['virtual_host_management_addr'] = $v1['virtual_host_management_addr'];
                          $data7[$k1]['virtual_host_name'] = $v1['virtual_host_name'];
                          $data7[$k1]['virtual_host_type'] = $v1['virtual_host_type'];
                          $data7[$k1]['hhid'] = $host[$k1]['hhid'];
                          $data7[$k1]['id'] = $v1['id'];
                          if($host[$k1]['status'] == "1"){
                            $data7[$k1]['status'] = "1";
                          }elseif($host[$k1]['status'] == "2" || $host[$k1]['status'] == "0"){
                            $data7[$k1]['status'] = $host[$k1]['status'];
                            if($host[$k1] != $data7[$k1]){
                              $data7[$k1]['status'] = "2";
                            }
                          }
                          
                          M('basic_virtualhost')->where(array('id'=>$v1['id']))->save($data7[$k1]);
                        }else{
                          $data7[$k1]['virtual_host_state'] = $v1['virtual_host_state'];
                          $data7[$k1]['virtual_host_addr'] = $v1['virtual_host_addr'];
                          $data7[$k1]['virtual_host_management_addr'] = $v1['virtual_host_management_addr'];
                          $data7[$k1]['virtual_host_name'] = $v1['virtual_host_name'];
                          $data7[$k1]['virtual_host_type'] = $v1['virtual_host_type'];
                          $data7[$k1]['hhid'] = $data5[$ke]['hhid'];
                          $data7[$k1]['status'] = '1';
                          unset($data7[$k1]['id']);
                          M('basic_virtualhost')->add($data7[$k1]);
                        }                        
                      }
                    }
                    if(!empty($valu['IP'])){
                      foreach ($valu['IP'] as $k => $val) {

                        if($val['id'] !=""){
                          $ip[$k] = M('basic_iptrans')->where(array('id'=>$val['id']))->select()[0];
                          $data6[$k]['internet_start_ip'] = $val['start_ip'];
                          $data6[$k]['internet_end_ip'] = $val['end_ip'];
                          $data6[$k]['net_start_ip'] = $val['net_start_ip'];
                          $data6[$k]['net_end_ip'] = $val['net_end_ip'];
                          $data6[$k]['hhid'] = $ip[$k]['hhid'];
                          $data6[$k]['id'] = $val['id'];
                          
                          
                          if($val['domain_id'] !='' && $val['domain_name'] !=''){
                            $data6[$k]['domain_id'] = $val['domain_id'];
                            $s[$k]['domain_name'] = $val['domain_name'];
                            $dd_res = M('basic_domain')->where(array('domain_id'=>$val['domain_id']))->select();
                            if($dd_res[0]['domain_name'] !=$val['domain_name']){
                              if($dd_res[0]['status'] == '0' || $dd_res[0]['status'] == '2'){
                                $s[$k]['status'] = '2';
                              }elseif($dd_res[0]['status'] == '1' || $dd_res[0]['status'] == '3'){
                                $s[$k]['status'] = '1';
                              }
                            }
                            if($ip[$k]['status'] == "1"){
                              $data6[$k]['status'] = "1";
                            }elseif($ip[$k]['status'] == "2" || $ip[$k]['status'] == "0"){
                              $data6[$k]['status'] = $ip[$k]['status'];
                              if($ip[$k] != $data6[$k]){
                                $data6[$k]['status'] = "2";
                              }
                            }
                            
                            M('basic_domain')->where(array('domain_id'=>$val['domain_id']))->save($s[$k]);
                          }elseif($val['domain_id'] =='' && $val['domain_name'] !=''){
                            $data8[$k]['domain_name'] = $val['domain_name'];
                            $data8[$k]['service_id'] = $server[$key]['service_id'];
                            $data8[$k]['status'] = "1";
                            M('basic_domain')->add($data8[$k]);
                            $data6[$k]['domain_id'] = M('basic_domain')->order('domain_id desc')->limit(0,1)->find()['domain_id'];
                          }elseif($val['domain_id'] !='' && $val['domain_name'] ==''){
                            $domain[$k] = M('basic_domain')->where(array('domain_id'=>$val['domain_id']))->find();
                            $dd['domain_id'] = NULL;
                            M('basic_iptrans')->where(array('domain_id'=>$val['domain_id']))->save($dd);
                            if($domain[$k]['status'] =="1"){
                              M('basic_domain')->where(array('domain_id'=>$val['domain_id']))->delete();
                            }elseif($domain[$k]['status'] =="3" || $domain[$k]['status'] == '0'|| $domain[$k]['status'] == '2'){
                              $s['status'] = '3';
                              M('basic_domain')->where(array('domain_id'=>$val['domain_id']))->save($s);
                            }
                          }
                          M('basic_iptrans')->where(array('id'=>$val['id']))->save($data6[$k]);
                        }else{
                          $data6[$k]['status'] = "1";
                          $data6[$k]['internet_start_ip'] = $val['start_ip'];
                          $data6[$k]['internet_end_ip'] = $val['end_ip'];
                          $data6[$k]['net_start_ip'] = $val['net_start_ip'];
                          $data6[$k]['net_end_ip'] = $val['net_end_ip'];
                          $data6[$k]['hhid'] = $data5[$ke]['hhid'];
                          unset($data6[$k]['id']);
                          if($val['domain_name']!=''){
                            $data8[$k]['domain_name'] = $val['domain_name'];
                            $data8[$k]['service_id'] = $server[$key]['service_id'];
                            $data8[$k]['status'] = "1";
                            M('basic_domain')->add($data8[$k]);
                            $data6[$k]['domain_id'] = M('basic_domain')->order('domain_id desc')->limit(0,1)->find()['domain_id'];
                          }else{
                            $data6[$k]['domain_id'] = NULL;
                          }
                          
                          M('basic_iptrans')->add($data6[$k]);
                        }                        
                      }
                    }
                  }else{
                    
                    $data5[$ke]['status'] = "1";
                    $data5[$ke]['service_id'] = $server[$key]['service_id'];
                    $data5[$ke]['house_id'] = $valu['house_id'];
                    $data5[$ke]['band_width'] = empty($valu['band_width'])?null:$valu['band_width'];
                    $data5[$ke]['distribute_time'] = strtotime($valu['distribute_time']);
                    $data5[$ke]['frame_info_id'] = $valu['frame_info_id'];
                    unset($data5[$ke]['id']);
                    $max_hhid = M('cu_hhid')->order('id desc')->limit(0,1)->select()[0]['hhid'];
                    if($max_hhid){
                      $data5[$ke]['hhid'] = $max_hhid+1;       
                    }else{
                      $data5[$ke]['hhid'] = 1;
                    }
                    $dat[$ke]['hhid'] = $data5[$ke]['hhid'];
                    M('cu_hhid')->add($dat[$ke]);
                    M('basic_ispuser_house')->add($data5[$ke]);
                    if(!empty($valu['Host'])){
                      foreach ($valu['Host'] as $k1 => $v1) { 
                        if($v1['virtual_host_state'] == ''){
                          $v1['virtual_host_state'] = NULL;
                        }
                        $data7[$k1]['virtual_host_state'] = $v1['virtual_host_state'];
                        $data7[$k1]['virtual_host_addr'] = $v1['virtual_host_addr'];
                        $data7[$k1]['virtual_host_management_addr'] = $v1['virtual_host_management_addr'];
                        $data7[$k1]['virtual_host_name'] = $v1['virtual_host_name'];
                        $data7[$k1]['virtual_host_type'] = $v1['virtual_host_type'];
                        $data7[$k1]['hhid'] = $data5[$ke]['hhid'];
                        $data7[$k1]['status'] = '1';
                        unset($data7[$k1]['id']);
                        M('basic_virtualhost')->add($data7[$k1]);                       
                      }
                    }
                    if(!empty($valu['IP'])){
                      foreach ($valu['IP'] as $k => $val) {                       
                        $data6[$k]['status'] = "1";
                        $data6[$k]['internet_start_ip'] = $val['start_ip'];
                        $data6[$k]['internet_end_ip'] = $val['end_ip'];
                        $data6[$k]['net_start_ip'] = $val['net_start_ip'];
                        $data6[$k]['net_end_ip'] = $val['net_end_ip'];
                        $data6[$k]['hhid'] = $data5[$ke]['hhid'];

                        unset($data6[$k]['id']);
                        if($val['domain_name']!=''){
                            $data8[$k]['domain_name'] = $val['domain_name'];
                            $data8[$k]['service_id'] = $server[$key]['service_id'];
                            $data8[$k]['status'] = "1";
                            M('basic_domain')->add($data8[$k]);
                            $data6[$k]['domain_id'] = M('basic_domain')->order('domain_id desc')->limit(0,1)->find()['domain_id'];
                          }else{
                            $data6[$k]['domain_id'] = NULL;
                          }
                        M('basic_iptrans')->add($data6[$k]);                       
                      }
                    }
                  }
                }
              }
            }else{
              $data4[$key]['status'] = "1";
              $data4[$key]['service_content'] = implode(',',$value['service_content']);
              $data4[$key]['regtype'] = $value['regtype'];
              $data4[$key]['set_mode'] = $value['set_mode'];
              $data4[$key]['regid'] = $value['regid'];
              $data4[$key]['user_id'] = $data['user_id'];
              $data4[$key]['business'] = $value['business'];
              unset($data4[$key]['id']);
              M('basic_service')->add($data4[$key]);
              $service_id = M('basic_service')->limit(0,1)->order('service_id desc')->select()[0]['service_id'];
              /*if(!empty($value['doman_name'])){
                foreach($value['doman_name'] as $kee=>$vall){
                    $data8[$kee]['domain_name'] = $vall['domain_name'];
                    $data8[$kee]['service_id']  = $service_id;
                    $data8[$kee]['status'] = "1";
                    M('basic_domain')->add($data8[$kee]);
                }
              }*/
              if(!empty($value['room_list'])){
                foreach ($value['room_list'] as $ke => $valu) {
                  $data5[$ke]['status'] = "1";
                  $data5[$ke]['service_id'] = $service_id;
                  $data5[$ke]['house_id'] = $valu['house_id'];
                  $data5[$ke]['band_width'] = empty($valu['band_width'])?null:$valu['band_width'];
                  $data5[$ke]['distribute_time'] = strtotime($valu['distribute_time']);
                  $data5[$ke]['frame_info_id'] = $valu['frame_info_id'];
                  unset($data5[$ke]['id']);
                  $max_hhid = M('cu_hhid')->order('id desc')->limit(0,1)->select()[0]['hhid'];
                  if($max_hhid){
                    $data5[$ke]['hhid'] = $max_hhid+1;       
                  }else{
                    $data5[$ke]['hhid'] = 1;
                  }
                  $dat[$ke]['hhid'] = $data5[$ke]['hhid'];
                  
                  M('cu_hhid')->add($dat[$ke]);
                  M('basic_ispuser_house')->add($data5[$ke]);
                  if(!empty($valu['Host'])){
                    foreach ($valu['Host'] as $k1 => $v1) {    
                                       
                      $data7[$k1]['virtual_host_state'] = $v1['virtual_host_state'];
                      $data7[$k1]['virtual_host_state'] = $v1['virtual_host_state'];
                      $data7[$k1]['virtual_host_management_addr'] = $v1['virtual_host_management_addr'];
                      $data7[$k1]['virtual_host_name'] = $v1['virtual_host_name'];
                      $data7[$k1]['virtual_host_type'] = $v1['virtual_host_type'];
                      $data7[$k1]['hhid'] = $data5[$ke]['hhid'];
                      $data7[$k1]['status'] ='1';
                      unset($data7[$k1]['id']);
                      M('basic_virtualhost')->add($data7[$k1]);   
                    }
                  }
                  if(!empty($valu['IP'])){
                    foreach ($valu['IP'] as $k => $val) {                       
                      $data6[$k]['status'] = "1";
                      $data6[$k]['internet_start_ip'] = $val['start_ip'];
                      $data6[$k]['internet_end_ip'] = $val['end_ip'];
                      $data6[$k]['net_start_ip'] = $val['net_start_ip'];
                      $data6[$k]['net_end_ip'] = $val['net_end_ip'];
                      $data6[$k]['hhid'] = $data5[$ke]['hhid'];
                      unset($data6[$k]['id']);
                      if($val['domain_name']!=''){
                        $data8[$k]['domain_name'] = $val['domain_name'];
                        $data8[$k]['service_id'] = $service_id;
                        $data8[$k]['status'] = "1";
                        M('basic_domain')->add($data8[$k]);
                        $data6[$k]['domain_id'] = M('basic_domain')->order('domain_id desc')->limit(0,1)->find()['domain_id'];
                      }else{
                            $data6[$k]['domain_id'] = NULL;
                          }
                      M('basic_iptrans')->add($data6[$k]);   
                    }
                  }
                }
              }
            }
          }
        }
        self::log('Web', '编辑提供互联网用户成功', 5);
        $this->ajaxReturn(array('status'=>'success'));
    }
    //其他用户的编辑展示
    public function user_edit_other($id){
        $where['user_id']=$id;
        $where['status'] = array('neq',"3");
        $result = M('basic_user')->where($where)->select()[0];
        $basic_other = M('basic_otheruser_house')->where($where)->select();
        foreach ($basic_other as $key => $value) {
          $basic_other[$key]['title'] = "占用机房-".($key+1);
          $ip = M('basic_otheruser_ipseg')->where(array('hhid'=>$value['hhid']))->select();
          foreach ($ip as $ke => $valu) {
            $ip[$ke]['start_ip'] = str_replace('(专线)', '', $valu['start_ip']);
            $ip[$ke]['end_ip'] = str_replace('(专线)', '', $valu['end_ip']);
          }
          $basic_other[$key]['ip'] = $ip;
        }
        $basic_idc = M('basic_idc')->select();
        $rooms = M('basic_house')->where(array('status'=>array('neq',3)))->select();
        //dump($basic_other);die;
        $this->assign('rooms',$rooms);
        $this->assign('basic',$basic_other);
        $this->assign('basic_idc',$basic_idc);
        $this->assign('data',$result);
        self::log('Web', '编辑其他用户', 5);
        Layout('Layout/layout');
        $this->display('user_edit_other');
    }
    //其他用户编辑的占用机房信息的删除
    public function other_edit_basic_del($id){
        $result = M('basic_otheruser_house')->where(array('id'=>$id))->select()[0];
        if($result['status'] == "1" || $result['status'] == "2"){
          M('basic_otheruser_house')->where(array('id'=>$id))->delete();
        }elseif($result['status'] == "0"){
          $data['status'] = "3";
          M('basic_otheruser_house')->where(array('id'=>$id))->save($data);
        }
    }
    //其他用户编辑的占用IP信息的删除
    public function other_edit_ip_del($id){
        $result = M('basic_otheruser_ipseg')->where(array('ip_id'=>$id))->select()[0];
        if($result['status'] == "1" || $result['status'] == "2"){
          M('basic_otheruser_ipseg')->where(array('ip_id'=>$id))->delete();
        }elseif($result['status'] == "0"){
          $data['status'] = "3";
          M('basic_otheruser_ipseg')->where(array('ip_id'=>$id))->save($data);
        }
    }
    //其他用户编辑
    public function do_user_edit_other($data1,$data2,$data3){
        $data = array_merge($data1,$data2);

        $data['register_time'] = strtotime($data['register_time']);
        $where['user_id'] =$data['user_id'];
        $where['status'] = array('neq',"3");

        $result = M('basic_user')->where($where)->select()[0];

        $data['service_reg_time'] = $result['service_reg_time'];
        if($result['status'] == "1"){
          $data['status'] = 1;
        }if($result['status'] == "2" || $result['status'] == "0"){
          
          $data['status'] = $result['status'];
          if($result != $data){
            $data['status'] = "2";
          }
        }
        $res = M('basic_user')->where($where)->save($data);
        
        if(!empty($data3)){
          //dump($data3);die;
          foreach ($data3 as $key => $value) {
            if($value['id'] != ""){
              $house[$key] = M('basic_otheruser_house')->where(array('id'=>$value['id']))->select()[0];
              $data4[$key]['id'] = $value['id'];
              $data4[$key]['user_id'] = $house[$key]['user_id'];
              $data4[$key]['hhid'] = $house[$key]['hhid'];
              $data4[$key]['house_id'] = $value['house_id'];
              $data4[$key]['band_width'] = empty($value['band_width'])?null:$value['band_width'];
              $data4[$key]['distribute_time'] = strtotime($value['distribute_time']);

              if($house[$key]['status'] == "1"){
                $data4[$key]['status'] = "1";             
              }elseif($house[$key]['status'] == '2' || $house[$key]['status'] == '0'){
                $data4[$key]['status'] = $house[$key]['status'];
                if($data4[$key] != $house[$key]){
                  $data4[$key]['status'] = "2";
                }             
              }
              
              M('basic_otheruser_house')->where(array('id'=>$value['id']))->save($data4[$key]);
              if(!empty($value['Ip'])){

                //dump($value['Ip']);die;
                foreach ($value['Ip'] as $ke => $valu) {
                  
                  if($valu['ip_id'] != ""){
                    $ip[$ke] = M('basic_otheruser_ipseg')->where(array('ip_id'=>$valu['ip_id']))->select()[0];
                    if($valu['is_special'] == '2'){
                      $data5[$ke]['start_ip'] = $valu['startip']."(专线)";
                      $data5[$ke]['end_ip'] = $valu['endip']."(专线)";
                    }else{
                      $data5[$ke]['start_ip'] = $valu['startip'];
                      $data5[$ke]['end_ip'] = $valu['endip'];
                    }
                    $data5[$ke]['is_special'] =$valu['is_special'];
                    $data5[$ke]['hhid'] = $ip[$ke]['hhid'];
                    $data5[$ke]['ip_id'] = $valu['ip_id'];
                    if($ip[$ke]['status'] == "1"){
                      $data5[$ke]['status'] = "1";
                    }elseif($ip[$ke]['status'] == "2" || $ip[$ke]['status'] == "0"){
                      $data5[$ke]['status'] = $ip[$ke]['status'];
                      if($ip[$ke] != $data5[$ke]){
                        $data5[$ke]['status'] = "2";
                      }                    
                    }
                    unset($data5[$ke]['ip_id']);
                    //dump($data5[$ke]);die;
                    M('basic_otheruser_ipseg')->where(array('ip_id'=>$valu['ip_id']))->save($data5[$ke]);
                  }else{
                    $data5[$ke]['status'] = "1"; 
                    if($valu['is_special'] == '2'){
                      $data5[$ke]['start_ip'] = $valu['startip']."(专线)";
                      $data5[$ke]['end_ip'] = $valu['endip']."(专线)";
                    }else{
                      $data5[$ke]['start_ip'] = $valu['startip'];
                      $data5[$ke]['end_ip'] = $valu['endip'];
                    }
                    $data5[$ke]['hhid'] = $data4[$key]['hhid'];

                    $data5[$ke]['is_special'] =$valu['is_special'];
                    unset($data5[$ke]['ip_id']);

                    //dump($data5[$ke]);die;
                    M('basic_otheruser_ipseg')->add($data5[$ke]);
                  }
                }                  
              }
            }else{
              $data4[$key]['status'] = "1";
              //$data4[$key]['user_id'] = $id;
              //王诺楠让改为
              $data4[$key]['user_id'] = $data['user_id'];
              
              $max_hhid = M('cu_hhid')->order('id desc')->limit(0,1)->select()[0]['hhid'];

              if($max_hhid){
                $data4[$key]['hhid'] = $max_hhid+1;       
              }else{
                $data4[$key]['hhid'] = 1;
              }
              $dat[$key]['hhid'] = $data4[$key]['hhid'];
              M('cu_hhid')->add($dat[$key]);

              $data4[$key]['house_id'] = $value['house_id'];
              $data4[$key]['band_width'] = empty($value['band_width'])?null:$value['band_width'];
              $data4[$key]['distribute_time'] = strtotime($value['distribute_time']);
              unset($data4[$key]['id']);
              M('basic_otheruser_house')->add($data4[$key]);
              if(!empty($value['Ip'])){
                foreach ($value['Ip'] as $ke => $valu) {

                  $data5[$key]['status'] = "1"; 
                  if($valu['is_special'] == '2'){
                    $data5[$ke]['start_ip'] = $valu['startip']."(专线)";
                    $data5[$ke]['end_ip'] = $valu['endip']."(专线)";
                  }else{
                    $data5[$ke]['start_ip'] = $valu['startip'];
                    $data5[$ke]['end_ip'] = $valu['endip'];
                  }
                  $data5[$ke]['hhid'] =$data4[$key]['hhid'];
                  $data5[$ke]['is_special'] =$valu['is_special'];
                  unset($data5[$ke]['ip_id']);
                  M('basic_otheruser_ipseg')->add($data5[$ke]);
                }
              }                  
            }
          }          
        } 
        self::log('Web', '编辑其他用户成功', 5);
        $this->ajaxReturn(array('status'=>'success'));
    }
    
    public function user_del(){
        switch ((int)(I('get.type'))) {
          case 1:
            $this -> server_del(I('get.id'));
            break;
          
          case 2:
            $this -> other_del(I('get.id'));
            break;
        }
    }
    //互联网用户的删除
    public function server_del($id){
        //$id = I('id');
        $where['user_id'] = $id;
        $result = M('basic_user')->where($where)->select()[0];
        if($result['status'] == '1' || $result['status'] == '2'){
          M('basic_user')->where($where)->delete();
        }elseif($result['status'] == "0"){
          $data1['status'] = "3";
          M('basic_user')->where($where)->save($data1);
        }
        $service = M('basic_service')->where($where)->select();
        if(!empty($service)){
          foreach ($service as $key => $value) {
            if($value['status'] == "1" || $value['status'] == "2"){
              M('basic_service')->where(array('service_id'=>$value['service_id']))->delete();

            }elseif($value['status']=="0"){
              $data2['status'] = "3";
              M('basic_service')->where(array('service_id'=>$value['service_id']))->save($data2);
            }
            $roomlist = M('basic_ispuser_house')->where(array('service_id'=>$value['service_id']))->select();
            foreach ($roomlist as $k => $val) {
              if($val['status'] == "1" || $val['status'] == "2"){
                M('basic_ispuser_house')->where(array('hhid'=>$val['hhid']))->delete();
              }elseif($val['status'] == "0"){
                $data3['status'] = "3";
                M('basic_ispuser_house')->where(array('hhid'=>$val['hhid']))->save($data3);
              }
              $ip = M('basic_iptrans')->where(array('hhid'=>$val['hhid']))->select();
              foreach ($ip as $kee => $vae) {
                if($vae['status'] == "1" || $vae['status'] == "2"){
                  M('basic_iptrans')->where(array('id'=>$vae['id']))->delete();
                }elseif($vae['status'] == "0"){
                  $data4['status'] = "3";
                  M('basic_iptrans')->where(array('id'=>$vae['id']))->save($data4);
                }
              }
              $host = M('basic_virtualhost')->where(array('hhid'=>$val['hhid']))->select();
              foreach ($host as $k1 => $v1) {
                if($v1['status'] == "1" || $v1['status'] == "2"){
                  M('basic_virtualhost')->where(array('id'=>$v1['id']))->delete();
                }elseif($v1['status'] == "0"){
                  $data5['status'] = "3";
                  M('basic_virtualhost')->where(array('id'=>$v1['id']))->save($data5);
                }
              }
            }
            $domain = M('basic_domain')->where(array('service_id'=>$value['service_id']))->select();
            foreach ($domain as $ke => $valu) {
              if($valu['status'] == "1" || $valu['status'] == "2"){
                M('basic_domain')->where(array('domain_id'=>$valu['domain_id']))->delete();
              }elseif($valu['status'] == "0"){
                $data6['status'] = "3";
                M('basic_domain')->where(array('domain_id'=>$valu['domain_id']))->save($data6);
              }
            }
          }
        }
        self::log('Web', '删除提供互联网用户', 5);
        $this->ajaxReturn(array('status'=>"success"));
      
    }
    //其他用户的删除
    public function other_del($id){
     
        $where['user_id'] = $id;
        $result = M('basic_user')->where($where)->select()[0];
        if($result['status'] == '1' || $result['status'] == '2'){
          M('basic_user')->where($where)->delete();
        }elseif($result['status'] == "0"){
          $data1['status'] = "3";
          M('basic_user')->where($where)->save($data1);
        }
        $roomlist = M('basic_otheruser_house')->where($where)->select();
        foreach ($roomlist as $key => $value) {
          if($value['status'] == "1" || $value['status'] == "2"){
            M('basic_otheruser_house')->where(array('hhid'=>$value['hhid']))->delete();
          }elseif($value['status'] == "0"){
            $data1['status'] = "3";
            M('basic_otheruser_house')->where(array('hhid'=>$value['hhid']))->save($data1);
          }
          $ip = M('basic_otheruser_ipseg')->where(array('hhid'=>$value['hhid']))->select();
          foreach ($ip as $k => $val) {
            if($val['status'] == "1" || $val['status'] == "2"){
              M('basic_otheruser_ipseg')->where(array('ip_id'=>$val['ip_id']))->delete();
            }elseif($val['status'] == "0"){}
              $data2['status'] = "3";
              M('basic_otheruser_ipseg')->where(array('ip_id'=>$val['ip_id']))->save($data2);
          }
        }
        self::log('Web', '删除其他用户', 5);
        $this->ajaxReturn(array('status'=>"success"));
    }

    public function basic_report(){

      if($_GET){
        $data = I('data');
        if($data == '1'){
          $file = './byzoro/basic_update.txt';
          $res=file_put_contents($file, $data, LOCK_EX);
          if($res){
            self::log('Web', '基础数据上报成功', 5);
            $this->ajaxReturn(array('status'=>"success"));
          }else{
            self::log('Web', '基础数据上报失败', 3);
            $this->ajaxReturn(array('status'=>"error",'info'=>'上报失败！'));
          }
        }
      }
    }

    //参数管理
    public function info_value($col,$value){
      $c = [
        'house_type' => function($v){
            $cc = [
              '1'                                   => '租用',
              '2'                                   => '自建',
              '999'                                 => '其他',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'type' => function($v){
            $cc = [
              '0'                                   => '静态',
              '1'                                   => '动态',
              '2'                                   => '保留',
              '3'                                   => '专线',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'type_value' => function($v){
            $cc = [
              '静态'                                =>'0',
              '动态'                                =>'1',
              '保留'                                =>'2',
              '专线'                                =>'3',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'linktype' => function($v){
            $cc = [
              '1'                                   => '电信',
              '2'                                   => '联通',
              '3'                                   => '移动',
              '4'                                   => '铁通',
              '9'                                   => '其他',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'linktype_value' => function($v){
            $cc = [
              '电信'                                => '1',
              '联通'                                => '2',
              '移动'                                => '3',
              '铁通'                                => '4',
              '其他'                                => '9',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'usertype' => function($v){
            $cc = [
              '1'                                   => '自用',
              '2'                                   => '出租',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'nature' => function($v){
            $cc = [
              '1'                                   => '提供互联网用户',
              '2'                                   => '其他用户',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'usertype_value' => function($v){
            $cc = [
              '自用'                                => '1',
              '出租'                                => '2',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'is_special' => function($v){
            $cc = [
              '1'                                => '否',
              '2'                                => '是',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'distribution' => function($v){
            $cc = [
              '1'                                   => '未分配',
              '2'                                   => '已分配',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'distribution_value' => function($v){
            $cc = [
              '未分配'                              =>'1',
              '已分配'                              =>'2',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'occupancy' => function($v){
            $cc = [
              '1'                                   => '未占用',
              '2'                                   => '已占用',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'occupancy_value' => function($v){
            $cc = [
              '未占用'                              =>'1',
              '已占用'                              =>'2',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'id_type' => function($v){
            $cc = [             
              '1'                                   => '工商营业执照号码',
              '2'                                   => '身份证',
              '3'                                   => '组织机构代码证书',
              '4'                                   => '事业法人证书',
              '5'                                   => '军队代号',
              '6'                                   => '社团法人证书',
              '7'                                   => '护照',
              '8'                                   => '军官证',
              '9'                                   => '台胞证',
              '999'                                 => '其他',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'unitnature' => function($v){
            $cc = [             
              '1'                                   => '军队',
              '2'                                   => '政府机关',
              '3'                                   => '事业单位',
              '4'                                   => '企业',
              '5'                                   => '个人',
              '6'                                   => '社团团体',
              '999'                                 => '其他',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },

        'id_type_value' => function($v){
            $cc = [
              
              '工商营业执照号码'                    => '1',
              '身份证'                              => '2',
              '组织机构代码证书'                    => '3',
              '事业法人证书'                        => '4',
              '军队代号'                            => '5',
              '社团法人证书'                        => '6',
              '护照'                                => '7',
              '军官证'                              => '8',
              '台胞证'                              => '9',
              '其他'                                => '999',
             
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'subtype_value' => function($v){
            $cc = [
              '域名'                                => '1',
              'URL'                                 => '2',
              '关键字'                              => '3',
              '源IP地址'                            => '4',
              '目的IP地址'                          => '5',
              '源端口'                              => '6',
              '目的端口'                            => '7',
              '传输层协议'                          => '8',
            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },  
           
    ];
    return isset($c[$col]) ? $c[$col]($value) : $value;
    }
}