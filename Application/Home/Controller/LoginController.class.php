<?php
namespace Home\Controller;
use Think\Controller;
//use Common\Controller\CommonController;
class LoginController extends Controller {
    public function index(){
        if(I('get.n')){
            $pass       =  I('get.password'); 
            $user_login =  I('get.username');
            if(I('get.code')){
                $mcode      =  I('get.code');
                $verify     =  new \Think\Verify();
                //dump($verify  -> check($mcode,''));die;
                if($verify  -> check($mcode,'')){
                    $this   -> login($user_login,$pass);
                }else{
                    $this   -> ajaxReturn(['status'=>'error','msg'=>'验证码错误请重新输入！']);
                }
            }else{
                $this   -> ajaxReturn(['status'=>'error','msg'=>'验证码不能为空！']);
                //$this   -> login($user_login,$pass);
            }
        }else{
           $this->display();
        }

    }
    public function login($user_login,$pass){
        $user  = M('users');
        $res   = $user->where(array('user_login'=>$user_login,'user_pass'=>md5($pass)))->find();
        if ($res) {
            session('uid',$res['id']);
            session('username',$res['user_login']);
            session('user_email',$res['user_email']);
            //默认不是超级管理员
            session('isAdmin',0);
            setcookie('userid',$res['id'],time()+3600*24);
            setcookie('username',$res['user_login'],time()+3600*24 );
            self::update($res['id']);
            //查看是否为超级管理员
            if($res['user_type']==1){
                session('isAdmin',1);
            }
            if(preg_match("/^[a-zA-Z]{1}[a-zA-Z0-9]{3,11}$/",$pass)&& preg_match("/^[\da-zA-Z.!@#$%^&*]{6,12}$/",$pass)){
                
                $this->ajaxReturn(array('status'=>'warning','msg'=>'您的密码过于简单，请及时修改！'));
            } else{
                $this->ajaxReturn(array('status'=>'success','msg'=>'登录成功'));
            } 
        }else{
            $this->ajaxReturn(array('status'=>'error','msg'=>'用户名或密码错误'));
        }
    }
    public function verify(){
          ob_clean();
          $config =    array(
                'imageW'    =>    130,
                'imageH'    =>    42,
                'fontSize'    =>    19,    // 验证码字体大小
                'length'    =>    4,     // 验证码位数
                'useNoise'    =>    false, // 关闭验证码杂点
                'useCurve'    =>    false,
                'bg'        =>    array(208, 238, 238)
        );
        $verify = new \Think\Verify($config);
        $ver = $verify->entry();

    }

    public function update($id){
       $user = M('users');
       $data['last_login_time'] = time();
       $data['last_login_ip'] = get_client_ip();
       $user->where(['id'=>$id])->save($data);
    }
   
    public function logout(){
          $_SESSION = array(); //清除SESSION值.
          setcookie(session_name(),'',time()-1,'/');
          session_destroy();  //清除服务器的sesion文件
          $this->redirect('Login/index');
    }
}