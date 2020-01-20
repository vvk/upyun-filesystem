<h1 align="center">Laravel 又拍云驱动</h1>

## 环境要求
- php >= 7.2

## 安装
通过 `cpomoser` 命令安装：
```$xslt
$ composer require vvk/upyun-filesystem
```
## 使用
1. 在`config/filesystems.php` 中添加又又拍云驱动相关账号配置：
```php
'disks' => [
    ...
    'upyun' => [
        'driver' => 'upyun',
        'service' => env('UPYUN_SERVICE', ''),
        'operator' => env('UPYUN_OPERATOR', ''),
        'password' => env('UPYUN_PASSWORD', ''),
        'domain' => env('UPYUN_DOMAIND', ''),
    ],
],
``` 
2. 目前支持的方法有：
```php
Storage::disk('upyun')->has('a.png');
Storage::disk('upyun')->get('a.png');
Storage::disk('upyun')->write('a.png', file_get_contents('a.png'));
Storage::disk('upyun')->writeStream('a.png', fopen('path/a.png', 'r'));
Storage::disk('upyun')->update('a.png', file_get_contents('a.png'));
Storage::disk('upyun')->updateStram('a.png', file_get_contents('a.png'));
Storage::disk('upyun')->rename('old.png', 'new.png');
Storage::disk('upyun')->copy('a.png', 'new.png');
Storage::disk('upyun')->delete('a.png');
Storage::disk('upyun')->deleteDir('/a');//非空目录不能删除
Storage::disk('upyun')->createDir('/a/b/c');
Storage::disk('upyun')->read('a.png');
Storage::disk('upyun')->readStream('a.png');
Storage::disk('upyun')->listContents('/');
Storage::disk('upyun')->getUrl('/a.png');
Storage::disk('upyun')->getType('/a.png');
Storage::disk('upyun')->getMetadata('/a.png');
Storage::disk('upyun')->getSize('/a.png');
Storage::disk('upyun')->getMimetype('/a.png');
Storage::disk('upyun')->getTimestamp('/a.png');
```
## License
MIT
