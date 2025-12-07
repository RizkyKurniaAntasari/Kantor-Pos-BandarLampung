<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGIS Kantor Pos Bandar Lampung</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css">
    <script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <!-- Font yang lebih unique -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #FF6B35;
            --secondary: #004E89;
            --accent: #FFD23F;
            --dark: #1A1A2E;
            --light: #F8F9FA;
        }

        * {
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0F0F1E;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 78, 137, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 210, 63, 0.05) 0%, transparent 50%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(2px 2px at 20% 30%, rgba(255, 107, 53, 0.3), transparent),
                radial-gradient(2px 2px at 60% 70%, rgba(0, 78, 137, 0.3), transparent),
                radial-gradient(1px 1px at 50% 50%, rgba(255, 210, 63, 0.2), transparent);
            background-size: 200% 200%;
            animation: particleMove 20s ease infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes particleMove {

            0%,
            100% {
                background-position: 0% 0%, 100% 100%, 50% 50%;
            }

            50% {
                background-position: 100% 100%, 0% 0%, 30% 70%;
            }
        }

        /* Navigation dengan style yang lebih bold */
        .nav-main {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 2px solid var(--primary);
            box-shadow: 0 4px 30px rgba(255, 107, 53, 0.2);
        }

        .nav-title {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Sidebar dengan style unik */
        .sidebar-main {
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(20px);
            border-right: 3px solid var(--primary);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.5);
        }

        .sidebar-header {
            border-bottom: 2px dashed var(--primary);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 107, 53, 0.3);
            transition: all 0.3s ease;
        }

        .search-box:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--primary) 0%, #FF8C61 100%);
            border: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }

        .btn-add::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-add:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 107, 53, 0.6);
        }

        .btn-near-me {
            background: linear-gradient(135deg, var(--secondary) 0%, #0EA5E9 100%);
            box-shadow: 0 4px 20px rgba(14, 165, 233, 0.4);
        }

        .btn-near-me:hover {
            box-shadow: 0 6px 30px rgba(14, 165, 233, 0.6);
        }

        /* List item dengan style unik */
        .list-item {
            background: rgba(255, 255, 255, 0.03);
            border-left: 4px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .list-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .list-item:hover {
            background: rgba(255, 107, 53, 0.1);
            border-left-color: var(--primary);
            transform: translateX(8px);
            box-shadow: -4px 0 15px rgba(255, 107, 53, 0.3);
        }

        .list-item:hover::before {
            transform: scaleY(1);
        }

        .list-item-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary) 0%, #FF8C61 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
            position: relative;
        }

        .list-item-icon::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 12px;
            padding: 2px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .list-item:hover .list-item-icon::after {
            opacity: 1;
        }

        /* Map container dengan border unik */
        .map-wrapper {
            border-radius: 24px;
            overflow: hidden;
            border: 3px solid var(--primary);
            box-shadow:
                0 0 0 8px rgba(26, 26, 46, 0.9),
                0 20px 60px rgba(255, 107, 53, 0.3),
                inset 0 0 60px rgba(255, 107, 53, 0.1);
            position: relative;
        }

        .map-wrapper::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 24px;
            padding: 3px;
            background: linear-gradient(135deg, var(--primary), var(--accent), var(--secondary));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            z-index: -1;
            animation: borderGlow 3s ease infinite;
        }

        @keyframes borderGlow {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }
        }

        /* Stat card dengan style neon */
        .stat-badge {
            background: rgba(255, 107, 53, 0.1);
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 8px 16px;
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
            position: relative;
            overflow: hidden;
        }

        .stat-badge::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        .stat-number {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }

        /* Scrollbar custom dengan style unik */
        .sidebar-main::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-main::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .sidebar-main::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary), var(--accent));
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }

        .sidebar-main::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--accent), var(--primary));
        }

        /* Loading animation yang lebih menarik */
        .loading-dots {
            display: inline-flex;
            gap: 4px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: bounce 1.4s ease-in-out infinite;
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }

        .loading-dots span:nth-child(1) {
            animation-delay: 0s;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {

            0%,
            80%,
            100% {
                transform: scale(0);
            }

            40% {
                transform: scale(1);
            }
        }

        /* Notification dengan style unik */
        .notification-custom {
            background: rgba(26, 26, 46, 0.95);
            border: 2px solid var(--primary);
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(255, 107, 53, 0.5);
            backdrop-filter: blur(20px);
        }

        /* Leaflet popup customization */
        .custom-popup .leaflet-popup-content-wrapper {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid var(--primary);
            border-radius: 16px;
            box-shadow: 0 0 40px rgba(255, 107, 53, 0.4);
            color: white;
            padding: 0;

            /* --- UPDATE: Kunci Ukuran --- */
            width: 300px !important;
            /* Lebar tetap */
            min-width: 300px !important;
            /* Jangan mengecil */
            max-width: 300px !important;
            /* Jangan membesar */
            /* ---------------------------- */
        }

        .custom-popup .leaflet-popup-content {
            margin: 0;
            padding: 0;
            /* --- UPDATE: Konten mengikuti wrapper --- */
            width: 100% !important;
        }

        .custom-popup .leaflet-popup-tip {
            background: rgba(26, 26, 46, 0.95);
            border: 2px solid var(--primary);
            border-top: none;
            border-right: none;
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
        }

        /* Leaflet controls styling */
        .leaflet-control-layers,
        .leaflet-control-zoom,
        .leaflet-control-geocoder {
            background: rgba(26, 26, 46, 0.9) !important;
            backdrop-filter: blur(20px) !important;
            border: 2px solid var(--primary) !important;
            border-radius: 12px !important;
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3) !important;
        }

        .leaflet-control-layers-toggle {
            background: var(--primary) !important;
        }

        .leaflet-control-zoom a {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: var(--primary) !important;
            border: 1px solid var(--primary) !important;
        }

        .leaflet-control-zoom a:hover {
            background-color: var(--primary) !important;
            color: white !important;
        }

        /* Geocoder search styling to match zoom */
        .leaflet-control-geocoder {
            padding: 4px !important;
        }

        .leaflet-control-geocoder .leaflet-control-geocoder-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            background-color: var(--primary) !important;
            border: 1px solid var(--primary) !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        }

        .leaflet-control-geocoder .leaflet-control-geocoder-icon:hover {
            background-color: #ffffff !important;
        }

        .leaflet-control-geocoder-expanded .leaflet-control-geocoder-form {
            margin-left: 6px !important;
        }

        .leaflet-control-geocoder-form input {
            color: white !important;
        }

        /* Fullscreen button styling to match primary */
        .leaflet-control-zoom-fullscreen {
            background-color: var(--primary) !important;
            border: 1px solid var(--primary) !important;
        }

        .leaflet-control-zoom-fullscreen:hover {
            background-color: #ffffff !important;
        }

        /* Styling checkbox dan radio button agar lebih terlihat */
        .leaflet-control-layers input[type="checkbox"],
        .leaflet-control-layers input[type="radio"] {
            width: 18px !important;
            height: 18px !important;
            margin-right: 8px !important;
            cursor: pointer !important;
            accent-color: var(--primary) !important;
            border: 2px solid #FFD23F !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        .leaflet-control-layers input[type="checkbox"]:checked,
        .leaflet-control-layers input[type="radio"]:checked {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }

        .leaflet-control-layers label {
            color: white !important;
            cursor: pointer !important;
        }

        .leaflet-control-layers label:hover {
            color: #FFD23F !important;
        }

        /* Marker pulse animation */
        .marker-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        /* Custom marker icon styles */
        .custom-marker-icon {
            background: transparent !important;
            border: none !important;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.6;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        /* Input placeholder styling */
        input::placeholder {
            color: #666 !important;
        }

        /* Button text styling */
        .btn-add span {
            position: relative;
            z-index: 1;
        }

        /* Leaflet popup close button styling */
        .leaflet-popup-close-button {
            color: #FF6B35 !important;
            font-size: 18px !important;
            font-weight: bold !important;
            padding: 4px !important;
            top: 8px !important;
            right: 8px !important;
            width: 24px !important;
            height: 24px !important;
            line-height: 16px !important;
            border-radius: 9999px !important;
            background: rgba(26, 26, 46, 0.95) !important;
        }

        .leaflet-popup-close-button:hover {
            color: #FFD23F !important;
            background: rgba(255, 107, 53, 0.2) !important;
        }

        /* Floating elements */
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* Title dengan gradient glow */
        .nav-title {
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
            letter-spacing: 1px;
        }

        /* Map container tidak scrollable */
        .map-wrapper {
            position: relative;
            overflow: hidden;
        }

        /* Leaflet map container fix */
        .leaflet-container {
            height: 100% !important;
            width: 100% !important;
        }

        /* Styling untuk info text di layer control */
        .leaflet-control-layers label {
            position: relative;
        }

        /* Info text styling - untuk text node setelah span */
        .leaflet-control-layers label {
            color: white !important;
        }

        /* Modal styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        /* Modal Detail Location */
        #modalLocationDetail.modal-overlay {
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: rgba(26, 26, 46, 0.95);
            border: 2px solid var(--primary);
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 0 50px rgba(255, 107, 53, 0.5);
            backdrop-filter: blur(20px);
            animation: slideUp 0.3s ease;
            position: relative;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            margin-bottom: 24px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .modal-subtitle {
            font-size: 14px;
            color: #9CA3AF;
            font-family: 'JetBrains Mono', monospace;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-input::placeholder {
            color: #6B7280;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn-modal {
            flex: 1;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Space Grotesk', sans-serif;
            border: 2px solid transparent;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            border-color: var(--primary);
        }

        .btn-modal-primary:hover {
            box-shadow: 0 0 30px rgba(255, 107, 53, 0.6);
            transform: translateY(-2px);
        }

        .btn-modal-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #9CA3AF;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .btn-modal-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Star Rating Styles */
        .star-rating {
            display: inline-flex;
            gap: 4px;
        }

        .star {
            cursor: pointer;
            font-size: 24px;
            color: #4B5563;
            transition: all 0.2s;
        }

        .star:hover {
            transform: scale(1.2);
        }

        .star.active {
            color: #FFD23F;
        }

        .star.half {
            background: linear-gradient(90deg, #FFD23F 50%, #4B5563 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Comments Styles */
        .comment-item {
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 3px solid var(--primary);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .comment-author {
            font-weight: 600;
            color: #FFD23F;
            font-family: 'Space Grotesk', sans-serif;
        }

        .comment-date {
            font-size: 12px;
            color: #6B7280;
            font-family: 'JetBrains Mono', monospace;
        }

        .comment-text {
            color: #E5E7EB;
            line-height: 1.6;
            margin-top: 8px;
        }

        /* Select dropdown styling untuk rating komentar */
        select.form-input,
        select[class*="form-input"],
        select[id^="comment-rating"] {
            background: rgba(255, 255, 255, 0.05) !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FFD23F' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            color: white !important;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 40px !important;
        }

        select.form-input:focus,
        select[class*="form-input"]:focus,
        select[id^="comment-rating"]:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FF6B35' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
            border-color: var(--primary) !important;
        }

        select.form-input option,
        select[class*="form-input"] option,
        select[id^="comment-rating"] option {
            background: rgba(26, 26, 46, 0.95) !important;
            color: white !important;
            padding: 8px;
        }

        select.form-input option:checked,
        select[class*="form-input"] option:checked,
        select[id^="comment-rating"] option:checked {
            background: rgba(255, 107, 53, 0.3) !important;
            color: #FFD23F !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar-main {
                width: 100%;
                max-width: 100%;
            }

            .modal-content {
                max-width: 95% !important;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="nav-main text-white py-4 fixed w-full z-50">
        <div class="w-full px-6 md:px-10">

            <div class="flex items-center justify-between w-full">

                <div class="flex items-center space-x-4 shrink-0">
                    <div class="w-14 h-14 bg-gradient-to-br from-[var(--primary)] to-[var(--accent)] rounded-xl flex items-center justify-center float-animation" style="box-shadow: 0 0 30px rgba(255, 107, 53, 0.5);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="nav-title text-2xl">WebGIS Kantor Pos</h1>
                        <p class="text-sm text-gray-400 font-mono mt-1">BANDAR LAMPUNG</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 shrink-0">
                    <div class="stat-badge">
                        <div class="text-xs text-gray-400 font-mono uppercase tracking-wider text-right">Total</div>
                        <div id="totalKantor" class="stat-number text-xl text-right">-</div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex pt-28 relative z-10" style="height: calc(100vh - 7rem); min-height: calc(100vh - 7rem);">
        <!-- Sidebar -->
        <aside class="sidebar-main w-80 p-6 flex flex-col" style="height: calc(100vh - 7rem); max-height: calc(100vh - 7rem); overflow-y: auto;">
            <div class="sidebar-header">
                <h2 class="text-2xl font-bold text-white mb-2 flex items-center">
                    <span class="text-[var(--primary)] font-mono mr-2">[</span>
                    <span>Daftar Kantor Pos</span>
                    <span class="text-[var(--primary)] font-mono ml-2">]</span>
                </h2>
                <p class="text-xs text-gray-400 font-mono">Klik item untuk fokus ke lokasi</p>
            </div>

            <!-- Search Box -->
            <div class="mb-4">
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Cari kantor pos..."
                        class="search-box w-full px-4 py-3 pl-12 rounded-lg text-white placeholder-gray-500 focus:outline-none font-mono">
                    <svg class="w-5 h-5 text-[var(--primary)] absolute left-4 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Near Me Button -->
            <button id="btnNearMe" class="btn-add btn-near-me w-full text-white py-3 px-4 rounded-lg mb-3 font-bold flex items-center justify-center space-x-2 relative z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2m10-10h-2M4 12H2m15.364-7.364l-1.414 1.414M7.05 16.95l-1.414 1.414m0-11.314L7.05 7.05m8.486 8.486l1.414 1.414"></path>
                </svg>
                <span class="font-mono uppercase tracking-wider text-sm">Kantor Terdekat</span>
            </button>

            <!-- Add Marker Button -->
            <button id="btnAddMarker" class="btn-add w-full text-white py-3 px-4 rounded-lg mb-4 font-bold flex items-center justify-center space-x-2 relative z-10">
                <span class="text-xl">+</span>
                <span class="font-mono uppercase tracking-wider">Tambah Marker</span>
            </button>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="hidden mb-4 flex items-center justify-center py-4">
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span class="ml-3 text-gray-400 font-mono text-sm">Memuat data</span>
            </div>

            <!-- List of Kantor Pos -->
            <ul id="sidebarList" class="space-y-3">
                <!-- Items will be added here -->
            </ul>
        </aside>

        <!-- Map Container -->
        <div class="flex-1 p-6 relative" style="height: calc(100vh - 7rem); overflow: hidden;">
            <div id="map" class="map-wrapper w-full h-full" style="border-radius: 12px; overflow: hidden; box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);"></div>
        </div>
    </div>

    <!-- Modal Tambah Marker -->
    <div id="modalAddMarker" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" id="modalClose">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="modal-header">
                <h2 class="modal-title">Tambah Kantor Pos Baru</h2>
                <p class="modal-subtitle">Masukkan informasi kantor pos yang akan ditambahkan</p>
            </div>

            <form id="formAddMarker">
                <div class="form-group">
                    <label class="form-label" for="inputNama">Nama Kantor Pos</label>
                    <input type="text" id="inputNama" class="form-input" placeholder="Contoh: Kantor Pos Bandar Lampung" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inputLokasi">Lokasi</label>
                    <input type="text" id="inputLokasi" class="form-input" placeholder="Contoh: Jl. Soekarno Hatta, Bandar Lampung" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inputPassword">Password Admin</label>
                    <input type="password" id="inputPassword" class="form-input" placeholder="Masukkan password admin" required autocomplete="off">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" id="btnCancel">Batal</button>
                    <button type="submit" class="btn-modal btn-modal-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Marker -->
    <div id="modalEditMarker" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" id="modalEditClose">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="modal-header">
                <h2 class="modal-title">Edit Kantor Pos</h2>
                <p class="modal-subtitle">Ubah informasi kantor pos</p>
            </div>

            <form id="formEditMarker">
                <input type="hidden" id="editFid" value="">

                <div class="form-group">
                    <label class="form-label" for="editNama">Nama Kantor Pos</label>
                    <input type="text" id="editNama" class="form-input" placeholder="Contoh: Kantor Pos Bandar Lampung" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editLokasi">Lokasi</label>
                    <input type="text" id="editLokasi" class="form-input" placeholder="Contoh: Jl. Soekarno Hatta, Bandar Lampung" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editLat">Latitude</label>
                    <input type="number" id="editLat" class="form-input" step="any" placeholder="Contoh: -5.450000" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editLng">Longitude</label>
                    <input type="number" id="editLng" class="form-input" step="any" placeholder="Contoh: 105.266670" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editPassword">Password Admin</label>
                    <input type="password" id="editPassword" class="form-input" placeholder="Masukkan password admin" required autocomplete="off">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-modal-secondary" id="btnEditCancel">Batal</button>
                    <button type="submit" class="btn-modal btn-modal-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="modalDeleteMarker" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <button class="modal-close" id="modalDeleteClose">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="modal-header">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #DC2626, #EF4444); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 0 30px rgba(220, 38, 38, 0.5);">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h2 class="modal-title" style="text-align: center;">Hapus Kantor Pos?</h2>
                <p class="modal-subtitle" style="text-align: center; color: #9CA3AF;" id="deleteMessage">Apakah Anda yakin ingin menghapus kantor pos ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="deletePassword">Password Admin</label>
                <input type="password" id="deletePassword" class="form-input" placeholder="Masukkan password admin" required autocomplete="off">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal btn-modal-secondary" id="btnDeleteCancel">Batal</button>
                <button type="button" class="btn-modal" id="btnDeleteConfirm" style="background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%); color: white; border-color: #DC2626;">Hapus</button>
            </div>
        </div>
    </div>

    <!-- Modal Detail Lokasi (Enhanced Popup) -->
    <div id="modalLocationDetail" class="modal-overlay">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <button class="modal-close" id="modalDetailClose">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div id="locationDetailContent">
                <!-- Content akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/features.js"></script>
</body>

</html>