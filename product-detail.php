<?php
require 'front/header.php';

$slug = $_GET['slug'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM products WHERE slug=?");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    die('Không tìm thấy sản phẩm');
}

// lấy ảnh
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id=?");
$stmt->execute([$product['id']]);
$images = $stmt->fetchAll();

$imageList = array_map(function($img){
    return $img['image_path'];
}, $images);


// lấy brand
$stmt = $pdo->prepare("SELECT name FROM brands WHERE id=?");
$stmt->execute([$product['brand_id']]);
$brand = $stmt->fetch();

// lấy category
$stmt = $pdo->prepare("SELECT name FROM categories WHERE id=?");
$stmt->execute([$product['category_id']]);
$category = $stmt->fetch();

?>



<style>

/* layout */
.container {
    max-width: 1100px;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
}

.flex {
    display: flex;
    gap: 30px;
}

/* main slider */
.main-slider img {
    width: 100%;
    height: 450px;
    object-fit: contain;
    background: #fff;
    cursor: zoom-in;
}

/* thumbs */
.thumb-slider .swiper-slide {
    opacity: 0.4;
    cursor: pointer;
}

.thumb-slider .swiper-slide-thumb-active {
    opacity: 1;
}

.thumb-slider img {
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
}

/* zoom hover */
.zoom-container {
    overflow: hidden;
}

.zoom-container img {
    transition: transform 0.3s ease;
}

.zoom-container:hover img {
    transform: scale(1.4);
}

/* ===== POPUP ===== */
#galleryPopup {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.95);
    display: none;
    z-index: 9999;
}

#galleryPopup.active {
    display: block;
}

.popup-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: #fff;
    font-size: 30px;
    cursor: pointer;
    z-index: 99;
}

.popup-swiper img {
    width: 100%;
    height: 90vh;
    object-fit: contain;
}

/* arrows */
.swiper-button-next,
.swiper-button-prev {
    color: #fff;
}
@media (max-width:600px) {
    .main-slider img {
        height: 315px;
    }
}
</style>

<div class="container">

    <div class="block md:flex rounded shadow hover:shadow-lg transition" style="padding: 30px">

        <!-- LEFT -->
        <div class="w-full  md:w-1/2">

            <!-- MAIN -->
            <div class="swiper main-slider">
                <div class="swiper-wrapper">
                    <?php foreach($images as $i => $img): ?>
                        <div class="swiper-slide">
                            <div class="zoom-container">
                                <img 
                                    src="<?= $img['image_path'] ?>"
                                    onclick="openGallery(<?= $i ?>)"
                                >
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- THUMB -->
            <div class="swiper thumb-slider" style="margin-top:10px;">
                <div class="swiper-wrapper">
                    <?php foreach($images as $img): ?>
                        <div class="swiper-slide !w-[65px] md:!w-[92px]">
                            <img src="<?= $img['image_path'] ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- RIGHT -->
        <div style="flex:1;" class="mt-[35px] md:mt-[0]">
            <h1 style="font-size:24px; font-weight:bold;">
                <?= $product['name'] ?>
            </h1>

            <p style="margin-top:10px; color:#666;">
                <?= nl2br($product['description']) ?>
            </p>
            <div class="mt-50 flex justify" style="margin-top: 35px;">
                <div><strong>Thương hiệu:</strong> <?= $brand['name'] ?? '' ?></div>
                <div><strong>Danh mục:</strong> <?= $category['name'] ?? '' ?></div>
            </div>
            <button 
                style="margin-top: 30px"
                class="add-print bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-lg shadow"
                data-id="<?= $product['id'] ?>"
                data-name="<?= htmlspecialchars($product['name']) ?>"
                data-images='<?= htmlspecialchars(json_encode($imageList)) ?>'
            >
                Thêm vào danh sách
                <!-- <i class="fa-solid fa-folder-plus"></i> -->
            </button>
        </div>

    </div>

</div>

<!-- ===== POPUP GALLERY ===== -->
<div id="galleryPopup">

    <div class="popup-close" onclick="closeGallery()">✕</div>

    <div class="swiper popup-swiper">
        <div class="swiper-wrapper">

            <?php foreach($images as $img): ?>
                <div class="swiper-slide">
                    <img src="<?= $img['image_path'] ?>">
                </div>
            <?php endforeach; ?>

        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>

</div>

<script>
// thumbs
const thumbs = new Swiper(".thumb-slider", {
    spaceBetween: 10,
    slidesPerView: 5,
    freeMode: true,
    watchSlidesProgress: true,
});

// main
const main = new Swiper(".main-slider", {
    spaceBetween: 10,
    loop: true,
    thumbs: { swiper: thumbs }
});

// popup slider
const popupSwiper = new Swiper(".popup-swiper", {
    loop: true,
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
});

// open popup
function openGallery(index) {
    document.getElementById('galleryPopup').classList.add('active');
    popupSwiper.slideToLoop(index);
}

// close popup
function closeGallery() {
    document.getElementById('galleryPopup').classList.remove('active');
    console.log('vào đây');
}

// ESC close
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeGallery();
});
</script>

<?php
require 'front/footer.php';