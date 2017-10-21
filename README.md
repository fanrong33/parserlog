# parselog


![image](https://github.com/fanrong33/parserlog/blob/master/parserlog.png)

#### 服务整体设计


  1. [client] 写日志到log文件
  2. [client] 同步日志文件到主服务器
  3. [client] -- 定期自动清理log文件
  4. [master] 分析日志进行统计汇总，写入报表等


## Contributing
Pull requests are the way to go here.
