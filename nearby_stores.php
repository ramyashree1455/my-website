<?php
session_start();

// Must be logged in
if(!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

// Get all stores from database
$stores = [];
$result = $conn->query(
    "SELECT * FROM medical_stores ORDER BY store_name"
);
while($row = $result->fetch_assoc()) {
    $stores[] = $row;
}

// Convert to JSON for JavaScript
$storesJson = json_encode($stores);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nearby Stores - MediCare</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .location-section {
            max-width: 700px;
            margin: 40px auto;
            text-align: center;
            padding: 0 20px;
        }
        .location-section h2 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        .location-section p {
            color: var(--text-light);
            margin-bottom: 30px;
        }
        .location-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .loc-btn {
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 15px;
            cursor: pointer;
            border: none;
        }
        .loc-btn-gps {
            background: linear-gradient(
                135deg, var(--primary), var(--secondary)
            );
            color: white;
        }
        .manual-input {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .manual-input input {
            width: 280px;
            padding: 12px 20px;
            border-radius: 30px;
        }
        .stores-list {
            max-width: 900px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        .store-card {
            background: white;
            border-radius: 18px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.07);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            border: 1px solid rgba(108,99,255,0.1);
            transition: 0.3s;
        }
        .store-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(0,0,0,0.12);
        }
        .store-info h3 {
            font-size: 1.3rem;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .store-info p {
            color: var(--text-light);
            font-size: 0.95rem;
            margin: 3px 0;
        }
        .store-right {
            text-align: right;
        }
        .distance-badge {
            background: linear-gradient(
                135deg, var(--primary), var(--secondary)
            );
            color: white;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        .open-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        .open-now {
            background: #d1fae5;
            color: #047857;
        }
        .closed-now {
            background: #fee2e2;
            color: #dc2626;
        }
        .open-24 {
            background: #e0e7ff;
            color: #4338ca;
        }
        .no-results {
            text-align: center;
            color: var(--text-light);
            padding: 40px;
            font-size: 1.1rem;
        }
        .loading {
            text-align: center;
            padding: 30px;
            color: var(--primary);
            font-size: 1.1rem;
        }
        .section-title {
            text-align: center;
            font-size: 1.5rem;
            color: var(--text-dark);
            margin: 30px 0 20px;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>💊 MediCare</h1>
    <div style="text-align:center; margin-top:5px;">
        <span style="color:var(--text-light); font-size:14px;">
            👋 Hello, 
            <?php echo htmlspecialchars($_SESSION['name']); ?>!
        </span>
        &nbsp;
        <a href="index.php" style="color:var(--primary); 
           font-size:14px; text-decoration:none; 
           font-weight:600;">🏠 Home</a>
        &nbsp;&nbsp;
        <a href="logout.php" style="color:#dc2626; 
           font-size:14px; text-decoration:none; 
           font-weight:600;">🚪 Logout</a>
    </div>
</div>

<!-- Location Input Section -->
<div class="location-section">
    <h2>📍 Find Nearby Medical Stores</h2>
    <p>Allow location access or enter your area manually</p>

    <div class="location-buttons">
        <button class="loc-btn loc-btn-gps" 
                onclick="getGPSLocation()">
            📡 Use My Current Location
        </button>
    </div>

    <div class="manual-input">
        <input type="text" id="manualLocation" 
               placeholder="Or type your area (e.g. Kadri, Mangalore)">
        <button onclick="searchByText()" 
                class="loc-btn loc-btn-gps">
            Search
        </button>
    </div>

    <div id="status-msg" style="color:var(--text-light); 
         font-size:14px; margin-top:10px;"></div>
</div>

<!-- Stores Results -->
<div class="stores-list">
    <div id="stores-container">
        <div class="no-results">
            👆 Enter your location to find nearby stores
        </div>
    </div>
</div>

<script>
// All stores from PHP
const allStores = <?php echo $storesJson; ?>;

// ── Get GPS location ──────────────────────────────
function getGPSLocation() {
    setStatus('📡 Getting your location...');
    
    if(!navigator.geolocation) {
        setStatus('❌ GPS not supported. Enter manually.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            setStatus('✅ Location found! Searching stores...');
            showNearbyStores(lat, lng);
        },
        function(err) {
            setStatus('❌ Location blocked. Please enter manually.');
        }
    );
}

// ── Search by text (city/area name) ──────────────
function searchByText() {
    const text = document.getElementById('manualLocation')
                         .value.trim();
    if(!text) {
        setStatus('Please enter your location!');
        return;
    }

    setStatus('🔍 Searching for: ' + text + '...');

    // Use OpenStreetMap Nominatim (free, no API key!)
    const url = 'https://nominatim.openstreetmap.org/search'
              + '?format=json&q=' 
              + encodeURIComponent(text)
              + '&limit=1';

    fetch(url, {
        headers: { 'Accept-Language': 'en' }
    })
    .then(r => r.json())
    .then(data => {
        if(data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            setStatus('✅ Found: ' + data[0].display_name
                     .split(',').slice(0,3).join(','));
            showNearbyStores(lat, lng);
        } else {
            setStatus('❌ Location not found. Try another name.');
        }
    })
    .catch(() => {
        setStatus('❌ Error finding location. Try GPS instead.');
    });
}

// ── Calculate distance using Haversine formula ───
function calcDistance(lat1, lng1, lat2, lng2) {
    const R = 6371; // Earth radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2)
            + Math.cos(lat1 * Math.PI/180)
            * Math.cos(lat2 * Math.PI/180)
            * Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// ── Check if store is open now ────────────────────
function getOpenStatus(store) {
    if(store.is_open_24h == 1) {
        return '<span class="open-badge open-24">🕐 Open 24/7</span>';
    }

    const now  = new Date();
    const hour = now.getHours();
    const min  = now.getMinutes();
    const current = hour * 60 + min;

    const open  = store.opening_time 
                ? parseInt(store.opening_time.split(':')[0]) * 60
                  + parseInt(store.opening_time.split(':')[1])
                : 9 * 60;
    const close = store.closing_time 
                ? parseInt(store.closing_time.split(':')[0]) * 60
                  + parseInt(store.closing_time.split(':')[1])
                : 21 * 60;

    if(current >= open && current < close) {
        return '<span class="open-badge open-now">✅ Open Now</span>';
    } else {
        const openHr = Math.floor(open/60);
        const openMn = String(open%60).padStart(2,'0');
        return '<span class="open-badge closed-now">'
             + '❌ Closed · Opens ' + openHr + ':' + openMn 
             + '</span>';
    }
}

// ── Show stores sorted by distance ───────────────
function showNearbyStores(userLat, userLng) {
    // Calculate distance for each store
    const storesWithDist = allStores.map(store => {
        const dist = calcDistance(
            userLat, userLng,
            parseFloat(store.latitude),
            parseFloat(store.longitude)
        );
        return { ...store, distance: dist };
    });

    // Sort by distance (nearest first)
    storesWithDist.sort((a, b) => a.distance - b.distance);

    const container = document.getElementById('stores-container');

    if(storesWithDist.length === 0) {
        container.innerHTML = 
            '<div class="no-results">No stores found.</div>';
        return;
    }

    let html = '<div class="section-title">'
             + '📍 Stores Near You (' 
             + storesWithDist.length + ' found)</div>';

    storesWithDist.forEach((store, index) => {
        const distKm   = store.distance.toFixed(1);
        const distText = distKm < 1 
                       ? (store.distance * 1000).toFixed(0) + ' m'
                       : distKm + ' km';

        const mapsUrl = 'https://www.google.com/maps/search/'
                      + encodeURIComponent(store.store_name 
                        + ' ' + store.address);

        html += `
        <div class="store-card">
            <div class="store-info">
                <h3>
                    ${index === 0 ? '🏆 ' : ''}
                    ${store.store_name}
                    ${index === 0 
                      ? '<span style="font-size:12px; '
                      + 'color:#059669; margin-left:8px;">'
                      + '(Nearest)</span>' 
                      : ''}
                </h3>
                <p>📍 ${store.address}, ${store.city}</p>
                <p>📞 ${store.phone}</p>
                <p style="margin-top:8px;">
                    ${getOpenStatus(store)}
                </p>
            </div>
            <div class="store-right">
                <div class="distance-badge">
                    📍 ${distText}
                </div>
                <br>
                <a href="${mapsUrl}" target="_blank"
                   style="font-size:13px; color:var(--primary);
                          text-decoration:none; font-weight:600;">
                    🗺️ View on Maps
                </a>
            </div>
        </div>`;
    });

    container.innerHTML = html;
}

function setStatus(msg) {
    document.getElementById('status-msg').innerHTML = msg;
}

// Search on Enter key press
document.getElementById('manualLocation')
        .addEventListener('keypress', function(e) {
    if(e.key === 'Enter') searchByText();
});
</script>

</body>
</html>