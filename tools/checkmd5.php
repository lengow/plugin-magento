<?php

/*
 * Check MD5 
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

$base = dirname(dirname(__FILE__));
$fp = fopen($base.'/app/code/community/Lengow/Connector/etc/checkmd5.csv', 'w+');

$list_folders = array(
    '/app/code/community/Lengow/Connector/Block',
    '/app/code/community/Lengow/Connector/Helper',
    '/app/code/community/Lengow/Connector/Model',
    '/app/code/community/Lengow/Connector/controllers',
    '/app/code/community/Lengow/Connector/locale',
    '/app/code/community/Lengow/Connector/sql',
    '/app/design/adminhtml/default/default/template/lengow',
    '/app/design/frontend/base/default/template/lengow',
    '/skin/adminhtml/default/default/lengow',
);

$file_paths = array(
    $base.'/app/design/adminhtml/default/default/layout/lengow.xml',
    $base.'/app/design/frontend/base/default/layout/lengow.xml',
    $base.'/app/etc/modules/Lengow_Connector.xml',
    $base.'/app/code/community/Lengow/Connector/etc/adminhtml.xml',
    $base.'/app/code/community/Lengow/Connector/etc/config.xml',
    $base.'/app/code/community/Lengow/Connector/etc/system.xml',
);

foreach ($list_folders as $folder) {
    if (file_exists($base.$folder)) {
        $result = explorer($base.$folder);
        $file_paths = array_merge($file_paths, $result);
    }
}
foreach ($file_paths as $file_path) {
    if (file_exists($file_path)) {
        $checksum = array(str_replace($base, '', $file_path) => md5_file($file_path));
        writeCsv($fp, $checksum);
    }
}
fclose($fp);

function explorer($path)
{
    $paths = array();
    if (is_dir($path)) {
        $me = opendir($path);
        while ($child = readdir($me)) {
            if ($child != '.' && $child != '..') {
                $result = explorer($path.DIRECTORY_SEPARATOR.$child);
                $paths = array_merge($paths, $result);
            }
        }
    } else {
        $paths[] = $path;
    }
    return $paths;
}

function writeCsv($fp, $text, &$frontKey = array())
{
    if (is_array($text)) {
        foreach ($text as $k => $v) {
            $frontKey[]= $k;
            writeCsv($fp, $v, $frontKey);
            array_pop($frontKey);
        }
    } else {
        $line = join('.', $frontKey).'|'.str_replace("\n", '<br />', $text).PHP_EOL;
        fwrite($fp, $line);
    }
}
