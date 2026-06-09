<?php
require '../../lib/db.php';
require '../../lib/image.php';
require '../../lib/categories.php';

// lấy id
$id = $_GET['id'] ?? 0;

// lấy product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$p = $stmt->fetch();

// lấy images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id=?");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// lấy brand + category
$brands = $pdo->query("SELECT id, name FROM brands")->fetchAll();
$categories = fetchCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $brand_id = $_POST['brand_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;

    // update product
    $stmt = $pdo->prepare("
        UPDATE products 
        SET name=?, description=?, brand_id=?, category_id=? 
        WHERE id=?
    ");
    $stmt->execute([$name, $desc, $brand_id, $category_id, $id]);

    // upload ảnh mới
    $uploadDir = __DIR__ . '/../../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($_FILES['images']['name'][0])) {

        foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {

            if ($_FILES['images']['error'][$k] === 0) {

                $fileName = uniqid();
                $png = $uploadDir . $fileName . ".png";

                if (move_uploaded_file($tmp, $png)) {

                    $webpPath = processImage($png);
                    $webp = '/uploads/products/' . basename($webpPath);

                    $stmt = $pdo->prepare("
                        INSERT INTO product_images(product_id, image_path)
                        VALUES(?,?)
                    ");
                    $stmt->execute([$id, $webp]);
                }
            }
        }
    }

    header("Location: index.php");
    exit();
}

include '../partials/header.php';
?>

<div class="bg-white p-6 rounded-xl shadow-md max-w-4xl">
    <h2 class="text-xl font-bold mb-4">Cập nhật sản phẩm</h2>

    <form method="POST" enctype="multipart/form-data">

        <!-- Tên -->
        <input name="name" value="<?= $p['name'] ?>"
            class="w-full border p-3 rounded mb-3">

        <!-- Mô tả -->
        <textarea name="description"
            class="w-full border p-3 rounded mb-3"><?= $p['description'] ?></textarea>

        <div class="flex justify-between gap-10">
            <!-- Brand -->
            <select name="brand_id" class="w-full border p-3 rounded mb-3">
                <option value="">-- Chọn thương hiệu --</option>
                <?php foreach($brands as $b): ?>
                    <option value="<?= $b['id'] ?>"
                        <?= $p['brand_id'] == $b['id'] ? 'selected' : '' ?>>
                        <?= $b['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Category -->
            <select name="category_id" class="w-full border p-3 rounded mb-3">
                <option value="">-- Chọn danh mục --</option>
                <?php renderCategorySelectOptions($categories, $p['category_id']); ?>
            </select>
        </div>

        <!-- Ảnh đã có -->
        <div class="mb-4">
            <h3 class="font-semibold mb-2">Ảnh hiện tại</h3>
            <div class="grid grid-cols-5 gap-3">
                <?php foreach($images as $img): ?>
                    <div class="relative">
                        <img src="<?= $img['image_path'] ?>" 
                             class="w-full h-30 object-cover rounded">

                        <button type="button"
                            onclick="deleteImage(<?= $img['id'] ?>, this)"
                            class="absolute top-1 right-1 bg-red-500 text-white px-2 rounded">
                            ×
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Upload mới -->
        <input type="file" id="imageInput" name="images[]" multiple
            class="mb-3">

        <!-- Preview -->
        <div id="preview" class="grid grid-cols-5 gap-3 mb-4"></div>

        <button class="bg-blue-500 text-white px-5 py-2 rounded">
            Cập nhật
        </button>
    </form>
</div>


<!-- Loading... -->
<div id="loadingOverlay"
    class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">

    <div class="bg-white p-5 rounded-xl shadow flex items-center gap-3">
        <!-- spinner -->
        <div class="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>

        <span class="text-gray-700 text-sm">Đang xử lý...</span>
    </div>
</div>

<!-- JS Loading... -->
<script>
function showLoading(text = 'Đang xử lý...') {
    const overlay = document.getElementById('loadingOverlay');
    overlay.classList.remove('hidden');

    overlay.querySelector('span').innerText = text;
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}
</script>




<script>
// preview ảnh mới
let filesArr = [];

document.getElementById('imageInput').addEventListener('change', function(e) {
    filesArr = Array.from(e.target.files);
    renderPreview();
});

function renderPreview() {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';

    filesArr.forEach((file, index) => {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.innerHTML += `
                <div class="relative">
                    <img src="${e.target.result}" class="w-full h-30 object-cover rounded">
                    <button onclick="removeImage(${index})"
                        class="absolute top-1 right-1 bg-red-500 text-white px-2 rounded">
                        ×
                    </button>
                </div>
            `;
        }

        reader.readAsDataURL(file);
    });
}

function removeImage(index) {
    filesArr.splice(index, 1);

    const dt = new DataTransfer();
    filesArr.forEach(f => dt.items.add(f));

    document.getElementById('imageInput').files = dt.files;

    renderPreview();
}

// xóa ảnh cũ (ajax)
function deleteImage(id, el) {
    if (!confirm('Xóa ảnh này?')) return;

    fetch('delete_img.php?id=' + id)
    .then(res => res.text())
    .then(() => {
        el.parentElement.remove();
    });
}

// loading...
document.querySelector('form').addEventListener('submit', function() {
    showLoading('Đang xử lý...');
});

</script>