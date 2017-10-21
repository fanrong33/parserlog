<?php
/**
 * 解析track点击日志文件 任务计划
 * @author 蔡繁荣
 * @version 1.1.0 build 20171020
 */

/*
服务部署目录： 日志分析服务器 /data/www/command_traking

vi /etc/crontab
4,9,14,19,24,29,34,39,44,49,54,59 * * * * root /usr/bin/php /data/www/command_traking/LogProcess/cron_click.php >> /data/www/command_traking/Logs/click_$(date +\%Y\%m\%d).log 2>&1
4,9,14,19,24,29,34,39,44,49,54,59 * * * * root /usr/bin/php /data/www/command_traking/LogProcess/cron_click.php  2>&1 > /dev/null &
确保上一时间片段的日志文件同步完全
*/
/**
 * TODO: 
 * 对失败操作情况的后续处理，所以其实还是需要记录操作日志的
 * php服务宕机情况，服务对log文件进行补处理
 */
error_reporting(0);
set_time_limit(0);
date_default_timezone_set('PRC');


require_once dirname(__FILE__)."/../Common/common.php";

$log_dir = dirname(__FILE__).'/Logs/';



while(true){


    // 1、读取日志文件目录，获取当前时间要处理的上一个时间片段的日志文件列表（多域名分布式）
    echo sprintf("------------------------------------ \n");
    echo sprintf("1、读取日志文件目录，获取当前时间要处理的上一个时间片段的日志文件 \n");
    $timestamp = time();
    $current_date = date('Y-m-d H:i:s', $timestamp);
    echo sprintf("当前时间: %s \n", $current_date);
    

    $interval_minutes = 5; // 因为为每5分钟保存一次日志
    $fragment_time = get_fragment_time($timestamp, $interval_minutes);
    // 获取当前时间要处理的上一个时间片段
    $previous_fragment_timestamp = $fragment_time - $interval_minutes*60;
    echo sprintf("要处理的上个时间片段: %s \n", date('Y-m-d H:i:s', $previous_fragment_timestamp));


    /** TODO 模拟测试 */
    // $previous_fragment_timestamp = 1508556000;


    $current_file_num    = 0;
    $target_logfile_list = get_logfile_list($log_dir, $previous_fragment_timestamp);
    $file_count          = count($target_logfile_list);


    ///////////////////////////////////////////////////////////////
    /// 循环判断日志文件是否已处理
    foreach ($target_logfile_list as $target_logfile) {

        $t1 = microtime(true);
        $current_file_num++;
        echo sprintf("当前处理第 %s/%s 个文件 \n", $current_file_num, $file_count);


        // 2、读取日志文件内容
        $result = read_log_file($target_logfile, $current_file_num, $file_count);


        // 3、进行汇总统计，累加计算
        echo sprintf("3、进行累加计算\n");
        $map_campaign_offer = array();
        process($result, $map_campaign_offer);


        // 4、汇总日志分析数据到report
        write_to_report($map_campaign_offer, $previous_fragment_timestamp);


        $t2 = microtime(true);
        $cost_time = round($t2-$t1, 3);

        // 5、写入当前大文件读取指针位置到log_process
        update_log_process_position($target_logfile, $result, $cost_time);

    } // end of foreach ($target_logfile_list as $target_logfile)
    ///////////////////////////////////////////////////////////////


}// while(true){
$mysql = get_mysql();
$mysql->closeConnection();






function get_logfile_list($log_dir, $previous_fragment_timestamp){
    
    $date_str = date('Ymd', $previous_fragment_timestamp);
    
    // linksvr_127.0.0.1_20171020_1508487000_click
    // 判断目录是否存在
    if(!is_dir($log_dir.$date_str)){
        echo sprintf("目录不存在: %s \n", $log_dir.$date_str);
        break;
    }

    // 罗列目录下所有的click.log文件，支持多域名track节点的日志文件解析
    $dir = opendir($log_dir.$date_str);

    $target_logfile_list = array();
    while(false !== ($file=readdir($dir))){
        if($file != "." && $file != ".."){
            // 判断是否正则匹配
            $pattern = "/^linksvr_(\d{1,3}\.\d{1,3}\.\d{1,3}.\d{1,3})_".$date_str."_".$previous_fragment_timestamp."_click.log$/i";
            preg_match($pattern, $file, $matches);
            if($matches){
                $target_logfile_list[] = $log_dir.$date_str.'/'.$file;
            }
        }
    }
    closedir($dir);


    if(!$target_logfile_list){
        echo sprintf("没有需要处理的日志文件\n");
        break;
    }
    return $target_logfile_list;
}


