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
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexzo License Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulseGlow 2s ease-in-out infinite alternate',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'bounce-subtle': 'bounceSubtle 2s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: Vazirmatn, sans-serif;
            background: linear-gradient(-45deg, #0f0f23, #1a1a2e, #16213e, #0f3460, #1a1a2e);
            background-size: 400% 400%;
            animation: gradient 20s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 5px rgba(34, 197, 94, 0.3); }
            100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.6); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes bounceSubtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }
        
        .glass-dark {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .card-hover:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(34, 197, 94, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669, #047857);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #059669, #047857, #065f46);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626, #b91c1c);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c, #991b1b);
            transform: translateY(-2px);
        }
        
        .license-key {
            font-family: 'Monaco', 'Menlo', monospace;
            background: linear-gradient(135deg, #10b981, #059669);
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
            background: linear-gradient(45deg, rgba(34, 197, 94, 0.1), rgba(59, 130, 246, 0.1));
            animation: float 8s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
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
    </style>
</head>
<body class="min-h-screen">
    <!-- Floating Shapes Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast glass p-4 rounded-lg shadow-lg">
        <div id="toast-content" class="text-white"></div>
    </div>

    <!-- Header -->
    <header class="glass fixed top-0 left-0 w-full z-50 py-4 px-4 lg:px-6">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center animate-pulse-glow">
                    <i class="fas fa-shield-alt text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold bg-gradient-to-r from-green-400 via-blue-400 to-purple-500 bg-clip-text text-transparent">
                        Nexzo Panel
                    </h1>
                    <p class="text-xs text-gray-400 hidden sm:block">مدیریت لایسنس پیشرفته</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center gap-2 text-sm text-gray-300">
                    <i class="fas fa-user text-green-400"></i>
                    <span>مدیر سیستم</span>
                </div>
                <a href="logout.php" class="btn-danger px-4 py-2 rounded-lg text-white font-medium transition-all duration-300 flex items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hidden sm:inline">خروج</span>
                </a>
            </div>
        </div>
    </header>

    <main class="pt-20 lg:pt-24 pb-12 px-4 max-w-7xl mx-auto">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass p-4 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-2xl lg:text-3xl mb-2">
                    <i class="fas fa-key text-green-400"></i>
                </div>
                <div class="text-xl lg:text-2xl font-bold text-white"><?= count($data["licenses"]) ?></div>
                <div class="text-sm text-gray-400">کل لایسنس</div>
            </div>
            
            <div class="glass p-4 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-2xl lg:text-3xl mb-2">
                    <i class="fas fa-check-circle text-blue-400"></i>
                </div>
                <div class="text-xl lg:text-2xl font-bold text-white"><?= count(array_filter($data["licenses"], fn($l) => $l["status"] === "active")) ?></div>
                <div class="text-sm text-gray-400">فعال</div>
            </div>
            
            <div class="glass p-4 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-2xl lg:text-3xl mb-2">
                    <i class="fas fa-users text-purple-400"></i>
                </div>
                <div class="text-xl lg:text-2xl font-bold text-white"><?= count(array_unique(array_column($data["licenses"], "owner"))) ?></div>
                <div class="text-sm text-gray-400">کاربران</div>
            </div>
            
            <div class="glass p-4 lg:p-6 rounded-2xl text-center card-hover transition-all duration-300">
                <div class="text-2xl lg:text-3xl mb-2">
                    <i class="fas fa-chart-line text-yellow-400"></i>
                </div>
                <div class="text-xl lg:text-2xl font-bold text-white"><?= array_sum(array_column($data["licenses"], "used")) ?></div>
                <div class="text-sm text-gray-400">استفاده کل</div>
            </div>
        </div>

        <!-- فرم افزودن لایسنس -->
        <div class="glass p-6 lg:p-8 rounded-3xl mb-8 animate-slide-in">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-plus text-white text-sm"></i>
                </div>
                <h2 class="text-xl lg:text-2xl font-bold text-white">افزودن لایسنس جدید</h2>
            </div>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                        <i class="fas fa-user text-green-400"></i>
                        نام کاربر
                    </label>
                    <input name="owner" type="text" placeholder="نام کاربر را وارد کنید" 
                           class="w-full p-3 lg:p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                        <i class="fas fa-calendar text-blue-400"></i>
                        تاریخ انقضا
                    </label>
                    <input name="expire" type="date" 
                           class="w-full p-3 lg:p-4 rounded-xl bg-white/5 border border-white/10 text-white focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                        <i class="fas fa-globe text-purple-400"></i>
                        آی‌پی مجاز (اختیاری)
                    </label>
                    <input name="limit_ip" type="text" placeholder="192.168.1.1, 10.0.0.1" 
                           class="w-full p-3 lg:p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300">
                </div>
                
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-300">
                        <i class="fas fa-chart-bar text-yellow-400"></i>
                        حداکثر استفاده
                    </label>
                    <input name="max_usage" type="number" placeholder="100" min="1" value="100"
                           class="w-full p-3 lg:p-4 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300" required>
                </div>
                
                <input type="hidden" name="action" value="add">
                
                <div class="md:col-span-2 flex justify-center mt-6">
                    <button type="submit" class="btn-primary px-8 py-3 lg:py-4 rounded-2xl text-white font-bold flex items-center gap-3 text-lg">
                        <i class="fas fa-plus-circle"></i>
                        ایجاد لایسنس
                    </button>
                </div>
            </form>
        </div>

        <!-- لیست لایسنس‌ها -->
        <div class="glass rounded-3xl overflow-hidden">
            <div class="bg-gradient-to-r from-gray-800/50 to-gray-900/50 p-4 lg:p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-list text-white text-sm"></i>
                        </div>
                        <h3 class="text-xl lg:text-2xl font-bold text-white">لیست لایسنس‌ها</h3>
                    </div>
                    <div class="text-sm text-gray-400">
                        <i class="fas fa-database"></i>
                        <?= count($data["licenses"]) ?> آیتم
                    </div>
                </div>
            </div>

            <?php if (empty($data["licenses"])): ?>
                <div class="p-12 text-center">
                    <div class="text-6xl text-gray-600 mb-4">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <p class="text-xl text-gray-400 mb-2">هیچ لایسنسی یافت نشد</p>
                    <p class="text-sm text-gray-500">اولین لایسنس خود را ایجاد کنید</p>
                </div>
            <?php else: ?>
                
                <!-- Desktop View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-800/30 to-gray-900/30">
                            <tr class="text-gray-300 text-sm">
                                <th class="p-4 text-right">
                                    <i class="fas fa-key text-green-400 ml-2"></i>
                                    کلید لایسنس
                                </th>
                                <th class="p-4 text-right">
                                    <i class="fas fa-user text-blue-400 ml-2"></i>
                                    مالک
                                </th>
                                <th class="p-4 text-center">
                                    <i class="fas fa-calendar text-purple-400 ml-2"></i>
                                    انقضا
                                </th>
                                <th class="p-4 text-center">
                                    <i class="fas fa-chart-bar text-yellow-400 ml-2"></i>
                                    استفاده
                                </th>
                                <th class="p-4 text-center">
                                    <i class="fas fa-link text-cyan-400 ml-2"></i>
                                    لینک
                                </th>
                                <th class="p-4 text-center">
                                    <i class="fas fa-cog text-gray-400 ml-2"></i>
                                    عملیات
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data["licenses"] as $i => $lic): ?>
                            <tr class="border-t border-white/5 hover:bg-white/5 transition-all duration-300">
                                <td class="p-4">
                                    <div class="license-key font-mono text-sm font-bold break-all">
                                        <?= htmlspecialchars($lic["key"]) ?>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-sm text-white font-bold">
                                            <?= strtoupper(substr($lic["owner"], 0, 1)) ?>
                                        </div>
                                        <span class="text-white font-medium"><?= htmlspecialchars($lic["owner"]) ?></span>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-gray-300"><?= htmlspecialchars($lic["expire"]) ?></span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="text-sm">
                                            <span class="text-green-400 font-bold"><?= $lic["used"] ?></span>
                                            <span class="text-gray-400">/</span>
                                            <span class="text-gray-300"><?= $lic["max_usage"] ?></span>
                                        </div>
                                        <div class="w-16 bg-gray-700 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full" 
                                                 style="width: <?= $lic["max_usage"] > 0 ? min(($lic["used"] / $lic["max_usage"]) * 100, 100) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="copyLink('<?= $lic["key"] ?>')" 
                                                class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded-lg text-xs transition-all duration-300 flex items-center gap-1">
                                            <i class="fas fa-copy"></i>
                                            کپی
                                        </button>
                                        <button onclick="showQR('<?= $lic["key"] ?>')" 
                                                class="bg-purple-600 hover:bg-purple-700 px-3 py-1 rounded-lg text-xs transition-all duration-300 flex items-center gap-1">
                                            <i class="fas fa-qrcode"></i>
                                            QR
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <button onclick="confirmDelete(<?= $i ?>)" 
                                            class="btn-danger px-4 py-2 rounded-lg text-white text-sm transition-all duration-300 flex items-center gap-2 mx-auto">
                                        <i class="fas fa-trash"></i>
                                        حذف
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View -->
                <div class="lg:hidden p-4 space-y-4">
                    <?php foreach ($data["licenses"] as $i => $lic): ?>
                    <div class="glass-dark p-4 rounded-2xl card-hover transition-all duration-300">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-sm text-white font-bold">
                                    <?= strtoupper(substr($lic["owner"], 0, 1)) ?>
                                </div>
                                <span class="text-white font-medium"><?= htmlspecialchars($lic["owner"]) ?></span>
                            </div>
                            <span class="status-active text-sm font-medium">
                                <i class="fas fa-check-circle ml-1"></i>
                                فعال
                            </span>
                        </div>

                        <!-- License Key -->
                        <div class="mb-3">
                            <div class="text-xs text-gray-400 mb-1">
                                <i class="fas fa-key ml-1"></i>
                                کلید لایسنس
                            </div>
                            <div class="license-key font-mono text-sm font-bold break-all bg-black/30 p-2 rounded-lg">
                                <?= htmlspecialchars($lic["key"]) ?>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <div class="text-xs text-gray-400 mb-1">
                                    <i class="fas fa-calendar ml-1"></i>
                                    انقضا
                                </div>
                                <div class="text-white text-sm"><?= htmlspecialchars($lic["expire"]) ?></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 mb-1">
                                    <i class="fas fa-chart-bar ml-1"></i>
                                    استفاده
                                </div>
                                <div class="text-sm">
                                    <span class="text-green-400 font-bold"><?= $lic["used"] ?></span>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-gray-300"><?= $lic["max_usage"] ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full" 
                                     style="width: <?= $lic["max_usage"] > 0 ? min(($lic["used"] / $lic["max_usage"]) * 100, 100) : 0 ?>%"></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <button onclick="copyLink('<?= $lic["key"] ?>')" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 py-2 px-3 rounded-lg text-sm transition-all duration-300 flex items-center justify-center gap-2">
                                <i class="fas fa-copy"></i>
                                کپی لینک
                            </button>
                            <button onclick="showQR('<?= $lic["key"] ?>')" 
                                    class="flex-1 bg-purple-600 hover:bg-purple-700 py-2 px-3 rounded-lg text-sm transition-all duration-300 flex items-center justify-center gap-2">
                                <i class="fas fa-qrcode"></i>
                                QR کد
                            </button>
                            <button onclick="confirmDelete(<?= $i ?>)" 
                                    class="btn-danger py-2 px-3 rounded-lg text-sm transition-all duration-300">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- QR Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="glass p-6 rounded-3xl max-w-sm w-full text-center">
            <h3 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-qrcode text-purple-400 ml-2"></i>
                QR کد سابسکریپشن
            </h3>
            <div id="qrcode" class="flex justify-center mb-4 p-4 bg-white rounded-xl"></div>
            <div class="text-sm text-gray-300 mb-4 break-all font-mono bg-black/30 p-3 rounded-lg" id="qrLink"></div>
            <button onclick="closeQR()" class="btn-primary px-6 py-2 rounded-lg text-white">
                <i class="fas fa-times ml-2"></i>
                بستن
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass py-6 text-center border-t border-white/10 mt-12">
        <div class="flex items-center justify-center gap-2 text-gray-400 mb-2">
            <i class="fas fa-rocket text-green-400 animate-bounce-subtle"></i>
            <span class="font-bold bg-gradient-to-r from-green-400 to-blue-400 bg-clip-text text-transparent">Nexzo Panel</span>
            <span>© 2025</span>
        </div>
        <div class="flex items-center justify-center gap-1 text-sm text-gray-500">
            <span>ساخته شده با</span>
            <i class="fas fa-heart text-red-500 animate-pulse"></i>
            <span>توسط EHSAN</span>
        </div>
    </footer>

    <script>
        // Show Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            
            const icon = type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-exclamation-circle text-red-400';
            content.innerHTML = `<div class="flex items-center gap-3"><i class="${icon}"></i><span>${message}</span></div>`;
            
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Copy Subscription Link
        function copyLink(licenseKey) {
            const link = `https://nexzo-v2ray.vercel.app?license=${licenseKey}`;
            navigator.clipboard.writeText(link).then(() => {
                showToast('🔗 لینک سابسکریپشن کپی شد!', 'success');
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

        // Show QR Code
        function showQR(licenseKey) {
            const link = `https://nexzo-v2ray.vercel.app?license=${licenseKey}`;
            const modal = document.getElementById('qrModal');
            const qrDiv = document.getElementById('qrcode');
            const linkDiv = document.getElementById('qrLink');
            
            // Clear previous QR code
            qrDiv.innerHTML = '';
            
            // Generate QR code
            QRCode.toCanvas(qrDiv, link, {
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff',
                margin: 2,
                errorCorrectionLevel: 'M'
            }, function (error) {
                if (error) {
                    console.error(error);
                    showToast('❌ خطا در تولید QR کد', 'error');
                }
            });
            
            linkDiv.textContent = link;
            modal.classList.remove('hidden');
            
            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeQR();
                }
            });
        }

        // Close QR Modal
        function closeQR() {
            document.getElementById('qrModal').classList.add('hidden');
        }

        // Confirm Delete
        function confirmDelete(index) {
            if (confirm('🗑️ آیا از حذف این لایسنس مطمئن هستید؟\n\nاین عمل غیرقابل بازگشت است!')) {
                window.location.href = `?delete=${index}`;
            }
        }

        // Auto-hide toast on mobile scroll
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            const toast = document.getElementById('toast');
            if (toast.classList.contains('show')) {
                toast.style.opacity = '0.5';
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    toast.style.opacity = '1';
                }, 150);
            }
        });

        // Set default date to 30 days from now
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="expire"]');
            if (dateInput) {
                const date = new Date();
                date.setDate(date.getDate() + 30);
                dateInput.value = date.toISOString().split('T')[0];
            }
        });

        // Enhanced form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const owner = this.querySelector('input[name="owner"]').value.trim();
            const maxUsage = parseInt(this.querySelector('input[name="max_usage"]').value);
            
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
            
            // Show loading state
            const button = this.querySelector('button[type="submit"]');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i>در حال ایجاد...';
            button.disabled = true;
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close QR modal
            if (e.key === 'Escape') {
                closeQR();
            }
            
            // Ctrl + N for new license (focus on name field)
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                document.querySelector('input[name="owner"]').focus();
            }
        });

        // Smooth scroll to top button
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                shape.style.transform = `translateY(${rate * (index + 1) * 0.1}px)`;
            });
        });

        // Enhanced hover effects for license cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });

        // Auto-refresh license data every 5 minutes
        setInterval(() => {
            if (!document.hidden) {
                // Silent refresh - could be implemented with AJAX
                console.log('Auto-refresh check...');
            }
        }, 300000);

        // Handle mobile back button for QR modal
        window.addEventListener('popstate', function(e) {
            const modal = document.getElementById('qrModal');
            if (!modal.classList.contains('hidden')) {
                closeQR();
                history.pushState(null, null, window.location.pathname);
            }
        });

        // Add ripple effect to buttons
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>

    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: rippleEffect 0.6s linear;
            pointer-events: none;
        }

        @keyframes rippleEffect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        /* Loading animation for buttons */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .glass {
                border-radius: 1rem;
            }
            
            .card-hover {
                transform: none !important;
            }
            
            .card-hover:active {
                transform: scale(0.98) !important;
            }
        }
    </style>
</body>
</html>