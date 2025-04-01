<?php
header('Content-Type: application/json');

// Get system metrics
function getSystemStats() {
    // CPU
    $cpu = round(sys_getloadavg()[0] * 100);
    
    // RAM
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match_all('/(MemTotal|MemAvailable|Buffers|Cached):\s+(\d+)/', $meminfo, $matches);
    $mem = array_combine($matches[1], $matches[2]);
    $used = $mem['MemTotal'] - $mem['MemAvailable'] - $mem['Buffers'] - $mem['Cached'];
    
    // Disk
    $total = disk_total_space('/');
    $free = disk_free_space('/');
    
    return [
        'cpu' => min($cpu, 100), // Cap at 100%
        'ram' => [
            'used' => round($used / 1024 / 1024),
            'total' => round($mem['MemTotal'] / 1024 / 1024),
            'percent' => round(($used / $mem['MemTotal']) * 100)
        ],
        'disk' => [
            'used' => round(($total - $free) / 1024 / 1024 / 1024, 1),
            'total' => round($total / 1024 / 1024 / 1024, 1),
            'percent' => round((($total - $free) / $total) * 100)
        ],
        'uptime' => shell_exec('uptime -p')
    ];
}

echo json_encode(getSystemStats());
?>
