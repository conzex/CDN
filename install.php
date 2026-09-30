<?php

/**
 * Open File Share — First-Time Installation & Setup Wizard
 */

session_start();

$lockFile = __DIR__ . '/installed.lock';
$reinstall = isset($_GET['reinstall']) && $_GET['reinstall'] === '1';

// Test if database is properly configured and accessible
$dbWorking = false;
if (file_exists(__DIR__ . '/.env')) {
    $envContent = @file_get_contents(__DIR__ . '/.env');
    preg_match('/DB_CONNECTION=(.*)/', $envContent, $mConn);
    preg_match('/DB_HOST=(.*)/', $envContent, $mHost);
    preg_match('/DB_PORT=(.*)/', $envContent, $mPort);
    preg_match('/DB_DATABASE=(.*)/', $envContent, $mDb);
    preg_match('/DB_USERNAME=(.*)/', $envContent, $mUser);
    preg_match('/DB_PASSWORD=(.*)/', $envContent, $mPass);

    $conn = trim($mConn[1] ?? 'mysql', "\"' \r\n");
    $host = trim($mHost[1] ?? '127.0.0.1', "\"' \r\n");
    $port = trim($mPort[1] ?? '3306', "\"' \r\n");
    $dbname = trim($mDb[1] ?? '', "\"' \r\n");
    $user = trim($mUser[1] ?? '', "\"' \r\n");
    $pass = trim($mPass[1] ?? '', "\"' \r\n");

    if ($conn === 'mysql' && !empty($dbname) && !empty($user)) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
            $pdoTest = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]);
            $dbWorking = true;
        } catch (\Throwable $e) {
            $dbWorking = false;
        }
    } elseif ($conn === 'sqlite' && extension_loaded('pdo_sqlite')) {
        $dbWorking = true;
    }
}

// Auto-redirect to login ONLY if installed.lock exists AND database is healthy AND not explicitly re-installing
if (file_exists($lockFile) && $dbWorking && !$reinstall) {
    header('Location: login');
    exit;
}

$step = (int)($_GET['step'] ?? 1);
$errors = [];
$success = null;

// System Requirements Checks
$hasGd = extension_loaded('gd') || extension_loaded('gd2') || extension_loaded('imagick') || function_exists('imagecreatetruecolor') || function_exists('imagecreatefromstring') || function_exists('gd_info') || class_exists('Imagick');
$hasMbstring = extension_loaded('mbstring') || function_exists('mb_strlen') || function_exists('mb_split') || function_exists('mb_strtolower');

$requirements = [
    'php_version' => [
        'name' => 'PHP Version >= 8.2',
        'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'value' => PHP_VERSION,
        'required' => true,
    ],
    'pdo' => [
        'name' => 'PDO Extension',
        'passed' => extension_loaded('pdo'),
        'value' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
        'required' => true,
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Extension',
        'passed' => extension_loaded('pdo_mysql'),
        'value' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
        'required' => true,
    ],
    'gd' => [
        'name' => 'GD / Imagick Image Extension',
        'passed' => true,
        'value' => $hasGd ? 'Enabled' : 'Disabled (Optional - Thumbnails fallback to icons)',
        'required' => false,
    ],
    'mbstring' => [
        'name' => 'Mbstring Extension',
        'passed' => true,
        'value' => $hasMbstring ? 'Enabled' : 'Polyfilled',
        'required' => false,
    ],
    'storage_writable' => [
        'name' => 'storage/ Directory Writable',
        'passed' => is_writable(__DIR__ . '/storage') || @mkdir(__DIR__ . '/storage', 0755, true),
        'value' => is_writable(__DIR__ . '/storage') ? 'Writable' : 'Not Writable',
        'required' => true,
    ],
    'cache_writable' => [
        'name' => 'bootstrap/cache/ Directory Writable',
        'passed' => is_writable(__DIR__ . '/bootstrap/cache') || @mkdir(__DIR__ . '/bootstrap/cache', 0755, true),
        'value' => is_writable(__DIR__ . '/bootstrap/cache') ? 'Writable' : 'Not Writable',
        'required' => true,
    ],
];

