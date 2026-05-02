<?php
require_once 'database.php';
$db = initDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $stmt = $db->prepare("DELETE FROM recipes WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
}
header('Location: index.php?deleted=1');
exit;
