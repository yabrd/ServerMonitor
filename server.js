const express = require('express');
const si = require('systeminformation');
const path = require('path');

const app = express();
const PORT = 3000;

// Serve static files
app.use(express.static('public'));

// API endpoints
app.get('/api/system', async (req, res) => {
    try {
        const [cpu, mem, os, fs, temp, network, currentLoad] = await Promise.all([
            si.cpu(),
            si.mem(),
            si.osInfo(),
            si.fsSize(),
            si.cpuTemperature(),
            si.networkStats(),
            si.currentLoad()
        ]);

        res.json({
            cpu: {
                usage: currentLoad.currentLoad.toFixed(1),
                cores: cpu.cores,
                model: cpu.manufacturer + ' ' + cpu.brand,
                speed: cpu.speed
            },
            memory: {
                total: (mem.total / 1024 / 1024).toFixed(2),
                used: (mem.used / 1024 / 1024).toFixed(2),
                free: (mem.free / 1024 / 1024).toFixed(2)
            },
            os: {
                platform: os.platform,
                distro: os.distro,
                release: os.release,
                arch: os.arch,
                uptime: os.uptime
            },
            storage: fs.map(disk => ({
                fs: disk.fs,
                size: (disk.size / 1024 / 1024 / 1024).toFixed(2),
                used: (disk.used / 1024 / 1024 / 1024).toFixed(2),
                mount: disk.mount
            })),
            temperature: {
                main: temp.main,
                cores: temp.cores
            },
            network: network[0] // Primary network interface
        });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

app.listen(PORT, () => {
    console.log(`Server Monitor running on http://localhost:${PORT}`);
});
