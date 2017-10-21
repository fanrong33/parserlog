#!/usr/bin/python
# encoding: utf-8
# -*- coding: utf-8 -*-
# 自动删除tracking追踪系统前天的log日志文件 任务计划
# @author 蔡繁荣
# @version 1.0.2 build 20170515

"""
# 服务部署目录 tracking服务器 /data/www/command_gotraking
# 服务器python env: 2.6.6 

/etc/crontab
# 每天0点1分自动删除前天的日志文件
1 0 * * * root /usr/bin/python /data/www/command_gotraking/RemoveDir/cron_auto_remove_dir.py  2>&1 > /dev/null &
"""


from datetime import datetime
from datetime import timedelta

import os


def nukedir(dir):
    if dir[-1] == os.sep: dir = dir[:-1]
    files = os.listdir(dir)
    for file in files:
        if file == '.' or file == '..': continue
        path = dir + os.sep + file
        if os.path.isdir(path):
            nukedir(path)
        else:
            os.unlink(path)
    os.rmdir(dir)


# 获取前天的日期，今天5月3号0点，删除5月1号的日志
now = datetime.now()
datetime = now - timedelta(days=2)
date_dir = datetime.strftime('%Y%m%d')


# 删除前天的目录文件夹及文件夹下所有文件
if date_dir != '':
    nukedir('/data/www/tracking.xxx.com/Tracking/Runtime/Logs/%s' % date_dir)
    print("remove dir /data/www/tracking.xxx.com/Tracking/Runtime/Logs/%s" % date_dir)

