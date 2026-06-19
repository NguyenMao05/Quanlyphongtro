/**
 * Khởi tạo bản đồ Leaflet cho trang chi tiết phòng trọ.
 */
(function () {
    function initMotelMap() {
        var el = document.getElementById('motel-map');
        if (!el || typeof L === 'undefined') {
            return;
        }

        var lat = parseFloat(el.dataset.lat);
        var lng = parseFloat(el.dataset.lng);
        var title = el.dataset.title || 'Vị trí phòng trọ';
        var address = el.dataset.address || '';

        if (isNaN(lat) || isNaN(lng)) {
            el.innerHTML = '<div class="map-fallback"><i class="fa-solid fa-triangle-exclamation"></i> Không thể hiển thị bản đồ. Vui lòng kiểm tra tọa độ GPS (vd: 10.762622, 106.660172).</div>';
            return;
        }

        var map = L.map(el, {
            scrollWheelZoom: true,
        }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        L.marker([lat, lng]).addTo(map).bindPopup('<strong>' + escapeHtml(title) + '</strong>' + (address ? '<br>' + escapeHtml(address) : ''));

        var external = document.getElementById('map-external-link');
        if (external) {
            external.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat + ',' + lng);
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 200);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMotelMap);
    } else {
        initMotelMap();
    }
})();
