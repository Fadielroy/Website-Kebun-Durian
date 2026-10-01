// peta_script.js - Modul Logika Peta & Pencarian WebGIS

// 1. Definisikan Peta Dasar (Basemaps)
var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 22,
    attribution: '© OpenStreetMap contributors'
});

var satelitLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-GP, and the GIS User Community'
});

// 2. Inisialisasi Peta (Default menggunakan Peta Jalan)
var map = L.map('map', {
    center: [-0.9150, 100.3525],
    zoom: 16,
    layers: [osmLayer]
});

// 3. KONTROL LAYER BASEMAP DENGAN OPTIMASI MOBILE
var baseMaps = {
    "🗺️ Peta Jalan": osmLayer,
    "🛰️ Citra Satelit": satelitLayer
};

// Deteksi apakah perangkat menggunakan layar sentuh/mobile
var isMobile = window.innerWidth <= 768;

// Jika di mobile, buat kontrol tidak collaps (selalu terbuka) agar mudah disentuh tanpa perlu mengklik ikon kecil
var layerControl = L.control.layers(baseMaps, null, { 
    position: 'topright', 
    collapsed: isMobile ? false : true 
}).addTo(map);

// Mencegah benturan event sentuh mobile dengan peta di bawahnya
var controlContainer = layerControl.getContainer();
L.DomEvent.disableClickPropagation(controlContainer);
L.DomEvent.disableScrollPropagation(controlContainer);


// 4. Render Polygon Petak Kebun
petakData.forEach(function(p) {
    try {
        var coords = JSON.parse(p.koordinat_json);
        var polygon = L.polygon(coords, { color: p.warna || '#3498db', weight: 2, fillOpacity: 0.15 }).addTo(map);

        var pkrj = pekerjaanData[p.nama_petak] || [];
        var htmlPekerjaan = '';
        if(pkrj.length > 0) {
            htmlPekerjaan += '<b>Aktivitas Petak:</b><ul style="margin:5px 0; padding-left:15px; font-size:11px;">';
            pkrj.forEach(function(item) {
                htmlPekerjaan += `<li><b>${item.jenis_pekerjaan}</b> (${item.tanggal_mulai})</li>`;
            });
            htmlPekerjaan += '</ul>';
        } else {
            htmlPekerjaan += '<i>Tidak ada aktivitas.</i>';
        }
        polygon.bindPopup(`<b>Blok: ${p.nama_petak}</b><hr style="margin:5px 0;">${htmlPekerjaan}`);
    } catch(e) {}
});

// 5. Render Polygon Zona Irigasi
zonaData.forEach(function(z) {
    try {
        var coords = JSON.parse(z.koordinat_json);
        var zonaPolygon = L.polygon(coords, {
            color: '#00bcd4',
            weight: 2,
            dashArray: '5, 5',
            fillOpacity: 0.1
        }).addTo(map);

        var pkrjZona = pekerjaanData[z.id_zona] || [];
        var htmlZonaKerja = '';
        if(pkrjZona.length > 0) {
            htmlZonaKerja += '<b>Aktivitas Irigasi:</b><ul style="margin:5px 0; padding-left:15px; font-size:11px;">';
            pkrjZona.forEach(function(item) {
                htmlZonaKerja += `<li><b>${item.jenis_pekerjaan}</b> (${item.tanggal_mulai})</li>`;
            });
            htmlZonaKerja += '</ul>';
        } else {
            htmlZonaKerja += '<i>Belum ada catatan penyiraman hari ini.</i>';
        }

        zonaPolygon.bindPopup(`<b>Zona Irigasi: ${z.nama_zona}</b><br><span style="font-size:11px; color:#00bcd4;">Sumber Air: ${z.sumber_air}</span><hr style="margin:5px 0;">${htmlZonaKerja}`);
    } catch(e) {}
});

