</main>

<footer class="bg-white mt-10 py-4 text-center text-gray-500 text-sm">
    © <?= date('Y') ?> Achau Group
</footer>

<!-- ICON -->
<div id="print-cart-icon" class="fixed right-4 top-1/2 -translate-y-1/2 z-50 cursor-pointer">
    <div class="relative bg-green-600 text-white p-3 rounded">
        <!-- <i class="fa-solid fa-images"></i> -->
         <span class="">In Pdf</span>
        <span id="cart-count"
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-1 rounded-full">0</span>
    </div>
</div>

<!-- POPUP -->
<div id="print-cart" class="fixed right-0 top-0 w-80 h-120 bg-white shadow-lg p-4 hidden z-50">
    <div class="flex justify-between">
        <p id="btn-close-cart" class="text-right text-red-700 font-bold cursor-pointer ">X</p>
        <h2 class="font-bold mb-3">Thiết kế đã chọn</h2>
    </div>
    <div id="cart-items" class="space-y-2 max-h-[70%] overflow-auto"></div>

    <button id="print-btn" class="mt-4 w-full bg-green-500 text-white py-2 rounded">
        In PDF
    </button>
</div>

</body>
</html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function getCart() {
    return JSON.parse(localStorage.getItem('print_cart') || '[]');
}

function saveCart(cart) {
    localStorage.setItem('print_cart', JSON.stringify(cart));
    renderCart();
}

// ADD
document.addEventListener('click', function(e) {
    if (e.target.closest('.add-print')) {

        let btn = e.target.closest('.add-print');

        let item = {
            id: btn.dataset.id,
            name: btn.dataset.name,
            images: JSON.parse(btn.dataset.images || '[]')
        };

        console.log(item); // debug bắt buộc

        let cart = getCart();

        if (!cart.find(i => i.id == item.id)) {
            cart.push(item);
            saveCart(cart);
        }else{
            alert('Thiết kế đã được thêm vào danh sách!');
        }
    }
});

// RENDER
function renderCart() {
    let cart = getCart();
    let html = '';
    cart.forEach((item, index) => {
        html += `
        <div class="flex gap-2 items-center border p-2">
            <img src="${item.images[0]}" class="w-12 h-12 object-cover">
            <div class="flex-1 text-sm">${item.name}</div>
            <button onclick="removeItem(${index})" class="text-red-500">x</button>
        </div>`;
    });

    document.getElementById('cart-items').innerHTML = html;
    document.getElementById('cart-count').innerText = cart.length;
}

// REMOVE
function removeItem(index) {
    let cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
}

// TOGGLE POPUP
document.getElementById('print-cart-icon').onclick = () => {
    document.getElementById('print-cart').classList.toggle('hidden');
};
document.getElementById('btn-close-cart').onclick = () => {
    document.getElementById('print-cart').classList.toggle('hidden');
}

// INIT
renderCart();
</script>


