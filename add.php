<?php
require_once 'database.php';
$db = initDB();

$errors = [];
$success = false;

// Get categories
$categories = $db->query("SELECT * FROM categories")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $prep_time = (int)($_POST['prep_time'] ?? 0);
    $cook_time = (int)($_POST['cook_time'] ?? 0);
    $servings = (int)($_POST['servings'] ?? 1);
    $difficulty = trim($_POST['difficulty'] ?? 'Mudah');
    $image_emoji = trim($_POST['image_emoji'] ?? '🍽️');
    $tags = trim($_POST['tags'] ?? '');
    
    $rawIng = trim($_POST['ingredients'] ?? '');
    $rawSteps = trim($_POST['steps'] ?? '');
    
    $ingredientsList = array_filter(array_map('trim', explode("\n", $rawIng)));
    $stepsList = array_filter(array_map('trim', explode("\n", $rawSteps)));

    if (!$title) $errors[] = 'Judul resep wajib diisi';
    if (empty($ingredientsList)) $errors[] = 'Bahan-bahan wajib diisi';
    if (empty($stepsList)) $errors[] = 'Langkah memasak wajib diisi';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO recipes (title, description, category_id, prep_time, cook_time, servings, difficulty, image_emoji, ingredients, steps, tags) VALUES (:title,:desc,:cat,:prep,:cook,:serv,:diff,:emoji,:ing,:steps,:tags)");
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':desc', $description);
        $stmt->bindValue(':cat', $category_id ?: null);
        $stmt->bindValue(':prep', $prep_time);
        $stmt->bindValue(':cook', $cook_time);
        $stmt->bindValue(':serv', $servings);
        $stmt->bindValue(':diff', $difficulty);
        $stmt->bindValue(':emoji', $image_emoji);
        $stmt->bindValue(':ing', json_encode(array_values($ingredientsList)));
        $stmt->bindValue(':steps', json_encode(array_values($stepsList)));
        $stmt->bindValue(':tags', $tags);
        $stmt->execute();
        $newId = $db->lastInsertId();
        header('Location: detail.php?id=' . $newId);
        exit;
    }
}

