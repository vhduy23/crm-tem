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

    if (!$name) {
        die('Tên không được để trống');
    }

    $slugInput = $_POST['slug'] ?? '';
    $slug = $slugInput ? createSlug($slugInput) : createSlug($name);
    $slug = uniqueSlug($pdo, $slug);

    $stmt = $pdo->prepare("
        INSERT INTO products(name, slug, description, brand_id, category_id, created_by)
        VALUES(?,?,?,?,?,?)
    ");
    $stmt->execute([$name, $slug, $desc, $brand_id, $category_id, $user_id]);

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

<div class="bg-white p-6 rounded-xl shadow-md max-w-3xl">
    <h2 class="text-xl font-bold mb-4">Thêm thiết kế</h2>

    <form method="POST" enctype="multipart/form-data">

        <!-- Tên -->
        <input name="name" placeholder="Tên thiết kế..."
            class="w-full border p-3 rounded mb-3">

        <!-- Slug -->
        <input id="slug" name="slug" placeholder="Đường dẫn..." 
            class="w-full border p-3 rounded mb-3">


        <!-- Mô tả -->
        <textarea name="description" placeholder="Mô tả..."
            class="w-full border p-3 rounded mb-3"></textarea>

        <div class="flex justify-between gap-10">
            <!-- Brand -->
            <select name="brand_id" class="w-full border p-3 rounded mb-3">
                <option value="">-- Chọn thương hiệu --</option>
                <?php foreach($brands as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Category -->
            <select name="category_id" class="w-full border p-3 rounded mb-3">
                <option value="">-- Chọn danh mục --</option>
                <?php renderCategorySelectOptions($categories); ?>
            </select>
        </div>

        <!-- Upload -->
        <input type="file" id="imageInput" name="images[]" multiple
            class="mb-3">

        <!-- Preview -->
        <div id="preview" class="grid grid-cols-4 gap-3 mb-4"></div>

        <button type="submit"
            class="bg-blue-500 text-white px-5 py-2 rounded">
            Lưu
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