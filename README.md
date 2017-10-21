# parselog
在并发量很大情况下，要实现可用的报表功能，为保证性能，采用日志文件存储分析的方式来实现，而不是使用数据库来存储元数据。这是一个该需求的简单实现。

#### 核心思想
简化模型，比如只统计offer的点击数
![image](WechatIMG113.jpg)


#### 服务整体设计


  1. [client] 写基础信息日志到log文件
  2. [client] 同步日志文件到主服务器
  3. [client] -- 定期自动清理log文件
  4. [master] 分析日志进行统计汇总，写入报表等


#### 同步服务架构
![image](parserlog.png)

#### 分析服务后台视图
![image](logprocess.png)

## Contributing
Pull requests are the way to go here.