<script>
    document.getElementById('print-btn').onclick = async () => {

    const originalScrollX = window.scrollX;
    const originalScrollY = window.scrollY;
    window.scrollTo(0, 0);

    let cart = getCart();
    if (!cart.length) return;

    // ==== Loading overlay ====
    let loadingOverlay = document.createElement('div');
    Object.assign(loadingOverlay.style, {
        position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
        backgroundColor: '#fff', zIndex: 999999, display: 'flex',
        alignItems: 'center', justifyContent: 'center',
        fontFamily: 'Arial, sans-serif', fontSize: '18px',
        fontWeight: 'bold', color: '#004B87'
    });
    loadingOverlay.innerText = 'Đang đồng bộ dữ liệu hình ảnh và xuất file PDF chất lượng cao, vui lòng đợi...';
    document.body.appendChild(loadingOverlay);

    // ==== Chuẩn bị hằng số trang ====
    const PAGE_W = 210, PAGE_H = 297;   // A4 mm
    const MARGIN = 10;
    const CONTENT_W = PAGE_W - MARGIN * 2; // 190mm
    const HEADER_H = 22;                // mm
    const HEADER_GAP = 6;               // khoảng cách header -> nội dung
    const CONTENT_TOP = MARGIN + HEADER_H + HEADER_GAP;
    const ITEM_GAP = 8;

    // ==== Dựng khối header (logo + tiêu đề) để chụp canvas 1 lần ====
    <?php $logoBase64 = file_get_contents(__DIR__ . '/../logo_b64.txt'); ?>
    let headerEl = document.createElement('div');
    Object.assign(headerEl.style, {
        display: 'flex', alignItems: 'stretch', height: '80px',
        border: '1px solid #e0e0e0', width: '794px', background: '#fff'
    });
    headerEl.innerHTML = `
        <div style="width:160px;display:flex;align-items:center;justify-content:center;padding:10px;box-sizing:border-box;">
            <img id="hdr-logo" src="<?php echo $logoBase64; ?>"
                style="max-width:100%;max-height:100%;object-fit:contain;">
        </div>
        <div style="background:#004B87;flex-grow:1;display:flex;align-items:center;padding:0 25px;box-sizing:border-box;">
            <span style="color:#fff;font-size:22px;font-weight:bold;text-transform:uppercase; display:inline-block; transform:translateY(-10px);">
                GRAPHIC DESIGNS
            </span>
        </div>`;
    headerEl.style.position = 'fixed';
    headerEl.style.left = '-9999px';
    document.body.appendChild(headerEl);

    await new Promise(res => {
        const img = headerEl.querySelector('#hdr-logo');
        if (img.complete) res();
        else { img.onload = res; img.onerror = res; }
    });

    const headerCanvas = await html2canvas(headerEl, { scale: 2.5, useCORS: true });
    const headerImgData = headerCanvas.toDataURL('image/jpeg', 1.0);
    headerEl.remove();

    // ==== Khởi tạo jsPDF trực tiếp ====
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');

    function drawHeader() {
        pdf.addImage(headerImgData, 'JPEG', MARGIN, MARGIN, CONTENT_W, HEADER_H);
    }

    let y = CONTENT_TOP;
    let firstPage = true;

    // ==== Duyệt từng sản phẩm, render riêng rồi ghép vào PDF ====
    for (const item of cart) {

        let block = document.createElement('div');
        Object.assign(block.style, { width: '794px', background: '#fff', boxSizing: 'border-box', padding: '0 4px' });

        let titleWrapper = document.createElement('div');
        Object.assign(titleWrapper.style, { display: 'flex', alignItems: 'center', marginBottom: '15px' });

        let title = document.createElement('h3');
        title.innerText = item.name.toUpperCase();
        Object.assign(title.style, { color: '#D32F2F', margin: '0 15px 0 0', fontSize: '16px', fontWeight: 'bold', whiteSpace: 'nowrap' });
        titleWrapper.appendChild(title);

        let redLine = document.createElement('div');
        Object.assign(redLine.style, { flexGrow: '1', height: '1px', backgroundColor: '#D32F2F' });
        titleWrapper.appendChild(redLine);
        block.appendChild(titleWrapper);

        let row = document.createElement('div');
        Object.assign(row.style, { display: 'flex', flexWrap: 'wrap', justifyContent: 'flex-start' });

        let imgLoadPromises = [];
        item.images.forEach(src => {
            let col = document.createElement('div');
            Object.assign(col.style, { width: '23%', marginRight: '1%', marginBottom: '15px', boxSizing: 'border-box' });
            let el = document.createElement('img');
            el.crossOrigin = 'anonymous';
            el.src = src;
            Object.assign(el.style, { width: '100%', height: 'auto', maxHeight: '150px', objectFit: 'contain' });
            imgLoadPromises.push(new Promise(res => { el.onload = res; el.onerror = res; }));
            col.appendChild(el);
            row.appendChild(col);
        });
        block.appendChild(row);

        block.style.position = 'fixed';
        block.style.left = '-9999px';
        document.body.appendChild(block);
        await Promise.all(imgLoadPromises);

        const canvas = await html2canvas(block, { scale: 2.5, useCORS: true });
        block.remove();

        const imgHmm = canvas.height * (CONTENT_W / canvas.width);
        const imgData = canvas.toDataURL('image/jpeg', 1.0);

        // Nếu không đủ chỗ trên trang hiện tại -> sang trang mới + vẽ header
        if (y + imgHmm > PAGE_H - MARGIN) {
            pdf.addPage();
            y = CONTENT_TOP;
        }
        if (firstPage) { drawHeader(); firstPage = false; }
        // vẽ header cho mọi trang mới được tạo ra ở trên (kể cả khi vừa addPage)
        // (đảm bảo header luôn có sau addPage)
        if (y === CONTENT_TOP) drawHeader();

        pdf.addImage(imgData, 'JPEG', MARGIN, y, CONTENT_W, imgHmm);
        y += imgHmm + ITEM_GAP;
    }
    
    pdf.save('catalog.pdf');

    loadingOverlay.remove();
    window.scrollTo(originalScrollX, originalScrollY);
    localStorage.removeItem('print_cart');
    renderCart();
    document.getElementById('print-cart').classList.add('hidden');
    };
</script>