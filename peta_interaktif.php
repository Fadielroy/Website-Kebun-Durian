<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>WebGIS Manajemen Kebun Durian - Mobile Edition</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a202c; color: #e2e8f0; display: flex; height: 100vh; overflow: hidden; }
        
        #sidebar { width: 340px; background: #2d3748; display: flex; flex-direction: column; border-right: 1px solid #4a5568; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.3); flex-shrink: 0; transition: transform 0.3s ease; }
        .sidebar-header { padding: 12px 15px; background: #1a202c; border-bottom: 1px solid #4a5568; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header h1 { font-size: 15px; margin: 0 0 3px 0; color: #48bb78; }
        
        .close-sidebar-btn { display: none; background: #e53e3e; color: #fff; border: none; padding: 5px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }

        .user-panel { background: #374151; padding: 8px 12px; margin: 8px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; border: 1px solid #4b5563; }
        .logout-btn { background: #e53e3e; color: #fff; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: bold; }
        .logout-btn:hover { background: #c53030; }

        .nav-tabs { display: flex; background: #1a202c; border-bottom: 1px solid #4a5568; }
        .nav-tab { flex: 1; padding: 8px 4px; text-align: center; font-size: 11px; color: #a0aec0; cursor: pointer; border: none; background: none; font-weight: bold; }
        .nav-tab.active { color: #48bb78; border-bottom: 2px solid #48bb78; background: #2d3748; }

        .tab-content { padding: 12px; flex: 1; overflow-y: auto; display: none; }
        .tab-content.active { display: block; }
        .section-title { font-size: 12px; font-weight: bold; color: #cbd5e0; margin-bottom: 8px; border-bottom: 1px solid #4a5568; padding-bottom: 4px; }

        #map { flex-grow: 1; height: 100%; position: relative; }
        
        /* Styling Scanner Barcode/QR */
        #reader { width: 100%; border-radius: 6px; overflow: hidden; margin-top: 8px; display: none; background: #000; }
        .btn-scan { width: 100%; padding: 8px; background: #3182ce; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 8px; font-size: 11px; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-scan:hover { background: #2b6cb0; }

        /* Tombol Mengambang (Floating Menu Button) khusus HP */
        #mobile-menu-btn {
            display: none;
            position: absolute;
            top: 15px;
            left: 15px;
            background: #2d3748;
            color: #48bb78;
            border: 2px solid #4a5568;
            padding: 8px 12px;
            border-radius: 6px;
            z-index: 999;
            font-weight: bold;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
            cursor: pointer;
        }

        /* Styling Box Legenda di Sidebar Bawah */
        .sidebar-legend {
            background: #1a202c;
            padding: 12px;
            border-top: 1px solid #4a5568;
            font-size: 11px;
        }
        .sidebar-legend h4 { margin: 0 0 8px 0; font-size: 12px; color: #48bb78; border-bottom: 1px solid #4a5568; padding-bottom: 4px; }
        .legend-item { display: flex; align-items: center; margin-bottom: 6px; color: #cbd5e0; }
        .legend-color { width: 14px; height: 14px; border-radius: 3px; margin-right: 8px; flex-shrink: 0; }
        .legend-line { width: 16px; height: 3px; border-top: 2px dashed #00bcd4; margin-right: 8px; flex-shrink: 0; }
        .legend-circle { width: 10px; height: 10px; border-radius: 50%; background: #e74c3c; border: 1.5px solid #fff; margin-right: 10px; margin-left: 2px; flex-shrink: 0; }

        label { font-size: 11px; color: #a0aec0; display: block; margin-bottom: 3px; }
        select, input[type="date"], input[type="text"] { width: 100%; padding: 6px; margin-bottom: 8px; background: #1a202c; color: #fff; border: 1px solid #4a5568; border-radius: 6px; box-sizing: border-box; font-size: 11px; }
        
        .layer-box { background: #1a202c; padding: 8px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #4a5568; }
        .layer-item { display: flex; align-items: center; margin-bottom: 4px; font-size: 11px; cursor: pointer; color: #e2e8f0; }
        .layer-item input { margin-right: 6px; transform: scale(1.1); cursor: pointer; }

        .filter-group { background: #374151; padding: 8px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #48bb78; display: none; }
        .filter-group.active { display: block; }
        
        .btn-filter { width: 100%; padding: 9px; background: #48bb78; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 8px; font-size: 12px; }
        .btn-filter:hover { background: #38a169; }

        .checkbox-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-bottom: 8px; font-size: 10px; max-height: 90px; overflow-y: auto; background: #1a202c; padding: 5px; border-radius: 4px; border: 1px solid #4a5568; }

        @media screen and (max-width: 768px) {
            body { flex-direction: column; }
            #sidebar {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                width: 85%;
                max-width: 320px;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            #sidebar.mobile-open {
                transform: translateX(0);
            }
            .close-sidebar-btn { display: inline-block; }
            #mobile-menu-btn { display: block; }
        }
    </style>
</head>
<body>

    <!-- TOMBOL MENU MENGAMBANG KHUSUS HP -->
    <button id="mobile-menu-btn" onclick="toggleMobileSidebar()">☰ Menu & Filter</button>

    <!-- SIDEBAR -->
    <div id="sidebar">
        <div class="sidebar-header">
            <div>
                <h1>WebGIS Durian Pro</h1>
                <span style="font-size: 10px; color: #a0aec0;">Mobile Edition</span>
            </div>
            <button class="close-sidebar-btn" onclick="toggleMobileSidebar()">Tutup ✕</button>
        </div>

        <div class="user-panel">
            <div>
                👤 <b><?php echo htmlspecialchars($nama_user); ?></b><br>
                <span style="font-size: 9px; color: #9ca3af; text-transform: uppercase;">Role: <?php echo htmlspecialchars($role_user); ?></span>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <!-- NAVIGASI TAB SIDEBAR -->
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="switchTab(event, 'infoTab')">Info</button>
            <button class="nav-tab" onclick="switchTab(event, 'cariTab')">Cari</button>
            <button class="nav-tab" onclick="switchTab(event, 'filterTab')">Layer</button>
        </div>

        <!-- TAB 1: INFORMASI -->
        <div id="infoTab" class="tab-content active">
            <div class="section-title">Petunjuk Peta & Zona</div>
            <p style="font-size: 11px; color: #cbd5e0; line-height: 1.4;">
                Gunakan tab <b>Cari</b> untuk melacak pohon atau pekerja secara cepat, dan tab <b>Layer</b> untuk mengatur tampilan peta. Gunakan ikon pengatur peta di pojok kanan atas peta untuk mengubah jenis tampilan basemap (Jalan/Satelit).
            </p>
        </div>
        
        <!-- TAB 2: PENCARIAN & SCANNER -->
        <div id="cariTab" class="tab-content">
            <div class="section-title">Pencarian & Scan Barcode</div>
            
            <!-- Tombol Pemicu Scanner Kamera -->
            <button type="button" class="btn-scan" onclick="toggleScanner()">
                📷 <span>Scan Barcode / QR Pohon & Petak</span>
            </button>

            <!-- Area Tampilan Kamera Scanner -->
            <div id="reader"></div>
            <div id="scan-result" style="font-size: 11px; color: #48bb78; margin-bottom: 8px; font-weight: bold; text-align: center;"></div>

            <label>Atau Ketik Manual:</label>
            <input type="text" id="inputPencarian" placeholder="Ketik nomor pohon / petak..." onkeyup="jalankanPencarian()" autocomplete="off">
            
            <div id="hasilPencarianContainer" style="max-height: 260px; overflow-y: auto; margin-top: 8px;">
                <p style="color:#a0aec0; font-size:11px; text-align:center; padding:10px;">Hasil pencarian atau scan akan tampil di sini...</p>
            </div>
        </div>

        <!-- TAB 3: KONTROL LAYER & FILTER -->
        <div id="filterTab" class="tab-content">
            <form method="GET" action="index.php">
                
                <div class="section-title">Fokus Wilayah</div>
                <label>Pilih Blok / Petak Kebun:</label>
                <select name="petak">
                    <option value="ALL">-- Semua Petak Kebun --</option>
                    <?php 
                    foreach($list_petak as $ptk) {
                        $sel = ($filter_petak == $ptk) ? 'selected' : '';
                        echo '<option value="' . $ptk . '" ' . $sel . '>' . $ptk . '</option>';
                    }
                    ?>
                </select>

                <div class="section-title" style="margin-top:12px;">Katalog & Filter Layer</div>
                
                <!-- LAYER 1: PETAK -->
                <div class="layer-box">
                    <label class="layer-item">
                        <input type="checkbox" name="layer_petak" id="chkPetak" <?php echo $show_petak ? 'checked' : ''; ?> onchange="toggleFilterBox('boxPetak', this)">
                        <b>Layer Batas Petak / Blok</b>
                    </label>
                </div>

                <!-- LAYER 2: ZONA IRIGASI -->
                <div class="layer-box">
                    <label class="layer-item">
                        <input type="checkbox" name="layer_zona" id="chkZona" <?php echo $show_zona ? 'checked' : ''; ?> onchange="toggleFilterBox('boxZona', this)">
                        <b>Layer Zona Irigasi (Air)</b>
                    </label>
                </div>

                <!-- LAYER 3: POHON -->
                <div class="layer-box">
                    <label class="layer-item">
                        <input type="checkbox" name="layer_pohon" id="chkPohon" <?php echo $show_pohon ? 'checked' : ''; ?> onchange="toggleFilterBox('boxPohon', this)">
                        <b>Layer Titik Pohon Durian</b>
                    </label>
                    <div id="boxPohon" class="filter-group <?php echo $show_pohon ? 'active' : ''; ?>">
                        <?php if($role_user != 'pekerja'): ?>
                        <label>Sortir Varietas Pohon:</label>
                        <div class="checkbox-grid">
                            <label>
                                <?php $isAllChecked = (empty($filter_varietas) OR in_array('ALL', $filter_varietas)) ? 'checked' : ''; ?>
                                <input type="checkbox" name="varietas[]" value="ALL" onclick="toggleAllVarietas(this)" <?php echo $isAllChecked; ?>> 
                                <b>[Semua]</b>
                            </label>
                            <?php 
                            foreach($list_varietas as $var) {
                                $isChecked = in_array($var, $filter_varietas) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="varietas[]" value="' . htmlspecialchars($var) . '" class="var-chk" ' . $isChecked . '> ' . htmlspecialchars($var) . '</label>';
                            }
                            ?>
                        </div>
                        <?php endif; ?>

                        <label>Tahun Tanam:</label>
                        <select name="tahun_tanam">
                            <option value="ALL">-- Semua Tahun Tanam --</option>
                            <?php 
                            foreach($list_tahun as $thn) {
                                $sel = ($filter_tahun_tanam == $thn) ? 'selected' : '';
                                echo '<option value="' . $thn . '" ' . $sel . '>Tahun ' . $thn . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- LAYER 4: PEKERJAAN -->
                <div class="layer-box">
                    <label class="layer-item">
                        <input type="checkbox" name="layer_kerja" id="chkKerja" <?php echo $show_kerja ? 'checked' : ''; ?> onchange="toggleFilterBox('boxKerja', this)">
                        <b>Layer Aktivitas Pekerjaan Lapangan</b>
                    </label>
                    <div id="boxKerja" class="filter-group <?php echo $show_kerja ? 'active' : ''; ?>">
                        <label>Target Pekerjaan:</label>
                        <select name="kerja_target">
                            <option value="ALL" <?php echo ($filter_kerja_target == 'ALL') ? 'selected' : ''; ?>>Semua Target</option>
                            <option value="PETAK" <?php echo ($filter_kerja_target == 'PETAK') ? 'selected' : ''; ?>>Skala Petak</option>
                            <option value="POHON" <?php echo ($filter_kerja_target == 'POHON') ? 'selected' : ''; ?>>Skala Pohon</option>
                            <option value="ZONA_IRIGASI" <?php echo ($filter_kerja_target == 'ZONA_IRIGASI') ? 'selected' : ''; ?>>Skala Zona Irigasi</option>
                            <option value="MASSAL_BLOK" <?php echo ($filter_kerja_target == 'MASSAL_BLOK') ? 'selected' : ''; ?>>Skala Massal Blok</option>
                        </select>

                        <?php if($role_user != 'pekerja'): ?>
                        <label>Nama Pekerja:</label>
                        <select name="pekerja">
                            <option value="ALL">-- Semua Pekerja --</option>
                            <?php 
                            foreach($list_pekerja as $pkj) {
                                $sel = ($filter_pekerja == $pkj['id_pekerja']) ? 'selected' : '';
                                echo '<option value="' . $pkj['id_pekerja'] . '" ' . $sel . '>' . $pkj['nama'] . '</option>';
                            }
                            ?>
                        </select>
                        <?php endif; ?>

                        <label>Dari Tanggal Pengerjaan:</label>
                        <input type="date" name="start" value="<?php echo htmlspecialchars($filter_start); ?>">

                        <label>Sampai Tanggal Pengerjaan:</label>
                        <input type="date" name="end" value="<?php echo htmlspecialchars($filter_end); ?>">
                    </div>
                </div>

                <!-- LAYER 5: POHON BERBUAH -->
                <?php if($role_user != 'pekerja'): ?>
                <div class="layer-box">
                    <label class="layer-item">
                        <input type="checkbox" name="layer_buah" id="chkBuah" <?php echo $show_buah ? 'checked' : ''; ?> onchange="toggleFilterBox('boxBuah', this)">
                        <b>Layer Yield / Pohon Berbuah</b>
                    </label>
                    <div id="boxBuah" class="filter-group <?php echo $show_buah ? 'active' : ''; ?>">
                        <label>Varietas Buah:</label>
                        <select name="buah_varietas">
                            <option value="ALL">-- Semua Varietas Buah --</option>
                            <?php 
                            foreach($list_varietas as $var) {
                                echo '<option value="' . htmlspecialchars($var) . '">' . htmlspecialchars($var) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-filter">Terapkan Layer & Filter</button>
            </form>
        </div>

        <!-- BOX LEGENDA -->
        <div class="sidebar-legend">
            <h4>Legenda Peta</h4>
            <?php if($show_petak): ?>
            <div class="legend-item">
                <div class="legend-color" style="background: rgba(52, 152, 219, 0.4); border: 1px solid #3498db;"></div>
                <span>Batas Petak / Blok</span>
            </div>
            <?php endif; ?>
            
            <?php if($show_zona): ?>
            <div class="legend-item">
                <div class="legend-line"></div>
                <span>Zona Irigasi (Air)</span>
            </div>
            <?php endif; ?>

            <?php if($show_pohon): ?>
            <div class="legend-item">
                <div class="legend-circle"></div>
                <span>Titik Pohon Durian</span>
            </div>
            <?php endif; ?>
            
            <?php if(!$show_petak && !$show_zona && !$show_pohon): ?>
            <i style="color: #a0aec0;">Belum ada layer yang aktif. Centang layer di menu Layer untuk menampilkan data.</i>
            <?php endif; ?>
        </div>
    </div>

    <!-- PETA UTAMA (Kontrol basemap otomatis ditangani Leaflet di pojok kanan atas) -->
    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Pustaka Html5-Qrcode CDN Utama & Cadangan -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        if (typeof Html5Qrcode === 'undefined') {
            var scriptFallback = document.createElement('script');
            scriptFallback.src = "https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js";
            document.head.appendChild(scriptFallback);
        }
    </script>
    
    <!-- PENGHUBUNG DATA PHP KE JAVASCRIPT -->
    <script>
        var petakData = <?php echo json_encode($data_polygon); ?>;
        var zonaData = <?php echo json_encode($data_zona); ?>;
        var pohonData = <?php echo json_encode($data_pohon); ?>;
        var pekerjaanData = <?php echo json_encode($data_pekerjaan); ?>;
        var buahData = <?php echo json_encode($data_buah); ?>;
        var userRole = "<?php echo $role_user; ?>";
    </script>

    <!-- MEMANGGIL MODUL SCRIPT EKSTERNAL -->
    <script src="scanner_module.js"></script>
    <script src="peta_script.js"></script>
</body>
</html>
