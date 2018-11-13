   <?php
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length) 
    return mb_substr($text, 0, $length, 'utf8').'...';
    return $text;
}
function authcheck($rule,$t,$f='没有权限'){
    $uid = session('uid');
    if ($uid == 1) {
        return $t;
    }   
    $auth = new \Think\Auth();
    return $auth->check($rule,$uid)?$t:$f;
}
function authcheck_basic($rule,$t,$f='没有权限'){
    $uid = session('uid');
    if ($uid == 1) {
        return $t;
    }   
    $auth = new \Think\Auth();
    return $auth->check($rule,$uid,1,'url','and')?$t:$f;
}