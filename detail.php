<?php
require_once 'database.php';
$db = initDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT r.*, c.name AS category_name, c.color AS category_color FROM recipes r LEFT JOIN categories c ON r.category_id = c.id WHERE r.id = ?");
$stmt->execute([$id]);
$recipe = $stmt->fetch();
if (!$recipe) { header('Location: index.php'); exit; }

$ingredients = json_decode($recipe['ingredients'], true) ?: [];
$steps       = json_decode($recipe['steps'], true)       ?: [];
$tags        = array_filter(explode(',', $recipe['tags']));
$totalTime   = $recipe['prep_time'] + $recipe['cook_time'];

// Related
$relStmt = $db->prepare("SELECT * FROM recipes WHERE category_id = ? AND id != ? LIMIT 4");
$relStmt->execute([$recipe['category_id'], $id]);
$related = $relStmt->fetchAll();

$diffColors = ['Mudah'=>'#10B981','Sedang'=>'#F59E0B','Sulit'=>'#EF4444'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($recipe['title']) ?> - DapurKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background-color:#FFFAF5; }
        .font-display { font-family:'Playfair Display',serif; }
        .step-line { position:relative; }
        .step-line::before { content:''; position:absolute; left:20px; top:44px; bottom:-16px; width:2px; background:linear-gradient(to bottom,#FF6B35,#FFD23F); }
        .step-line:last-child::before { display:none; }
        .floating-btn { background:linear-gradient(135deg,#FF6B35,#FF4D6D); box-shadow:0 8px 25px rgba(255,107,53,0.4); }
        .hero-recipe { background:linear-gradient(135deg,#FFF3E0 0%,#FFE0B2 100%); }
        @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        .slide-up { animation:slideUp 0.5s ease forwards; }
        .ingredient-item:hover { background:#FFF3E0; }
        @media print { .print-hide{display:none} }
    </style>
</head>
<body class="min-h-screen">
<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-orange-100 print-hide">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="index.php" class="flex items-center gap-1 text-gray-500 hover:text-orange-500 transition text-sm font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
            </a>
            <span class="text-gray-200">|</span>
            <a href="index.php" class="text-xl font-bold" style="font-family:'Playfair Display',serif;color:#FF6B35;">DapurKita</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="edit.php?id=<?= $id ?>" class="flex items-center gap-2 px-4 py-2 bg-orange-50 text-orange-600 rounded-xl text-sm font-semibold hover:bg-orange-100 transition">
                <i data-lucide="pencil" class="w-4 h-4"></i>Edit
            </a>
            <a href="delete.php?id=<?= $id ?>" onclick="return confirm('Hapus resep ini?')" class="flex items-center gap-2 px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-sm font-semibold hover:bg-rose-100 transition">
                <i data-lucide="trash-2" class="w-4 h-4"></i>Hapus
            </a>
        </div>
    </div>
</nav>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="hero-recipe rounded-3xl p-8 slide-up flex flex-col md:flex-row gap-6 items-center">
                <div class="text-8xl flex-shrink-0"><?= $recipe['image_emoji'] ?></div>
                <div>
                    <?php if ($recipe['category_name']): ?>
                    <span class="text-xs font-bold px-3 py-1 rounded-full text-white mb-3 inline-block" style="background-color:<?= $recipe['category_color'] ?>">
                        <?= htmlspecialchars($recipe['category_name']) ?>
                    </span>
                    <?php endif; ?>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 font-display mb-2"><?= htmlspecialchars($recipe['title']) ?></h1>
                    <p class="text-gray-600 leading-relaxed"><?= htmlspecialchars($recipe['description']) ?></p>
                    <?php if ($tags): ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <?php foreach ($tags as $tag): ?>
                        <span class="text-xs bg-orange-100 text-orange-600 px-3 py-1 rounded-full font-medium">#<?= trim(htmlspecialchars($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-3 slide-up">
                <?php foreach ([
                    ['clock','Persiapan',$recipe['prep_time'].' mnt'],
                    ['flame','Memasak',$recipe['cook_time'].' mnt'],
                    ['timer','Total',$totalTime.' mnt'],
                    ['users','Porsi',$recipe['servings'].' porsi'],
                ] as $s): ?>
                <div class="bg-white rounded-2xl p-4 text-center border border-orange-100 shadow-sm">
                    <i data-lucide="<?= $s[0] ?>" class="w-5 h-5 mx-auto mb-2 text-orange-400"></i>
                    <p class="text-xs text-gray-500 mb-1"><?= $s[1] ?></p>
                    <p class="text-sm font-bold text-gray-800"><?= $s[2] ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm slide-up">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="list-checks" class="w-5 h-5 text-orange-500"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 font-display">Bahan-Bahan</h2>
                        <p class="text-xs text-gray-500"><?= count($ingredients) ?> bahan</p>
                    </div>
                </div>
                <ul class="space-y-2">
                    <?php foreach ($ingredients as $i => $ing): ?>
                    <li class="ingredient-item flex items-start gap-3 p-3 rounded-xl transition cursor-default">
                        <span class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white mt-0.5" style="background:linear-gradient(135deg,#FF6B35,#FF4D6D)"><?= $i+1 ?></span>
                        <span class="text-gray-700"><?= htmlspecialchars($ing) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-orange-100 shadow-sm slide-up">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="chef-hat" class="w-5 h-5 text-amber-500"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 font-display">Langkah Memasak</h2>
                        <p class="text-xs text-gray-500"><?= count($steps) ?> langkah</p>
                    </div>
                </div>
                <ol class="space-y-4">
                    <?php foreach ($steps as $i => $step): ?>
                    <li class="step-line flex gap-4 pb-4">
                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-white text-sm z-10" style="background:linear-gradient(135deg,#FF6B35,#FFD23F)"><?= $i+1 ?></div>
                        <div class="pt-2"><p class="text-gray-700 leading-relaxed"><?= htmlspecialchars($step) ?></p></div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-white rounded-2xl p-5 border border-orange-100 shadow-sm">
                <h3 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-wider">Tingkat Kesulitan</h3>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color:<?= ($diffColors[$recipe['difficulty']]??'#6B7280') ?>22">
                        <i data-lucide="bar-chart-2" class="w-5 h-5" style="color:<?= $diffColors[$recipe['difficulty']]??'#6B7280' ?>"></i>
                    </div>
                    <span class="font-bold text-lg" style="color:<?= $diffColors[$recipe['difficulty']]??'#6B7280' ?>"><?= htmlspecialchars($recipe['difficulty']) ?></span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-orange-100 shadow-sm space-y-3">
                <h3 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-wider">Aksi</h3>
                <a href="edit.php?id=<?= $id ?>" class="w-full flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-xl transition font-medium text-sm">
                    <i data-lucide="pencil" class="w-4 h-4"></i>Edit Resep
                </a>
                <button onclick="window.print()" class="w-full flex items-center gap-3 p-3 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-xl transition font-medium text-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i>Cetak Resep
                </button>
                <a href="delete.php?id=<?= $id ?>" onclick="return confirm('Hapus resep ini?')" class="w-full flex items-center gap-3 p-3 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl transition font-medium text-sm">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>Hapus Resep
                </a>
            </div>

            <?php if ($related): ?>
            <div class="bg-white rounded-2xl p-5 border border-orange-100 shadow-sm">
                <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Resep Serupa</h3>
                <div class="space-y-3">
                    <?php foreach ($related as $rel): ?>
                    <a href="detail.php?id=<?= $rel['id'] ?>" class="flex items-center gap-3 p-2 rounded-xl hover:bg-orange-50 transition">
                        <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-2xl flex-shrink-0"><?= $rel['image_emoji'] ?></div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm line-clamp-1"><?= htmlspecialchars($rel['title']) ?></p>
                            <p class="text-xs text-gray-500"><?= ($rel['prep_time']+$rel['cook_time']) ?> mnt • <?= $rel['difficulty'] ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<a href="add.php" class="floating-btn print-hide fixed bottom-6 right-6 w-14 h-14 rounded-full flex items-center justify-center text-white z-40 shadow-xl">
    <i data-lucide="plus" class="w-6 h-6"></i>
</a>
<script>lucide.createIcons();</script>
</body>
</html>