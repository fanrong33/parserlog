<?php
/**
 * 日志存储客户端示例
 * @author 蔡繁荣
 * @version 1.0.1 build 20171021
 */
date_default_timezone_set('PRC');

/* 模拟ThinkPHP框架的C函数 */
function C($name){
    return '';
}



// click日志打点，写入日志到log文件
click_log('3529322', '6700912');


/**
 * Campaign ----->  LP  --here-->  Offer
 * @param  string $referrer      从Campaign URL的referrer，真正的referrer, 而不是LP的URL
 */
function click_log($campaign_id, $offer_id){

    $timestamp   = microtime(true);
    
    $user_agent  = $_SERVER['HTTP_USER_AGENT'];
    $language    = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    // 在存入日志文件时，就将其中的信息解析为需要的字段，否则在主服务器集中处理那么多数据的时候非常影响性能！
    
    // $ip
    // $isp
    // $country
    // $city
    // $region

    // click log
    // timestamp  ip  clickid  cid  lp  offer  request_uri  user_agent  language  

    // dump($user_agent);
    // $user_agent =  'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Mobile Safari/537.36';
    // $which_browser = get_which_browser($user_agent);


    // $device_type     = $which_browser->device->type;
    // $device_model    = $which_browser->device->manufacturer.' '.$which_browser->device->model;
    
    // $os              = $which_browser->os->name;
    // $os_version      = $which_browser->os->name.' '.$which_browser->os->version->toString();
    
    // $browser         = $which_browser->browser->name;
    // $browser_version = $which_browser->browser->name.' '.intval($which_browser->browser->version->toString());
    

    // 1. 获取当前时间的时间片段
    $fragment_time = get_fragment_time($timestamp, 5);
    

    $log_list = array(
        $timestamp, $campaign_id, $offer_id, $user_agent, $language
    );
    // 使用\t分隔符分隔
    $log_info = join("\t", $log_list);
    

    // 按支持分布式规则的命名，写入日志文件
    $server_ip = $_SERVER['SERVER_ADDR'];
    $file = date('Ymd').'/linksvr_'.$server_ip.'_'.date('Ymd').'_'.$fragment_time.'_click.log';
    // 20171020/linksvr_127.0.0.1_20171020_1508485800_click.log
    write($log_info, $file);
}


/**
 * 获取传入时间戳所在的时间片段
 * @param  integer $timestamp           时间戳
 * @param  integer $interval_minutes    间隔时间, 例如5，则为每5分钟保存一次日志
 */
function get_fragment_time($timestamp, $interval_minutes = 5){
    
    $prefix = date('Y-m-d H:', $timestamp);
    $ratio  = floor(date('i', $timestamp)/$interval_minutes);
    $middle = sprintf('%02d', $ratio*$interval_minutes);
    /*
        00-04:59
        05-09:59
        10-14:59
        15-19:59
        20-24:59
        25-29:59
        30-34:59
        35-39:59
        40-44:59
        45-49:59
        50-54:59
        55-59:59
    */
    $fragment_date = $prefix.$middle.':00';
    $fragment_time = strtotime($fragment_date);
    return $fragment_time;
}


/**
 * 日志直接写入
 * @param string  $message      日志信息
 * @param string  $destination  写入目标
 */
function write($message, $destination='') {
    $now = date('[ c ]');

    if(empty($destination))
        $destination = C('LOG_PATH').date('y_m_d').'.log';

    $dir = dirname($destination);
    if(!is_dir($dir)){
        $d = mkdir($dir, 0755, true);
    }

    //检测日志文件大小，超过配置大小则备份日志文件重新生成
    // if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination) )
    //       rename($destination,dirname($destination).'/'.time().'-'.basename($destination));

    // error_log("{$now} {$level}: {$message}\r\n", 3, $destination, $extra);
    error_log("{$message}\r\n", 3, $destination);
    //clearstatcache();
}

