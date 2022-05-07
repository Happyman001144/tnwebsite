<?php
namespace App;

class Helpers
{
    static function verStrToInt($ver) {
        $verArr = explode('.',$ver);
        $verStr = ''; foreach ($verArr as $k=>$ver) { $verStr = $verStr.sprintf("%02d", $ver); }
        $ver = (int)$verStr;
        return $ver;
    }

    static function removeRecursively($dir) {
        if (file_exists($dir)) {
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $pN = $path->getPathname(); !$path->isDir() || $path->isLink() ? unlink($pN) : rmdir($pN);
            }
        }
    }

    static function whitelist_keys(array $array, array $wlKeys) {
        $filteredNested = []; // temp array for multidimensional arrays

        $array = array_filter(
            $array,
            function ($v,$k) use ($wlKeys, &$filteredNested, $array) {
              if (is_array($v) && !in_array($k, $wlKeys)) { // handle multidimensional keys
                  if (is_numeric($k) && in_array('*', array_keys($wlKeys))) { // handle wildcard for multiple nested arrays on the same level
                    foreach ($array as $elK=>$el) { // iterate over all arrays on this level using the same filter rules
                        $filteredNested[$elK] = Helpers::whitelist_keys($el, $wlKeys['*']);
                    }
                  } else if (isset($wlKeys[$k])) {
                      $filteredNested[$k] = Helpers::whitelist_keys($v, $wlKeys[$k]);
                  }
                  return false;
              } else {
                return in_array($k, $wlKeys); // include value if its key is part of the whitelist
              }
            },
            ARRAY_FILTER_USE_BOTH
        );

        foreach ($filteredNested as $k=>$v) {
            $array[$k] = $v;
        }

        return $array;
    }
}
