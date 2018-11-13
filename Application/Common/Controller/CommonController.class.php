<?php
namespace Common\Controller;

use Think\Controller;
use Think\Auth;

Class CommonController extends Controller
{

    public function _initialize()
    {
        $this->check_login();
         $auth = new Auth();
         if(!$auth->check(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME,session('uid'))){
            $this ->error('没有权限或该模块不存在');
         }
        $this->assign('module', $think . MODULE_NAME);
        $this->assign('controller', $think . CONTROLLER_NAME);
        $this->assign('action', $think . ACTION_NAME);
        $report_type = array('FTP' => 'FTP', 'VSFTP' => 'VSFTP');
        $user_type   = array('static' => '静态用户', 'dial' => '拨号用户', 'msisdn' => 'MSISDN', 'imsi' => 'IMSI', 'imei' => 'IMEI');
        $site_type   = array('综合', '新闻', '视频', '娱乐', '汽车', '科技', '教育', '公益', '财经', '体育', '时尚', '房产', '游戏', '文化');
        $task_list = array('UpgradeOs'=>'升级OS',
                           'UpgradeDpi'=>'升级DPI特征库',
                           'UpgradeVirus'=>'升级病毒库',
                           'UpgradeConfig'=>'升级CONFIG',
                           'DataBakup'=>'远程备份',
                           'SystermReboot'=>'远程重启',
                           'BackupSnap'=>'快照文件备份',
                           'Sniff'=>'抓包',
                           'UpgradeUrl'=>'升级URL库');
        $this->assign('report_type', $report_type);
        $this->assign('user_type', $user_type);
        $this->assign('site_type', $site_type);
        $this->assign('task_list',$task_list);
    }

    public function authorize()
    {
        $this->auth_access_model = D("Common/AuthAccess");
        //角色ID
        $roleid = intval(I("get.id"));
        if (!$roleid) {
            $this->error("参数错误！");
        }
        import("Tree");
        $menu       = new \Tree();
        $menu->icon = array('│ ', '├─ ', '└─ ');
        $menu->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result     = $this->initMenu();
        $newmenus   = array();
        $priv_data  = $this->auth_access_model->where(array("role_id" => $roleid))->getField("rule_name", true);//获取权限表数据
        foreach ($result as $m) {
            $newmenus[$m['id']] = $m;
        }

        foreach ($result as $n => $t) {
            $result[$n]['checked']       = ($this->_is_checked($t, $roleid, $priv_data)) ? ' checked' : '';
            $result[$n]['level']         = $this->_get_level($t['id'], $newmenus);
            $result[$n]['parentid_node'] = ($t['parentid']) ? ' class="child-of-node-' . $t['parentid'] . '"' : '';
        }
        $str = "<tr id='node-\$id' \$parentid_node>
                       <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
                    </tr>";
        $menu->init($result);
        $categorys = $menu->get_tree(0, $str);

        $this->assign("categorys", $categorys);
        $this->assign("roleid", $roleid);
        $this->display();
    }

    public function log($app, $message='', $level=5, $data=[]){
        $log = M('log');
        $data['app'] = $app;
        // $str = '';
        // foreach ($data as $k => $v) {
        //     $str .= $k.'='.$v.' ';
        // }
        $data['ip'] = get_client_ip();
        $data['username'] = $_SESSION['username'];
        $data['message'] = $message;
        $data['level'] = $level;
        $data['uid'] = session('uid') ? session('uid'):0;
        $data['time'] = time();
        $log->add($data);
    }


    public function check_rooms(){
        $users = M('users');
        $room = M('room');
        $uid = session('uid');

        if ($uid == "1") {  
            $where = [];
        } else {
            $room_id = $users->where(['id' => $uid])->field('local_roomname')->find();
            $room_ids = explode(" ", trim($room_id['local_roomname']));
            $where['r_id'] = array('in', $room_ids); 
        }

        $rooms = $room->field('r_id,room_name,city')->where($where)->order('city')->select();
        return $rooms;
    }
    public function check_physics_rooms(){
         $users = M('users');
        $room = M('room');
        $uid = session('uid');

        if ($uid == "1") {  
            $where['room_type'] =array('neq',2);
        } else {
            $room_id = $users->where(['id' => $uid])->field('local_roomname')->find();
            $room_ids = explode(" ", trim($room_id['local_roomname']));
            $where['r_id'] = array('in', $room_ids); 
             $where['room_type'] =array('neq',2);
        }

        $rooms = $room->field('r_id,room_name,city')->where($where)->order('city')->select();
        return $rooms;
    }
    public function check_logic_rooms(){
         $users = M('users');
        $room = M('room');
        $uid = session('uid');

        if ($uid == "1") {  
            $where['room_type'] =array('neq',1);
        } else {
            $room_id = $users->where(['id' => $uid])->field('local_roomname')->find();
            $room_ids = explode(" ", trim($room_id['local_roomname']));
            $where['r_id'] = array('in', $room_ids); 
             $where['room_type'] =array('neq',1);
        }

        $rooms = $room->field('r_id,room_name,city')->where($where)->order('city')->select();
        return $rooms;

    }


    public function in_rooms(){

        $users = M('users');
        $room = M('room');
        $uid = session('uid');
        $room_id = $users->where(['id' => $uid])->field('local_roomname')->find();
        if (!$room_id['local_roomname']) {
            return '--';
        }
        $room_ids = explode(" ", trim($room_id['local_roomname']));
        
        return array('in', $room_ids); 
    }
     public function in_physics_rooms(){

        $users = M('users');
        $room = M('room');
        $uid = session('uid');
        $room_id = $users->where(['id' => $uid])->field('local_roomname')->find();
        if (!$room_id['local_roomname']) {
            return '--';
        }
        $room_ids = explode(" ", trim($room_id['local_roomname']));
       // dump($room_ids);die;
        foreach ($room_ids as $key => $value) {
            $r = $room->where(array('r_id'=>$value,'room_type'=>array('neq',2)))->select();
            if($r){
                $roomids[] = $room_ids[$key];
            }
            # code...
        }
        if (!$roomids) {
            return '--';
        }
        return array('in', $roomids); 
    }
     public function in_logic_rooms(){

        $users = M('users');
        $room = M('room');
        $uid = session('uid');
        $room_id = $users->where(['id' => $uid])->field('local_roomname')->find();
        if (!$room_id['local_roomname']) {
            return '--';
        }
        $room_ids = explode(" ", trim($room_id['local_roomname']));
         foreach ($room_ids as $key => $value) {
            $r = $room->where(array('r_id'=>$value,'room_type'=>array('neq',1)))->select();
            if($r){
                $roomids[] = $room_ids[$key];
            }
            # code...
        }
        if (!$roomids) {
            return '--';
        }
        
        return array('in', $roomids); 
    }

    /**
     *  检查指定菜单是否有权限
     * @param array $menu menu表中数组
     * @param int $roleid 需要检查的角色ID
     */
    private function _is_checked($menu, $roleid, $priv_data)
    {

        $app    = $menu['app'];
        $model  = $menu['model'];
        $action = $menu['action'];
        $name   = strtolower("$app/$model/$action");
        if ($priv_data) {
            if (in_array($name, $priv_data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**
     * 获取菜单深度
     * @param $id
     * @param $array
     * @param $i
     */
    protected function _get_level($id, $array = array(), $i = 0)
    {

        if ($array[$id]['parentid'] == 0 || empty($array[$array[$id]['parentid']]) || $array[$id]['parentid'] == $id) {
            return $i;
        } else {
            $i++;

            return $this->_get_level($array[$id]['parentid'], $array, $i);
        }

    }


    public function check_login()
    {
        if (!session('username') && !session('uid')) {
            $this->redirect('Login/index');
        }
    }

    //获取策略组
    public function get_plcygroup_list()
    {
        $plcygroup      = M('plcygroup');
        $plcygroup_data = $plcygroup->select();

        return $plcygroup_data;
    }

    public function get_controle_type_list()
    {
        $controle_type      = M('control_type');
        $controle_type_list = $controle_type->select();

        return $controle_type_list;
    }

    //获取上报模板
    public function get_report_template_list()
    {
        $report_template      = M('report_template');
        $report_template_data = $report_template->select();

        return $report_template_data;
    }

    public function get_report_type_list()
    {
        $report_type      = M('report_plcy_type');
        $report_type_data = $report_type->where(array('type' => '0'))->select();

        return $report_type_data;
    }

    public function get_report_sub_type_list()
    {
        $report_type      = M('report_plcy_sub_type');
        $report_type_data = $report_type->select();

        return $report_type_data;
    }

    public function get_controle_action_list()
    {
        $controle_action      = M('control_action');
        $controle_action_data = $controle_action->select();

        return $controle_action_data;
    }

    public function get_controle_condition_list()
    {
        $controle_condition      = M('control_condition');
        $controle_condition_data = $controle_condition->select();

        return $controle_condition_data;
    }

    public function get_report_type()
    {
        $type        = $_POST['type'];
        $report_type = M('report_plcy_sub_type');
        $data        = $report_type->where(array('type' => $type))->select();
        //dump($data);
        if ($data)
            echo json_encode(array('status' => 'success', 'data' => $data));
        else
            echo json_encode(array('status' => 'error'));
    }

    public function get_report_content()
    {
        $type        = $_POST['type'];
        $report_type = M('report_content');
        $data        = $report_type->where(array('type' => $type))->select();
        if ($data)
            echo json_encode(array('status' => 'success', 'data' => $data));
        else
            echo json_encode(array('status' => 'error'));
    }

    public function get_condition()
    {
        $type           = $_POST['type'];
        $condition      = M('report_condition');
        $condition_data = $condition->where(array('type' => $type))->select();
        if ($condition_data)
            echo json_encode(array('status' => 'success', 'condition' => $condition_data));
        else
            echo json_encode(array('status' => 'error'));
    }

    public function get_condition_list($type = '')
    {
        $condition      = M('report_condition');
        $condition_data = $condition->where(array('type' => $type))->select();

        return $condition_data;
    }

    public function get_room_list()
    {
        $room      = M('room');
        $room_data = $room->select();

        return $room_data;
    }

    public function get_usergroup_list()
    {
        $usergroup      = M('usergroup');
        $usergroup_data = $usergroup->select();

        return $usergroup_data;
    }

    public function get_report_type_name($id)
    {
        $report_plcy = M('report_plcy_type');
        $result      = $report_plcy->where(array('id' => $id))->find();

        return $result;
    }

    public function disbuild_xml($xml)
    {
        $xml  = simplexml_load_string($xml);
        $data = json_decode(json_encode($xml), TRUE);
        return $data;
    }

    public function get_data_source()
    {
        $report = M('report_plcy');
        $data   = $report->select();

        return $data;
    }

    public function get_area_name($id)
    {
        $area = M('area_ip');
        $data = $area->where(array('id' => $id))->field('area_name')->find();

        return $data;
    }

    public function get_dev()
    {
        //$dav = M('dev');
        return array('设备1', '设备2', '设备3');
    }

    public function get_name($id, $table)
    {
        $table = M($table);
        $data  = $table->where(array('id' => $id))->find();
        return $data;
    }

    public function get_plcygroup($field, $name, $table)
    {
        $table        = M($table);
        $plcygroup_id = $table->where($field = $name)->field('plcygroup_id')->find();

        return $plcygroup_id;
    }

    public function update_time($id)
    {
        $plcygroup           = M('plcygroup');
        $data['modify_time'] = time();
        $plcygroup->where(array('id' => $id))->save($data);

        return TRUE;
    }

    public function initMenu()
    {
        $Menu = F("Menu");
        if (!$Menu) {
            $Menu = D("Common/Menu")->menu_cache();
        }

        return $Menu;
    }

}