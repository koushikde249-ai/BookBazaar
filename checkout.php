
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$stmt = $pdo->prepare("
    SELECT b.*, u.name AS seller_name
    FROM books b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
      AND b.status = 'approved'
      AND b.is_sold = 0
    LIMIT 1
");
$stmt->execute([$id ?: 0]);
$book = $stmt->fetch();

if (
    !$book ||
    (int)$book['user_id'] === (int)current_user()['id']
) {
    flash('This book is unavailable or you cannot buy your own listing.', 'error');
    header('Location: index.php');
    exit;
}

$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<style>
.delivery-map {
    height: 350px;
    width: 100%;
    border-radius: 12px;
    border: 1px solid #ddd;
    margin: 12px 0;
    z-index: 1;
}
.map-search-row {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}
.map-search-row input {
    flex: 1;
    min-width: 0;
}
.map-help {
    font-size: 14px;
    color: #666;
    margin: 8px 0 14px;
}
</style>

<section class="form-page">
    <div class="form-intro">
        <span class="eyebrow">One more step</span>
        <h1>Checkout</h1>
        <p>Choose your delivery address on the map.</p>
    </div>

    <div class="form-card">
        <div class="checkout-book">
            <div class="admin-thumb">
                <?php if ($book['image_path']): ?>
                    <img src="<?= e($book['image_path']) ?>" alt="">
                <?php else: ?>
                    BOOK
                <?php endif; ?>
            </div>

            <div>
                <strong><?= e($book['title']) ?></strong>
                <p>
                    by <?= e($book['author']) ?>
                    · Seller: <?= e($book['seller_name']) ?>
                </p>
            </div>

            <strong class="price">
                ₹<?= number_format((float)$book['price'], 2) ?>
            </strong>
        </div>

        <form action="place-order.php" method="post" class="stack-form">
            <input type="hidden" name="csrf_token"
                   value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="book_id"
                   value="<?= (int)$book['id'] ?>">

            <label>
                Full name
                <input name="buyer_name" required maxlength="100"
                       value="<?= e(current_user()['name']) ?>">
            </label>

            <label>
                Email
                <input type="email" name="buyer_email" required maxlength="190"
                       value="<?= e(current_user()['email']) ?>">
            </label>

            <label>
                Mobile number
                <input name="buyer_phone" required maxlength="25"
                       pattern="[0-9+() -]{8,25}"
                       placeholder="Your contact number">
            </label>

            <label>
                Search delivery address
                <div class="map-search-row">
                    <input type="text" id="mapSearch"
                           placeholder="Enter area, road or landmark">
                    <button type="button" class="button"
                            id="searchAddress">Search</button>
                </div>
            </label>

            <div id="deliveryMap" class="delivery-map"></div>

            <button type="button" class="button" id="useMyLocation">
                Use My Current Location
            </button>

            <p class="map-help">
                Move the map and tap your delivery point. The marker can
                also be dragged. Confirm the pin before placing the order.
            </p>

            <label>
                Selected delivery address
                <textarea name="delivery_address" id="delivery_address"
                          required maxlength="1500" rows="3"
                          placeholder="Select a point on the map first"></textarea>
            </label>

            <input type="hidden" name="delivery_latitude"
                   id="delivery_latitude" required>
            <input type="hidden" name="delivery_longitude"
                   id="delivery_longitude" required>

            <div id="locationStatus" class="notice">
                Search for an address or tap the map to select your location.
            </div>

            <label>
                Delivery note (optional)
                <textarea name="delivery_note" maxlength="500" rows="2"
                          placeholder="Preferred delivery time or other details"></textarea>
            </label>

            <fieldset class="payment-options">
                <legend>Payment method</legend>
                <label class="payment-choice">
                    <input type="radio" name="payment_method"
                           value="cod" checked>
                    <span>
                        <strong>Cash on Delivery (COD)</strong>
                        <small>Pay the seller when you receive the book.</small>
                    </span>
                </label>
            </fieldset>

            <div class="notice">
                Buyer and seller will arrange delivery and any delivery
                charges directly. BookBazaar does not provide a delivery service.
            </div>

            <button class="button button-wide" type="submit">
                Place Order
            </button>
        </form>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const map = L.map('deliveryMap').setView([23.2324, 87.0784], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker = null;
let selectedPoint = null;

const addressBox = document.getElementById('delivery_address');
const latBox = document.getElementById('delivery_latitude');
const lngBox = document.getElementById('delivery_longitude');
const statusBox = document.getElementById('locationStatus');

async function reverseAddress(lat, lng) {
    statusBox.textContent = 'Finding address...';

    try {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.search = new URLSearchParams({
            format: 'jsonv2',
            lat: lat,
            lon: lng
        });

        const response = await fetch(url);
        if (!response.ok) throw new Error('Address lookup failed');

        const data = await response.json();
        addressBox.value = data.display_name || `${lat}, ${lng}`;
        statusBox.textContent = 'Location selected. Please verify the address.';
    } catch (error) {
        addressBox.value = `${lat}, ${lng}`;
        statusBox.textContent =
            'Coordinates selected. Please enter the full delivery address manually.';
    }
}

function selectLocation(lat, lng) {
    selectedPoint = { lat, lng };
    latBox.value = lat.toFixed(7);
    lngBox.value = lng.toFixed(7);

    if (!marker) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function () {
            const p = marker.getLatLng();
            selectLocation(p.lat, p.lng);
        });
    } else {
        marker.setLatLng([lat, lng]);
    }

    reverseAddress(lat, lng);
}

map.on('click', function (event) {
    selectLocation(event.latlng.lat, event.latlng.lng);
});

document.getElementById('searchAddress').addEventListener('click', async function () {
    const query = document.getElementById('mapSearch').value.trim();

    if (!query) {
        alert('Please enter an address to search.');
        return;
    }

    statusBox.textContent = 'Searching address...';

    try {
        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.search = new URLSearchParams({
            q: query,
            format: 'jsonv2',
            limit: '1',
            countrycodes: 'in'
        });

        const response = await fetch(url);
        if (!response.ok) throw new Error('Search failed');

        const results = await response.json();

        if (!results.length) {
            statusBox.textContent = 'Address not found. Try a nearby landmark.';
            return;
        }

        const place = results[0];
        const lat = Number(place.lat);
        const lng = Number(place.lon);

        map.setView([lat, lng], 17);
        selectLocation(lat, lng);
    } catch (error) {
        statusBox.textContent = 'Search failed. Try again or select directly on the map.';
    }
});

document.getElementById('useMyLocation').addEventListener('click', function () {
    if (!navigator.geolocation) {
        alert('Your browser does not support location access.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 17);
            selectLocation(lat, lng);
        },
        function () {
            alert('Location access failed. Allow location permission or search your address.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
});

document.querySelector('form[action="place-order.php"]').addEventListener('submit', function (event) {
    if (!selectedPoint || !latBox.value || !lngBox.value || !addressBox.value.trim()) {
        event.preventDefault();
        alert('Please select and confirm your delivery location on the map.');
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
