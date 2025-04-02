<?php
header('Content-Type: application/json');

function getServerStats() {
    // CPU Usage
    $load = sys_getloadavg();
    $cpu = $load[0];

    // Memory Usage
    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);

    $memory = [
        'used' => round($mem[2] / 1024, 2),
        'total' => round($mem[1] / 1024, 2),
        'percent' => round(($mem[2] / $mem[1]) * 100, 2)
    ];

    // Storage Usage (Filter hanya penyimpanan utama)
    $diskInfo = shell_exec('df -h --output=source,size,used,avail,pcent,target');
    $diskLines = explode("\n", trim($diskInfo));
    array_shift($diskLines); // Hapus header
    $disks = [];
    
    foreach ($diskLines as $line) {
        $parts = preg_split('/\s+/', $line);
        
        if (isset($parts[5]) && !preg_match('/(tmpfs|udev|zram)/', $parts[0])) {
            $disks[] = [
                'filesystem' => $parts[0],
                'size' => $parts[1],
                'used' => $parts[2],
                'available' => $parts[3],
                'percent' => $parts[4],
                'mountpoint' => $parts[5]
            ];
        }
    }

    // Uptime
    $uptime = trim(shell_exec('uptime -p'));

    // Processes
    $processes = explode("\n", trim(shell_exec('ps aux --sort=-%cpu | head -n 10')));

    return [
        'cpu' => $cpu,
        'memory' => $memory,
        'disks' => $disks,
        'uptime' => $uptime,
        'processes' => $processes
    ];
}

echo json_encode(getServerStats(), JSON_PRETTY_PRINT);
?>
