<?php
namespace Home\Controller;
use Think\Controller;
class SystemlogController extends CommonController {
    public function index(){
       Layout('Layout/layout');
       $this->display();
    }
    public function user(){
       $user = M('users');
        if (!empty($_GET)) {

            if ($name = I('get.username')) {
                $map['users.user_login'] = array('like', '%' . $name . '%');
            }

            if ($time = I('get.time')) {
                $time = explode('-', $time);
                $map['users.create_time'] = ['between', [strtotime($time[0]), strtotime($time[1] . ":59")]];
            }
            
            if ($usergroup = I('get.usergroup')) {
                $group = M('auth_group_access');
                $data = $group->where(array('group_id'=>$usergroup))->field('uid')->select();
                if ($data) {
                    foreach ($data as $k => $v) {
                        $temp[] = $data[$k]['uid'];
                    }
                    $map['users.id'] = array('in',$temp);
                }
            }

        }
        $user_count = $user
            ->where($map)->count();// 查询满足要求的总记录数 $map表示查询条件

        $user_Page = new \Think\Page($user_count, 25);// 实例化分页类 传入总记录数
        $user_data = $user
            ->join('LEFT JOIN auth_group_access ON users.id=auth_group_access.uid')
            ->join('LEFT JOIN auth_group ON auth_group_access.group_id=auth_group.id')
            ->where($map)
            ->field('users.*,auth_group_access.group_id,auth_group.title')
            ->group('users.user_login')
            ->limit($user_Page->firstRow . ',' . $user_Page->listRows)->select();

        $auth_group_access = M('auth_group_access');

        foreach ($user_data as $key => &$value) {
            $value['title']= $auth_group_access
                ->where(array('uid' => $value['id']))
                ->join('LEFT JOIN auth_group ON auth_group_access.group_id=auth_group.id')
                ->field('auth_group.title')
                ->select();
        }
        
        foreach ($user_data as $k1 => $v1) {
            $title = '';
            foreach ($v1['title'] as $k2 => $v2) {
                if($v2['title']){
                    if($k2==0){
                        $title .= $v2['title'];
                    }else{
                        $title .= ','.$v2['title'];
                    }
                }
            }
            $user_data[$k1]['title'] = $title;
        }
        //dump($user_data);die;
        //self::log('Web', '查看用户信息', 5);

        $user_Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");

        $user_show = $user_Page->show();// 分页显示输出
        if(I('get.usergroup')){
            $usergroup_id = I('get.usergroup');
            $this->assign('usergroup_id',$usergroup_id );
        }
        if(I('get.username')){
            $user_login   = I('get.username');
            $this->assign('user_login',$user_login );
        }
        $this->assign('user_data', $user_data);
        $this->assign('user_show', $user_show);
        $group = M('auth_group');
        $group_data = $group->select();
        unset($group_data[0]);
        $this->assign('group_data', $group_data);
        $username_data = M('users') ->field('id,user_login')-> select();
        unset($username_data[0]);
        $this->assign('username_data',$username_data );
        self::log('Web', '用户管理查询', 5);
        layout('Layout/layout');
        $this->display();
    }
    public function user_add(){
        $usergroup = I('get.usergroup');
        /*if ($_SESSION['isAdmin']=='0'&& in_array('1', $usergroup)) {
            $this->ajaxReturn(['status'=>'error','msg'=>'没有权限']);
        }*/
        if ($_GET) {
            $data['user_login']  = $datas['user_name'] = I('get.user_login','','htmlspecialchars');
            self::check_user($data['user_login']);
            $data['ipver']       = I('get.ip_type');
            $data['user_pass']   = md5(I('get.user_pass'));
            $data['user_email']  = I('get.email');
            $data['create_time'] = time();
            $user = M('users');
            $result = $user->add($data);
            $user_group = M('auth_group_access');
            foreach ($usergroup as $v) {
                $user_group->add(array('uid' => $result, 'group_id' => $v));
            }
            $sql = "用户名:" . $data['user_login'];
            if ($result) {
                self::log('Web', '添加用户', 5);
                echo json_encode(array('status' => 'success'));
            } else {
                self::log('Web', '添加用户', 3);
                echo json_encode(array('status' => 'error'));
            }
        }/* else {
            $usergroup_data = self::get_usergroup_list();
            $this->assign('usergroup_data', $usergroup_data);
            layout('Layout/layout');
            $this->display();
        }*/
    }
    public function user_del(){
        $id = I('get.id','','htmlspecialchars');
        $id = explode(',', $id);
        if (in_array(session('uid'),$id)) {
            $this->ajaxReturn(['status'=>'error','msg'=>'不能删除超级管理员']);
        }else{
            $user = M('users');
            $status = $user->where(array('id'=>array('in',$id)))->delete();
            $auth_group_access = M('auth_group_access');
            if ($status!==false) {
                $auth_group_access->where(array('uid'=>array('in',$id)))->delete();
                self::log('Web','删除用户成功',5);
                $this->ajaxReturn(['status'=>'success','msg'=>'删除成功']);
            }else {
                self::log('Web','删除用户失败',5);
                $this->ajaxReturn(['status'=>'error','msg'=>'删除失败']);
            }
        } 
    }
    public function user_edit(){
        if((int)I('get.type')==1){
            $id        = (int)I('get.id');
            $usergroup = I('get.usergroup');
            if ($_SESSION['isAdmin']=='0'&& in_array('1', $usergroup)) {
                $this->ajaxReturn(['status'=>'error','msg'=>'不能修改超级管理员']);
            }
            $data['user_login'] = $datas['user_name'] = I('get.username','','htmlspecialchars');
            $res = self::check_other_user($id,$data['user_login']);
            if ($res) {
                $this->ajaxReturn(['status'=>'1','msg'=>'用户名已存在']);
            }
            if(I('get.password')){
                $data['user_pass'] = md5(I('get.password'));
            }
            $user = M('users');
            $result = $user->where(['id'=>$id])->save($data);
            $user_group = M('auth_group_access');
            if($result!==false){
                $user_group->where(['uid'=>$id])->delete();
                foreach ($usergroup as $v) {
                    $result = $user_group->add(array('uid'=>$id,'group_id'=>$v));
                }
                self::log('Web','修改用户成功',5);
                $this->ajaxReturn(['status'=>'success','msg'=>'修改成功']);
            }else{
                self::log('Web','修改用户失败',5);
                $this->ajaxReturn(['status'=>'error','msg'=>'修改失败']);
            }
        }else{
            $group = M('auth_group_access');
            $id    = I('get.id');
            $data  = $group->where(array('uid'=>$id))->field('group_id')->select();
            $this -> ajaxReturn($data);
        }
    }
    private function check_other_user($id,$name){
        $user = M('users');
        $map['id'] = array('neq',$id);
        $map['user_login'] = $name;
        $res = $user->where($map)->find();
        if ($res) {
            return true;
        }else{
            return false;
        }
    }
    public function usergroup(){
        $usergroup = M('auth_group');
        $usergroup_count = $usergroup->count();// 查询满足要求的总记录数 $map表示查询条件
        //$usergroup_Page = new \Think\Page($usergroup_count, PAGENUM);// 实例化分页类 传入总记录数
        $usergroup_Page = new \Think\Page($usergroup_count, 25);// 实例化分页类 传入总记录数
        $usergroup_Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $usergroup_show = $usergroup_Page->show();// 分页显示输出
        $usergroup_data = $usergroup->limit($usergroup_Page->firstRow . ',' . $usergroup_Page->listRows)->select();
        self::log('Web', '用户组管理查询', 5);
        $this->assign('usergroup_data', $usergroup_data);
        $this->assign('usergroup_show', $usergroup_show);
        layout('Layout/layout');
        $this->display();
    }
    public function usergroup_add(){
        if ($_GET) {
            $data['title'] = I('get.title','','htmlspecialchars') ;
            $data['note']  = I('get.note','','htmlspecialchars');
            $arr_ids       = M('menu')->field('GROUP_CONCAT(id) as ids') ->find();
            $data['rules'] = $arr_ids['ids'];
            $usergroup     = M('auth_group');
            if ($usergroup->where(['title' => $data['title']])->find()){
                self::log('Web','添加用户组失败',5);
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该用户组已存在'));
            }
            $result = $usergroup->add($data);
            if($result){
                self::log('Web','添加用户组成功',5);
                $this->ajaxReturn(array('status' => 'success', 'msg' => '添加成功'));
            }else{
                self::log('Web','添加用户组失败',5);
                $this->ajaxReturn(array('status' => 'error', 'msg' => '添加失败'));
            }
        }
        
    }
    public function usergroup_edite(){
        $usergroup = M('auth_group');
        $id = (int)I('get.id');
        $check = M('auth_group_access')->where(array('group_id'=>$id))->find();
        if($check){
            $this->ajaxReturn(array('status'=>'error','msg'=>'该用户组已有用户绑定，不允许编辑！'));
        }
        $data['title'] = I('get.title','','htmlspecialchars');
        $data['note'] = I('get.note','','htmlspecialchars');
        $usergroup_data = $usergroup->where(array('id'=>$id))->save($data);
        if ($usergroup_data!==false) {
            self::log('Web','修改用户组成功',5);
            $this->ajaxReturn(array('status'=>'success','msg'=>'编辑成功'));
        }else{
            self::log('Web','修改用户组失败',3);
            $this->ajaxReturn(array('status'=>'error','msg'=>'编辑失败'));
        }
    }
    public function usergroup_del(){
        //删除前先判断组内有没有关联用户如果有就提示不能删除
        $id        = I('get.id','','htmlspecialchars');
        $usergroup = M('auth_group');
        $res       = M('auth_group_access')->where(array('group_id'=>array('in',$id)))->find();
        if(!is_null($res)){
            self::log('Web','删除用户组失败',5);
            $this->ajaxReturn(array('status'=>'error','msg'=>'组内有用户不能删除'));
        }
        $result = $usergroup->where(array('id'=>array('in',$id)))->delete();
        if ($result) {
            self::log('Web','删除用户组成功',5);
            $this->ajaxReturn(array('status'=>'success','msg'=>'删除成功'));
        }else{
            self::log('Web','删除用户组失败',5);
            $this->ajaxReturn(array('status'=>'error','msg'=>'删除失败'));
        }
    }
    public function authorize()
    {
        $this->auth_access_model = D("Common/auth_group");
        //角色ID
        $roleid = intval(I("get.id"));
        if (!$roleid) {
            $this->error("参数错误！");
        }
        $menu = new \Think\Tree();
        $menu->icon = array('│ ', '├─ ', '└─ ');
        $menu->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = $this->initMenu();
        $newmenus = array();
        $priv_data = $this->auth_access_model->where(array("id" => $roleid))->getField("rules", true);//获取权限表数据
        $priv_data = explode(',', $priv_data[0]);
        //var_dump($priv_data);die();
        $auth_rule = M('auth_rule');
        $data = $auth_rule->where(array('id' => array('in', $priv_data)))->getField('name', true);
        foreach ($data as $k => $v) {
            $data[$k] = strtolower($v);
        }

        foreach ($result as $m) {
            $newmenus[$m['id']] = $m;
        }

        foreach ($result as $n => $t) {
            $result[$n]['checked'] = ($this->_is_checked($t, $roleid, $priv_data)) ? 'checked' : '';
            $result[$n]['level'] = $this->_get_level($t['id'], $newmenus);
            $result[$n]['parentid_node'] = ($t['parentid']) ? ' class="child-of-node-' . $t['parentid'] . '"' : '';
        }

        $str = "<tr id='node-\$id' \$parentid_node>
                       <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
                    </tr>";
        $menu->init($result);
        $categorys = $menu->get_tree(0, $str);

        self::log('Web', '查看用户组权限', 5);
        $this->assign("categorys", $categorys);
        $this->assign("roleid", $roleid);
        layout('Layout/layout');
        $this->display();
    }

