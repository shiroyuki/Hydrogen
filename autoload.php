<?php
function __autoload($className)
{
    $basePath  = dirname(__FILE__);
    $classPath = preg_replace('@\\\\@', '/', $className);

    if ( ! preg_match('/Shiroyuki\/Hydrogen\//', $classPath)) {
        return;
    }

    require_once "$basePath/lib/$classPath.php";
}

spl_autoload_register('__autoload');