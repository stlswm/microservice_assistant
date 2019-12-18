<?php

namespace stlswm\MicroserviceAssistant;

/**
 * Class Category
 * 无限级分类处理
 *
 * @package lib
 * @Date    2018/7/20
 * @Time    10:15
 */
class Category
{
    /**
     * @param array  $category
     * @param int    $pid
     * @param string $idKey
     * @param string $pidKey
     * @param string $childrenKey
     *
     * @return array
     */
    public static function unlimitedForLayer(
        array $category,
        int $pid,
        string $idKey = 'id',
        string $pidKey = 'pid',
        string $childrenKey = 'children'
    ): array {
        $dataContainer = [];
        if (count($category) == 0) {
            return $dataContainer;
        }
        foreach ($category as $item) {
            if ($item[$pidKey] == $pid) {
                $item[$childrenKey] = self::unlimitedForLayer($category, $item[$idKey], $idKey, $pidKey, $childrenKey);
                $dataContainer[] = $item;
            }
        }
        return $dataContainer;
    }

    /**
     * @param array  $category
     * @param int    $pid
     * @param int    $level
     * @param string $prefix
     * @param string $idKey
     * @param string $pidKey
     *
     * @return array
     */
    public static function unlimitedForLevel(
        array $category,
        int $pid,
        int $level,
        string $prefix,
        string $idKey = 'id',
        string $pidKey = 'pid'
    ): array {
        $dataContainer = [];
        if (count($category) == 0) {
            return $dataContainer;
        }
        foreach ($category as $item) {
            if ($item[$pidKey] == $pid) {
                $item["level"] = $level;
                $item["prefix"] = str_repeat($prefix, $level);
                $item["lastLevel"] = FALSE;
                $children = self::unlimitedForLevel($category, $item[$idKey], $level + 1, $prefix, $idKey, $pidKey);
                $childrenLen = count($children);
                if ($childrenLen == 0) {
                    $item["lastLevel"] = TRUE;
                }
                $dataContainer[] = $item;
                if ($childrenLen > 0) {
                    foreach ($children as $childrenItem) {
                        $dataContainer[] = $childrenItem;
                    }
                }
            }
        }
        return $dataContainer;
    }


    /**
     * 递归查询父辈
     *
     * @param array  $category
     * @param int    $pid
     * @param string $idKey
     * @param string $pidKey
     *
     * @return array
     */
    public static function unlimitedParent(
        array &$category,
        int $pid,
        string $idKey = 'id',
        string $pidKey = 'pid'
    ): array {
        $parent = [];
        foreach ($category as $item) {
            if ($item[$idKey] == $pid) {
                $parent[] = $item;
            }
            if ($item[$pidKey] != 0) {
                $parent = array_merge(self::unlimitedParent($category, $item[$pidKey], $idKey, $pidKey));
            }
        }
        return $parent;
    }
}