    /**
     *  检查指定菜单是否有权限
     * @param array $menu menu表中数组
     * @param int $roleid 需要检查的角色ID
     */
    private function _is_checked($menu, $roleid, $priv_data)
    {
        // $app=$menu['app'];
        // $model=$menu['model'];
        // $action=$menu['action'];
        // $name=strtolower("$app/$model/$action");
        //var_dump($priv_data);die;
        $id = $menu['id'];
        if ($priv_data) {
            if (in_array($id, $priv_data)) {
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

    /**
     * 角色授权
     * 角色授权
     */
    public function authorize_post()
    {
        $this->auth_access_model = M("auth_group");
        if (IS_POST) {
            $roleid = intval(I("post.roleid"));
            $sql = "用户组ID:" . $roleid;
            if (!$roleid) {
                self::log('Web', '用户组权限设置', 3);
                $this->ajaxReturn(['status' => 'failed', 'info' => '授权成功']);
            }
            if (is_array($_POST['menuid']) && count($_POST['menuid']) > 0) {
                $rules = implode(",", $_POST['menuid']);
                $this->auth_access_model->where(array("id" => $roleid))->save(array('rules' => $rules));
                self::log('Web', '用户组权限设置', 5);
                // $this->success("授权成功！", "usergroup");
                $this->ajaxReturn(['status' => 'success', 'info' => '授权成功']);
            } else {
                //当没有数据时，清除当前角色授权
                $this->auth_access_model->where(array("id" => $roleid))->setField('rules', '');
                self::log('Web', '用户组权限设置', 3);
                $this->ajaxReturn(['status' => 'clear', 'info' => '没有接收到数据，执行清除授权成功']);
            }
        }
    }
    //日志
    public function logset()
    {
        $log = M('log');
        $where = [];
        if (!empty($_GET)) {
            $time     = I('get.time');
            $ip       = I('get.ip');
            $username = I('get.username');
            if ($time) {
                $times = explode('-', $time);
                $time_start = strtotime($times[0]);
                $time_end = strtotime($times[1].":59");
                $where['time'] = array('between', array($time_start, $time_end));
            }
            if ($ip != '') {
                if (strpos($ip, '*')) {
                    $where['ip'] = array('like', str_replace('*', '', $ip) . '%');
                } else {
                    $where['ip'] = $ip;
                }
            }
            if ($username != '') {
                $where['username'] = $username;
            }
        }

        if (session('uid') != 1) {
            $where['uid'] = session('uid');
        }

        $count = $log->where($where)->count();
        $Page = new\Think\Page($count, 25);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $log_data = $log->field("users.user_login, log.*")->join("LEFT JOIN users ON log.uid=users.id")
            ->limit($Page->firstRow . ',' . $Page->listRows)->where($where)
            ->order('time desc')->select();

       // self::log('Web', '操作日志查询', 5);
        $show = $Page->show();// 分页显示输出

        $this->assign('log_data', $log_data);
        $this->assign('show', $show);

        layout('Layout/layout');
        $this->display();
    }
    public function test(){
      $menu = M('menu');
      $result = $menu ->select();
      foreach ($result as $key => $value) {
        $data['id'] = $value['id'];
        $data['name'] = $value['app'].'/'.$value['model'].'/'.$value['action'];
        $data['title'] = $value['name'];
        $auth_rule = M('auth_rule');
        $auth_rule -> add($data);
      }
      /*$this->assign('result',$result);
      layout('Layout/layout');
      $this->display();*/
    }/**/
    private function check_user($username){
        $user = M('users');
        $res  = $user->where(['user_login'=>$username])->find();
        if ($res) {
            $this->ajaxReturn(['status'=>1,'msg'=>'用户名已存在']);
        }else{
            return true;
        }
    }


}