function read_log_file($target_logfile, $current_file_num, $file_count){
    $mysql = get_mysql();

    $filename = basename($target_logfile); // 需要读取的文件， linksvr1_20170105_1483588800_click.log
    echo sprintf("日志文件为: %s \n", $filename);


    // 2、从该数据库中获取当前文件最后读取的指针位置，没有则为0
    $log_process = $mysql->executeSQL("select * from t_log_process where filename='{$filename}'");
    if(is_array($log_process)){
        if($log_process['is_end'] == 1){
            echo sprintf("日志文件已解析完。\n");
            if($current_file_num == $file_count){
                // break(2);
            }else{
                // continue; // break
            }
        }

        $start = $log_process['current_position'];
        echo sprintf("上次解析到位置, position: %s \n", $start);
    }else{
        $start = 0;
        echo sprintf("第一次解析日志\n");
    }

    $tag   = "\n"; // 行分隔符 注意这里必须用双引号
    $count = 5000; // 读取行数

    
    echo sprintf("开始解析日志, start: %s, count: %s \n", $start, $count);
    $result = read_big_file($target_logfile, $tag, $start, $count);

    echo sprintf("解析完成, end: %d \n", $result['end']);
    if($result['content'] == ''){
        echo sprintf("解析内容为空, break。\n");

        $update_time = time();
        $mysql->executeSQL("update t_log_process set is_end=1, update_time={$update_time} where id={$log_process['id']}");
        echo $current_file_num.', '.$file_count;
        if($current_file_num == $file_count){
            // break(2);
            continue;
        }else{
        }
    }
    return $result;
}

/**
 * 统计汇总展示数
 */
function process($data, &$map_campaign_offer){

    $key_list = array('timestamp', 'campaign_id', 'offer_id', 'user_agent', 'language');
    foreach ($data['line_list'] as $key => $line) {
        $array = split("\t", $line);
        $raw = array_combine($key_list, $array);
        
        // campaign - offer维度
        $map_key = $raw['campaign_id'].'_'.$raw['offer_id'];
        if(!isset($map_campaign_offer[$map_key])){
            $map_campaign_offer[$map_key] = array( 'clicks' => 1, 'cost' => $raw['cpc'] );
        }else{
            $map_campaign_offer[$map_key] = array(
                'clicks' => $map_campaign_offer[$map_key]['clicks']+1,
                'cost'   => $map_campaign_offer[$map_key]['cost']+$raw['cpc'],
            );
        }
    }
    
}


function write_to_report($map_campaign_offer, $previous_fragment_timestamp){
    echo sprintf("4、开始汇总日志分析数据到report表\n");
    $hour_timestamp = strtotime(date('Y-m-d H:00:00', $previous_fragment_timestamp));

    $mysql = get_mysql();
    // campaign - offer维度的点击日志统计汇总
    foreach ($map_campaign_offer as $key => $summary_data) {
        list($campaign_id, $offer_id) = explode("_", $key);


        $cond = array(
            'aff_id' => $campaign_id,
            'offer_id'    => $offer_id,
            
            'timestamp'   => $hour_timestamp,
        );
        $report = $mysql->select('t_report', $cond);

        echo sprintf("4.2、循环判断该报告点report是否存在, aff_id: %s, offer_id: %s, timestamp: %s\n", $campaign_id, $offer_id, $hour_timestamp);
        if($report){
            echo sprintf("该报告点存在, 开始更新report, clicks+%s, cost+%s \n", $summary_data['clicks'], $summary_data['cost']);

            $update_time = time();
            $sql ="UPDATE `t_report` SET `clicks`=clicks+{$summary_data['clicks']},`update_time`={$update_time} WHERE ( `id` = {$report['id']} )";

            // `cost`=cost+{$summary_data['cost']}, 
            $effect = $mysql->executeSQL($sql);
            if($effect){
                echo sprintf("更新成功, report id: %d \n", $report['id']);
            }else{
                echo sprintf("更新失败, report id: %d \n", $report['id']);
            }
        }else{
            echo sprintf("该报告点不存在, 开始新增report, clicks+%s, cost+%s \n", $summary_data['clicks'], $summary_data['cost']);

            $data = array(
                'aff_id'        => $campaign_id,
                'offer_id'      => $offer_id,
                'timestamp'     => $hour_timestamp,
                
                'clicks'        => $summary_data['clicks'],
                // 'cost'          => $summary_data['cost'],
                
                'update_time'   => time(),
                'create_time'   => time(),
            );
            $effect = $mysql->insert('t_report', $data);
            if($effect){
                echo sprintf("新增成功， report id: %d \n", $mysql->lastInsertID());
            }else{
                echo sprintf("新增失败, 可能已经存在, 重试一次 \n");
            }
        }
    }
}

function update_log_process_position($target_logfile, $result, $cost_time){
    echo sprintf("5、开始写入当前大文件读取指针位置到log_process\n");

    $filename = basename($target_logfile); // 需要读取的文件， linksvr1_20170105_1483588800_click.log

    $mysql = get_mysql();
    $log_process = $mysql->executeSQL("select * from t_log_process where filename='{$filename}'");

    $current_position = $result['end'];
    $is_end           = $result['content'] == '' ? 1 : 0;

    if(is_array($log_process)){ // 注意！一定要if(is_array($log_process)), 而不是if($log_process), 因为函数执行成功都会返回true
        $update_time = time();

        $sql = <<<EOF
            UPDATE `t_log_process` 
            SET `current_position`={$current_position}, `is_end`={$is_end}, `cost_time`=cost_time+{$cost_time}, `update_time`={$update_time}
            WHERE ( `id` = {$log_process['id']} )
EOF;
        $mysql->executeSQL($sql);
    }else{
        $data = array(
            'filename'         => $filename,
            'filesize'         => filesize($target_logfile),
            'current_position' => $current_position,
            'is_end'           => $is_end,
            
            'cost_time'        => $cost_time,
            'update_time'      => time(),
            'create_time'      => time()
        );
        $mysql->insert('t_log_process', $data);
    }

    echo sprintf("php运行时间消耗 %s 秒。\n", $cost_time);
}
