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
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// lấy brand + category
$brands = $pdo->query("SELECT id, name FROM brands")->fetchAll();
$categories = fetchCategories($pdo);

$allUsers = $pdo->query("SELECT id, username, name FROM users WHERE role_id != 1 ORDER BY name ASC")->fetchAll();
$assignedUsersStmt = $pdo->prepare("SELECT user_id FROM user_product_access WHERE product_id = ?");
$assignedUsersStmt->execute([$id]);
$assignedUsers = $assignedUsersStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $brand_id = $_POST['brand_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    $approval_status = isset($_POST['approval_status']) ? (int)$_POST['approval_status'] : 0;

    if (!$name) {
        echo "<script>alert('Tên không được để trống'); history.back();</script>";
        exit;
    }

    // update product
    $stmt = $pdo->prepare("
        UPDATE products 
        SET name=?, description=?, brand_id=?, category_id=?, status=?, approval_status=?
        WHERE id=?
    ");
    $stmt->execute([$name, $desc, $brand_id, $category_id, $status, $approval_status, $id]);

    // update access users
    $pdo->prepare("DELETE FROM user_product_access WHERE product_id = ?")->execute([$id]);
    $access_users = $_POST['access_users'] ?? [];
    if (!empty($access_users)) {
        $stmtAcc = $pdo->prepare("INSERT INTO user_product_access (user_id, product_id) VALUES (?, ?)");
        foreach ($access_users as $uid) {
            $stmtAcc->execute([$uid, $id]);
        }
    }

    // cập nhật thứ tự các ảnh cũ
    $currentMaxSort = 0;
    if (isset($_POST['image_order']) && !empty($_POST['image_order'])) {
        $imageOrder = explode(',', $_POST['image_order']);
        $sort = 1;
        foreach ($imageOrder as $imgId) {
            $imgId = (int)$imgId;
            if ($imgId > 0) {
                $stmt = $pdo->prepare("UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?");
                $stmt->execute([$sort, $imgId, $id]);
                $currentMaxSort = $sort;
                $sort++;
            }
        }
    }

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
                    if ($webpPath === false) {
                        if (file_exists($png)) {
                            unlink($png);
                        }
                        echo "<script>alert('Lỗi: Không thể xử lý ảnh {$_FILES['images']['name'][$k]}. Có thể ảnh được xuất ở định dạng không tương thích (ví dụ: PNG 16-bit/32-bit từ KeyShot). Vui lòng cấu hình KeyShot để xuất ảnh dưới dạng JPEG hoặc PNG 8-bit thông thường trước khi tải lên.'); history.back();</script>";
                        exit;
                    }
                    $webp = '/uploads/products/' . basename($webpPath);

                    $newSortOrder = $currentMaxSort + 1;
                    $stmt = $pdo->prepare("
                        INSERT INTO product_images(product_id, image_path, sort_order)
                        VALUES(?,?,?)
                    ");
                    $stmt->execute([$id, $webp, $newSortOrder]);
                    $currentMaxSort = $newSortOrder;
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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
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
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Hiển thị</label>
                <select id="statusSelect" name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="0" <?= $p['status'] == 0 ? 'selected' : '' ?>>Không công khai</option>
                    <option value="1" <?= $p['status'] == 1 ? 'selected' : '' ?>>Nội bộ</option>
                    <option value="2" <?= $p['status'] == 2 ? 'selected' : '' ?>>Công khai</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Trạng thái</label>
                <select name="approval_status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="0" <?= $p['approval_status'] == 0 ? 'selected' : '' ?>>Chờ</option>
                    <option value="1" <?= $p['approval_status'] == 1 ? 'selected' : '' ?>>Duyệt</option>
                    <option value="2" <?= $p['approval_status'] == 2 ? 'selected' : '' ?>>Hủy</option>
                </select>
            </div>
        </div>

        <div class="mb-4" id="accessUsersWrapper">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Người dùng được xem (chỉ định riêng)</label>
            <select name="access_users[]" multiple class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" style="min-height: 120px;">
                <?php foreach($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= in_array($u['id'], $assignedUsers) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name'] . ' (' . $u['username'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">Giữ phím Ctrl hoặc Cmd để chọn nhiều người.</p>
        </div>

        <!-- Ảnh đã có -->
        <div class="mb-4 p-4 border border-gray-100 rounded-xl bg-gray-50/50">
            <h3 class="text-sm font-medium text-gray-700 mb-1">Ảnh hiện tại</h3>
            <p class="text-xs text-gray-400 mb-3">Kéo thả ảnh để thay đổi thứ tự sắp xếp.</p>
            <input type="hidden" name="image_order" id="imageOrderInput" value="<?= implode(',', array_column($images, 'id')) ?>">
            <div id="imageSortContainer" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <?php foreach($images as $img): ?>
                    <div class="relative group cursor-grab active:cursor-grabbing border-2 border-transparent rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200" draggable="true" data-id="<?= $img['id'] ?>">
                        <img src="<?= $img['image_path'] ?>" 
                             class="w-full h-34 object-cover rounded-lg border border-gray-200 pointer-events-none">
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

function convertImageTo8Bit(file) {
    return new Promise((resolve) => {
        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
            resolve(file);
            return;
        }

        const img = new Image();
        img.src = URL.createObjectURL(file);
        img.onload = function() {
            URL.revokeObjectURL(img.src);
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const exportType = file.type === 'image/jpeg' ? 'image/jpeg' : 'image/png';
            canvas.toBlob(function(blob) {
                if (blob) {
                    const convertedFile = new File([blob], file.name, { type: exportType });
                    resolve(convertedFile);
                } else {
                    resolve(file);
                }
            }, exportType, 0.95);
        };
        img.onerror = function() {
            resolve(file);
        };
    });
}

document.getElementById('imageInput').addEventListener('change', async function(e) {
    showLoading('Đang xử lý định dạng ảnh...');
    
    const originalFiles = Array.from(e.target.files);
    const processedFiles = [];
    
    for (let file of originalFiles) {
        const processed = await convertImageTo8Bit(file);
        processedFiles.push(processed);
    }
    
    filesArr = processedFiles;
    
    const dt = new DataTransfer();
    filesArr.forEach(f => dt.items.add(f));
    document.getElementById('imageInput').files = dt.files;
    
    hideLoading();
    renderPreview();
});

function renderPreview() {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';

    filesArr.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        preview.innerHTML += `
            <div class="relative group cursor-grab active:cursor-grabbing border-2 border-transparent rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200" draggable="true" data-index="${index}">
                <img src="${url}" class="w-full h-34 object-cover rounded-lg border border-gray-200 pointer-events-none">
                <button onclick="removeImage(${index})" type="button"
                    class="absolute top-1 right-1 w-6 h-6 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        `;
    });

    bindPreviewDragEvents();
}

function removeImage(index) {
    filesArr.splice(index, 1);

    const dt = new DataTransfer();
    filesArr.forEach(f => dt.items.add(f));

    document.getElementById('imageInput').files = dt.files;

    renderPreview();
}

// Drag and drop preview sorting logic
let draggedPreviewItem = null;

function bindPreviewDragEvents() {
    const preview = document.getElementById('preview');
    if (!preview) return;
    
    const items = preview.querySelectorAll('[draggable="true"]');
    items.forEach(item => {
        item.addEventListener('dragstart', handlePreviewDragStart);
        item.addEventListener('dragover', handlePreviewDragOver);
        item.addEventListener('dragenter', handlePreviewDragEnter);
        item.addEventListener('dragleave', handlePreviewDragLeave);
        item.addEventListener('drop', handlePreviewDrop);
        item.addEventListener('dragend', handlePreviewDragEnd);
    });
}

function handlePreviewDragStart(e) {
    draggedPreviewItem = this;
    this.classList.add('opacity-40');
    e.dataTransfer.effectAllowed = 'move';
}

function handlePreviewDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handlePreviewDragEnter(e) {
    if (this !== draggedPreviewItem) {
        this.classList.add('border-blue-500', 'scale-[1.02]');
    }
}

function handlePreviewDragLeave(e) {
    this.classList.remove('border-blue-500', 'scale-[1.02]');
}

function handlePreviewDrop(e) {
    e.stopPropagation();
    
    if (draggedPreviewItem !== this) {
        const draggedIndex = parseInt(draggedPreviewItem.dataset.index);
        const targetIndex = parseInt(this.dataset.index);
        
        // Di chuyển phần tử trong mảng filesArr
        const temp = filesArr[draggedIndex];
        filesArr.splice(draggedIndex, 1);
        filesArr.splice(targetIndex, 0, temp);
        
        // Cập nhật lại input files
        const dt = new DataTransfer();
        filesArr.forEach(f => dt.items.add(f));
        document.getElementById('imageInput').files = dt.files;
        
        // Re-render preview
        renderPreview();
    }
    return false;
}

function handlePreviewDragEnd(e) {
    this.classList.remove('opacity-40');
    const preview = document.getElementById('preview');
    if (preview) {
        preview.querySelectorAll('[draggable="true"]').forEach(item => {
            item.classList.remove('border-blue-500', 'scale-[1.02]');
        });
    }
}

// xóa ảnh cũ (ajax)
function deleteImage(id, el) {
    if (!confirm('Xóa ảnh này?')) return;

    fetch('delete_img.php?id=' + id)
    .then(res => res.text())
    .then(() => {
        el.parentElement.remove();
        updateImageOrder();
    });
}

// loading...
document.querySelector('form').addEventListener('submit', function() {
    showLoading('Đang xử lý...');
});

// Drag and drop sorting logic
const sortContainer = document.getElementById('imageSortContainer');
const orderInput = document.getElementById('imageOrderInput');
let draggedItem = null;

if (sortContainer) {
    const bindDragEvents = (item) => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragend', handleDragEnd);
    };

    sortContainer.querySelectorAll('[draggable="true"]').forEach(bindDragEvents);
}

function handleDragStart(e) {
    draggedItem = this;
    this.classList.add('opacity-40');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    if (this !== draggedItem) {
        this.classList.add('border-blue-500', 'scale-[1.02]');
    }
}

function handleDragLeave(e) {
    this.classList.remove('border-blue-500', 'scale-[1.02]');
}

function handleDrop(e) {
    e.stopPropagation();
    
    if (draggedItem !== this) {
        const allItems = Array.from(sortContainer.querySelectorAll('[draggable="true"]'));
        const draggedIndex = allItems.indexOf(draggedItem);
        const targetIndex = allItems.indexOf(this);
        
        if (draggedIndex < targetIndex) {
            sortContainer.insertBefore(draggedItem, this.nextSibling);
        } else {
            sortContainer.insertBefore(draggedItem, this);
        }
        
        updateImageOrder();
    }
    return false;
}

function handleDragEnd(e) {
    this.classList.remove('opacity-40');
    sortContainer.querySelectorAll('[draggable="true"]').forEach(item => {
        item.classList.remove('border-blue-500', 'scale-[1.02]');
    });
}

function updateImageOrder() {
    const items = sortContainer.querySelectorAll('[draggable="true"]');
    const ids = Array.from(items).map(item => item.dataset.id);
    orderInput.value = ids.join(',');
}

document.querySelector('form').addEventListener('submit', function(e) {
    const nameInput = document.querySelector('[name="name"]');
    if (!nameInput.value.trim()) {
        e.preventDefault();
        alert('Tên thiết kế không được để trống!');
        nameInput.focus();
        return;
    }
    showLoading('Đang xử lý...');
});

const statusSelect = document.getElementById('statusSelect');
const accessUsersWrapper = document.getElementById('accessUsersWrapper');
function toggleAccessUsers() {
    if (statusSelect.value === '2') {
        accessUsersWrapper.style.display = 'none';
    } else {
        accessUsersWrapper.style.display = 'block';
    }
}
statusSelect.addEventListener('change', toggleAccessUsers);
toggleAccessUsers();

</script>