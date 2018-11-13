<?php

namespace Home\Model;


use Common\Model\Model;

class Policy extends Model
{
    public static function col()
    {
    	$c = [
            '指令ID'        => 'commandid',
            '指令类型'      => 'type',
            '机房名称'      => 'house_id',
            '生效时间'      => 'effect_time',
            '过期时间'      => 'expired_time',
            '操作类型'      => 'operationtype',
            '域名'          => '域名',
            'URL'           => 'URL',
            '关键字'        => '关键字',
            '源IP地址'      => '源IP地址',
            '目的IP地址'    => '目的IP地址',
            '源端口'        => '源端口',
            '目的端口'      => '目的端口',
            '传输层协议'    => '传输层协议',
    	];

    	return $c;
    }
    public static function famous_col()
    {
        $c = [
            '网站名称' => 'popularweb_name',
            '域名/URL'     => 'popularweb_url',
            '监测标识' => 'monitor_flag',
        ];

        return $c;
    }
    public static function domain_col()
    {
        $c = [
            '域名'     => 'host',
            '用户名'   => 'username',
            '密码'     => 'password',
            '备注'     => 'remark',
            '监测标识' => 'monitor_flag',
        ];

        return $c;
    }
     public static function col2value($key,$value)
    {
        
        
        $c = [
            '指令类型' => function($v){
                return $v == '监测'?1:2;
            },
            '机房名称' => function($v){
                
                return self::col2house_id($v);
            },
            '操作类型' => function($v){
                
                return $v == '新增'?0:1;
            }
        ];
        
        return isset($c[$key]) ? $c[$key]($value) : $value;
    }
    public static function col2house_id($value)
    {   
        $house_names = explode('，',$value);
        foreach ($house_names as $key => $v) {
            $res[] = M('basic_house') -> where(['house_name'=>$v])->field('house_id')->find();
        }
        foreach ($res as $k => $val) {
            $house_id[] = $val['house_id'];
        }
        
        $con = [
            $value => $house_id,
        ];  
        return isset($con[$value]) ? $con[$value] : "";
    }
}
