<?php
session_start();

// اگر لاگین شده مستقیم بره پنل
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? "";
    $password = $_POST['password'] ?? "";

    if ($username === "nexzo" && $password === "nexzo") {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "❌ یوزرنیم یا رمز اشتباه است!";
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به Nexzo Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'pulse-glow': 'pulseGlow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: Vazirmatn, sans-serif; 
            background: linear-gradient(-45deg, #0f0f23, #1a1a2e, #16213e, #0f3460);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        @keyframes glow {
            from { box-shadow: 0 0 20px #00ff88, 0 0 30px #00ff88, 0 0 40px #00ff88; }
            to { box-shadow: 0 0 30px #00aaff, 0 0 40px #00aaff, 0 0 50px #00aaff; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulseGlow {
            0%, 100% { 
                box-shadow: 0 0 5px rgba(34, 197, 94, 0.5), 0 0 10px rgba(34, 197, 94, 0.3), 0 0 15px rgba(34, 197, 94, 0.2);
            }
            50% { 
                box-shadow: 0 0 10px rgba(34, 197, 94, 0.8), 0 0 20px rgba(34, 197, 94, 0.5), 0 0 30px rgba(34, 197, 94, 0.3);
            }
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }
        
        .input-glow:focus {
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.3);
            transform: scale(1.02);
        }
        
        .btn-hover:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 15px 35px rgba(34, 197, 94, 0.4);
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
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @media (max-width: 640px) {
            .glass {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 overflow-hidden">
    <!-- Floating Shapes Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-md">
        <!-- Logo/Brand Section -->
        <div class="text-center mb-8 animate-slide-up">
            <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-full bg-gradient-to-r from-green-400 via-blue-500 to-purple-600 animate-pulse-glow">
                <span class="text-3xl font-bold text-white">N</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-green-400 via-blue-500 to-purple-600 bg-clip-text text-transparent mb-2">
                Nexzo Panel
            </h1>
            <p class="text-gray-300 text-sm">سامانه مدیریت پیشرفته</p>
        </div>
        
        <!-- Login Card -->
        <div class="glass p-6 md:p-8 rounded-3xl shadow-2xl animate-slide-up" style="animation-delay: 0.2s">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-xl md:text-2xl font-semibold text-white mb-2">🔐 ورود به پنل</h2>
                <p class="text-gray-400 text-sm">لطفا اطلاعات خود را وارد کنید</p>
            </div>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white p-4 rounded-xl mb-6 text-center text-sm animate-slide-up border border-red-400/30">
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-lg">⚠️</span>
                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" class="space-y-6" id="loginForm">
                <!-- Username Field -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">👤 نام کاربری</label>
                    <input 
                        name="username" 
                        type="text" 
                        placeholder="نام کاربری خود را وارد کنید"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full p-4 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300 input-glow"
                        required
                    >
                </div>
                
                <!-- Password Field -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">🔑 رمز عبور</label>
                    <input 
                        name="password" 
                        type="password" 
                        placeholder="رمز عبور خود را وارد کنید"
                        class="w-full p-4 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300 input-glow"
                        required
                    >
                </div>
                
                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-green-500 via-emerald-500 to-teal-600 hover:from-green-600 hover:via-emerald-600 hover:to-teal-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 btn-hover shadow-lg"
                    id="loginBtn"
                >
                    <span class="flex items-center justify-center gap-2">
                        🚀 <span>ورود به پنل</span>
                    </span>
                </button>
            </form>
            
            <!-- Footer -->
            <div class="text-center mt-6 pt-6 border-t border-white/10">
                <p class="text-xs text-gray-400">
                    © 2024 Nexzo Panel - تمامی حقوق محفوظ است
                </p>
            </div>
        </div>
        
        <!-- Additional Info -->
        <div class="text-center mt-6 text-gray-400 text-xs animate-slide-up" style="animation-delay: 0.4s">
            <p>🔒 اتصال امن و رمزگذاری شده</p>
        </div>
    </div>
    
    <!-- JavaScript for Enhanced Interactivity -->
    <script>
        // Add loading state to form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginBtn');
            const originalContent = button.innerHTML;
            
            button.innerHTML = '<span class="flex items-center justify-center gap-2">⏳ <span>در حال ورود...</span></span>';
            button.disabled = true;
            button.classList.add('opacity-75');
            
            // Re-enable button if form submission fails (client-side validation)
            setTimeout(function() {
                if (button.disabled) {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    button.classList.remove('opacity-75');
                }
            }, 5000);
        });
        
        // Add floating animation on focus
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Add subtle parallax effect
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.shape');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = 0.5 + (index * 0.2);
                const moveX = (x - 0.5) * speed * 20;
                const moveY = (y - 0.5) * speed * 20;
                shape.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        });
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    const inputs = Array.from(document.querySelectorAll('input'));
                    const currentIndex = inputs.indexOf(activeElement);
                    const nextInput = inputs[currentIndex + 1];
                    
                    if (nextInput) {
                        nextInput.focus();
                    } else {
                        document.getElementById('loginBtn').click();
                    }
                    e.preventDefault();
                }
            }
        });
        
        // Auto-focus on first input
        window.addEventListener('load', function() {
            const firstInput = document.querySelector('input[name="username"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>