<?php
require '../auth.php';
checkLogin(); 
include '../partials/header.php'; 
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Quản lý thương hiệu</h2>
    </div>
    <button onclick="openModal()" class="size-max inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-colors">
        <i class="fa-solid fa-plus text-xs"></i> Thêm thương hiệu
    </button>
</div>

<div id="list" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"></div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 id="modalTitle" class="text-lg font-bold text-gray-900 mb-4">Thêm thương hiệu</h3>

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên thương hiệu</label>
        <input id="name" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Nhập tên thương hiệu">

        <div class="flex justify-end gap-2">
            <button onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">Hủy</button>
            <button onclick="save()" class="px-4 py-2.5 rounded-xl text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors">Lưu</button>
        </div>
    </div>
</div>

<script>
let editId = null;
let allBrands = [];

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function renderTable() {
    if (!allBrands.length) {
        document.getElementById('list').innerHTML = `
            <div class="p-12 text-center text-gray-500">
                <i class="fa-solid fa-building-columns text-3xl text-gray-300 mb-3"></i>
                <p>Chưa có thương hiệu nào</p>
            </div>`;
        return;
    }

    let n = 1;
    let rows = '';

    allBrands.forEach(b => {
        rows += `
        <tr class="border-t border-gray-100 hover:bg-gray-50/80">
            <td class="p-3 text-center text-gray-500">${n++}</td>
            <td class="p-3">
                <div class="flex items-center gap-2.5 font-semibold text-gray-900">
                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-building-columns"></i>
                    </span>
                    ${esc(b.name)}
                </div>
            </td>
            <td class="p-3 text-center">
                <button onclick="edit(${b.id})" class="text-blue-600 hover:underline text-sm font-medium">Sửa</button>
                <span class="text-gray-300 mx-1.5">|</span>
                <button onclick="remove(${b.id})" class="text-red-500 hover:underline text-sm font-medium">Xóa</button>
            </td>
        </tr>`;
    });

    document.getElementById('list').innerHTML = `
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="p-3 text-center w-16">STT</th>
                    <th class="p-3 text-left">Tên thương hiệu</th>
                    <th class="p-3 text-center w-40">Thao tác</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

async function load() {
    const res = await fetch('/api/brands.php');
    allBrands = await res.json();
    renderTable();
}

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Thêm thương hiệu';
    document.getElementById('name').value = '';
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

function edit(id) {
    const item = allBrands.find(b => b.id == id);
    if (!item) return;

    editId = id;
    document.getElementById('modalTitle').textContent = 'Sửa thương hiệu';
    document.getElementById('name').value = item.name;
    document.getElementById('modal').classList.remove('hidden');
}

async function save() {
    const name = document.getElementById('name').value.trim();

    if (!name) {
        alert('Vui lòng nhập tên thương hiệu');
        return;
    }

    const res = await fetch('/api/brands.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: editId, name })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        alert(data.error || 'Có lỗi xảy ra');
        return;
    }

    closeModal();
    load();
}

async function remove(id) {
    if (!confirm('Xóa thương hiệu này?')) return;

    const res = await fetch('/api/brands.php?id=' + id, { method: 'DELETE' });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        alert(data.error || 'Không thể xóa thương hiệu');
        return;
    }

    load();
}

load();
</script>

<?php include '../partials/footer.php'; ?>