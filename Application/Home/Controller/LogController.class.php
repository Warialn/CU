<?php
namespace Home\Controller;
use Think\Controller;

//use Elasticsearch\elasticsearch\elasticsearch\src\Elasticsearch\Client;

class LogController extends CommonController {
    
    
    public function access_log(){
        $params['index'] = 'byzoro_access';
        $params['type'] = 'log_access';        
        $params['body']['sort']['accesstime']['order']  = 'desc';
        $params['body']['aggs']['src_ip']['terms']['field'] = 'src_ip';
        if($_GET){
            $where = [];
            if(I('house_id')!=''){
               $house_id = I('house_id');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('commandid')!=''){
               $commandid = I('commandid');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['commandid'=>$commandid]];
            }
            if(I('src_ip')!=''){
               $src_ip = I('src_ip');  
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['src_ip'=>$src_ip]];
            }
            if(I('src_port')!=''){
               $src_port = I('src_port'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['src_port'=>$src_port]];
            }
            if(I('url')!=''){
               $url = I('url');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['url'=>$url]];
            }
            
            if(I('dst_ip')!=''){
               $dst_ip = I('dst_ip');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['dst_ip'=>$dst_ip]];
            }
            if(I('dst_port')!=''){
               $dst_port = I('dst_port');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['dst_port'=>$dst_port]];
            }
            if(I('accesstime') !=''){
               $accesstime = I('accesstime');
               $start = strtotime(explode('-',$accesstime)[0]);
               $end = strtotime(explode('-', $accesstime)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params['body']['query']['bool']['filter']['range']['accesstime'] = ['gte'=>$start,'lte'=>$end];
            }   
        } 
        $ress = $this->client->search($params);  
        $count = $ress['hits']['total'];
        $Page  = new \Think\Page($count,12);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $params['from'] = $Page->firstRow;
        $params['size'] = $Page->listRows;
        $resss = $this->client->search($params);

        foreach($resss['hits']['hits'] as $key=>$val){
            $res[$key] = $val['_source']; 
            $res[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$val['_source']['house_id']))->select()[0]['house_name'];
        }
        $show = $Page->show();
        $this->assign('show', $show);
        $this->assign('res',$res);
        $this->assign('house_id',$house_id);
        $this->assign('commandid',$commandid);
        $this->assign('src_ip',$src_ip);
        $this->assign('src_port',$src_port);
        $this->assign('dst_ip',$dst_ip);
        $this->assign('dst_port',$dst_port);
        $this->assign('url',$url);
        $this->assign('accesstime',$accesstime);
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        self::log('Web', '访问日志查询', 5);
        Layout('Layout/layout');
        $this->display();
    }
    public function active_ip(){
        $params['index'] = 'byzoro_active_ip';
        $params['type'] = 'log_active_ip';
        $first_time = date('m/d/Y 00:00',time()).' - '.date('m/d/Y H:i',time());
        $start = strtotime(explode(' - ',$first_time)[0]);
        $end = strtotime(explode(' - ', $first_time)[1]);
        $start = date('Y-m-d H:i:s',$start);
        $end = date('Y-m-d H:i:s',$end);
        $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];

        //$params['body']['aggs']['ip']['cardinality'] = ['field'=>'ip'];
        //$params['body']['aggs']['views']['stats'] = ['field'=>'visitscount'];

        if($_GET){
            if(I('house_id')!=''){
               $house_id = I('house_id');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('ip')!=''){
               $ip = I('ip');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$ip]];
            }
            if(I('is_seg')!=''){
               $is_seg = I('is_seg'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['is_seg'=>$is_seg]];
            }
            if(I('block')!=''){
               $block = I('block');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['block'=>$block]];
            }
            if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
            }   
        } 
       /* $params['body']['aggs']['ip']['terms'] = ['field'=>'ip'];
        $params['body']['aggs']['ip']['aggs']['view']['sum'] = ['field'=>'visitscount'];*/
        $params['body']['aggs']['ip_lab']['terms']['script']['inline'] = "doc['ip'].value+'-split-'+ doc['house_id'].value";
        $params['body']['aggs']['ip_lab']['terms']['size'] = 100000000;
        $params['body']['aggs']['ip_lab']['aggs']['view']['sum'] = ['field'=>'visitscount'];
        $ress = $this->client->search($params);
        foreach ($ress['aggregations']['ip_lab']['buckets'] as $key => $value) {
          $sip = explode('-split-',$value['key'])[0];
          $shouse_id = explode('-split-',$value['key'])[1];
          $params[$key]['index'] = 'byzoro_active_ip';
          $params[$key]['type'] = 'log_active_ip';
          if(I('house_id')!=''){
               $house_id = I('house_id');
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
          }else{
            $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$shouse_id]];
          }
          if(I('ip')!=''){
             $ip = I('ip');
             $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$ip]];
          }else{
            $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$sip]];
          }
          if(I('is_seg')!=''){
             $is_seg = I('is_seg'); 
             $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['is_seg'=>$is_seg]];
          }
          if(I('block')!=''){
               $block = I('block');
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['block'=>$block]];
          }
          if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          } 
          
          $params[$key]['body']['sort'] = ['first_time'=>'desc'];
          
          
          $params[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          $params[$key]['body']['aggs']['first_time']['stats'] = ['field'=>'first_time']; 
          $params[$key]['body']['aggs']['last_time']['stats'] = ['field'=>'last_time']; 
          $a[$key] = $this->client->search($params[$key]);
          $resss[$key]['ip'] =$sip ;
          $resss[$key]['is_special'] =$a[$key]['hits']['hits'][0]['_source']['is_special'];
          $resss[$key]['views'] = $value['view']['value'];
          $resss[$key]['idc_id'] = $a[$key]['hits']['hits'][0]['_source']['idc_id'];
          $resss[$key]['house_id'] = $a[$key]['hits']['hits'][0]['_source']['house_id'];
          $resss[$key]['is_seg'] = $a[$key]['hits']['hits'][0]['_source']['is_seg'];
          $resss[$key]['protocol'] = $a[$key]['hits']['hits'][0]['_source']['protocol'];
          $resss[$key]['port'] = $a[$key]['hits']['hits'][0]['_source']['port'];
          $resss[$key]['block'] = $a[$key]['hits']['hits'][0]['_source']['block'];
          $resss[$key]['first_time'] = $a[$key]['aggregations']['first_time']['min_as_string'];
          $resss[$key]['last_time'] = $a[$key]['aggregations']['last_time']['max_as_string'];
         } 
         //dump($resss);die;
       // $count = $ress['hits']['total'];
        /*$Page  = new \Think\Page($count,12);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $params['from'] = $Page->firstRow;
        $params['size'] = $Page->listRows;
        $resss = $this->client->search($params);*/

        foreach($resss as $key=>$val){
          $res[$key] = $val;
            $res[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$val['house_id']))->select()[0]['house_name'];
            $res[$key]['is_seg'] = $this->info_value('is_seg',$val['is_seg']);
            $res[$key]['protocol'] = $this->info_value('protocol',$val['protocol']);
            $res[$key]['block'] = $this->info_value('block',$val['block']);
            $res[$key]['is_special'] = $val['is_special'] == 1?'是':'否';
        }
        //$show = $Page->show();
        //$this->assign('show', $show);
        $this->assign('res',$res);
        $this->assign('house_id',$house_id);
        $this->assign('is_seg',$is_seg);
        $this->assign('block',$block);
        $this->assign('ip',$ip);
        $this->assign('first_time',$first_time);
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        self::log('Web', '活跃资源IP查询', 5);
        Layout('Layout/layout');
        $this->display();
      
    }
    public function active_domain(){
        $params['index'] = 'byzoro_active_domain';
        $params['type'] = 'log_active_domain';
        $first_time = date('m/d/Y 00:00',time()).' - '.date('m/d/Y H:i',time());
        $start = strtotime(explode(' - ',$first_time)[0]);
        $end = strtotime(explode(' - ', $first_time)[1]);
        $start = date('Y-m-d H:i:s',$start);
        $end = date('Y-m-d H:i:s',$end);
       
        $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
        
        
        if($_GET){
            $where = [];
            if(I('house_id')!=''){
               $house_id = I('house_id');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('domain')!=''){
               $domain = I('domain');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
            }
            if(I('block')!=''){
               $block = I('block');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['block'=>$block]];
            }
            if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
              // dump($start);dump($end);
              $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
            }   
        } 
        $params['body']['aggs']['ip_domain_lab']['terms']['script']['inline'] = "doc['ip'].value+'-split-'+ doc['domain'].value +'-split-'+ doc['house_id'].value";
        $params['body']['aggs']['ip_domain_lab']['terms']['size'] = 100000000;
        //$params['body']['aggs']['ip_domain']['terms'] = ['field'=>'ip'];
        $params['body']['aggs']['ip_domain_lab']['aggs']['view']['sum'] = ['field'=>'visitscount'];
        $ress = $this->client->search($params);
        //dump($ress['aggregations']['ip_domain_lab']['buckets']);die;
        foreach ($ress['aggregations']['ip_domain_lab']['buckets'] as $key => $value) {
          $sdomain = explode('-split-',$value['key'])[1];
          //dump($sdomain);
          $sip = explode('-split-',$value['key'])[0];
          $shouse_id = explode('-split-',$value['key'])[2];
          if(I('house_id')!=''){
               $house_id = I('house_id');
               $para[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('domain')!=''){
               $domain = I('domain');
               $para[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
            }else{
              $para[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$sdomain]];
            }
            if(I('block')!=''){
               $block = I('block');
               $para[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['block'=>$block]];
            }
            if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          }
          $para[$key]['index'] = 'byzoro_active_domain';
          $para[$key]['type'] = 'log_active_domain';
          $para[$key]['body']['sort'] = ['first_time'=>'desc'];
          $para[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          $para[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$sip]];
          $para[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$shouse_id]];
          $para[$key]['body']['aggs']['first_time']['stats'] = ['field'=>'first_time']; 
          $para[$key]['body']['aggs']['last_time']['stats'] = ['field'=>'last_time']; 
          $a[$key] = $this->client->search($para[$key]);
          $resss[$key]['idc_id'] = $a[$key]['hits']['hits'][0]['_source']['idc_id'];
          $resss[$key]['block'] = $a[$key]['hits']['hits'][0]['_source']['block'];
          $resss[$key]['domain'] = explode('-split-',$value['key'])[1];
          //$resss[$key]['domain'] = $a[$key]['hits']['hits'][0]['_source']['domain'];
          $resss[$key]['port'] = $a[$key]['hits']['hits'][0]['_source']['port'];
          $resss[$key]['house_id'] = $a[$key]['hits']['hits'][0]['_source']['house_id'];
          $resss[$key]['topdomainflag'] = NUll?0:$a[$key]['hits']['hits'][0]['_source']['topdomainflag'];
          $resss[$key]['topdomain'] = $a[$key]['hits']['hits'][0]['_source']['topdomain'];
          $resss[$key]['first_time'] = $a[$key]['aggregations']['first_time']['min_as_string'];
          $resss[$key]['last_time'] = $a[$key]['aggregations']['last_time']['max_as_string'];
          $resss[$key]['ip'] = explode('-split-',$value['key'])[0];
          $resss[$key]['is_special'] =$a[$key]['hits']['hits'][0]['_source']['is_special'];
          $resss[$key]['views'] = $value['view']['value'];
        } 
        /*$count = $ress['hits']['total'];
        $Page  = new \Think\Page($count,12);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $params['from'] = $Page->firstRow;
        $params['size'] = $Page->listRows;
        $resss = $this->client->search($params);*/
        foreach($resss as $key=>$val){
            $res[$key] = $val; 
            $res[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$val['house_id']))->select()[0]['house_name'];
            $res[$key]['block'] = $this->info_value('block',$val['block']);
            $res[$key]['topdomainflag'] = $this->info_value('topdomainflag',$val['topdomainflag']);
            $res[$key]['is_special'] = $val['is_special'] == 1?'是':'否';
        }
       // dump($res);die;
        $this ->assign('res',$res);
        $this ->assign('house_id',$house_id);
        $this ->assign('domain',$domain);
        $this ->assign('block',$block);
        $this ->assign('first_time',$first_time);
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        self::log('Web', '活跃资源域名查询', 5);
        Layout('Layout/layout');
        $this->display();
    }

    public function filter_log(){
        $params['index'] = 'byzoro_isms';
        $params['type'] = 'log_isms';
        if($_GET){
            $where = [];
            if(I('house_id')!=''){
               $house_id = I('house_id');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('commandid')!=''){
               $commandid = I('commandid');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['commandid'=>$commandid]];
            }
            if(I('src_ip')!=''){
               $src_ip = I('src_ip'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['src_ip'=>$src_ip]];
            }
            if(I('dst_ip')!=''){
               $dst_ip = I('dst_ip');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['dst_ip'=>$dst_ip]];
            }
            if(I('src_port')!=''){
               $src_port = I('src_port'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['src_port'=>$src_port]];
            }
            if(I('dst_port')!=''){
               $dst_port = I('dst_port');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['dst_port'=>$dst_port]];
            }
            if(I('domain')!=''){
               $domain = I('domain');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
            }
            if(I('url')!=''){
               $url = I('url');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['url'=>$url]];
            }
            if(I('is_sc')!=''){
               $is_sc = I('is_sc');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['is_link'=>$is_sc]];
               
            }
            if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params['body']['query']['bool']['filter']['range']['gathertime'] = ['gte'=>$start,'lte'=>$end];
            }   
        } 
        $ress = $this->client->search($params); 
        //dump($ress);die; 
        $count = $ress['hits']['total'];
        $Page  = new \Think\Page($count,12);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $params['from'] = $Page->firstRow;
        $params['size'] = $Page->listRows;
        $resss = $this->client->search($params);

        foreach($resss['hits']['hits'] as $key=>$val){
            $res[$key] = $val['_source'];
            if($val['_source']['attachment_link'] !=''){
                $res[$key]['is_sc'] = '是';
            } else{
                $res[$key]['is_sc'] = '否';
            }
            $res[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$val['_source']['house_id']))->select()[0]['house_name'];
            $res[$key]['is_seg'] = $this->info_value('is_seg',$val['_source']['is_seg']);
            $res[$key]['protocol'] = $this->info_value('protocol',$val['_source']['protocol']);
            $res[$key]['block'] = $this->info_value('block',$val['_source']['block']);
           
        }
        $show = $Page->show();
        $this->assign('show', $show);
        $this->assign('res',$res);
        $this->assign('house_id',$house_id);
        $this->assign('commandid',$commandid);
        $this->assign('src_ip',$src_ip);
        $this->assign('src_port',$src_port);
        $this->assign('dst_ip',$dst_ip);
        $this->assign('dst_port',$dst_port);
        $this->assign('url',$url);
        $this->assign('domain',$domain);
        $this->assign('is_sc',$is_sc);
        $this->assign('first_time',$first_time);
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        self::log('Web', '监测过滤日志查询', 5);
        Layout('Layout/layout');
        $this->display();
    }
    public function txt_info(){
      $params['index'] = 'byzoro_isms';
      $params['type'] = 'log_isms';
      $logid = I('logid');
      $params['body']['query']['bool']['must'][] = ['match_phrase'=>['logid'=>$logid]];
      $ress = $this->client->search($params); 
      $file_path = './byzoro/snap/'.$ress['hits']['hits'][0]['_source']['content_link'];
      //$file_path = '/home/cutest/report/snap/1_0_252_1529548720_0.html';
      //$file_path = '/home/wangnuonuo/1.html';
      $contents = file_get_contents($file_path);
      //dump($file_path);die;
      $encode2 = mb_detect_encoding($contents,array('ASCII','UTF-8','GB2312','GBK','BIG5','CP936'));
      $contents = iconv($encode2, 'UTF-8', $contents);
      //dump($encode2);die;
      /*$preg = "/<script[\s\S]*?<\/script>/i";
      $preg2 = '/<img.*?src=\"(.*?)\".*?>/';
      $preg3 = '/<link(.*?)[^>]*>/i';
      $preg4 = '/<a.*?href="(.*?)".*?>*<\/a>/is';
      $contents = preg_replace($preg, '', $contents,20);
      $contents = preg_replace($preg2, '', $contents,50);
      $contents = preg_replace($preg3, '', $contents,20);
      $contents = preg_replace($preg4, '', $contents,20); */
     
      echo $contents;  
    }
    public function illegal_log(){
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit','-1'); // 不够继续加大   
        ini_set("display_errors","On");
        $params['index'] = 'log_illegalweb';
        $params['type'] = 'log_illegalweb';
        $first_time = date('m/d/Y 00:00',time()).' - '.date('m/d/Y H:i',time());
        $start = strtotime(explode(' - ',$first_time)[0]);
        $end = strtotime(explode(' - ', $first_time)[1]);
        $start = date('Y-m-d H:i:s',$start);
        $end = date('Y-m-d H:i:s',$end);
        $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];

        if($_GET){
            $where = [];
            if(I('house_id')!=''){
               $house_id = I('house_id');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('ip')!=''){
               $ip = I('ip');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$ip]];
            }
            if(I('port')!=''){
               $port = I('port'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['port'=>$port]];
            }
            if(I('domain')!=''){
               $domain = I('domain'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
            }
            if(I('block')!=''){
               $block = I('block');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['block'=>$block]];
            }  
            if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
            } 
        } 
        /*$params['body']['aggs']['domain']['terms'] = ['field'=>'domain'];
        $params['body']['aggs']['domain']['aggs']['view']['sum'] = ['field'=>'visitscount'];*/
        $params['body']['aggs']['domain_lab']['terms']['script']['inline'] = "doc['domain'].value+'-split-'+ doc['house_id'].value";
        $params['body']['aggs']['domain_lab']['terms']['size'] = 100000000;
        $params['body']['aggs']['domain_lab']['aggs']['view']['sum'] = ['field'=>'visitscount'];

        $ress = $this->client->search($params); 
        foreach ($ress['aggregations']['domain_lab']['buckets'] as $key => $value) {
          $params[$key]['index'] = 'log_illegalweb';
          $params[$key]['type'] = 'log_illegalweb';
          $resss[$key]['domain'] = explode('-split-',$value['key'])[0];
          $resss[$key]['views'] = $value['view']['value'];
          $sdomain = explode('-split-',$value['key'])[0];
          $shouse_id = explode('-split-',$value['key'])[1];
          if(I('house_id')!=''){
              $house_id = I('house_id');
              $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
          }else{
              $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$shouse_id]];
          }
          if(I('ip')!=''){
             $ip = I('ip');
             $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$ip]];
          }
          if(I('port')!=''){
             $port = I('port'); 
             $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['port'=>$port]];
          }
          if(I('domain')!=''){
            $domain = I('domain'); 
            $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
          }else{
            $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$sdomain]];
          }
          if(I('block')!=''){
             $block = I('block');
             $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['block'=>$block]];
          }
          if(I('first_time') !=''){
               $first_time = I('first_time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          }
          $params[$key]['body']['sort'] = ['first_time'=>'desc'];
          
          $params[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          $params[$key]['body']['aggs']['first_time']['stats'] = ['field'=>'first_time']; 
          $params[$key]['body']['aggs']['last_time']['stats'] = ['field'=>'last_time'];
          $a[$key] = $this->client->search($params[$key]);
          $resss[$key]['idc_id'] = $a[$key]['hits']['hits'][0]['_source']['idc_id'];
          $resss[$key]['illegal_type'] = $a[$key]['hits']['hits'][0]['_source']['illegal_type'];
          $resss[$key]['ip'] = $a[$key]['hits']['hits'][0]['_source']['ip'];
          $resss[$key]['house_id'] = $a[$key]['hits']['hits'][0]['_source']['house_id'];
          $resss[$key]['protocol'] = $a[$key]['hits']['hits'][0]['_source']['protocol'];
          $resss[$key]['port'] = $a[$key]['hits']['hits'][0]['_source']['port'];
          $resss[$key]['operation_account'] = $a[$key]['hits']['hits'][0]['_source']['operation_account'];
          $resss[$key]['service_content'] = $a[$key]['hits']['hits'][0]['_source']['service_content'];
          $resss[$key]['first_time'] = $a[$key]['aggregations']['first_time']['min_as_string'];
          $resss[$key]['last_time'] = $a[$key]['aggregations']['last_time']['max_as_string'];
          $resss[$key]['block'] = $a[$key]['hits']['hits'][0]['_source']['block'];
          /*if($resss[$key]['block'] == 1){
            $resss[$key]['operation_account'] = 'ISMS';
          }*/
        } 
       /* $count = $ress['hits']['total'];
        $Page  = new \Think\Page($count,12);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $params['from'] = $Page->firstRow;
        $params['size'] = $Page->listRows;
        $resss = $this->client->search($params);*/

        foreach($resss as $key=>$val){
            $res[$key] = $val; 
            $res[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$val['house_id']))->select()[0]['house_name'];
            $res[$key]['block'] = $this->info_value('block',$val['block']);
            $res[$key]['protocol'] = $this->info_value('protocol',$val['protocol']);
            $res[$key]['illegal_type'] = $this->info_value('illegal_type',$val['illegal_type']);
            $res[$key]['service_content'] = $this->info_value('service_content',$val['service_content']);
        }
       // dump($resss);die;
        $this->assign('res',$res);
        $this->assign('house_id',$house_id);
        $this->assign('ip',$ip);
        $this->assign('port',$port);
        $this->assign('domain',$domain);
        $this->assign('block',$block);
        $this->assign('first_time',$first_time);
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        self::log('Web', '违法违规日志查询', 5);
        Layout('Layout/layout');
        $this->display();
    }
    public function basic_abnormal(){
        $params['index'] = 'log_idcmonitor';
        $params['type'] = 'log_idcmonitor';
        $first_time = date('m/d/Y 00:00',time()).' - '.date('m/d/Y H:i',time());
        $start = strtotime(explode(' - ',$first_time)[0]);
        $end = strtotime(explode(' - ', $first_time)[1]);
        $start = date('Y-m-d H:i:s',$start);
        $end = date('Y-m-d H:i:s',$end);
        $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
       
        if($_GET){
            $where = [];
            if(I('house_id')!=''){
               $house_id = I('house_id');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }
            if(I('ip')!=''){
               $ip = I('ip');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$ip]];
            }
            if(I('port')!=''){
               $port = I('port'); 
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['port'=>$port]];
            }
            if(I('domain')!=''){
               $domain = I('domain');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
            }
            if(I('regerror')!=''){
               $regerror = I('regerror');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['regerror'=>$regerror]];
            }
            if(I('illegaltype')!=''){
               $illegaltype = I('illegaltype');
               $params['body']['query']['bool']['must'][] = ['match_phrase'=>['illegaltype'=>$illegaltype]];
            }
            if(I('time') !=''){
               $first_time = I('time');
               $start = strtotime(explode(' - ',$first_time)[0]);
               $end = strtotime(explode(' - ', $first_time)[1]);
               $start = date('Y-m-d H:i:s',$start);
               $end = date('Y-m-d H:i:s',$end);
               $params['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];

            }   
        } 
        $params['body']['aggs']['ip_lab']['terms']['script']['inline'] = "doc['ip'].value+'-split-'+ doc['house_id'].value";
        $params['body']['aggs']['ip_lab']['terms']['size'] = 100000000;
        $params['body']['aggs']['ip_lab']['aggs']['view']['sum'] = ['field'=>'visitscount'];
        $params['size']= 100000000;
        $ress = $this->client->search($params);
        foreach ($ress['aggregations']['ip_lab']['buckets'] as $key => $value) {
          $params[$key]['index'] = 'log_idcmonitor';
          $params[$key]['type'] = 'log_idcmonitor';
          $resss[$key]['ip'] = explode('-split-',$value['key'])[0];
          $resss[$key]['views'] = $value['view']['value'];
          $sip = explode('-split-',$value['key'])[0];
          $shouse_id = explode('-split-',$value['key'])[1];
          if(I('house_id')!=''){
               $house_id = I('house_id');
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$house_id]];
            }else{
              $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['house_id'=>$shouse_id]];
            }
            if(I('ip')!=''){
              $ip = I('ip');
              $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$ip]];
            }else{
              $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['ip'=>$sip]];
            }
            if(I('port')!=''){
               $port = I('port'); 
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['port'=>$port]];
            }
            if(I('domain')!=''){
               $domain = I('domain');
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['domain'=>$domain]];
            }
            if(I('regerror')!=''){
               $regerror = I('regerror');
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['regerror'=>$regerror]];
            }
            if(I('illegaltype')!=''){
               $illegaltype = I('illegaltype');
               $params[$key]['body']['query']['bool']['must'][] = ['match_phrase'=>['illegaltype'=>$illegaltype]];
            }
          $params[$key]['body']['sort'] = ['first_time'=>'desc'];
          $params[$key]['body']['query']['bool']['filter']['range']['first_time'] = ['gte'=>$start,'lte'=>$end];
          $params[$key]['body']['aggs']['first_time']['stats'] = ['field'=>'first_time']; 
          $params[$key]['body']['aggs']['last_time']['stats'] = ['field'=>'last_time']; 
          $a[$key] = $this->client->search($params[$key]);
          $resss[$key]['idc_id'] = $a[$key]['hits']['hits'][0]['_source']['idc_id'];
          $resss[$key]['domain'] = $a[$key]['hits']['hits'][0]['_source']['domain'];
          $resss[$key]['house_id'] = $a[$key]['hits']['hits'][0]['_source']['house_id'];
          $resss[$key]['protocol'] = $a[$key]['hits']['hits'][0]['_source']['protocol'];
          $resss[$key]['port'] = $a[$key]['hits']['hits'][0]['_source']['port'];
          $resss[$key]['operation_account'] = $a[$key]['hits']['hits'][0]['_source']['operation_account'];
          $resss[$key]['service_content'] = $a[$key]['hits']['hits'][0]['_source']['service_content'];
          $resss[$key]['first_time'] = $a[$key]['aggregations']['first_time']['min_as_string'];
          $resss[$key]['last_time'] = $a[$key]['aggregations']['last_time']['max_as_string'];
          $resss[$key]['block'] = $a[$key]['hits']['hits'][0]['_source']['block'];
          $resss[$key]['regerror'] = $a[$key]['hits']['hits'][0]['_source']['regerror'];
          $resss[$key]['regdomain'] = $a[$key]['hits']['hits'][0]['_source']['regdomain'];
          $resss[$key]['currentstate'] = $a[$key]['hits']['hits'][0]['_source']['currentstate'];
          $resss[$key]['illegaltype'] = $a[$key]['hits']['hits'][0]['_source']['illegaltype'];
          /*if($resss[$key]['currentstate'] == 1){
            $resss[$key]['operation_account'] = 'ISMS';
          }*/

        } 
        //dump($resss);die;  
        /*$count = $ress['hits']['total'];
        $Page  = new \Think\Page($count,12);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $params['from'] = $Page->firstRow;
        $params['size'] = $Page->listRows;
        $resss = $this->client->search($params);*/

        foreach($resss as $key=>$val){
            $res[$key] = $val; 
            $res[$key]['house_name'] = M('basic_house')->where(array('house_id'=>$val['house_id']))->select()[0]['house_name'];
            $res[$key]['protocol'] = $this->info_value('protocol',$val['protocol']);
            $res[$key]['block'] = $this->info_value('block',$val['block']);
            $res[$key]['illegaltype'] = $this->info_value('illegal_type',$val['illegaltype']);
            $res[$key]['regerror'] = $this->info_value('regerror',$val['regerror']);
            $res[$key]['currentstate'] = $this->info_value('currentstate',$val['currentstate']);
        }

        $this->assign('res',$res);
        $this->assign('house_id',$house_id);
        $this->assign('regerror',$regerror);
        $this->assign('time',$first_time);
        $this->assign('illegaltype',$illegaltype);
        $this->assign('ip',$ip);
        $this->assign('port',$port);
        $this->assign('domain',$domain);
        $rooms = M('basic_house')->select();
        $this->assign('rooms',$rooms);
        self::log('Web', '基础数据异常日志查询', 5);
        Layout('Layout/layout');
        $this->display();
    }
    public function isms_status(){
      if($_GET){
        if(I('time') !=''){
          $time = I('time');
          $times = explode(' - ', $time);
          $where['timestamp'] = ['between', [strtotime($times[0]), strtotime($times[1])]];
        }
        
      }
      $count = M('log_active_state')->where($where)->count();
      $Page  = new \Think\Page($count,8);// 实例化分页类 传入总记录数
        $Page->setConfig('theme', "<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
      $result = M('log_active_state')->where($where)->order('timestamp desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
      foreach ($result as $key => $value) {
        $result[$key]['idc_name'] = M('basic_idc')->where(array('idc_id'=>$value['idc_id']))->select()[0]['idc_name'];
      }
      $show = $Page->show();
      $this->assign('show', $show);
      $this->assign('time', $time);
      $this->assign('res',$result);
      self::log('Web', 'ISMS活动状态上报查询', 5);
      Layout('Layout/layout');
      $this->display();
    }

    //参数管理
  public function info_value($col,$value){
    $c = [        
        'protocol'  => function($v){
            $cc = [
              '1'                                   => 'TCP',
              '2'                                   => 'UDP',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'is_seg'  => function($v){
            $cc = [
              '0'                                   => '已上报',
              '1'                                   => '未上报',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'block'  => function($v){
            $cc = [
              '0'                                   => '未阻断',
              '1'                                   => '阻断',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'currentstate'  => function($v){
            $cc = [
              '0'                                   => '未处置',
              '1'                                   => '已处置',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'topdomainflag'  => function($v){
            $cc = [
              '0'                                   => '顶级域名',
              '1'                                   => '非顶级域名',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'illegal_type'  => function($v){
            $cc = [
              '0'                                   => '正常',
              '1'                                   => '未备案',
              '2'                                   => '违法网站',
              '999'                                 => '其他',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'regerror'  => function($v){
            $cc = [
              '0'                                   => '正常',
              '1'                                   => 'IP登记保留，实际为占用',
              '2'                                   => 'IP登记域名有误',
              '3'                                   => '未登记',

            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
        'service_content'  => function($v){
            $cc = [
              '500'                                   => '基础应用',
              '501'                                   => '网络媒体',
              '502'                                   => '电子政务、电子商务',
              '503'                                   => '数字娱乐',
              '504'                                   => '其他',
              '1'                                     => '即时通信',
              '2'                                     => '搜索引擎',
              '3'                                     => '综合门户',
              '4'                                     => '网上邮局',
              '5'                                     => '网络新闻',
              '6'                                     => '博客/个人空间',
              '7'                                     => '网络广告/信息',
              '8'                                     => '单位门户网站',
              '9'                                     => '网络购物',
              '10'                                    => '网上支付',
              '11'                                    => '网上银行',
              '12'                                    => '网上炒股/股票基金',
              '13'                                    => '网络游戏',
              '14'                                    => '网络音乐',
              '15'                                    => '网络影视',
              '16'                                    => '网络图片',
              '17'                                    => '网络软件/下载',
              '18'                                    => '网上求职',
              '19'                                    => '网上交友/婚介',
              '20'                                    => '网上房产',
              '21'                                    => '网络教育',
              '22'                                    => '网站建设',
              '23'                                    => 'WAP',
              '24'                                    => '其他',


            ];
            return isset($cc[$v]) ? $cc[$v] : $v;
        },
    ];
    return isset($c[$col]) ? $c[$col]($value) : $value;
  }



}