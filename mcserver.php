<?php
// Minecraft Server Web Control Panel
$service_name = 'minecraft-server.service';

// Password protection
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if password is correct
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === '2616') {
            $_SESSION['authenticated'] = true;
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Not allowed";
            exit;
        }
    } else {
        // Show password form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Minecraft Server Control - Login</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .login-container {
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    text-align: center;
                    max-width: 400px;
                    width: 100%;
                }
                .login-container h1 {
                    color: #2c3e50;
                    margin-bottom: 30px;
                }
                .password-input {
                    width: 100%;
                    padding: 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 18px;
                    margin-bottom: 20px;
                    box-sizing: border-box;
                }
                .password-input:focus {
                    outline: none;
                    border-color: #007bff;
                }
                .login-btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 15px 30px;
                    border-radius: 8px;
                    font-size: 18px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                .login-btn:hover {
                    background: #0056b3;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>🔒 Server Access</h1>
                <form method="POST">
                    <input type="password" name="password" class="password-input" placeholder="Enter password" required autofocus>
                    <br>
                    <button type="submit" class="login-btn">Access Server</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle POST requests for server control
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $message = '';
        $message_type = 'success';
        
        switch ($_POST['action']) {
            case 'start':
                exec("sudo /usr/local/bin/minecraft-control start", $output, $return_code);
                $message = $return_code === 0 ? "Server started successfully!" : "Failed to start server.";
                $message_type = $return_code === 0 ? 'success' : 'error';
                break;
            case 'stop':
                exec("sudo /usr/local/bin/minecraft-control stop", $output, $return_code);
                $message = $return_code === 0 ? "Server stopped successfully!" : "Failed to stop server.";
                $message_type = $return_code === 0 ? 'success' : 'error';
                break;
            case 'restart':
                exec("sudo /usr/local/bin/minecraft-control restart", $output, $return_code);
                $message = $return_code === 0 ? "Server restarted successfully!" : "Failed to restart server.";
                $message_type = $return_code === 0 ? 'success' : 'error';
                break;
            case 'backup':
                exec("sudo /usr/local/bin/backup-world.sh", $output, $return_code);
                $message = $return_code === 0 ? "Manual backup created successfully!" : "Failed to create backup.";
                $message_type = $return_code === 0 ? 'success' : 'error';
                break;
            case 'restore':
                if (isset($_POST['backup_file'])) {
                    $backup_file = $_POST['backup_file'];
                    $backup_path = "/var/www/backups/" . basename($backup_file);
                    
                    if (file_exists($backup_path)) {
                        // First create a backup of current world
                        exec("sudo /root/minecraft-server/backup-world.sh", $backup_output, $backup_return);
                        
                        if ($backup_return === 0) {
                            // Stop server before restore
                            exec("sudo /usr/local/bin/minecraft-control stop", $stop_output, $stop_return);
                            
                            if ($stop_return === 0) {
                                // Wait a moment for server to stop
                                sleep(2);
                                
                                // Restore the backup
                                exec("cd /root/minecraft-server && sudo tar -xzf '$backup_path'", $restore_output, $restore_return);
                                
                                if ($restore_return === 0) {
                                    // Start server after restore
                                    exec("sudo /usr/local/bin/minecraft-control start", $start_output, $start_return);
                                    
                                    if ($start_return === 0) {
                                        $message = "World restored successfully from $backup_file! Server restarted.";
                                        $message_type = 'success';
                                    } else {
                                        $message = "World restored but failed to restart server. Please start manually.";
                                        $message_type = 'error';
                                    }
                                } else {
                                    $message = "Failed to restore world from backup.";
                                    $message_type = 'error';
                                    // Try to start server again
                                    exec("sudo /usr/local/bin/minecraft-control start");
                                }
                            } else {
                                $message = "Failed to stop server for restore.";
                                $message_type = 'error';
                            }
                        } else {
                            $message = "Failed to create backup before restore.";
                            $message_type = 'error';
                        }
                    } else {
                        $message = "Backup file not found.";
                        $message_type = 'error';
                    }
                } else {
                    $message = "No backup file specified.";
                    $message_type = 'error';
                }
                break;
        }
        
        // Store message in session and redirect to prevent form resubmission
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get message from session and clear it
$message = '';
$message_type = 'success';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get server status
function getServerStatus($service_name) {
    exec("sudo /usr/local/bin/minecraft-control status", $output, $return_code);
    if ($return_code === 0 && !empty($output)) {
        return trim($output[0]) === 'active' ? 'active' : 'inactive';
    }
    // Fallback to direct systemctl if the script fails
    exec("systemctl is-active $service_name", $output, $return_code);
    return $return_code === 0 ? 'active' : 'inactive';
}

// Get server logs (last 20 lines)
function getServerLogs($service_name) {
    // Try journalctl first
    exec("journalctl -u $service_name -n 20 --no-pager 2>/dev/null", $output, $return_code);
    
    // If journalctl fails, try to read from the Minecraft log file
    if ($return_code !== 0 || empty($output)) {
        $log_file = "/root/minecraft-server/logs/latest.log";
        if (file_exists($log_file)) {
            $lines = file($log_file);
            if ($lines) {
                $output = array_slice($lines, -20); // Get last 20 lines
                // Clean up the lines
                $output = array_map('trim', $output);
                $output = array_filter($output); // Remove empty lines
            }
        }
    }
    
    // If still no output, provide a fallback message
    if (empty($output)) {
        $output = ["No logs available. Server might not be running or logs are not accessible."];
    }
    
    return $output;
}

// Get backup information
function getBackups() {
    $backup_dir = "/var/www/backups";
    $backups = [];
    
    if (is_dir($backup_dir)) {
        $files = glob($backup_dir . "/world_backup_*.tar.gz");
        
        foreach ($files as $file) {
            $filename = basename($file);
            $filepath = $file;
            $size = filesize($file);
            $size_gb = round($size / (1024 * 1024 * 1024), 2);
            $modified = filemtime($file);
            $date = date('Y-m-d H:i:s', $modified);
            
            $backups[] = [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $size,
                'size_gb' => $size_gb,
                'date' => $date,
                'modified' => $modified
            ];
        }
        
        // Sort by modification time (newest first)
        usort($backups, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
    }
    
    return $backups;
}

// Calculate total backup size
function getTotalBackupSize() {
    $backups = getBackups();
    $total_size = 0;
    
    foreach ($backups as $backup) {
        $total_size += $backup['size'];
    }
    
    return round($total_size / (1024 * 1024 * 1024), 2);
}

$status = getServerStatus($service_name);
$logs = getServerLogs($service_name);
$backups = getBackups();
$total_backup_size = getTotalBackupSize();

// Debug information
$debug_info = [];
$debug_info[] = "Service Status: " . $status;
$debug_info[] = "Log Count: " . count($logs);
$debug_info[] = "PHP User: " . exec('whoami');
$debug_info[] = "Can exec: " . (function_exists('exec') ? 'Yes' : 'No');
$debug_info[] = "Journalctl test: " . (exec('journalctl --version 2>/dev/null') ? 'Working' : 'Not working');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft Server Control Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .content {
            padding: 30px;
        }
        .status-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #007bff;
        }
        .status-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #dc3545; }
        .control-buttons {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn:active { transform: translateY(0); }
        .logs-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #28a745;
        }
        .logs-content {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .backup-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #ffc107;
        }
        .backup-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
        .backup-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .backup-header {
            background: #e9ecef;
            padding: 15px 20px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
        }
        .backup-item {
            display: grid;
            grid-template-columns: 1fr auto auto auto auto;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            align-items: center;
        }
        .backup-item:last-child { border-bottom: none; }
        .backup-item:hover { background: #f8f9fa; }
        .backup-filename { font-weight: 500; }
        .backup-date { color: #6c757d; }
        .backup-size { color: #007bff; font-weight: 500; }
        .restore-btn {
            padding: 8px 16px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        .restore-btn:hover { background: #138496; }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        .message-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .debug-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #6c757d;
        }
        .debug-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
        }
        .auto-refresh {
            text-align: center;
            margin: 20px 0;
            color: #6c757d;
            font-style: italic;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover {
            background: #5a6268;
        }
        @media (max-width: 768px) {
            .backup-item {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }
            .control-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="?logout=1" class="logout-btn">🚪 Logout</a>
            <h1>🎮 Minecraft Server Control</h1>
            <p>Manage your Fabric 1.21.1 server with CustomNPCs</p>
        </div>
        
        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message message-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Server Status Section -->
            <div class="status-section">
                <h2>📊 Server Status</h2>
                <div>
                    <span class="status-indicator status-<?php echo $status; ?>"></span>
                    <strong>Server is currently: <?php echo ucfirst($status); ?></strong>
                </div>
                
                <div class="control-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="btn btn-success" <?php echo $status === 'active' ? 'disabled' : ''; ?>>
                            🚀 Start Server
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="stop">
                        <button type="submit" class="btn btn-danger" <?php echo $status === 'inactive' ? 'disabled' : ''; ?>>
                            🛑 Stop Server
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="restart">
                        <button type="submit" class="btn btn-warning">
                            🔄 Restart Server
                        </button>
                    </form>
                </div>
            </div>

            <!-- Backup Management Section -->
            <div class="backup-section">
                <h2>💾 World Backup Management</h2>
                
                <div class="backup-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($backups); ?></div>
                        <div class="stat-label">Total Backups</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_backup_size; ?> GB</div>
                        <div class="stat-label">Total Size</div>
                    </div>
                    <div class="stat-card">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="backup">
                            <button type="submit" class="btn btn-info">
                                🔄 Create Backup Now
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($backups)): ?>
                    <div class="backup-list">
                        <div class="backup-header">
                            <div>Backup Files</div>
                        </div>
                        <?php foreach ($backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-filename"><?php echo htmlspecialchars($backup['filename']); ?></div>
                                <div class="backup-date"><?php echo $backup['date']; ?></div>
                                <div class="backup-size"><?php echo $backup['size_gb']; ?> GB</div>
                                <div>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('⚠️ This will restore the world from this backup. The current world will be backed up first, then replaced. Continue?');">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" class="restore-btn">🔄 Restore</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 20px;">No backups found. Create your first backup using the button above!</p>
                <?php endif; ?>
            </div>

            <!-- Server Logs Section -->
            <div class="logs-section">
                <h2>📝 Server Logs (Last 20 lines)</h2>
                <div class="logs-content"><?php echo htmlspecialchars(implode("\n", $logs)); ?></div>
            </div>

            <!-- Debug Information Section -->
            <div class="debug-section">
                <h2>🔧 Debug Information</h2>
                <div class="debug-info">
                    <?php foreach ($debug_info as $info): ?>
                        <?php echo htmlspecialchars($info); ?><br>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="auto-refresh">
                Page will refresh automatically every 30 seconds to show latest status
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
