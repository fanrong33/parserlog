<?php
/**
* 字节格式化 把字节数格式为 B K M G T 描述的大小
* @return  string
*/
function byte_format($size , $dec=2){
    $a = array("B", "KB", "MB" , "GB" , "TB" , "PB" );
    $pos = 0;
    while ( $size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec)." " .$a[$pos];
}

