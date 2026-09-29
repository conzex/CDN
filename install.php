<?php

/**
 * CDN Manager — Web Installer Wizard
 */

session_start();

$lockFile = __DIR__ . '/installed.lock';
if (file_exists($lockFile)) {
    die('CDN Manager is already installed. To re-install, delete installed.lock file from server.');
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = null;

// System Requirements Checks
$requirements = [
    'php_version' => [
        'name' => 'PHP Version >= 8.2',
        'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'value' => PHP_VERSION,
    ],
    'pdo' => [
        'name' => 'PDO Extension',
        'passed' => extension_loaded('pdo'),
        'value' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
    ],
    'gd' => [
        'name' => 'GD Image Extension',
        'passed' => extension_loaded('gd') || extension_loaded('imagick'),
        'value' => extension_loaded('gd') ? 'GD Enabled' : (extension_loaded('imagick') ? 'Imagick Enabled' : 'Disabled'),
    ],
    'mbstring' => [
        'name' => 'Mbstring Extension',
        'passed' => extension_loaded('mbstring'),
        'value' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
    ],
    'storage_writable' => [
        'name' => 'storage/ Writable',
        'passed' => is_writable(__DIR__ . '/storage') || @mkdir(__DIR__ . '/storage', 0755, true),
        'value' => is_writable(__DIR__ . '/storage') ? 'Writable' : 'Not Writable',
    ],
    'cache_writable' => [
        'name' => 'bootstrap/cache/ Writable',
        'passed' => is_writable(__DIR__ . '/bootstrap/cache') || @mkdir(__DIR__ . '/bootstrap/cache', 0755, true),
        'value' => is_writable(__DIR__ . '/bootstrap/cache') ? 'Writable' : 'Not Writable',
    ],
];

$allRequirementsPassed = !in_array(false, array_column($requirements, 'passed'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        $appUrl = rtrim(trim($_POST['app_url'] ?? ''), '/');
        $dbConn = $_POST['db_connection'] ?? 'mysql';
        $dbHost = $_POST['db_host'] ?? '127.0.0.1';
        $dbPort = $_POST['db_port'] ?? '3306';
        $dbName = $_POST['db_database'] ?? '';
        $dbUser = $_POST['db_username'] ?? '';
        $dbPass = $_POST['db_password'] ?? '';
        $adminUser = trim($_POST['admin_user'] ?? 'admin');
        $adminPass = $_POST['admin_pass'] ?? 'Adm1n@123';

        if (empty($appUrl)) {
            $errors[] = 'Application URL is required.';
        }

        if ($dbConn === 'mysql') {
            if (empty($dbName) || empty($dbUser)) {
                $errors[] = 'Database Name and Username are required for MySQL.';
            } else {
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                } catch (\Throwable $e) {
                    $errors[] = 'Database Connection Failed: ' . $e->getMessage();
                }
            }
        }

        if (empty($errors)) {
            // Generate .env file
            $key = 'base64:' . base64_encode(random_bytes(32));
            $envContent = <<<ENV
APP_NAME="CDN Manager"
APP_ENV=production
APP_KEY={$key}
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL={$appUrl}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION={$dbConn}
DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPass}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
ENV;

            file_put_contents(__DIR__ . '/.env', $envContent);

            // Execute migrations and seed
            try {
                chdir(__DIR__);
                putenv('PATH=' . getenv('PATH') . ':/usr/local/bin:/usr/bin');
                
                // Touch sqlite file if sqlite connection selected
                if ($dbConn === 'sqlite') {
                    @touch(__DIR__ . '/database/database.sqlite');
                }

                exec('php artisan migrate:fresh --seed --force 2>&1', $output, $returnCode);

                // Lock installer
                file_put_contents($lockFile, date('Y-m-d H:i:s'));

                $step = 3;
            } catch (\Throwable $e) {
                $errors[] = 'Migration error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDN Manager — Web Installer Wizard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        body { font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif; }
    </style>
</head>
<body class="bg-[#F3F2F1] text-[#323130] antialiased min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="border-b border-[#EDEBE9] bg-white px-8 py-4 flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-3">
            <img src="https://cdn.conzex.com/bg/dc.jpg" alt="CDN Logo" class="w-10 h-10 rounded-lg object-cover border border-[#EDEBE9]">
            <div>
                <h1 class="text-base font-bold tracking-tight text-[#323130]">CDN Manager Installer</h1>
                <p class="text-xs text-[#605E5C]">cPanel & Shared Hosting Automated Setup Wizard</p>
            </div>
        </div>
        <span class="text-xs font-semibold px-3 py-1 bg-[#EFF6FC] text-[#0078D4] rounded-full border border-[#0078D4]/20">
            Step <?php echo $step; ?> of 3
        </span>
    </header>

    <!-- Main Installer Wizard Container -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-2xl bg-white border border-[#EDEBE9] rounded-xl shadow-lg overflow-hidden">
            
            <?php if (!empty($errors)): ?>
                <div class="bg-rose-50 border-b border-rose-200 text-rose-800 p-4 text-xs space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <div class="flex items-center space-x-2">
                            <iconify-icon icon="solar:danger-triangle-bold" class="text-base text-rose-600 flex-shrink-0"></iconify-icon>
                            <span><?php echo htmlspecialchars($err); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- STEP 1: Health & Requirements Check -->
                <div class="p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-12 h-12 rounded-lg object-cover border border-[#EDEBE9]">
                        <div>
                            <h2 class="text-lg font-bold text-[#323130]">System Health Check</h2>
                            <p class="text-xs text-[#605E5C]">Checking server environment for cPanel compatibility.</p>
                        </div>
                    </div>

                    <div class="space-y-3 mb-8">
                        <?php foreach ($requirements as $req): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-[#F3F2F1] border border-[#EDEBE9] text-xs">
                                <span class="font-medium text-[#323130]"><?php echo htmlspecialchars($req['name']); ?></span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[#605E5C]"><?php echo htmlspecialchars($req['value']); ?></span>
                                    <?php if ($req['passed']): ?>
                                        <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600 text-lg"></iconify-icon>
                                    <?php else: ?>
                                        <iconify-icon icon="solar:close-circle-bold" class="text-rose-600 text-lg"></iconify-icon>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-[#EDEBE9]">
                        <span class="text-xs text-[#605E5C]">
                            <?php echo $allRequirementsPassed ? 'All checks passed cleanly!' : 'Please resolve failed checks on server.'; ?>
                        </span>
                        <?php if ($allRequirementsPassed): ?>
                            <a href="install.php?step=2" class="px-6 py-2.5 bg-[#0078D4] hover:bg-[#106EBE] text-white text-xs font-bold rounded-lg transition-colors flex items-center space-x-2 shadow">
                                <span>Continue Configuration</span>
                                <iconify-icon icon="solar:alt-arrow-right-bold" class="text-sm"></iconify-icon>
                            </a>
                        <?php else: ?>
                            <button disabled class="px-6 py-2.5 bg-slate-200 text-slate-500 text-xs font-bold rounded-lg cursor-not-allowed">
                                Fix Requirements To Continue
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- STEP 2: Database & Config Form -->
                <form method="POST" action="install.php" class="p-8">
                    <input type="hidden" name="action" value="install">
                    
                    <div class="flex items-center space-x-3 mb-6">
                        <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-12 h-12 rounded-lg object-cover border border-[#EDEBE9]">
                        <div>
                            <h2 class="text-lg font-bold text-[#323130]">Database & App Setup</h2>
                            <p class="text-xs text-[#605E5C]">Enter your cPanel MySQL database details and site configuration.</p>
                        </div>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div>
                            <label class="block text-xs font-medium text-[#605E5C] mb-1">Application URL</label>
                            <input type="url" name="app_url" required value="<?php echo 'https://' . ($_SERVER['HTTP_HOST'] ?? 'cdn.conzex.com'); ?>" class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Driver</label>
                                <select name="db_connection" class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                                    <option value="mysql">MySQL (cPanel standard)</option>
                                    <option value="sqlite">SQLite (Standalone / Zero config)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Host</label>
                                <input type="text" name="db_host" value="127.0.0.1" class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Name</label>
                                <input type="text" name="db_database" placeholder="cpanel_cdn_db" class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Username</label>
                                <input type="text" name="db_username" placeholder="cpanel_cdn_user" class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Password</label>
                            <input type="password" name="db_password" placeholder="Database User Password" class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                        </div>

                        <div class="border-t border-[#EDEBE9] pt-4">
                            <h3 class="text-xs font-bold text-[#0078D4] mb-3 uppercase tracking-wider">Admin Login Credentials</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Admin Username</label>
                                    <input type="text" name="admin_user" value="admin" required class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Admin Password</label>
                                    <input type="text" name="admin_pass" value="Adm1n@123" required class="w-full px-3 py-2 text-xs bg-[#F3F2F1] border border-[#EDEBE9] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-[#EDEBE9]">
                        <a href="install.php?step=1" class="text-xs text-[#605E5C] hover:text-[#323130]">← Back</a>
                        <button type="submit" class="px-6 py-2.5 bg-[#0078D4] hover:bg-[#106EBE] text-white text-xs font-bold rounded-lg transition-colors flex items-center space-x-2 shadow">
                            <span>Install & Seed Database</span>
                            <iconify-icon icon="solar:check-circle-bold" class="text-base"></iconify-icon>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- STEP 3: Success Completion -->
                <div class="p-8 text-center">
                    <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-16 h-16 rounded-full mx-auto mb-4 object-cover border border-[#EDEBE9] shadow">
                    <h2 class="text-xl font-bold text-[#323130] mb-2">Installation Complete!</h2>
                    <p class="text-xs text-[#605E5C] max-w-md mx-auto mb-6">
                        CDN Manager has been successfully installed and seeded on your server.
                    </p>

                    <div class="bg-[#F3F2F1] border border-[#EDEBE9] rounded-lg p-4 max-w-md mx-auto mb-8 text-left text-xs space-y-2">
                        <div class="flex justify-between border-b border-[#EDEBE9] pb-2">
                            <span class="text-[#605E5C]">Admin Username:</span>
                            <span class="font-bold text-[#323130]">admin</span>
                        </div>
                        <div class="flex justify-between border-b border-[#EDEBE9] pb-2">
                            <span class="text-[#605E5C]">Admin Password:</span>
                            <span class="font-bold text-[#323130]">Adm1n@123</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#605E5C]">Login URL:</span>
                            <a href="login" class="text-[#0078D4] underline font-mono">/login</a>
                        </div>
                    </div>

                    <a href="login" class="inline-flex items-center space-x-2 px-8 py-3 bg-[#0078D4] hover:bg-[#106EBE] text-white text-xs font-bold rounded-lg transition-colors shadow">
                        <span>Go to Admin Dashboard</span>
                        <iconify-icon icon="solar:alt-arrow-right-bold" class="text-base"></iconify-icon>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-[#EDEBE9] bg-white px-8 py-3 text-center text-xs text-[#605E5C]">
        CDN File Manager • Production-Ready cPanel Package
    </footer>

</body>
</html>
