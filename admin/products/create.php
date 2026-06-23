<?php
require '../../lib/db.php';
require '../../lib/image.php';
require '../../lib/categories.php';
session_start();

// lấy brand + category
$brands = $pdo->query("SELECT id, name FROM brands")->fetchAll();
$categories = fetchCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $brand_id = $_POST['brand_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $user_id = (int) $_SESSION['user']['id'];
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

    if (!$name) {
        die('Tên không được để trống');
    }

    $slugInput = $_POST['slug'] ?? '';
    $slug = $slugInput ? createSlug($slugInput) : createSlug($name);
    $slug = uniqueSlug($pdo, $slug);

    $stmt = $pdo->prepare("
        INSERT INTO products(name, slug, description, brand_id, category_id, created_by, status)
        VALUES(?,?,?,?,?,?,?)
    ");
    $stmt->execute([$name, $slug, $desc, $brand_id, $category_id, $user_id, $status]);

    $product_id = $pdo->lastInsertId();

    // upload folder
    $uploadDir = __DIR__ . '/../../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // upload ảnh
    if (!empty($_FILES['images']['name'][0])) {

        foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {

            if ($_FILES['images']['error'][$k] === 0) {

                $fileName = uniqid();

                $uploadDir = __DIR__ . '/../../uploads/products/';
                $png = $uploadDir . $fileName . ".png";

                if (move_uploaded_file($tmp, $png)) {

                    $webpPath = processImage($png);

                    // FIX: chỉ lưu URL
                    $webp = '/uploads/products/' . basename($webpPath);

                    $stmt = $pdo->prepare("
                        INSERT INTO product_images(product_id, image_path)
                        VALUES(?,?)
                    ");
                    $stmt->execute([$product_id, $webp]);
                }
            }
        }
    } 
    header("Location: index.php");
    exit();
}

include '../partials/header.php';

function createSlug($string) {
    $string = strtolower($string);

    // bỏ dấu tiếng Việt
    $string = preg_replace([
        '/[áàảạãăắằẳẵặâấầẩẫậ]/u',
        '/[éèẻẽẹêếềểễệ]/u',
        '/[íìỉĩị]/u',
        '/[óòỏõọôốồổỗộơớờởỡợ]/u',
        '/[úùủũụưứừửữự]/u',
        '/[ýỳỷỹỵ]/u',
        '/đ/u'
    ], [
        'a','e','i','o','u','y','d'
    ], $string);

    // replace ký tự đặc biệt
    $string = preg_replace('/[^a-z0-9]+/i', '-', $string);
    $string = trim($string, '-');

    return $string;
}

function uniqueSlug($pdo, $slug) {
    $base = $slug;
    $i = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug=?");
        $stmt->execute([$slug]);

        if (!$stmt->fetch()) break;

        $slug = $base . '-' . $i++;
    }

    return $slug;
}


?>

<div class="mb-6">
    <div class="flex items-center gap-3">
        <a href="index.php" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-gray-900">Thêm thiết kế</h2>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-4xl">
    <form method="POST" enctype="multipart/form-data">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên thiết kế</label>
        <input name="name" placeholder="Tên thiết kế..."
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Đường dẫn (Slug)</label>
        <input id="slug" name="slug" placeholder="Đường dẫn..." 
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mô tả</label>
        <textarea name="description" placeholder="Mô tả..."
            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 min-h-[100px]"></textarea>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Thương hiệu</label>
                <select name="brand_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">-- Chọn thương hiệu --</option>
                    <?php foreach($brands as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Danh mục</label>
                <select name="category_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">-- Chọn danh mục --</option>
                    <?php renderCategorySelectOptions($categories); ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Trạng thái</label>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="0">Không công khai</option>
                    <option value="1">Nội bộ</option>
                    <option value="2">Công khai</option>
                </select>
            </div>
        </div>

        <label class="block text-sm font-medium text-gray-700 mb-1.5 mt-2">Upload hình ảnh</label>
        <input type="file" id="imageInput" name="images[]" multiple class="mb-3 text-sm">

        <!-- Preview -->
        <div id="preview" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6"></div>

        <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
            <a href="index.php" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Hủy</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors">
                Lưu thiết kế
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

function toSlug(str) {
    return str.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

document.querySelector('[name="name"]').addEventListener('input', function() {
    document.getElementById('slug').value = toSlug(this.value);
});

document.querySelector('form').addEventListener('submit', function() {
    showLoading('Đang xử lý...');
});


</script>