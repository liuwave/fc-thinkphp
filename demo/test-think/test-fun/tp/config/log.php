<?php

// +----------------------------------------------------------------------
// | 日志设置
// +----------------------------------------------------------------------
return [
    // 默认日志记录通道
  'default'      => env('log.channel', 'sls'),
    // 日志记录级别
  'level'        => [],
    // 日志类型记录的通道 ['error'=>'email',...]
  'type_channel' => [],
    // 关闭全局日志写入
  'close'        => false,
    // 全局日志处理 支持闭包
  'processor'    => null,
    
    // 日志通道列表
  'channels'     => [
    'file' => [
        // 日志记录方式
      'type'           => 'File',
        // 日志保存目录
      'path'           => '',
        // 单文件日志写入
      'single'         => false,
        // 独立日志级别
      'apart_level'    => [],
        // 最大日志文件数量
      'max_files'      => 0,
        // 使用JSON格式记录
      'json'           => false,
        // 日志处理
      'processor'      => null,
        // 关闭通道日志写入
      'close'          => false,
        // 日志输出格式化
      'format'         => '[%s][%s] %s',
        // 是否实时写入
      'realtime_write' => false,
    ],
      // 其它日志通道配置
    'sls'  => [
      'type'           => \liuwave\think\log\driver\Sls::class,
      'debug'          => false,
      'json'           => false,
        // 关闭通道日志写入
      'close'          => false,
        // 是否实时写入
      'realtime_write' => true,
      
      'use_fc'      => true,//是否使用函数计算内置的 fcLogger，
        //仅在函数计算 php runtime cli模式下有效，若为true,则以下配置可不填
        //若设置为true，但不在函数计算 cli模式下，会尝试使用日志服务 php SDK
        //参见[相关参考](#相关参考)
      'source'      => 'think_dev_master',//来源
      'credentials' => false, //如设置为false，且不在函数计算环境下，则不会写入日志
    
    ],
  ],

];
