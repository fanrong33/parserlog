<?php

/**
 * SSDB缓存管理
 * @param mixed $name 缓存名称，如果为数组表示进行缓存设置
 * @return mixed
 */
function get_mysql() {
    static $mysql = '';
    if(empty($mysql)) { // 自动初始化

        require_once dirname(__FILE__).'/../Core/MySQL.class.php';
        $config = include dirname(__FILE__)."/../Conf/config.php";
        try{
            $mysql = new MySQL($config['DB_NAME'], $config['DB_USER'], $config['DB_PWD'], $config['DB_HOST'], $config['DB_PORT']);
        }catch(Exception $e){
            // TODO 为失败编程
            die(__LINE__ . ' ' . $e->getMessage());
        }
    }
    return $mysql;
}


/**
 * 获取传入时间戳所在的时间片段的时间戳
 * @param  integer $timestamp           时间戳
 * @param  integer $interval_minutes    间隔时间, 例如5，则为每5分钟保存一次日志
 * 例如：当前时间: 2017-10-20 20:32:51，间隔10分钟
 *      则时间片段为: 2017-10-20 20:30:00
 *      返回时间戳: 1508502600
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
 * 读取超大文件
 * @param  string  $filename  文件名
 * @param  string  $tag       行分隔符 注意这里必须用双引号
 * @param  integer $start     起始位置
 * @param  integer $line_num  读取行数
 * @return array
 */
function read_big_file($filename, $tag = "\r\n", $start=0, $line_num = 200) {
    $content     = "";      // 最终内容，组装后的
    $raw_content = ""; // 
    $current     = "";      // 当前读取内容寄存 
    $step        = 1;       // 每次走多少字符 
    $tagLen      = strlen($tag); 
    // $start    = 0;       // 起始位置
    $i           = 0;       // 计数器 

    $handle = fopen($filename,'r+');//读写模式打开文件，指针指向文件起始位置 
    while($i < $line_num && !feof($handle)) { 
        fseek($handle, $start, SEEK_SET);//指针设置在文件开头 
        $current = fread($handle, $step);//读取文件 
        $content .= $current;//组合字符串
        $start += $step;//依据步长向前移动 

        $raw_content .= $current;

        //依据分隔符的长度截取字符串最后免得几个字符 
        $substrTag = substr($content, -$tagLen); 
        if ($substrTag == $tag) { //判断是否为判断是否是换行或其他分隔符 
            $i++;
            $content .= "<br/>"; // !?
        }
    }

    // 关闭文件 
    fclose($handle); 


    $possible_line_ends = array("\r\n", "\n\r", "\r");
    $raw_content = str_replace($possible_line_ends, "\n", $raw_content);
    $line_list   = explode("\n", $raw_content);
    $line_list   = array_filter($line_list);

    // 返回结果 
    $result = array(
        'content'   => $content, 
        'line_list' => $line_list,
        'end'       => $start
    );
    return $result;
}


