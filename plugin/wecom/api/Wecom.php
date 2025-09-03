<?php

namespace plugin\wecom\api;

use plugin\admin\app\model\Option;

class Wecom
{

    /**
     * Option表的name字段值
     */
    const SETTING_OPTION_NAME = 'wecom_setting';

    /**
     * @return array|null
     */
    public static function getConfig(): ?array
    {
        $config = Option::where('name', static::SETTING_OPTION_NAME)->value('value');
        return $config ? json_decode($config, true) : null;
    }
}