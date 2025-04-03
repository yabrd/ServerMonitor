<?php
// header("Access-Control-Allow-Origin: *");  // Mengizinkan akses dari semua origin
header('Content-Type: application/json');

function getCpuInfo() {
    // Ambil output dari lscpu
    $lscpu = shell_exec('lscpu');

    // Ekstrak informasi yang diperlukan menggunakan regular expressions
    preg_match('/Architecture:\s*(\S+)/', $lscpu, $architecture);
    preg_match('/CPU op-mode\(s\):\s*(\S+)/', $lscpu, $cpu_op_mode);
    preg_match('/Byte Order:\s*(\S+)/', $lscpu, $byte_order);
    preg_match('/Vendor ID:\s*(\S+)/', $lscpu, $vendor_id);
    preg_match('/Model name:\s*(.*)/', $lscpu, $model_name);
    preg_match('/Thread\(s\) per core:\s*(\d+)/', $lscpu, $threads_per_core);
    preg_match('/Core\(s\) per cluster:\s*(\d+)/', $lscpu, $cores_per_cluster);

    // Mengambil jumlah total CPU
    $total_cores = (int)shell_exec('nproc --all');

    return [
        'architecture' => $architecture[1] ?? 'N/A',
        'cpu_op_mode' => $cpu_op_mode[1] ?? 'N/A',
        'byte_order' => $byte_order[1] ?? 'N/A',
        'vendor_id' => $vendor_id[1] ?? 'N/A',
        'model_name' => $model_name[1] ?? 'N/A',
        'threads' => isset($threads_per_core[1]) ? (int)$threads_per_core[1] : 'N/A',
        'cores' => isset($cores_per_cluster[1]) ? (int)$cores_per_cluster[1] : 'N/A',
        'usage' => sys_getloadavg()[0]  // CPU usage
    ];
}

function getMemoryInfo() {
    $free = shell_exec('free -m');
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $free, $mem);
    return [
        'used' => (int)$mem[2],
        'total' => (int)$mem[1],
        'percent' => round(($mem[2]/$mem[1])*100, 2)
    ];
}

function getStorageInfo() {
    $disks = [];
    $df = shell_exec('df -h --output=source,size,used,avail,pcent,target | grep -E "/dev/sd|/dev/nvme|/dev/mmcblk"');
    $lines = explode("\n", trim($df));

    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 6) {
            $disks[] = [
                'device' => $parts[0],
                'size' => $parts[1],
                'used' => $parts[2],
                'percent' => $parts[4],
            ];
        }
    }
    return $disks;
}

function getUptime() {
    return trim(shell_exec('uptime -p'));
}

function getProcesses() {
    // Ambil 10 proses yang menggunakan CPU terbanyak
    $processes = shell_exec('ps aux --sort=-%cpu | head -n 10');
    $processes = explode("\n", trim($processes));

    // Ambil header
    array_shift($processes);

    // Proses data
    $processList = [];
    foreach ($processes as $process) {
        $processData = preg_split('/\s+/', $process);

        // Jika data valid, ambil PID, USER, CPU%, MEM%, COMMAND
        if (count($processData) >= 11) {
            $processList[] = [
                'pid' => $processData[1],
                'user' => $processData[0],
                'cpu' => $processData[2],
                'mem' => $processData[3],
                'command' => implode(' ', array_slice($processData, 10)) // Mengambil command
            ];
        }
    }

    return $processList;
}

// Contoh pemanggilan fungsi dan pengembalian hasil dalam format JSON
echo json_encode([
    'cpu' => getCpuInfo(),
    'memory' => getMemoryInfo(),
    'storage' => getStorageInfo(),
    'uptime' => getUptime(),
    'processes' => getProcesses(),
]);
?>