// 6. Render Titik Pohon Durian
pohonData.forEach(function(po) {
    var marker = L.circleMarker([po.latitude, po.longitude], {
        radius: 7, fillColor: '#e74c3c', color: '#fff', weight: 1.5, fillOpacity: 0.9
    }).addTo(map);

    var pkrjPohon = pekerjaanData[po.nomor_pohon] || [];
    var htmlPekerjaanPohon = '';
    if(pkrjPohon.length > 0) {
        htmlPekerjaanPohon += '<b>Aktivitas Pekerjaan:</b><ul style="margin:5px 0; padding-left:15px; font-size:11px;">';
        pkrjPohon.forEach(function(item) {
            htmlPekerjaanPohon += `<li><b>${item.jenis_pekerjaan}</b> (${item.tanggal_mulai})</li>`;
        });
        htmlPekerjaanPohon += '</ul>';
    } else {
        htmlPekerjaanPohon += '<i>Tidak ada pekerjaan.</i>';
    }

    var htmlBuah = '';
    if(userRole !== 'pekerja') {
        var bh = buahData[po.nomor_pohon] || [];
        if(bh.length > 0) {
            htmlBuah += '<br><b>Yield Buah:</b><ul style="margin:5px 0; padding-left:15px; font-size:11px; color:#48bb78;">';
            bh.forEach(function(b) {
                htmlBuah += `<li>${b.tgl_berbuah}: <b>${b.jumlah_buah} buah</b></li>`;
            });
            htmlBuah += '</ul>';
        }
    }

    // Bersihkan karakter khusus pada nomor pohon untuk dijadikan ID yang aman di DOM HTML
    var safeId = po.nomor_pohon.replace(/[^a-zA-Z0-9-_]/g, '_');
    var targetContainerId = 'riwayat-pohon-' + safeId;

    var popupContent = `
        <div style="min-width: 180px;">
            <b>Pohon: ${po.nomor_pohon}</b><br>
            <span style="font-size:11px; color:#cbd5e0;">Blok: ${po.petak}</span>
            <hr style="margin:5px 0;">
            ${htmlPekerjaanPohon}
            ${htmlBuah}
            <div id="${targetContainerId}" style="margin-top:5px;">
                <i style="font-size:10px; color:#a0aec0;">Memuat riwayat siklus...</i>
            </div>
        </div>
    `;

    marker.bindPopup(popupContent);

    // Event saat popup dibuka untuk mengambil data riwayat via get_riwayat.php
    marker.on('popupopen', function() {
        fetch('get_riwayat.php?nomor=' + encodeURIComponent(po.nomor_pohon))
            .then(response => response.text())
            .then(htmlData => {
                var el = document.getElementById(targetContainerId);
                if (el) {
                    el.innerHTML = htmlData;
                }
            })
            .catch(error => {
                var el = document.getElementById(targetContainerId);
                if (el) {
                    el.innerHTML = "<span style='color:#e53e3e; font-size:10px;'>Gagal memuat riwayat.</span>";
                }
            });
    });
});

// --- FUNGSI JAVASCRIPT PENCARIAN & NAVIGASI PETA ---
function jalankanPencarian() {
    var keyword = document.getElementById('inputPencarian').value;
    var container = document.getElementById('hasilPencarianContainer');
    
    if (keyword.length < 2) {
        container.innerHTML = '<p style="color:#a0aec0; font-size:11px; text-align:center; padding:10px;">Ketik minimal 2 karakter...</p>';
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'cari_data.php?q=' + encodeURIComponent(keyword), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            container.innerHTML = xhr.responseText;
        } else {
            container.innerHTML = '<p style="color:#e53e3e; font-size:11px; text-align:center;">Gagal memuat data pencarian.</p>';
        }
    };
    xhr.send();
}

function fokusKeObjek(tipe, targetId, lat, lon) {
    if (lat !== null && lon !== null && !isNaN(lat) && !isNaN(lon)) {
        map.setView([lat, lon], 20); 
        if (window.innerWidth <= 768) {
            toggleMobileSidebar();
        }
        return;
    }

    if (tipe === 'petak') {
        var found = petakData.find(function(p) { return p.nama_petak === targetId; });
        if (found) {
            try {
                var coords = JSON.parse(found.koordinat_json);
                var poly = L.polygon(coords);
                map.fitBounds(poly.getBounds());
                if (window.innerWidth <= 768) {
                    toggleMobileSidebar();
                }
                return;
            } catch(e) {}
        }
        alert('Layer Petak "' + targetId + '" belum aktif!');
    } else {
        alert('Data ' + tipe.toUpperCase() + ' "' + targetId + '" dipilih.');
    }
}

function toggleMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('mobile-open');
}

function toggleFilterBox(boxId, checkbox) {
    var box = document.getElementById(boxId);
    if (checkbox.checked) { box.classList.add('active'); } else { box.classList.remove('active'); }
}

function toggleAllVarietas(master) {
    var checkboxes = document.querySelectorAll('.var-chk');
    checkboxes.forEach(function(chk) { chk.checked = false; });
}

function switchTab(evt, tabId) {
    var contents = document.getElementsByClassName("tab-content");
    for (var i = 0; i < contents.length; i++) { contents[i].classList.remove("active"); }
    var tabs = document.getElementsByClassName("nav-tab");
    for (var i = 0; i < tabs.length; i++) { tabs[i].classList.remove("active"); }
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}
