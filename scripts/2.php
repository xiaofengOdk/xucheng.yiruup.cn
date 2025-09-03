<?php
$str='2-A-keyword-0301-视频';
echo mb_strlen($str,'GBK');
function truncateString($str, $maxLength = 100) {
    $length = 0;
    $result = '';
    $strLength = mb_strlen($str, 'UTF-8');

    for ($i = 0; $i < $strLength; $i++) {
        $char = mb_substr($str, $i, 1, 'UTF-8');
        // 判断字符是否为中文
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $char)) {
            $charLength = 2;
        } else {
            $charLength = 1;
        }

        if ($length + $charLength <= $maxLength) {
            $result .= $char;
            $length += $charLength;
        } else {
            break;
        }
    }

    return $result;
}

// 测试示例
$truncatedString = truncateString($str);
echo $truncatedString;