<?php
require_once 'database.php';
$db = initDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$recipe = $stmt->fetch();
if (!$recipe) { header('Location: index.php'); exit; }

$categories = $db->query("SELECT * FROM categories")->fetchAll();

$errors = [];

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
        $stmt = $db->prepare("UPDATE recipes SET title=:title, description=:desc, category_id=:cat, prep_time=:prep, cook_time=:cook, servings=:serv, difficulty=:diff, image_emoji=:emoji, ingredients=:ing, steps=:steps, tags=:tags WHERE id=:id");
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
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        header('Location: detail.php?id=' . $id);
        exit;
    }
    // On error, update recipe with posted data
    $recipe = array_merge($recipe, $_POST);
}

// Pre-fill ingredients and steps
$ingredients = json_decode($recipe['ingredients'], true) ?: [];
$steps = json_decode($recipe['steps'], true) ?: [];
$ingText = is_array($_POST['ingredients'] ?? null) ? $_POST['ingredients'] : implode("\n", $ingredients);
$stepsText = is_array($_POST['steps'] ?? null) ? $_POST['steps'] : implode("\n", $steps);

$emojis = ['🍳','🍜','🍗','🥩','🥗','🍱','🍛','🥘','🫕','🍲','🍚','🥣','🧆','🥙','🌮','🫔','🍝','🥞','🧇','🍰','🎂','🍮','🍡','🍢','🧁','🍩','🍪','🧋','🍵','🥤','🍌','🍇','🍓','🫐'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resep - DapurKita</title>
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
        .floating-btn { background: linear-gradient(135deg, #FF6B35, #FF4D6D); box-shadow: 0 8px 25px rgba(255,107,53,0.4); }
    </style>
</head>
<body class="min-h-screen">
<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-orange-100">
    <div class="max-w-4xl mx-auto px-4 h-16 flex items-center gap-3">
        <a href="detail.php?id=<?= $id ?>" class="flex items-center gap-1 text-gray-500 hover:text-orange-500 transition text-sm font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
        </a>
        <span class="text-gray-200">|</span>
        <a href="index.php" class="text-xl font-bold" style="font-family:'Playfair Display',serif; color:#FF6B35;">DapurKita</a>
    </div>
</nav>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <div class="text-4xl mb-3">✏️</div>
        <h1 class="text-3xl font-bold font-display text-gray-900">Edit Resep</h1>
        <p class="text-gray-500 mt-2"><?= htmlspecialchars($recipe['title']) ?></p>
    </div>

    <?php if ($errors): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 mb-6">
        <?php foreach ($errors as $e): ?>
        <p class="text-rose-700 text-sm font-medium"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-orange-400"></i> Informasi Dasar
            </h2>
            <div class="mb-5">
                <label class="form-label">Icon Resep</label>
                <input type="hidden" name="image_emoji" id="emojiInput" value="<?= htmlspecialchars($recipe['image_emoji']) ?>">
                <div class="grid grid-cols-8 sm:grid-cols-12 gap-1 p-3 bg-orange-50 rounded-2xl">
                    <?php foreach ($emojis as $e): ?>
                    <div class="emoji-option <?= $recipe['image_emoji'] == $e ? 'selected' : '' ?>" onclick="selectEmoji('<?= $e ?>', this)"><?= $e ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Judul Resep *</label>
                <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($recipe['title']) ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-input" rows="3"><?= htmlspecialchars($recipe['description']) ?></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-input">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $recipe['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tingkat Kesulitan</label>
                    <select name="difficulty" class="form-input">
                        <?php foreach (['Mudah','Sedang','Sulit'] as $d): ?>
                        <option value="<?= $d ?>" <?= $recipe['difficulty'] == $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                <i data-lucide="clock" class="w-5 h-5 text-orange-400"></i> Waktu & Porsi
            </h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Persiapan (menit)</label>
                    <input type="number" name="prep_time" class="form-input" min="0" value="<?= $recipe['prep_time'] ?>">
                </div>
                <div>
                    <label class="form-label">Memasak (menit)</label>
                    <input type="number" name="cook_time" class="form-input" min="0" value="<?= $recipe['cook_time'] ?>">
                </div>
                <div>
                    <label class="form-label">Porsi</label>
                    <input type="number" name="servings" class="form-input" min="1" value="<?= $recipe['servings'] ?>">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i data-lucide="list-checks" class="w-5 h-5 text-orange-400"></i> Bahan-Bahan *
            </h2>
            <p class="text-gray-500 text-sm mb-4">Satu bahan per baris</p>
            <textarea name="ingredients" class="form-input font-mono text-sm" rows="8"><?= htmlspecialchars(is_string($ingText) ? $ingText : implode("\n", $ingredients)) ?></textarea>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i data-lucide="chef-hat" class="w-5 h-5 text-amber-400"></i> Langkah Memasak *
            </h2>
            <p class="text-gray-500 text-sm mb-4">Satu langkah per baris</p>
            <textarea name="steps" class="form-input text-sm" rows="8"><?= htmlspecialchars(is_string($stepsText) ? $stepsText : implode("\n", $steps)) ?></textarea>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i data-lucide="tag" class="w-5 h-5 text-teal-500"></i> Tags
            </h2>
            <input type="text" name="tags" class="form-input" placeholder="ayam, goreng, pedas" value="<?= htmlspecialchars($recipe['tags']) ?>">
        </div>

        <div class="flex gap-3">
            <a href="detail.php?id=<?= $id ?>" class="flex-1 py-4 border-2 border-orange-200 text-orange-600 rounded-2xl font-semibold text-center hover:bg-orange-50 transition">
                Batal
            </a>
            <button type="submit" class="floating-btn flex-1 py-4 text-white rounded-2xl font-semibold flex items-center justify-center gap-2">
                <i data-lucide="save" class="w-5 h-5"></i>
                Simpan Perubahan
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
