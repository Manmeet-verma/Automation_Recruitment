<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECH ON - Automated Recruitment System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@400;700;900&display=swap');
        
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #f97316;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            color: #e2e8f0;
            min-height: 100vh;
        }
        
        .font-tech { font-family: 'Orbitron', sans-serif; }
        
        .bg-grid {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(249, 115, 22, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(249, 115, 22, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: -1;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(249, 115, 22, 0.4);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid #f97316;
            color: #f97316;
            padding: 10px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(249, 115, 22, 0.1);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px; left: 0; right: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
        }
        
        .step-circle {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: #f97316;
            border-color: #f97316;
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.5);
        }
        
        .step.completed .step-circle {
            background: #10b981;
            border-color: #10b981;
        }
        
        .proctoring-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: rgba(239, 68, 68, 0.95);
            color: white;
            padding: 12px;
            text-align: center;
            z-index: 9999;
            display: none;
        }
        
        .proctoring-bar.active { display: flex; }
        
        .webcam-preview {
            position: fixed;
            bottom: 20px; right: 20px;
            width: 200px; height: 150px;
            background: #000;
            border: 3px solid #f97316;
            border-radius: 12px;
            overflow: hidden;
            z-index: 1000;
        }
        
        .recording-dot {
            position: absolute;
            top: 10px; right: 10px;
            width: 12px; height: 12px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .security-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9998;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
        }
        
        .security-overlay.active { display: flex; }
        
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(249, 115, 22, 0.3);
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            z-index: 10000;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active { display: flex; }
        
        .hidden { display: none !important; }
        
        .loading-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            flex-direction: column;
        }
        
        .loading-overlay.active { display: flex; }
        
        .loader {
            width: 48px; height: 48px;
            border: 3px solid rgba(249, 115, 22, 0.3);
            border-radius: 50%;
            border-top-color: #f97316;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader mb-4"></div>
        <p class="text-orange-400 font-semibold">Processing...</p>
    </div>
    <div class="bg-grid"></div>
        <!-- Proctoring Bar -->
    <div class="proctoring-bar" id="proctoringBar">
        <i class="fas fa-shield-alt"></i>
        <span><strong>PROCTORING ACTIVE:</strong> Do not switch tabs or applications.</span>
    </div>
    
    <!-- Webcam Preview -->
    <div class="webcam-preview hidden" id="webcamPreview">
        <video id="webcamVideo" autoplay muted></video>
        <div class="recording-dot"></div>
    </div>
    
    <!-- Security Violation Overlay -->
    <div class="security-overlay" id="securityOverlay">
        <i class="fas fa-exclamation-triangle" style="font-size: 64px; color: #ef4444; margin-bottom: 24px;"></i>
        <h2 style="font-size: 32px; margin-bottom: 16px; color: #ef4444;">Security Violation Detected</h2>
        <p style="font-size: 18px; color: #94a3b8; margin-bottom: 32px;">Test terminated due to policy violation.</p>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 20px; border-radius: 8px;">
            <p style="color: #fca5a5;"><strong>Violation:</strong> <span id="violationType"></span></p>
            <p style="color: #fca5a5;"><strong>Time:</strong> <span id="violationTime"></span></p>
        </div>
        <button class="btn-primary mt-8" onclick="location.reload()">Return to Home</button>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast"></div>
    