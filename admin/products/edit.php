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
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

    // update product
    $stmt = $pdo->prepare("
        UPDATE products 
        SET name=?, description=?, brand_id=?, category_id=?, status=? 
        WHERE id=?
    ");
    $stmt->execute([$name, $desc, $brand_id, $category_id, $status, $id]);

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

<div class="mb-6">
    <div class="flex items-center gap-3">
        <a href="index.php" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-gray-900">Cập nhật thiết kế</h2>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-4xl">
    <form method="POST" enctype="multipart/form-data">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên thiết kế</label>
        <input name="name" value="<?= $p['name'] ?>"
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mô tả</label>
        <textarea name="description"
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 min-h-[100px]"><?= $p['description'] ?></textarea>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Thương hiệu</label>
                <select name="brand_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">-- Chọn thương hiệu --</option>
                    <?php foreach($brands as $b): ?>
                        <option value="<?= $b['id'] ?>"
                            <?= $p['brand_id'] == $b['id'] ? 'selected' : '' ?>>
                            <?= $b['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Danh mục</label>
                <select name="category_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">-- Chọn danh mục --</option>
                    <?php renderCategorySelectOptions($categories, $p['category_id']); ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Trạng thái</label>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="0" <?= $p['status'] == 0 ? 'selected' : '' ?>>Không công khai</option>
                    <option value="1" <?= $p['status'] == 1 ? 'selected' : '' ?>>Nội bộ</option>
                    <option value="2" <?= $p['status'] == 2 ? 'selected' : '' ?>>Công khai</option>
                </select>
            </div>
        </div>

        <!-- Ảnh đã có -->
        <div class="mb-4 p-4 border border-gray-100 rounded-xl bg-gray-50/50">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Ảnh hiện tại</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <?php foreach($images as $img): ?>
                    <div class="relative group">
                        <img src="<?= $img['image_path'] ?>" 
                             class="w-full h-24 object-cover rounded-lg border border-gray-200">
                        <button type="button"
                            onclick="deleteImage(<?= $img['id'] ?>, this)"
                            class="absolute top-1 right-1 w-6 h-6 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <label class="block text-sm font-medium text-gray-700 mb-1.5 mt-2">Upload hình ảnh mới</label>
        <input type="file" id="imageInput" name="images[]" multiple class="mb-3 text-sm">

        <!-- Preview -->
        <div id="preview" class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6"></div>

        <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
            <a href="index.php" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Hủy</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors">
                Lưu thay đổi
            </button>
        </div>
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
                <div class="relative group">
                    <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                    <button onclick="removeImage(${index})" type="button"
                        class="absolute top-1 right-1 w-6 h-6 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fa-solid fa-xmark text-xs"></i>
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