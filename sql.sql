CREATE TABLE `t_log_process` (                         
                 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,       
                 `filename` varchar(255) DEFAULT '',                  
                 `filesize` varchar(50) DEFAULT '',                   
                 `current_position` int(11) DEFAULT '0',              
                 `is_end` tinyint(1) DEFAULT '0',                     
                 `cost_time` decimal(10,3) DEFAULT '0.000',           
                 `update_time` int(10) DEFAULT '0',                   
                 `create_time` int(10) DEFAULT '0',                   
                 PRIMARY KEY (`id`)                                   
               ) ENGINE=MyISAM


 CREATE TABLE `t_report` (                                      
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,            
            `offer_id` int(11) DEFAULT '0',                              
            `clicks` int(11) DEFAULT '0',                                
            `timestamp` int(10) DEFAULT '0',                             
            `update_time` int(10) DEFAULT '0',                           
            `create_time` int(10) DEFAULT '0',                           
            PRIMARY KEY (`id`)                                         
          ) ENGINE=MyISAM