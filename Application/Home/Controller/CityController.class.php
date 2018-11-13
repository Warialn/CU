<?php
namespace Home\Controller;
use Think\Controller;
class CityController extends Controller {
	public function city(){
       if (IS_GET) {
       	$parent_id['parent_id'] = I('pro_id');
		$region = M('city')->where($parent_id)->select();
		$opt = '<option value="">请选择</option>';
		foreach($region as $key=>$val){
		    $opt .= "<option value='{$val['id']}'>{$val['name']}</option>";
		}
		echo json_encode($opt);
        } 
	}
	public function index(){
        $pid = $_GET['pid'];
        $city = M("city");
        $data = $city->where(['parent_id' => $pid])->select();
        $this->ajaxReturn($data);
    }
}