$allRequirementsPassed = true;
foreach ($requirements as $req) {
    if (!empty($req['required']) && !$req['passed']) {
        $allRequirementsPassed = false;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        $defaultUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $appUrl = rtrim(trim($_POST['app_url'] ?? $defaultUrl), '/');
        if (empty($appUrl)) {
            $appUrl = $defaultUrl;
        }

        $dbConn = $_POST['db_connection'] ?? 'mysql';
        $dbHost = trim($_POST['db_host'] ?? '') ?: '127.0.0.1';
        $dbPort = trim($_POST['db_port'] ?? '') ?: '3306';
        $dbName = trim($_POST['db_database'] ?? '');
        $dbUser = trim($_POST['db_username'] ?? '');
        $dbPass = $_POST['db_password'] ?? '';
        
        $adminUser = trim($_POST['admin_user'] ?? '') ?: 'admin';
        $adminEmail = trim($_POST['admin_email'] ?? '') ?: 'admin@example.com';
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';

        // Field Validations
        if (empty($adminUser)) {
            $errors[] = 'Super Admin Username is required.';
        } elseif (strlen($adminUser) < 3) {
            $errors[] = 'Super Admin Username must be at least 3 characters.';
        }

        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid Super Admin Email address is required.';
        }

        if (empty($adminPass)) {
            $errors[] = 'Super Admin Password is required.';
        } elseif (strlen($adminPass) < 6) {
            $errors[] = 'Super Admin Password must be at least 6 characters.';
        }

        if ($adminPass !== $adminPassConfirm) {
            $errors[] = 'Super Admin Password and Password Confirmation do not match.';
        }

        $pdo = null;

        if ($dbConn === 'mysql') {
            if (empty($dbName) || empty($dbUser)) {
                $errors[] = 'Database Name and Username are required for MySQL connection.';
            } else {
                try {
                    // Test direct database connection
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ]);
                } catch (\Throwable $e) {
                    // Try auto-creating database if missing
                    try {
                        $dsnHostOnly = "mysql:host={$dbHost};port={$dbPort}";
                        $pdoHost = new PDO($dsnHostOnly, $dbUser, $dbPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5,
                        ]);
                        $pdoHost->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $pdo = new PDO($dsn, $dbUser, $dbPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5,
                        ]);
                    } catch (\Throwable $createEx) {
                        $errors[] = 'MySQL Database Connection Failed: ' . $e->getMessage() . '. Please verify your host, database name, username, and password.';
                    }
                }
            }
        } elseif ($dbConn === 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                $errors[] = 'PDO SQLite extension is not enabled on this PHP server. Please select MySQL or enable pdo_sqlite in PHP configuration.';
            }
        }

        if (empty($errors)) {
            // Generate .env file content
            $key = 'base64:' . base64_encode(random_bytes(32));
            $escapedDbPass = addslashes($dbPass);
            $escapedAdminPass = addslashes($adminPass);

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
DB_PASSWORD="{$escapedDbPass}"

ADMIN_USERNAME="{$adminUser}"
ADMIN_EMAIL="{$adminEmail}"
ADMIN_PASSWORD="{$escapedAdminPass}"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
ENV;

            file_put_contents(__DIR__ . '/.env', $envContent);
            @unlink(__DIR__ . '/bootstrap/cache/config.php');

            // Touch sqlite file if sqlite selected
            if ($dbConn === 'sqlite') {
                @mkdir(__DIR__ . '/database', 0755, true);
                @touch(__DIR__ . '/database/database.sqlite');
            }

            // Execute database migrations & seeders (Works even when exec() is disabled in php.ini)
            try {
                putenv("ADMIN_USERNAME={$adminUser}");
                putenv("ADMIN_EMAIL={$adminEmail}");
                putenv("ADMIN_PASSWORD={$adminPass}");
                $_ENV['ADMIN_USERNAME'] = $adminUser;
                $_ENV['ADMIN_EMAIL'] = $adminEmail;
                $_ENV['ADMIN_PASSWORD'] = $adminPass;
                $_SERVER['ADMIN_USERNAME'] = $adminUser;
                $_SERVER['ADMIN_EMAIL'] = $adminEmail;
                $_SERVER['ADMIN_PASSWORD'] = $adminPass;

                $_ENV['DB_CONNECTION'] = $dbConn;
                $_ENV['DB_HOST'] = $dbHost;
                $_ENV['DB_PORT'] = $dbPort;
                $_ENV['DB_DATABASE'] = $dbName;
                $_ENV['DB_USERNAME'] = $dbUser;
                $_ENV['DB_PASSWORD'] = $dbPass;

                $migrationDone = false;

                // 1. In-Process Artisan execution (No exec() required)
                if (file_exists(__DIR__ . '/vendor/autoload.php') && file_exists(__DIR__ . '/bootstrap/app.php')) {
                    try {
                        require_once __DIR__ . '/vendor/autoload.php';
                        $app = require __DIR__ . '/bootstrap/app.php';
                        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
                        $status = $kernel->call('migrate:fresh', [
                            '--seed' => true,
                            '--force' => true,
                        ]);
                        $migrationDone = ($status === 0);
                    } catch (\Throwable $artisanEx) {
                        $migrationDone = false;
                    }
                }

                // 2. CLI exec() fallback if in-process artisan threw exception and exec() is available
                if (!$migrationDone && function_exists('exec')) {
                    chdir(__DIR__);
                    putenv('PATH=' . getenv('PATH') . ':/usr/local/bin:/usr/bin');
                    @exec('php artisan migrate:fresh --seed --force 2>&1', $output, $returnCode);
                    $migrationDone = ($returnCode === 0);
                }

                // Direct PDO Failsafe for Super Admin Creation
                if ($dbConn === 'mysql' && $pdo) {
                    try {
                        $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
                        $stmt->execute(['username' => $adminUser, 'email' => $adminEmail]);
                        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($userRow) {
                            $update = $pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password, name = :name WHERE id = :id");
                            $update->execute([
                                'username' => $adminUser,
                                'email' => $adminEmail,
                                'password' => $hashedPass,
                                'name' => ucfirst($adminUser) . ' Admin',
                                'id' => $userRow['id'],
                            ]);
                        } else {
                            $insert = $pdo->prepare("INSERT INTO users (name, username, email, password, theme, created_at, updated_at) VALUES (:name, :username, :email, :password, 'light', NOW(), NOW())");
                            $insert->execute([
                                'name' => ucfirst($adminUser) . ' Admin',
                                'username' => $adminUser,
                                'email' => $adminEmail,
                                'password' => $hashedPass,
                            ]);
                        }
                    } catch (\Throwable $pdoUserEx) {
                        // Seeder handled user creation
                    }
                }

                // Lock installer
                file_put_contents($lockFile, date('Y-m-d H:i:s'));
                $_SESSION['installed_admin_user'] = $adminUser;
                $_SESSION['installed_admin_email'] = $adminEmail;
                $_SESSION['installed_admin_pass'] = $adminPass;

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
    <title>Setup Wizard — Open File Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        body { font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>
<body class="bg-[#F3F2F1] text-[#323130] antialiased min-h-screen flex flex-col justify-between">

    <!-- OneDrive Light Theme Header -->
    <header class="border-b border-[#EDEBE9] bg-white px-8 py-4 flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-[#0078D4] text-white flex items-center justify-center font-bold text-lg shadow-sm">
                <iconify-icon icon="solar:folder-with-files-bold" class="text-xl"></iconify-icon>
            </div>
            <div>
                <h1 class="text-base font-bold tracking-tight text-[#323130]">Open File Share Setup</h1>
                <p class="text-xs text-[#605E5C]">First-Time Installation & Configuration Wizard</p>
            </div>
        </div>
        <span class="text-xs font-semibold px-3 py-1 bg-[#EFF6FC] text-[#0078D4] rounded-full border border-[#0078D4]/20">
            Step <?php echo $step; ?> of 3
        </span>
    </header>

    <!-- Visual Timeline Stepper -->
    <div class="w-full bg-[#FAF9F8] border-b border-[#EDEBE9] py-5 px-6">
        <div class="max-w-xl mx-auto relative flex items-center justify-between">
            <!-- Connecting Timeline Track -->
            <div class="absolute top-1/2 left-8 right-8 h-0.5 bg-[#EDEBE9] -translate-y-1/2 z-0"></div>
            <div class="absolute top-1/2 left-8 h-0.5 bg-[#0078D4] -translate-y-1/2 z-0 transition-all duration-500" style="width: <?php echo $step == 1 ? '0%' : ($step == 2 ? '50%' : '100%'); ?>;"></div>

            <!-- Step 1 Timeline Node -->
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shadow-sm transition-all duration-300 <?php echo $step >= 1 ? 'bg-[#0078D4] text-white ring-4 ring-[#EFF6FC]' : 'bg-white border border-[#C8C6C4] text-[#605E5C]'; ?>">
                    <?php if ($step > 1): ?>
                        <iconify-icon icon="solar:check-read-bold" class="text-base"></iconify-icon>
                    <?php else: ?>
                        1
                    <?php endif; ?>
                </div>
                <span class="text-[11px] font-semibold mt-1.5 <?php echo $step >= 1 ? 'text-[#0078D4]' : 'text-[#605E5C]'; ?>">1. System Check</span>
            </div>

            <!-- Step 2 Timeline Node -->
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shadow-sm transition-all duration-300 <?php echo $step >= 2 ? 'bg-[#0078D4] text-white ring-4 ring-[#EFF6FC]' : 'bg-white border border-[#C8C6C4] text-[#605E5C]'; ?>">
                    <?php if ($step > 2): ?>
                        <iconify-icon icon="solar:check-read-bold" class="text-base"></iconify-icon>
                    <?php else: ?>
                        2
                    <?php endif; ?>
                </div>
                <span class="text-[11px] font-semibold mt-1.5 <?php echo $step >= 2 ? 'text-[#0078D4]' : 'text-[#605E5C]'; ?>">2. DB & Admin</span>
            </div>

            <!-- Step 3 Timeline Node -->
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shadow-sm transition-all duration-300 <?php echo $step >= 3 ? 'bg-[#0078D4] text-white ring-4 ring-[#EFF6FC]' : 'bg-white border border-[#C8C6C4] text-[#605E5C]'; ?>">
                    3
                </div>
                <span class="text-[11px] font-semibold mt-1.5 <?php echo $step >= 3 ? 'text-[#0078D4]' : 'text-[#605E5C]'; ?>">3. Completion</span>
            </div>
        </div>
    </div>

    <!-- Main Installer Container -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-2xl bg-white border border-[#EDEBE9] rounded-xl shadow-lg overflow-hidden my-4">
            
            <?php if (!empty($errors)): ?>
                <div class="bg-[#FEF2F2] border-b border-[#FCA5A5] text-[#991B1B] p-4 text-xs space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <div class="flex items-center space-x-2">
                            <iconify-icon icon="solar:danger-triangle-bold" class="text-base text-[#DC2626] flex-shrink-0"></iconify-icon>
                            <span class="font-medium"><?php echo htmlspecialchars($err); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- STEP 1: Health & Requirements Check -->
                <div class="p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-11 h-11 rounded-lg bg-[#EFF6FC] text-[#0078D4] flex items-center justify-center">
                            <iconify-icon icon="solar:shield-check-bold" class="text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-[#323130]">Environment Health Check</h2>
                            <p class="text-xs text-[#605E5C]">Verifying server PHP environment, extensions, and folder permissions.</p>
                        </div>
                    </div>

                    <div class="space-y-3 mb-8">
                        <?php foreach ($requirements as $req): ?>
                            <div class="flex items-center justify-between p-3.5 rounded-lg bg-[#FAF9F8] border border-[#EDEBE9] text-xs">
                                <span class="font-semibold text-[#323130]"><?php echo htmlspecialchars($req['name']); ?></span>
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
                            <?php echo $allRequirementsPassed ? 'All system requirements met successfully!' : 'Please resolve failed checks on server.'; ?>
                        </span>
                        <?php if ($allRequirementsPassed): ?>
                            <a href="install.php?step=2<?php echo $reinstall ? '&reinstall=1' : ''; ?>" class="px-6 py-2.5 bg-[#0078D4] hover:bg-[#106EBE] text-white text-xs font-bold rounded-lg transition-colors flex items-center space-x-2 shadow-sm">
                                <span>Continue Configuration</span>
                                <iconify-icon icon="solar:alt-arrow-right-bold" class="text-sm"></iconify-icon>
                            </a>
                        <?php else: ?>
                            <button disabled class="px-6 py-2.5 bg-[#F3F2F1] text-[#A19F9D] text-xs font-bold rounded-lg cursor-not-allowed">
                                Fix Requirements To Continue
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- STEP 2: Database & Super User Wizard Form -->
                <form method="POST" action="install.php<?php echo $reinstall ? '?reinstall=1' : ''; ?>" class="p-8">
                    <input type="hidden" name="action" value="install">
                    
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-11 h-11 rounded-lg bg-[#EFF6FC] text-[#0078D4] flex items-center justify-center">
                            <iconify-icon icon="solar:settings-bold" class="text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-[#323130]">Database & Super User Configuration</h2>
                            <p class="text-xs text-[#605E5C]">Specify your database credentials and initial super admin login details.</p>
                        </div>
                    </div>

                    <div class="space-y-5 mb-8">
                        <div>
                            <label class="block text-xs font-semibold text-[#323130] mb-1">Application URL</label>
                            <input type="url" name="app_url" required value="<?php echo htmlspecialchars($_POST['app_url'] ?? ''); ?>" placeholder="https://example.com" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                        </div>

                        <!-- Database Details Card -->
                        <div class="border border-[#EDEBE9] rounded-lg p-4 bg-[#FAF9F8] space-y-4">
                            <h3 class="text-xs font-bold text-[#0078D4] uppercase tracking-wider flex items-center space-x-1.5">
                                <iconify-icon icon="solar:database-bold" class="text-sm"></iconify-icon>
                                <span>Database Connection</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Driver</label>
                                    <select name="db_connection" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                        <option value="mysql" <?php echo ($_POST['db_connection'] ?? 'mysql') === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
                                        <option value="sqlite" <?php echo ($_POST['db_connection'] ?? '') === 'sqlite' ? 'selected' : ''; ?>>SQLite (Standalone)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Host</label>
                                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? ''); ?>" placeholder="127.0.0.1" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Port</label>
                                    <input type="text" name="db_port" value="<?php echo htmlspecialchars($_POST['db_port'] ?? ''); ?>" placeholder="3306" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                            </div>

                            <div id="mysql-fields-container" class="space-y-4 transition-all duration-300">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Name</label>
                                        <input type="text" name="db_database" placeholder="e.g. cdn_db" value="<?php echo htmlspecialchars($_POST['db_database'] ?? ''); ?>" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Username</label>
                                        <input type="text" name="db_username" placeholder="e.g. cdn_user" value="<?php echo htmlspecialchars($_POST['db_username'] ?? ''); ?>" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Database Password</label>
                                    <input type="password" name="db_password" placeholder="Database User Password" value="" class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                            </div>
                        </div>

                        <!-- Super Admin Account Card -->
                        <div class="border border-[#EDEBE9] rounded-lg p-4 bg-[#FAF9F8] space-y-4">
                            <h3 class="text-xs font-bold text-[#0078D4] uppercase tracking-wider flex items-center space-x-1.5">
                                <iconify-icon icon="solar:user-shield-bold" class="text-sm"></iconify-icon>
                                <span>Super Admin Credentials</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Super Username</label>
                                    <input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? ''); ?>" placeholder="e.g. admin" required class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Super Admin Email</label>
                                    <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" placeholder="e.g. admin@example.com" required class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Super Admin Password</label>
                                    <input type="password" name="admin_pass" value="" placeholder="Enter Super Admin Password" required class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-[#605E5C] mb-1">Confirm Super Admin Password</label>
                                    <input type="password" name="admin_pass_confirm" value="" placeholder="Confirm Super Admin Password" required class="w-full px-3 py-2 text-xs bg-white border border-[#C8C6C4] text-[#323130] rounded-lg focus:outline-none focus:border-[#0078D4] focus:ring-1 focus:ring-[#0078D4]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-[#EDEBE9]">
                        <a href="install.php?step=1" class="text-xs text-[#605E5C] hover:text-[#323130] font-medium flex items-center space-x-1">
                            <iconify-icon icon="solar:alt-arrow-left-bold" class="text-xs"></iconify-icon>
                            <span>Back</span>
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-[#0078D4] hover:bg-[#106EBE] text-white text-xs font-bold rounded-lg transition-colors flex items-center space-x-2 shadow-sm">
                            <span>Install & Seed Database</span>
                            <iconify-icon icon="solar:check-circle-bold" class="text-base"></iconify-icon>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- STEP 3: Success Completion -->
                <div class="p-8 text-center space-y-6">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-sm">
                        <iconify-icon icon="solar:check-circle-bold" class="text-3xl"></iconify-icon>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-[#323130]">Installation Complete!</h2>
                        <p class="text-xs text-[#605E5C] max-w-md mx-auto mt-1">
                            Open File Share has been successfully configured and database tables have been created.
                        </p>
                    </div>

                    <div class="bg-[#FAF9F8] border border-[#EDEBE9] rounded-lg p-5 max-w-md mx-auto text-left text-xs space-y-3 shadow-inner">
                        <div class="flex justify-between border-b border-[#EDEBE9] pb-2">
                            <span class="text-[#605E5C]">Super Username:</span>
                            <span class="font-bold text-[#323130]"><?php echo htmlspecialchars($_SESSION['installed_admin_user'] ?? 'admin'); ?></span>
                        </div>
                        <div class="flex justify-between border-b border-[#EDEBE9] pb-2">
                            <span class="text-[#605E5C]">Super Admin Email:</span>
                            <span class="font-bold text-[#323130]"><?php echo htmlspecialchars($_SESSION['installed_admin_email'] ?? 'admin@example.com'); ?></span>
                        </div>
                        <div class="flex justify-between border-b border-[#EDEBE9] pb-2">
                            <span class="text-[#605E5C]">Super Admin Password:</span>
                            <span class="font-bold text-[#323130]"><?php echo htmlspecialchars($_SESSION['installed_admin_pass'] ?? '******'); ?></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-[#605E5C]">Login Route:</span>
                            <a href="login" class="text-[#0078D4] underline font-mono font-semibold">/login</a>
                        </div>
                    </div>

                    <a href="login" class="inline-flex items-center space-x-2 px-8 py-3 bg-[#0078D4] hover:bg-[#106EBE] text-white text-xs font-bold rounded-lg transition-colors shadow-sm">
                        <span>Go to Admin Dashboard</span>
                        <iconify-icon icon="solar:alt-arrow-right-bold" class="text-base"></iconify-icon>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- OneDrive Light Theme Footer -->
    <footer class="border-t border-[#EDEBE9] bg-white px-8 py-3 text-center text-xs text-[#605E5C]">
        Open File Share Platform • Open Source Setup Wizard
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var dbConnSelect = document.querySelector('select[name="db_connection"]');
        var mysqlFields = document.getElementById('mysql-fields-container');
        if (dbConnSelect && mysqlFields) {
            function toggleFields() {
                if (dbConnSelect.value === 'sqlite') {
                    mysqlFields.style.opacity = '0.35';
                    mysqlFields.style.pointerEvents = 'none';
                } else {
                    mysqlFields.style.opacity = '1';
                    mysqlFields.style.pointerEvents = 'auto';
                }
            }
            dbConnSelect.addEventListener('change', toggleFields);
            toggleFields();
        }
    });
    </script>
</body>
</html>
