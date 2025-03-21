<?php

// 数据库配置
$config = include __DIR__ . '/config.php';
$targetDir = isset($argv[1]) ? rtrim($argv[1], '/\\') : __DIR__;
$targetDir = $targetDir. '/phpstorm_meta';

// 如果目录存在则递归删除
if (is_dir($targetDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        if ($fileinfo->isDir()) {
            rmdir($fileinfo->getRealPath());
        } else {
            unlink($fileinfo->getRealPath());
        }
    }
    rmdir($targetDir);
}

try {
    // 连接数据库
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 获取所有表名
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // 为每个表创建目录
        // 获取命令行参数中的目标目录，如果没有则使用当前目录下的meta文件夹
        $dirPath = $targetDir . '/' . $table;
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        // 获取表的所有字段信息
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

        // 读取模板文件
        $metaTemplate = file_get_contents(__DIR__ . '/templates/phpstorm.meta.php.tpl');
        $modelTemplate = file_get_contents(__DIR__ . '/templates/model.php.tpl');

        // 准备替换变量
        $className = ($table) . $config['modelSuffix'];
        
        // 处理属性文档和属性定义
        $propertyDocs = '';
        // $properties = '';
        foreach ($columns as $column) {
            // 将MySQL类型转换为PHP类型
            $phpType = strtolower(preg_replace('/\(.*\)/', '', $column['Type']));
            switch ($phpType) {
                case 'int':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'bigint':
                    $phpType = 'int';
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $phpType = 'float';
                    break;
                case 'datetime':
                case 'timestamp':
                case 'date':
                case 'time':
                    $phpType = 'string';
                    break;
                default:
                    $phpType = 'string';
            }
            
            // 判断字段是否允许为空
            $nullableStr = '';
            if ($column['Null'] === 'YES') {
                $nullableStr = '|null';
            }

            $comment = $column['Comment']??'';
            $propertyDocs .= " * @property {$phpType}{$nullableStr} {$column['Field']} {$comment}\n";
            // $properties .= "    product \${$column['Field']};\n";
        }

        // 替换meta文件模板中的变量
        $metaContent = str_replace(
            ['{{table_name}}', '{{class_name}}'],
            [$table, $className],
            $metaTemplate
        );

        // 替换模型类模板中的变量
        $modelContent = str_replace(
            // ['{{class_name}}', '{{property_docs}}', '{{properties}}'],
            // [$className, $propertyDocs, $properties],
            ['{{class_name}}', '{{property_docs}}'],
            [$className, $propertyDocs],
            $modelTemplate
        );

        // 写入文件
        file_put_contents($dirPath . '/.phpstorm.meta.php', $metaContent);
        file_put_contents($dirPath . '/' . $className . '.php', $modelContent);
    }

    echo "元数据文件生成成功！\n";

} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
}