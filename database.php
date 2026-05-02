<?php
// ============================================================
//  KONFIGURASI DATABASE MYSQL
//  Sesuaikan nilai berikut dengan server MySQL Anda
// ============================================================
define('DB_HOST', 'localhost');
define('DB_PORT', '44262');
define('DB_NAME', 'railway');
define('DB_USER', 'root');
define('DB_PASS', 'cerXQQokcMkVmtBESZuWKKeYQCRYFJwM@tramway.proxy.rlwy.net');
define('DB_CHARSET', 'utf8mb4');
// ============================================================

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><title>Koneksi Gagal</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-orange-50 flex items-center justify-center min-h-screen p-4"><div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-lg border border-red-200"><div class="text-4xl text-center mb-4">⚠️</div><h1 class="text-xl font-bold text-red-600 text-center mb-3">Koneksi Database Gagal</h1><p class="text-gray-600 text-sm mb-4">Tidak dapat terhubung ke MySQL. Pastikan:</p><ul class="text-sm text-gray-700 space-y-1 list-disc list-inside"><li>MySQL server berjalan</li><li>Database <strong>' . DB_NAME . '</strong> sudah dibuat</li><li>Username &amp; password benar di <code>database.php</code></li><li>Host: <strong>' . DB_HOST . ':' . DB_PORT . '</strong></li></ul><p class="text-xs text-red-400 mt-4 p-2 bg-red-50 rounded-lg">' . htmlspecialchars($e->getMessage()) . '</p></div></body></html>';
        exit;
    }
    return $pdo;
}

