<?php
session_start();

// اگر لاگین نشده باشه بفرست به login.php
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// مسیر فایل JSON
$dataFile = __DIR__ . "/data.json";

// اگر فایل وجود نداشت، بساز
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(["licenses" => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// دریافت داده‌ها
$data = json_decode(file_get_contents($dataFile), true);

// تابع تولید کلید رندوم
function generateRandomKey($length = 32) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $key;
}

// افزودن لایسنس
if (isset($_POST['action']) && $_POST['action'] === "add") {
    $newLic = [
        "key" => generateRandomKey(),
        "status" => "active",
        "expire" => $_POST['expire'],
        "owner" => $_POST['owner'],
        "limit_ip" => array_filter(array_map('trim', explode(",", $_POST['limit_ip']))),
        "max_usage" => (int) $_POST['max_usage'],
        "server_limit" => (int) $_POST['server_limit'], // فیلد جدید برای تعداد سرور
        "used" => 0,
        "created" => date('Y-m-d H:i:s')
    ];
    $data["licenses"][] = $newLic;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: index.php");
    exit;
}

// حذف لایسنس
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if (isset($data["licenses"][$id])) {
        array_splice($data["licenses"], $id, 1);
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: index.php");
    exit;
}

// ویرایش لایسنس
if (isset($_POST['action']) && $_POST['action'] === "edit" && isset($_POST['edit_id'])) {
    $id = (int) $_POST['edit_id'];
    if (isset($data["licenses"][$id])) {
        $data["licenses"][$id]["owner"] = $_POST['owner'];
        $data["licenses"][$id]["expire"] = $_POST['expire'];
        $data["licenses"][$id]["limit_ip"] = array_filter(array_map('trim', explode(",", $_POST['limit_ip'])));
        $data["licenses"][$id]["max_usage"] = (int) $_POST['max_usage'];
        $data["licenses"][$id]["server_limit"] = (int) $_POST['server_limit'];
        $data["licenses"][$id]["status"] = $_POST['status'];
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: index.php");
    exit;
}

// آمارها
$totalLicenses = count($data["licenses"]);
$activeLicenses = count(array_filter($data["licenses"], fn($l) => $l["status"] === "active"));
$expiredLicenses = count(array_filter($data["licenses"], function($l) {
    return strtotime($l["expire"]) < time();
}));
$totalUsers = count(array_unique(array_column($data["licenses"], "owner")));
$totalUsage = array_sum(array_column($data["licenses"], "used"));
$totalServers = array_sum(array_column($data["licenses"], "server_limit"));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexzo License Panel - Advanced</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulseGlow 2s ease-in-out infinite alternate',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'bounce-subtle': 'bounceSubtle 2s ease-in-out infinite',
                        'rotate-slow': 'rotateSlow 20s linear infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    }
                }
            }
        }
    </script>
    
    <style>
        body { 
            font-family: Vazirmatn, sans-serif;
            background: linear-gradient(-45deg, #0f0f23, #1a1a2e, #16213e, #0f3460, #1a1a2e, #0e1b44);
            background-size: 400% 400%;
            animation: gradient 25s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 10px rgba(34, 197, 94, 0.3); }
            100% { box-shadow: 0 0 30px rgba(34, 197, 94, 0.8); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes bounceSubtle {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(5deg); }
        }
        
        @keyframes rotateSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes glow {
            0% { filter: drop-shadow(0 0 5px rgba(34, 197, 94, 0.3)); }
            100% { filter: drop-shadow(0 0 20px rgba(34, 197, 94, 0.8)); }
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }
        
        .glass-dark {
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 25px 50px rgba(34, 197, 94, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669, #047857);
            transition: all 0.4s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #059669, #047857, #065f46);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626, #b91c1c);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c, #991b1b);
            transform: translateY(-3px);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706, #b45309);
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309, #92400e);
            transform: translateY(-3px);
        }
        
        .license-key {
            font-family: 'Monaco', 'Menlo', monospace;
            background: linear-gradient(135deg, #10b981, #22c55e, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .status-active {
            background: linear-gradient(135deg, #10b981, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .status-expired {
            background: linear-gradient(135deg, #ef4444, #f87171);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(34, 197, 94, 0.08), rgba(59, 130, 246, 0.08));
            animation: float 12s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 15%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 45%;
            right: 15%;
            animation-delay: 3s;
        }
        
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 25%;
            left: 25%;
            animation-delay: 6s;
        }
        
        .shape:nth-child(4) {
            width: 140px;
            height: 140px;
            top: 70%;
            right: 35%;
            animation-delay: 9s;
        }
        
        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.4s ease;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .progress-ring__circle {
            stroke-dasharray: 283;
            stroke-dashoffset: 283;
            transition: stroke-dashoffset 0.6s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .toast {
                right: 10px;
                left: 10px;
                transform: translateY(-100px);
            }
            .toast.show {
                transform: translateY(0);
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }
        
        .modal-backdrop {
            backdrop-filter: blur(15px);
            background: rgba(0, 0, 0, 0.6);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Floating Shapes Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast glass p-5 rounded-2xl shadow-2xl">
        <div id="toast-content" class="text-white"></div>
    </div>

    <!-- Header -->
    <header class="glass fixed top-0 left-0 w-full z-50 py-5 px-4 lg:px-6">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center animate-pulse-glow">
                    <i class="fas fa-shield-alt text-white text-xl animate-glow"></i>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-green-400 via-blue-400 to-purple-500 bg-clip-text text-transparent">
                        Nexzo Panel Pro
                    </h1>
                    <p class="text-sm text-gray-400 hidden sm:block">🚀 Advanced License Management System</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-3 text-sm text-gray-300">
                    <i class="fas fa-user-shield text-green-400"></i>
                    <span>Super Admin</span>
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                </div>
                <button onclick="openSettings()" class="glass p-3 rounded-xl hover:scale-105 transition-all duration-300">
                    <i class="fas fa-cog text-gray-300 hover:text-white animate-rotate-slow"></i>
                </button>
                <a href="logout.php" class="btn-danger px-5 py-3 rounded-xl text-white font-medium transition-all duration-300 flex items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hidden sm:inline">خروج</span>
                </a>
            </div>
        </div>
    </header>

    <main class="pt-24 lg:pt-28 pb-16 px-4 max-w-7xl mx-auto">
        <!-- Enhanced Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
            <div class="glass p-5 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-3xl mb-3">
                    <i class="fas fa-key text-green-400 animate-bounce-subtle"></i>
                </div>
                <div class="text-2xl lg:text-3xl font-bold text-white"><?= $totalLicenses ?></div>
                <div class="text-xs text-gray-400">کل لایسنس</div>
            </div>
            
            <div class="glass p-5 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-3xl mb-3">
                    <i class="fas fa-check-circle text-blue-400"></i>
                </div>
                <div class="text-2xl lg:text-3xl font-bold text-white"><?= $activeLicenses ?></div>
                <div class="text-xs text-gray-400">فعال</div>
            </div>
            
            <div class="glass p-5 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-3xl mb-3">
                    <i class="fas fa-times-circle text-red-400"></i>
                </div>
                <div class="text-2xl lg:text-3xl font-bold text-white"><?= $expiredLicenses ?></div>
                <div class="text-xs text-gray-400">منقضی</div>
            </div>
            
            <div class="glass p-5 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-3xl mb-3">
                    <i class="fas fa-users text-purple-400"></i>
                </div>
                <div class="text-2xl lg:text-3xl font-bold text-white"><?= $totalUsers ?></div>
                <div class="text-xs text-gray-400">کاربران</div>
            </div>
            
            <div class="glass p-5 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-3xl mb-3">
                    <i class="fas fa-chart-line text-yellow-400"></i>
                </div>
                <div class="text-2xl lg:text-3xl font-bold text-white"><?= $totalUsage ?></div>
                <div class="text-xs text-gray-400">استفاده کل</div>
            </div>
            
            <div class="glass p-5 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-3xl mb-3">
                    <i class="fas fa-server text-cyan-400"></i>
                </div>
                <div class="text-2xl lg:text-3xl font-bold text-white"><?= $totalServers ?></div>
                <div class="text-xs text-gray-400">کل سرورها</div>
            </div>
        </div>

        <!-- فرم افزودن لایسنس پیشرفته -->
        <div class="glass p-6 lg:p-8 rounded-3xl mb-8 animate-slide-in">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center animate-pulse-glow">
                        <i class="fas fa-plus text-white"></i>
                    </div>
                    <h2 class="text-2xl lg:text-3xl font-bold text-white">ایجاد لایسنس جدید</h2>
                </div>
                <button onclick="resetForm()" class="glass p-3 rounded-xl hover:scale-105 transition-all duration-300">
                    <i class="fas fa-refresh text-gray-300 hover:text-white"></i>
                </button>
            </div>
            
            <form method="POST" id="licenseForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                            <i class="fas fa-user text-green-400"></i>
                            نام کاربر *
                        </label>
                        <input name="owner" type="text" placeholder="نام کاربر را وارد کنید" 
                               class="w-full p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                            <i class="fas fa-calendar text-blue-400"></i>
                            تاریخ انقضا *
                        </label>
                        <input name="expire" type="date" 
                               class="w-full p-4 rounded-xl bg-white/5 border border-white/10 text-white focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                            <i class="fas fa-server text-purple-400"></i>
                            تعداد سرور *
                        </label>
                        <input name="server_limit" type="number" placeholder="10" min="1" max="100" value="10"
                               class="w-full p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                        <p class="text-xs text-gray-500">حداکثر تعداد سرورهای قابل استفاده</p>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                            <i class="fas fa-chart-bar text-yellow-400"></i>
                            حداکثر استفاده *
                        </label>
                        <input name="max_usage" type="number" placeholder="1000" min="1" max="10000" value="1000"
                               class="w-full p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                    </div>
                    
                    <div class="space-y-2 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                            <i class="fas fa-globe text-cyan-400"></i>
                            آی‌پی مجاز (اختیاری)
                        </label>
                        <input name="limit_ip" type="text" placeholder="192.168.1.1, 10.0.0.1, 172.16.0.1" 
                               class="w-full p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300">
                        <p class="text-xs text-gray-500">آدرس‌های IP را با کاما از هم جدا کنید</p>
                    </div>
                </div>
                
                <input type="hidden" name="action" value="add">
                
                <div class="flex justify-center mt-8">
                    <button type="submit" class="btn-primary px-10 py-4 rounded-2xl text-white font-bold flex items-center gap-3 text-lg transform hover:scale-105 transition-all duration-300">
                        <i class="fas fa-magic"></i>
                        ✨ ایجاد لایسنس جادویی
                    </button>
                </div>
            </form>
        </div>

        <!-- لیست لایسنس‌ها پیشرفته -->
        <div class="glass rounded-3xl overflow-hidden">
            <div class="bg-gradient-to-r from-gray-800/50 to-gray-900/50 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-database text-white"></i>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-white">مدیریت لایسنس‌ها</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-layer-group"></i>
                            <span><?= count($data["licenses"]) ?> آیتم</span>
                        </div>
                        <button onclick="exportData()" class="glass p-3 rounded-xl hover:scale-105 transition-all duration-300">
                            <i class="fas fa-download text-gray-300 hover:text-white"></i>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (empty($data["licenses"])): ?>
                <div class="p-16 text-center">
                    <div class="text-8xl text-gray-600 mb-6">
                        <i class="fas fa-rocket animate-bounce-subtle"></i>
                    </div>
                    <p class="text-2xl text-gray-400 mb-3">🚀 آماده برای اولین پرتاب!</p>
                    <p class="text-gray-500">اولین لایسنس خود را ایجاد کنید و سفر را آغاز نمائید</p>
                </div>
            <?php else: ?>
                
                <!-- Desktop View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-800/40 to-gray-900/40">
                            <tr class="text-gray-300">
                                <th class="p-5 text-right">
                                    <i class="fas fa-key text-green-400 ml-2"></i>
                                    کلید لایسنس
                                </th>
                                <th class="p-5 text-right">
                                    <i class="fas fa-user text-blue-400 ml-2"></i>
                                    مالک
                                </th>
                                <th class="p-5 text-center">
                                    <i class="fas fa-server text-purple-400 ml-2"></i>
                                    سرورها
                                </th>
                                <th class="p-5 text-center">
                                    <i class="fas fa-calendar text-orange-400 ml-2"></i>
                                    انقضا
                                </th>
                                <th class="p-5 text-center">
                                    <i class="fas fa-chart-bar text-yellow-400 ml-2"></i>
                                    استفاده
                                </th>
                                <th class="p-5 text-center">
                                    <i class="fas fa-toggle-on text-green-400 ml-2"></i>
                                    وضعیت
                                </th>
                                <th class="p-5 text-center">
                                    <i class="fas fa-tools text-gray-400 ml-2"></i>
                                    عملیات
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data["licenses"] as $i => $lic): ?>
                            <?php 
                            $isExpired = strtotime($lic["expire"]) < time();
                            $usagePercent = $lic["max_usage"] > 0 ? min(($lic["used"] / $lic["max_usage"]) * 100, 100) : 0;
                            ?>
                            <tr class="border-t border-white/5 hover:bg-white/5 transition-all duration-300 group">
                                <td class="p-5">
                                    <div class="license-key font-mono text-sm font-bold break-all bg-black/20 p-2 rounded-lg">
                                        <?= htmlspecialchars($lic["key"]) ?>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                            <?= strtoupper(substr($lic["owner"], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="text-white font-medium"><?= htmlspecialchars($lic["owner"]) ?></span>
                                            <div class="text-xs text-gray-400"><?= date('Y/m/d', strtotime($lic["created"])) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <div class="inline-flex items-center gap-2 bg-purple-600/20 px-3 py-1 rounded-full">
                                        <i class="fas fa-server text-purple-400"></i>
                                        <span class="text-white font-bold"><?= $lic["server_limit"] ?></span>
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <div class="<?= $isExpired ? 'text-red-400' : 'text-gray-300' ?>">
                                        <i class="fas fa-<?= $isExpired ? 'exclamation-triangle' : 'calendar-check' ?> ml-1"></i>
                                        <?= htmlspecialchars($lic["expire"]) ?>
                                    </div>
                                    <?php if ($isExpired): ?>
                                        <div class="text-xs text-red-500">منقضی شده</div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-5 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <div class="text-sm">
                                            <span class="text-green-400 font-bold"><?= $lic["used"] ?></span>
                                            <span class="text-gray-400">/</span>
                                            <span class="text-gray-300"><?= $lic["max_usage"] ?></span>
                                        </div>
                                        <div class="w-20 bg-gray-700 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full transition-all duration-500" 
                                                 style="width: <?= $usagePercent ?>%"></div>
                                        </div>
                                        <div class="text-xs text-gray-400"><?= round($usagePercent, 1) ?>%</div>
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <span class="<?= $lic["status"] === "active" ? 'status-active' : 'status-expired' ?> text-sm font-bold flex items-center justify-center gap-2">
                                        <i class="fas fa-<?= $lic["status"] === "active" ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $lic["status"] === "active" ? 'فعال' : 'غیرفعال' ?>
                                    </span>
                                </td>
                                <td class="p-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="copyLink('<?= $lic["key"] ?>', <?= $lic["server_limit"] ?>)" 
                                                class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-lg text-xs transition-all duration-300 flex items-center gap-1 hover:scale-105">
                                            <i class="fas fa-copy"></i>
                                            کپی
                                        </button>
                                        <button onclick="showQR('<?= $lic["key"] ?>', <?= $lic["server_limit"] ?>)" 
                                                class="bg-purple-600 hover:bg-purple-700 px-3 py-2 rounded-lg text-xs transition-all duration-300 flex items-center gap-1 hover:scale-105">
                                            <i class="fas fa-qrcode"></i>
                                            QR
                                        </button>
                                        <button onclick="editLicense(<?= $i ?>, <?= htmlspecialchars(json_encode($lic)) ?>)" 
                                                class="btn-warning px-3 py-2 rounded-lg text-white text-xs transition-all duration-300 flex items-center gap-1 hover:scale-105">
                                            <i class="fas fa-edit"></i>
                                            ویرایش
                                        </button>
                                        <button onclick="confirmDelete(<?= $i ?>)" 
                                                class="btn-danger px-3 py-2 rounded-lg text-white text-xs transition-all duration-300 flex items-center gap-1 hover:scale-105">
                                            <i class="fas fa-trash"></i>
                                            حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View -->
                <div class="lg:hidden p-4 space-y-4">
                    <?php foreach ($data["licenses"] as $i => $lic): ?>
                    <?php 
                    $isExpired = strtotime($lic["expire"]) < time();
                    $usagePercent = $lic["max_usage"] > 0 ? min(($lic["used"] / $lic["max_usage"]) * 100, 100) : 0;
                    ?>
                    <div class="glass-dark p-5 rounded-2xl card-hover transition-all duration-300">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    <?= strtoupper(substr($lic["owner"], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="text-white font-medium"><?= htmlspecialchars($lic["owner"]) ?></span>
                                    <div class="text-xs text-gray-400"><?= date('Y/m/d', strtotime($lic["created"])) ?></div>
                                </div>
                            </div>
                            <span class="<?= $lic["status"] === "active" ? 'status-active' : 'status-expired' ?> text-sm font-bold flex items-center gap-2">
                                <i class="fas fa-<?= $lic["status"] === "active" ? 'check-circle' : 'times-circle' ?>"></i>
                                <?= $lic["status"] === "active" ? 'فعال' : 'غیرفعال' ?>
                            </span>
                        </div>

                        <!-- License Key -->
                        <div class="mb-4">
                            <div class="text-xs text-gray-400 mb-2 flex items-center gap-2">
                                <i class="fas fa-key"></i>
                                کلید لایسنس
                            </div>
                            <div class="license-key font-mono text-sm font-bold break-all bg-black/30 p-3 rounded-lg">
                                <?= htmlspecialchars($lic["key"]) ?>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <div class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                                    <i class="fas fa-server text-purple-400"></i>
                                    تعداد سرور
                                </div>
                                <div class="text-white font-bold"><?= $lic["server_limit"] ?></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                                    <i class="fas fa-calendar <?= $isExpired ? 'text-red-400' : 'text-blue-400' ?>"></i>
                                    انقضا
                                </div>
                                <div class="text-white text-sm"><?= htmlspecialchars($lic["expire"]) ?></div>
                                <?php if ($isExpired): ?>
                                    <div class="text-xs text-red-500">منقضی شده</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-span-2">
                                <div class="text-xs text-gray-400 mb-2 flex items-center gap-1">
                                    <i class="fas fa-chart-bar text-yellow-400"></i>
                                    استفاده: <?= $lic["used"] ?>/<?= $lic["max_usage"] ?> (<?= round($usagePercent, 1) ?>%)
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-3">
                                    <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-3 rounded-full transition-all duration-500" 
                                         style="width: <?= $usagePercent ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="copyLink('<?= $lic["key"] ?>', <?= $lic["server_limit"] ?>)" 
                                    class="bg-blue-600 hover:bg-blue-700 py-3 px-3 rounded-lg text-sm transition-all duration-300 flex items-center justify-center gap-2 hover:scale-105">
                                <i class="fas fa-copy"></i>
                                کپی لینک
                            </button>
                            <button onclick="showQR('<?= $lic["key"] ?>', <?= $lic["server_limit"] ?>)" 
                                    class="bg-purple-600 hover:bg-purple-700 py-3 px-3 rounded-lg text-sm transition-all duration-300 flex items-center justify-center gap-2 hover:scale-105">
                                <i class="fas fa-qrcode"></i>
                                QR کد
                            </button>
                            <button onclick="editLicense(<?= $i ?>, <?= htmlspecialchars(json_encode($lic)) ?>)" 
                                    class="btn-warning py-3 px-3 rounded-lg text-white text-sm transition-all duration-300 flex items-center justify-center gap-2 hover:scale-105">
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </button>
                            <button onclick="confirmDelete(<?= $i ?>)" 
                                    class="btn-danger py-3 px-3 rounded-lg text-white text-sm transition-all duration-300 flex items-center justify-center gap-2 hover:scale-105">
                                <i class="fas fa-trash"></i>
                                حذف
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- QR Modal -->
    <div id="qrModal" class="fixed inset-0 modal-backdrop z-50 hidden flex items-center justify-center p-4">
        <div class="glass p-8 rounded-3xl max-w-md w-full text-center transform scale-95 transition-transform duration-300" id="qrModalContent">
            <h3 class="text-2xl font-bold text-white mb-6 flex items-center justify-center gap-3">
                <i class="fas fa-qrcode text-purple-400"></i>
                🎯 QR کد سابسکریپشن
            </h3>
            <div id="qrcode" class="flex justify-center mb-6 p-4 bg-white rounded-2xl shadow-2xl"></div>
            <div class="text-sm text-gray-300 mb-6 break-all font-mono bg-black/40 p-4 rounded-xl border border-white/10" id="qrLink"></div>
            <div class="flex gap-3">
                <button onclick="copyQRLink()" class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-3 rounded-xl text-white transition-all duration-300 flex items-center justify-center gap-2">
                    <i class="fas fa-copy"></i>
                    کپی لینک
                </button>
                <button onclick="closeQR()" class="flex-1 btn-primary px-4 py-3 rounded-xl text-white flex items-center justify-center gap-2">
                    <i class="fas fa-times"></i>
                    بستن
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 modal-backdrop z-50 hidden flex items-center justify-center p-4">
        <div class="glass p-8 rounded-3xl max-w-2xl w-full">
            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i class="fas fa-edit text-yellow-400"></i>
                ویرایش لایسنس
            </h3>
            <form method="POST" id="editForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">نام کاربر</label>
                        <input name="owner" type="text" id="editOwner" class="w-full p-3 rounded-xl bg-white/5 border border-white/10 text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">تاریخ انقضا</label>
                        <input name="expire" type="date" id="editExpire" class="w-full p-3 rounded-xl bg-white/5 border border-white/10 text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">تعداد سرور</label>
                        <input name="server_limit" type="number" id="editServerLimit" min="1" max="100" class="w-full p-3 rounded-xl bg-white/5 border border-white/10 text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">حداکثر استفاده</label>
                        <input name="max_usage" type="number" id="editMaxUsage" min="1" class="w-full p-3 rounded-xl bg-white/5 border border-white/10 text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">وضعیت</label>
                        <select name="status" id="editStatus" class="w-full p-3 rounded-xl bg-white/5 border border-white/10 text-white">
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">آی‌پی مجاز</label>
                        <input name="limit_ip" type="text" id="editLimitIp" placeholder="192.168.1.1, 10.0.0.1" class="w-full p-3 rounded-xl bg-white/5 border border-white/10 text-white">
                    </div>
                </div>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="editId">
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 btn-primary px-6 py-3 rounded-xl text-white font-bold flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        ذخیره تغییرات
                    </button>
                    <button type="button" onclick="closeEdit()" class="flex-1 btn-danger px-6 py-3 rounded-xl text-white font-bold flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass py-8 text-center border-t border-white/10 mt-16">
        <div class="flex items-center justify-center gap-3 text-gray-400 mb-3">
            <i class="fas fa-rocket text-green-400 animate-bounce-subtle text-xl"></i>
            <span class="text-xl font-bold bg-gradient-to-r from-green-400 via-blue-400 to-purple-500 bg-clip-text text-transparent">Nexzo Panel Pro</span>
            <span>© 2025</span>
        </div>
        <div class="flex items-center justify-center gap-2 text-sm text-gray-500 mb-2">
            <span>ساخته شده با</span>
            <i class="fas fa-heart text-red-500 animate-pulse"></i>
            <span>توسط EHSAN</span>
        </div>
        <div class="text-xs text-gray-600">
            🔥 Advanced License Management System with QR Support
        </div>
    </footer>

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>

    <script>
        let currentQRLink = '';

        // Show Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            
            const icons = {
                success: 'fas fa-check-circle text-green-400',
                error: 'fas fa-exclamation-circle text-red-400',
                warning: 'fas fa-exclamation-triangle text-yellow-400',
                info: 'fas fa-info-circle text-blue-400'
            };
            
            content.innerHTML = `<div class="flex items-center gap-3"><i class="${icons[type]}"></i><span>${message}</span></div>`;
            
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Copy Subscription Link with server limit
        function copyLink(licenseKey, serverLimit = 10) {
            const link = `https://nexzo-v2ray.vercel.app?license=${licenseKey}&limit=${serverLimit}`;
            navigator.clipboard.writeText(link).then(() => {
                showToast('🔗 لینک سابسکریپشن با قابلیت ' + serverLimit + ' سرور کپی شد!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = link;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('🔗 لینک سابسکریپشن کپی شد!', 'success');
            });
        }

        // Show QR Code with fixed implementation
        function showQR(licenseKey, serverLimit = 10) {
            const link = `https://nexzo-v2ray.vercel.app?license=${licenseKey}&limit=${serverLimit}`;
            const modal = document.getElementById('qrModal');
            const qrDiv = document.getElementById('qrcode');
            const linkDiv = document.getElementById('qrLink');
            const modalContent = document.getElementById('qrModalContent');
            
            currentQRLink = link;
            
            // Clear previous QR code
            qrDiv.innerHTML = '';
            
            // Show modal first
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.style.transform = 'scale(1)';
            }, 10);
            
            // Generate QR code with better error handling
            try {
                QRCode.toCanvas(link, {
                    width: 220,
                    height: 220,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    margin: 3,
                    errorCorrectionLevel: 'M'
                }, function (error, canvas) {
                    if (error) {
                        console.error('QR Code generation error:', error);
                        qrDiv.innerHTML = '<div class="text-red-400 p-4">❌ خطا در تولید QR کد</div>';
                        showToast('❌ خطا در تولید QR کد', 'error');
                    } else {
                        qrDiv.appendChild(canvas);
                        canvas.style.borderRadius = '12px';
                        canvas.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
                    }
                });
            } catch (error) {
                console.error('QR Code error:', error);
                qrDiv.innerHTML = '<div class="text-red-400 p-4">❌ خطا در تولید QR کد</div>';
            }
            
            linkDiv.textContent = link;
            
            // Add click outside to close
            modal.onclick = function(e) {
                if (e.target === modal) {
                    closeQR();
                }
            };
        }

        // Copy QR Link
        function copyQRLink() {
            navigator.clipboard.writeText(currentQRLink).then(() => {
                showToast('🔗 لینک QR کپی شد!', 'success');
            }).catch(() => {
                showToast('❌ خطا در کپی کردن', 'error');
            });
        }

        // Close QR Modal
        function closeQR() {
            const modal = document.getElementById('qrModal');
            const modalContent = document.getElementById('qrModalContent');
            
            modalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        // Edit License
        function editLicense(index, licenseData) {
            const modal = document.getElementById('editModal');
            
            document.getElementById('editId').value = index;
            document.getElementById('editOwner').value = licenseData.owner;
            document.getElementById('editExpire').value = licenseData.expire;
            document.getElementById('editServerLimit').value = licenseData.server_limit || 10;
            document.getElementById('editMaxUsage').value = licenseData.max_usage;
            document.getElementById('editStatus').value = licenseData.status;
            document.getElementById('editLimitIp').value = licenseData.limit_ip ? licenseData.limit_ip.join(', ') : '';
            
            modal.classList.remove('hidden');
        }

        // Close Edit Modal
        function closeEdit() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Confirm Delete
        function confirmDelete(index) {
            const result = confirm('🗑️ آیا از حذف این لایسنس مطمئن هستید؟\n\n⚠️ این عمل غیرقابل بازگشت است!');
            if (result) {
                showToast('🗑️ در حال حذف...', 'warning');
                setTimeout(() => {
                    window.location.href = `?delete=${index}`;
                }, 1000);
            }
        }

        // Reset Form
        function resetForm() {
            document.getElementById('licenseForm').reset();
            // Set default values
            const dateInput = document.querySelector('input[name="expire"]');
            const date = new Date();
            date.setDate(date.getDate() + 30);
            dateInput.value = date.toISOString().split('T')[0];
            
            document.querySelector('input[name="server_limit"]').value = 10;
            document.querySelector('input[name="max_usage"]').value = 1000;
            
            showToast('📝 فرم بازنشانی شد!', 'info');
        }

        // Export Data
        function exportData() {
            const data = <?= json_encode($data) ?>;
            const dataStr = JSON.stringify(data, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = 'nexzo_licenses_' + new Date().toISOString().slice(0,10) + '.json';
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
            
            showToast('📁 داده‌ها با موفقیت صادر شد!', 'success');
        }

        // Settings Modal (placeholder)
        function openSettings() {
            showToast('⚙️ بخش تنظیمات در حال توسعه...', 'info');
        }

        // Enhanced form validation
        document.getElementById('licenseForm').addEventListener('submit', function(e) {
            const owner = this.querySelector('input[name="owner"]').value.trim();
            const maxUsage = parseInt(this.querySelector('input[name="max_usage"]').value);
            const serverLimit = parseInt(this.querySelector('input[name="server_limit"]').value);
            
            if (owner.length < 2) {
                e.preventDefault();
                showToast('❌ نام کاربر باید حداقل 2 کاراکتر باشد', 'error');
                return;
            }
            
            if (maxUsage < 1 || maxUsage > 10000) {
                e.preventDefault();
                showToast('❌ حداکثر استفاده باید بین 1 تا 10000 باشد', 'error');
                return;
            }
            
            if (serverLimit < 1 || serverLimit > 100) {
                e.preventDefault();
                showToast('❌ تعداد سرور باید بین 1 تا 100 باشد', 'error');
                return;
            }
            
            // Show loading state
            const button = this.querySelector('button[type="submit"]');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i>در حال ایجاد...';
            button.disabled = true;
            
            showToast('🎯 در حال ایجاد لایسنس...', 'info');
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close modals
            if (e.key === 'Escape') {
                closeQR();
                closeEdit();
            }
            
            // Ctrl + N for new license
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                document.querySelector('input[name="owner"]').focus();
            }
            
            // Ctrl + R for reset form
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                resetForm();
            }
        });

        // Set default date to 30 days from now
        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[name="expire"]');
            dateInputs.forEach(dateInput => {
                const date = new Date();
                date.setDate(date.getDate() + 30);
                dateInput.value = date.toISOString().split('T')[0];
            });
            
            // Welcome message
            setTimeout(() => {
                showToast('🚀 خوش آمدید به پنل پیشرفته Nexzo!', 'success');
            }, 1000);
        });

        // Auto-refresh functionality
        let autoRefreshInterval;
        function toggleAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                showToast('🔄 تازه‌سازی خودکار غیرفعال شد', 'info');
            } else {
                autoRefreshInterval = setInterval(() => {
                    if (!document.hidden) {
                        location.reload();
                    }
                }, 300000); // 5 minutes
                showToast('🔄 تازه‌سازی خودکار فعال شد', 'success');
            }
        }

        // Mobile optimizations
        if (window.innerWidth < 768) {
            // Disable hover effects on mobile
            document.querySelectorAll('.card-hover').forEach(card => {
                card.style.transform = 'none';
            });
        }

        // Performance optimizations
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-slide-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.glass').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
