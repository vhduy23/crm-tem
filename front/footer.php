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

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

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
    document.getElementById('print-btn').onclick = () => {

        const originalScrollX = window.scrollX;
        const originalScrollY = window.scrollY;
        window.scrollTo(0, 0);

        let cart = getCart();

        let loadingOverlay = document.createElement('div');
        loadingOverlay.style.position = 'fixed';
        loadingOverlay.style.top = '0';
        loadingOverlay.style.left = '0';
        loadingOverlay.style.width = '100%';
        loadingOverlay.style.height = '100%';
        loadingOverlay.style.backgroundColor = '#ffffff';
        loadingOverlay.style.zIndex = '999999';
        loadingOverlay.style.overflowY = 'auto'; // Cho phép cuộn ngầm nếu data dài
        loadingOverlay.style.padding = '20px';
        loadingOverlay.style.boxSizing = 'border-box';
        loadingOverlay.style.fontFamily = 'Arial, sans-serif';
        
        let infoText = document.createElement('div');
        infoText.innerText = 'Đang đồng bộ dữ liệu hình ảnh và xuất file PDF chất lượng cao, vui lòng đợi...';
        infoText.style.textAlign = 'center';
        infoText.style.color = '#004B87';
        infoText.style.fontSize = '18px';
        infoText.style.fontWeight = 'bold';
        infoText.style.marginBottom = '20px';
        loadingOverlay.appendChild(infoText);

        let container = document.createElement('div');
        container.style.width = '794px'; // Kích thước chuẩn trang A4 dọc
        container.style.margin = '0 auto';
        container.style.padding = '30px';
        container.style.boxSizing = 'border-box';
        container.style.backgroundColor = '#ffffff';
        loadingOverlay.appendChild(container);

        document.body.appendChild(loadingOverlay);

        // ==========================================
        // 2. TẠO BANNER HEADER CÓ LOGO 
        // ==========================================
        let header = document.createElement('div');
        header.style.display = 'flex';
        header.style.alignItems = 'stretch'; 
        header.style.height = '80px';        
        header.style.marginBottom = '35px';
        header.style.border = '1px solid #e0e0e0'; 

        let logoBox = document.createElement('div');
        logoBox.style.backgroundColor = '#ffffff';
        logoBox.style.width = '160px';       
        logoBox.style.display = 'flex';
        logoBox.style.alignItems = 'center';
        logoBox.style.justifyContent = 'center';
        logoBox.style.padding = '10px';
        logoBox.style.boxSizing = 'border-box';

        let logoImg = document.createElement('img');
        logoImg.crossOrigin = 'anonymous';  
        logoImg.src = 'https://achau1.bzz.vn/uploads/Logo-nen-trang.png';
        logoImg.style.maxWidth = '100%';
        logoImg.style.maxHeight = '100%';
        logoImg.style.objectFit = 'contain'; 
        logoBox.appendChild(logoImg);
        
        // Thêm khối logo vào header
        header.appendChild(logoBox);

        // KHỐI BÊN PHẢI: Chứa tiêu đề (Nền màu xanh)
        let titleBox = document.createElement('div');
        titleBox.style.backgroundColor = '#004B87'; 
        titleBox.style.flexGrow = '1';       
        titleBox.style.display = 'flex';
        titleBox.style.alignItems = 'center';
        titleBox.style.padding = '0 25px';
        titleBox.style.boxSizing = 'border-box';

        let headerText = document.createElement('div');
        headerText.innerText = 'TỔNG HỢP CATALOGUE SẢN PHẨM'; 
        headerText.style.color = 'white';
        headerText.style.fontSize = '22px';
        headerText.style.fontWeight = 'bold';
        headerText.style.textTransform = 'uppercase';
        headerText.style.letterSpacing = '1px';
        titleBox.appendChild(headerText);

        // Thêm khối tiêu đề vào header
        header.appendChild(titleBox);

        // Đưa toàn bộ header vào container chính
        container.appendChild(header);
        // ==========================================

        let imagePromises = [];

        // 4. DUYỆT RENDER DANH SÁCH SẢN PHẨM
        cart.forEach(item => {
            let itemBlock = document.createElement('div');
            itemBlock.style.pageBreakInside = 'avoid'; 
            itemBlock.style.breakInside = 'avoid';
            itemBlock.style.marginBottom = '40px';

            // Tiêu đề sản phẩm chữ in hoa màu đỏ + đường gạch ngang dài
            let titleWrapper = document.createElement('div');
            titleWrapper.style.display = 'flex';
            titleWrapper.style.alignItems = 'center';
            titleWrapper.style.marginBottom = '20px';

            let title = document.createElement('h3');
            title.innerText = item.name.toUpperCase(); 
            title.style.color = '#D32F2F'; 
            title.style.margin = '0 15px 0 0';
            title.style.fontSize = '16px';
            title.style.fontWeight = 'bold';
            title.style.whiteSpace = 'nowrap';
            titleWrapper.appendChild(title);

            let redLine = document.createElement('div');
            redLine.style.flexGrow = '1';
            redLine.style.height = '1px';
            redLine.style.backgroundColor = '#D32F2F';
            titleWrapper.appendChild(redLine);

            itemBlock.appendChild(titleWrapper);

            // THAY THẾ TOÀN BỘ GRID THÀNH FLEXBOX (Giải quyết triệt để lỗi sụp đổ trang trắng)
            let row = document.createElement('div');
            row.style.display = 'flex';
            row.style.flexWrap = 'wrap';
            row.style.justifyContent = 'flex-start';

            item.images.forEach(img => {
                // Tạo một khối bọc ảnh đóng vai trò như 1 cột (Chiếm ~23% bề rộng để xếp vừa 4 cột)
                let col = document.createElement('div');
                col.style.width = '23%';
                col.style.marginRight = '1%';
                col.style.marginBottom = '15px';
                col.style.boxSizing = 'border-box';

                let el = document.createElement('img');
                
                let imgLoad = new Promise((resolve) => {
                    el.onload = () => resolve();
                    el.onerror = () => resolve(); 
                });
                imagePromises.push(imgLoad);

                // FIX LỖI BẢO MẬT ẢNH (CORS) - Ngăn chặn tình trạng Canvas bị khóa gây trắng trang
                el.crossOrigin = 'anonymous'; 
                el.src = img;
                el.style.width = '100%';
                el.style.height = 'auto'; 
                el.style.maxHeight = '150px'; 
                el.style.objectFit = 'contain';
                
                col.appendChild(el);
                row.appendChild(col);
            });

            itemBlock.appendChild(row);
            container.appendChild(itemBlock);
        });

        // 5. CHỜ ẢNH TẢI XONG + DELAY 500MS ĐỂ TRÌNH DUYỆT KỊP VẼ RỒI MỚI CHỤP
        Promise.all(imagePromises).then(() => {
            setTimeout(() => {
                let opt = {
                    margin:       [10, 10, 10, 10], 
                    filename:     'catalog.pdf',
                    image:        { type: 'jpeg', quality: 1.0 }, 
                    html2canvas:  { 
                        scale: 2.5,         // Tối ưu độ nét ở mức 2.5 để giảm tải cho bộ nhớ RAM, tránh crash canvas
                        useCORS: true,      
                        allowTaint: true,   // Cho phép vẽ ảnh kể cả khi dính lỗi bảo mật CORS nhẹ từ server
                        scrollY: 0,         
                        scrollX: 0,
                        logging: false 
                    },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                // Tiến hành xuất và tải file PDF từ vùng `container`
                html2pdf().set(opt).from(container).save().then(() => {
                    // DỌN DẸP: Xóa bỏ màn hình phủ và đưa người dùng về vị trí cũ
                    loadingOverlay.remove(); 
                    window.scrollTo(originalScrollX, originalScrollY);

                    // clear giỏ hàng
                    localStorage.removeItem('print_cart');
                    renderCart();

                    document.getElementById('print-cart').classList.toggle('hidden');

                }).catch(err => {
                    console.error("Lỗi xuất PDF:", err);
                    loadingOverlay.remove();
                });
            }, 500); // Khoảng hoãn 500 mili giây cực kỳ quan trọng giúp ổn định DOM
        });
    };
</script>