function initDB(): PDO {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name  VARCHAR(100) NOT NULL,
        icon  VARCHAR(50)  NOT NULL,
        color VARCHAR(20)  NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS recipes (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title        VARCHAR(255) NOT NULL,
        description  TEXT,
        category_id  INT UNSIGNED,
        prep_time    SMALLINT UNSIGNED DEFAULT 0,
        cook_time    SMALLINT UNSIGNED DEFAULT 0,
        servings     TINYINT UNSIGNED  DEFAULT 1,
        difficulty   ENUM('Mudah','Sedang','Sulit') DEFAULT 'Mudah',
        image_emoji  VARCHAR(20),
        ingredients  JSON,
        steps        JSON,
        tags         VARCHAR(500),
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_recipe_cat FOREIGN KEY (category_id)
            REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $count = (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($count === 0) seedData($db);
    return $db;
}

function seedData(PDO $db): void {
    $catStmt = $db->prepare("INSERT INTO categories (name, icon, color) VALUES (?, ?, ?)");
    foreach ([
        ['Sarapan','Sunrise','#FF6B35'],['Makan Siang','Sun','#F7C59F'],
        ['Makan Malam','Moon','#6B4EFF'],['Camilan','Cookie','#FF4D6D'],
        ['Minuman','Coffee','#2EC4B6'],['Dessert','Cake','#E63946'],
    ] as $c) $catStmt->execute($c);

    $sql = "INSERT INTO recipes (title,description,category_id,prep_time,cook_time,servings,difficulty,image_emoji,ingredients,steps,tags) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $db->prepare($sql);
    $U = JSON_UNESCAPED_UNICODE;

    $recipes = [
        ['Nasi Goreng Spesial','Nasi goreng lezat dengan bumbu rempah khas Indonesia yang kaya cita rasa, dilengkapi telur mata sapi dan kerupuk renyah.',2,10,15,2,'Mudah','🍳',
            json_encode(['2 piring nasi putih (dingin)','2 butir telur','3 siung bawang putih, cincang','5 siung bawang merah, iris','2 cabai merah, iris','2 sdm kecap manis','1 sdm saus tiram','Garam dan merica secukupnya','Minyak goreng','Daun bawang untuk taburan'],$U),
            json_encode(['Panaskan minyak, tumis bawang putih dan bawang merah hingga harum keemasan.','Masukkan cabai merah, aduk rata selama 1 menit.','Masukkan telur, orak-arik hingga setengah matang.','Tambahkan nasi, aduk rata dengan api besar.','Tuangkan kecap manis dan saus tiram, aduk hingga merata dan nasi sedikit kering.','Bumbui dengan garam dan merica, koreksi rasa.','Sajikan dengan taburan daun bawang dan kerupuk.'],$U),
            'nasi,goreng,indonesia,cepat'],
        ['Ayam Bakar Madu','Ayam bakar empuk dengan marinade madu dan rempah pilihan, dimasak hingga karamelisasi sempurna dengan aroma yang menggugah selera.',3,30,45,4,'Sedang','🍗',
            json_encode(['1 ekor ayam, potong 8 bagian','4 sdm madu','3 sdm kecap manis','2 sdm saus tiram','1 sdm air jeruk nipis','4 siung bawang putih, haluskan','1 sdt jahe, parut','1 sdt kunyit bubuk','Garam dan merica secukupnya'],$U),
            json_encode(['Campurkan madu, kecap manis, saus tiram, jeruk nipis, bawang putih, jahe, kunyit, garam, dan merica.','Lumuri ayam dengan bumbu marinasi, diamkan minimal 30 menit di kulkas.','Panggang ayam di atas bara api atau oven 180°C selama 20 menit.','Balik ayam dan olesi lagi dengan sisa bumbu marinasi.','Panggang kembali 15-20 menit hingga matang dan karamelisasi.','Sajikan hangat dengan nasi dan lalapan segar.'],$U),
            'ayam,bakar,madu,panggang'],
        ['Soto Ayam Bening','Soto ayam dengan kuah bening segar dan rempah pilihan, disajikan dengan soun, telur rebus, dan perasan jeruk nipis.',2,20,60,6,'Sedang','🍜',
            json_encode(['1 ekor ayam kampung','2 liter air','3 lembar daun salam','2 batang serai, memarkan','3 cm lengkuas, memarkan','5 siung bawang putih','8 siung bawang merah','1 sdt kunyit bubuk','Soun secukupnya, rendam','3 butir telur rebus','Tauge, daun bawang, seledri','Bawang goreng untuk pelengkap'],$U),
            json_encode(['Rebus ayam dalam air dingin hingga mendidih, buang buih yang muncul.','Haluskan bawang putih, bawang merah, dan kunyit.','Tumis bumbu halus hingga harum, masukkan ke dalam rebusan ayam.','Tambahkan daun salam, serai, dan lengkuas.','Masak dengan api kecil selama 45 menit hingga ayam empuk.','Angkat ayam, suwir-suwir dagingnya.','Sajikan kuah dengan soun, ayam suwir, tauge, telur, dan taburan bawang goreng.'],$U),
            'soto,ayam,kuah,bening,tradisional'],
        ['Pisang Goreng Crispy','Pisang goreng renyah dengan balutan tepung crispy yang golden brown sempurna, cocok untuk camilan sore hari bersama keluarga.',4,10,20,4,'Mudah','🍌',
            json_encode(['6 buah pisang raja, kupas','150 gr tepung terigu','50 gr tepung beras','1 sdt baking powder','1/2 sdt garam','200 ml air dingin','Minyak goreng untuk menggoreng'],$U),
            json_encode(['Campurkan tepung terigu, tepung beras, baking powder, dan garam.','Tuang air dingin sedikit demi sedikit sambil diaduk hingga adonan kental.','Belah pisang menjadi dua secara memanjang.','Celupkan pisang ke dalam adonan tepung hingga rata.','Goreng dalam minyak panas dengan api sedang hingga golden brown.','Tiriskan dan sajikan selagi hangat.'],$U),
            'pisang,goreng,crispy,camilan'],
        ['Es Teh Manis Susu','Minuman segar perpaduan teh hitam pekat dengan susu kental manis dan es batu, kesegaran terbaik untuk hari yang panas.',5,5,10,2,'Mudah','🧋',
            json_encode(['2 kantong teh hitam','400 ml air mendidih','4 sdm susu kental manis','Es batu secukupnya','2 sdm gula pasir (opsional)'],$U),
            json_encode(['Seduh kantong teh dengan air mendidih selama 3-5 menit.','Angkat kantong teh, jangan diperas.','Tambahkan gula jika suka lebih manis, aduk.','Dinginkan sebentar di suhu ruang.','Siapkan gelas dengan es batu.','Tuang teh ke dalam gelas berisi es.','Tambahkan susu kental manis, aduk atau biarkan bergradasi cantik.'],$U),
            'teh,susu,es,minuman,segar'],
        ['Bubur Ayam Jakarta','Bubur nasi lembut dengan topping ayam suwir berbumbu, cakwe, bawang goreng, dan kuah kaldu gurih khas Jakarta.',1,15,50,4,'Sedang','🥣',
            json_encode(['200 gr beras, cuci bersih','1.5 liter kaldu ayam','200 gr dada ayam, rebus suwir','3 siung bawang putih, goreng crispy','2 batang daun bawang, iris','2 sdm kecap asin','1 sdt minyak wijen','Jahe 2 cm, memarkan','Cakwe untuk pelengkap','Bawang goreng'],$U),
            json_encode(['Didihkan kaldu ayam, masukkan beras dan jahe.','Masak dengan api kecil sambil terus diaduk hingga beras hancur menjadi bubur (40 menit).','Bumbu ayam: tumis bawang putih, masukkan ayam suwir, kecap asin, garam merica.','Koreksi kekentalan bubur, tambah air panas jika terlalu kental.','Tambahkan minyak wijen ke dalam bubur, aduk.','Sajikan bubur dengan topping ayam suwir, cakwe, daun bawang, dan bawang goreng.'],$U),
            'bubur,ayam,jakarta,sarapan'],
        ['Klepon Pandan','Kue klepon tradisional berwarna hijau dari pandan dengan isian gula merah yang meleleh di mulut dan balutan kelapa parut segar.',6,20,15,20,'Sedang','🟢',
            json_encode(['200 gr tepung ketan','150 ml air daun pandan (blender pandan + air)','100 gr gula merah, potong kecil','1/4 sdt garam','150 gr kelapa parut, kukus dengan sedikit garam'],$U),
            json_encode(['Campur tepung ketan, garam, dan air pandan sedikit demi sedikit hingga adonan bisa dibentuk.','Ambil adonan sebesar kelereng, pipihkan, isi dengan potongan gula merah.','Bulatkan kembali dengan rapat agar gula tidak bocor.','Rebus dalam air mendidih hingga klepon mengapung (sekitar 5 menit).','Angkat, tiriskan sebentar.','Gulingkan dalam kelapa parut kukus selagi masih hangat.','Sajikan segera.'],$U),
            'klepon,pandan,tradisional,dessert,kue'],
        ['Rendang Daging Sapi','Rendang daging sapi empuk bercita rasa kaya dengan 40+ rempah pilihan, dimasak perlahan hingga kering dan berwarna coklat pekat yang khas.',3,30,180,8,'Sulit','🥩',
            json_encode(['1 kg daging sapi, potong dadu','800 ml santan kental','400 ml santan encer','5 lembar daun jeruk','3 lembar daun salam','2 batang serai, memarkan','2 cm lengkuas, memarkan','Bumbu halus: 15 cabai merah, 10 bawang merah, 6 bawang putih, 3 cm jahe, 3 cm kunyit, 2 cm lengkuas'],$U),
            json_encode(['Haluskan semua bumbu halus hingga lembut.','Campurkan santan kental dan encer dalam wajan besar.','Masukkan bumbu halus, daun jeruk, salam, serai, lengkuas.','Masukkan daging sapi, aduk rata.','Masak dengan api sedang sambil terus diaduk hingga mendidih.','Kecilkan api, masak 2-3 jam sambil sesekali diaduk hingga kuah menyusut dan daging berwarna coklat gelap.','Rendang siap saat minyak keluar dan daging sudah kering berwarna coklat pekat.'],$U),
            'rendang,daging,padang,tradisional,rempah'],
    ];

    foreach ($recipes as $r) $stmt->execute($r);
}