$emojis = ['🍳','🍜','🍗','🥩','🥗','🍱','🍛','🥘','🫕','🍲','🍚','🥣','🧆','🥙','🌮','🫔','🍝','🥞','🧇','🍰','🎂','🍮','🍡','🍢','🧁','🍩','🍪','🧋','🍵','🥤','🍌','🍇','🍓','🫐'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Resep - DapurKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FFFAF5; }
        .font-display { font-family: 'Playfair Display', serif; }
        .form-input { border: 2px solid #FED7AA; border-radius: 12px; padding: 12px 16px; width: 100%; background: white; transition: all 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; }
        .form-input:focus { outline: none; border-color: #FF6B35; box-shadow: 0 0 0 3px rgba(255,107,53,0.15); }
        .form-label { font-weight: 600; color: #374151; margin-bottom: 6px; display: block; font-size: 14px; }
        .emoji-option { cursor: pointer; padding: 8px; border-radius: 12px; font-size: 24px; border: 2px solid transparent; transition: all 0.2s; text-align: center; }
        .emoji-option:hover { background: #FFF3E0; border-color: #FF6B35; transform: scale(1.1); }
        .emoji-option.selected { background: #FFF3E0; border-color: #FF6B35; transform: scale(1.1); }
        .floating-btn { background: linear-gradient(135deg, #FF6B35, #FF4D6D); box-shadow: 0 8px 25px rgba(255,107,53,0.4); transition: all 0.3s; }
        .floating-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(255,107,53,0.5); }
    </style>
</head>
<body class="min-h-screen">

<!-- Navbar -->
<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-orange-100">
    <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="index.php" class="flex items-center gap-1 text-gray-500 hover:text-orange-500 transition text-sm font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
            <span class="text-gray-200">|</span>
            <a href="index.php" class="text-xl font-bold" style="font-family:'Playfair Display',serif; color:#FF6B35;">DapurKita</a>
        </div>
    </div>
</nav>

<div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="text-4xl mb-3">📝</div>
        <h1 class="text-3xl font-bold font-display text-gray-900">Tambah Resep Baru</h1>
        <p class="text-gray-500 mt-2">Bagikan resep favoritmu ke semua orang</p>
    </div>

    <?php if ($errors): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5"></i>
            <div>
                <?php foreach ($errors as $e): ?>
                <p class="text-rose-700 text-sm font-medium"><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <!-- Basic Info Card -->
        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-orange-400"></i>
                Informasi Dasar
            </h2>
            
            <!-- Emoji Picker -->
            <div class="mb-5">
                <label class="form-label">Icon Resep</label>
                <input type="hidden" name="image_emoji" id="emojiInput" value="<?= htmlspecialchars($_POST['image_emoji'] ?? '🍳') ?>">
                <div class="grid grid-cols-8 sm:grid-cols-12 gap-1 p-3 bg-orange-50 rounded-2xl">
                    <?php foreach ($emojis as $e): ?>
                    <div class="emoji-option <?= ($_POST['image_emoji'] ?? '🍳') == $e ? 'selected' : '' ?>"
                         onclick="selectEmoji('<?= $e ?>', this)"><?= $e ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Judul Resep <span class="text-rose-500">*</span></label>
                <input type="text" name="title" class="form-input" placeholder="contoh: Nasi Goreng Spesial" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-input" rows="3" placeholder="Ceritakan sedikit tentang resep ini..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-input">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tingkat Kesulitan</label>
                    <select name="difficulty" class="form-input">
                        <?php foreach (['Mudah','Sedang','Sulit'] as $d): ?>
                        <option value="<?= $d ?>" <?= ($_POST['difficulty'] ?? 'Mudah') == $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Time & Servings Card -->
        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                <i data-lucide="clock" class="w-5 h-5 text-orange-400"></i>
                Waktu & Porsi
            </h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Persiapan (menit)</label>
                    <input type="number" name="prep_time" class="form-input" placeholder="10" min="0" value="<?= htmlspecialchars($_POST['prep_time'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Memasak (menit)</label>
                    <input type="number" name="cook_time" class="form-input" placeholder="30" min="0" value="<?= htmlspecialchars($_POST['cook_time'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Porsi</label>
                    <input type="number" name="servings" class="form-input" placeholder="4" min="1" value="<?= htmlspecialchars($_POST['servings'] ?? '4') ?>">
                </div>
            </div>
        </div>

        <!-- Ingredients Card -->
        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i data-lucide="list-checks" class="w-5 h-5 text-orange-400"></i>
                Bahan-Bahan <span class="text-rose-500">*</span>
            </h2>
            <p class="text-gray-500 text-sm mb-4">Tulis satu bahan per baris</p>
            <textarea name="ingredients" class="form-input font-mono text-sm" rows="8" placeholder="200 gr tepung terigu&#10;3 butir telur&#10;100 ml susu&#10;1 sdt garam"><?= htmlspecialchars($_POST['ingredients'] ?? '') ?></textarea>
        </div>

        <!-- Steps Card -->
        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i data-lucide="chef-hat" class="w-5 h-5 text-amber-400"></i>
                Langkah Memasak <span class="text-rose-500">*</span>
            </h2>
            <p class="text-gray-500 text-sm mb-4">Tulis satu langkah per baris secara berurutan</p>
            <textarea name="steps" class="form-input text-sm" rows="8" placeholder="Campur tepung dan telur hingga rata&#10;Tambahkan susu sedikit demi sedikit&#10;Aduk terus hingga adonan licin"><?= htmlspecialchars($_POST['steps'] ?? '') ?></textarea>
        </div>

        <!-- Tags -->
        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i data-lucide="tag" class="w-5 h-5 text-teal-500"></i>
                Tags
            </h2>
            <p class="text-gray-500 text-sm mb-4">Pisahkan dengan koma</p>
            <input type="text" name="tags" class="form-input" placeholder="ayam, goreng, pedas, indonesia" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
        </div>

        <!-- Submit -->
        <div class="flex gap-3">
            <a href="index.php" class="flex-1 py-4 border-2 border-orange-200 text-orange-600 rounded-2xl font-semibold text-center hover:bg-orange-50 transition">
                Batal
            </a>
            <button type="submit" class="floating-btn flex-1 py-4 text-white rounded-2xl font-semibold flex items-center justify-center gap-2">
                <i data-lucide="check" class="w-5 h-5"></i>
                Simpan Resep
            </button>
        </div>
    </form>
</div>

<script>
function selectEmoji(emoji, el) {
    document.getElementById('emojiInput').value = emoji;
    document.querySelectorAll('.emoji-option').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
}
lucide.createIcons();
</script>
</body>
</html>
