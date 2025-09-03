<?php

function decode_custom_ext_info($raw) {
    // 1. 去除前缀（如 AFD2）
    $prefix = 'AFD2';
    if (strpos($raw, $prefix) === 0) {
        $raw = substr($raw, strlen($prefix));
    }

    // 2. 拆出 base64 第一段（到 == 为止）
    $base64_end_pos = strpos($raw, '==');
    if ($base64_end_pos === false) {
        echo "未找到 base64 结束位置\n";
        return;
    }

    $base64_part = substr($raw, 0, $base64_end_pos + 2); // 包含 ==
    $remaining = substr($raw, $base64_end_pos + 2); // 后面是拼接的第二段

    // 3. Base64 解码第一段
    $decoded1 = base64_decode($base64_part, true);
    if (!$decoded1) {
        echo "第一段 Base64 解码失败\n";
        return;
    }

    // 4. 尝试转 JSON
    $json1 = json_decode($decoded1, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "第一段 JSON 解码失败: " . json_last_error_msg() . "\n";
        return;
    }

    // 5. 尝试解析后续 JSON 片段
    $try_json2 = '{' . $remaining;
    $json2 = json_decode($try_json2, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // 合并两个 JSON
        $merged = array_merge($json1, $json2);
        echo "✅ 解码成功，合并内容如下：\n";
        echo json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "⚠️ 第二段无法作为 JSON 解码，仅第一段结果如下：\n";
        echo json_encode($json1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// ▶ 输入你的字符串（粘贴完整字符串）
$ext_info_raw = 'AFD2iYyI6IjAiLCJkIjoiODMwNzAzNjc1IiwiZSI6IjExMjE4MTEwMDY1IiwiZiI6IjEwMzkxNTExMDUyNTAiLCJnIjoiNjE0Nzc2NDEiLCJoIjoiNjMyMzA5NzAzNDc1NDIzMjQwNCIsImkiOiI3MDAiLCJqIjoiMTY1MTgiLCJrIjoiIiwibCI6IjYzMjMwOTcwMzQ3NTQyMzI0MDRfMTc0NDA5NTU0ODU0OCIsIm0iOiIxNzQ0MDk1NTQ4NTQ4IiwibiI6IjEiLCJvIjoiMTg5IiwicCI6IjMiLCJxIjoiMiIsInIiOiI3OSIsInMiOiIxNDU5OSIsInQiOiIxNTE2NjEyMjU5OTE4IiwieCI6IjEwMDEiLCJ5IjoiIiwieiI6IjAifQ==mUxYy03OTVmLTQ3ZGQtOWJiZS0wZWUzM2MzNzZlNDIiLCIzOSI6IjUiLCI0IjoiMCIsIjQwIjoiODQyOTE3NTUxIiwiNDMiOiI4NDI5MTc1NTEiLCI0NyI6IjEiLCI1IjoiMCIsIjUwIjoiMjYxIiwiNTEiOiIxNzQ0MDk1NTQ0MDAwIiwiNTIiOiIyIiwiNTMiOiIxNSIsIjYiOiIiLCI3IjoiMTMwMzAwOTIzNTAxNzM0NjMzNjciLCI4IjoiNTIzNDkwIiwiOSI6IjEiLCJhIjoiNTFEOTZFMDVCNTg5Qzc3RTcwRDQ3QzA1QTRCMzExN0F8VkRMM0VZSU02IiwiYiI6IkRGNzA3RTU4NjQyOTQxNDc1OTU4NjVDNjYzMDczNUU1IiweyIwIjoiMSIsIjEiOiIxMDM5MTUxMTIxMjYxIiwiMTAiOiIzNDIyNDcxMDQ3IiwiMTEiOiIwIiwiMTIiOiIxIiwiMTQiOiIxIiwiMTUiOiJmZDI0ZmUxYy03OTVmLTQ3ZGQtOWJiZS0wZWUzM2MzNzZlNDIiLCIxNiI6IjkiLCIxNyI6IjMiLCIxOSI6Ijg4Nzc4MzUiLCIyIjoiMTAzOTE1MTEyMTI1OSIsIjIxIjoiMSIsIjIyIjoiZmFsc2UiLCIyNCI6IjI0IiwiMjUiOiIwIiwiMjciOiIxODYiLCIyOSI6IjIiLCIzMSI6IjciLCIzMyI6IjAiLCIzNCI6IjE2Mzg0IiwiMzUiOiIwIiwiMzYiOiIxMDAwIiwiMzgiOiJmZDI0Z';

decode_custom_ext_info($ext_info_raw);