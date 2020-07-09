<?php

return [
    // 默认磁盘
  'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
  'disks'   => [
    'local'  => [
      'type' => 'local',
      'root' => app()->getRuntimePath().'storage',
    ],
    'public' => [
        // 磁盘类型
      'type'       => 'local',
        // 磁盘路径
      'root'       => app()->getRootPath().'public/storage',
        // 磁盘路径对应的外部URL路径
      'url'        => '/storage',
        // 可见性
      'visibility' => 'public',
    ],
      // 更多的磁盘配置信息
    
    'oss' => [
      'type'        => \liuwave\think\filesystem\driver\Oss::class,
      'credentials' => false,
//      'credentials' => [//为false,则使用函数计算 runtime context提供的 credentials
//        'accessId'     => '',
//        'accessSecret' => '******',//使用函数计算credentials时，可以为空
//      ],
      
      'bucket'   => 'oss-test-for-all',
      'endpoint' => 'oss-cn-beijing-internal.aliyuncs.com',
      'url'      => '//oss-test-for-all.oss-cn-beijing.aliyuncs.com',
    ],
  ],
];
