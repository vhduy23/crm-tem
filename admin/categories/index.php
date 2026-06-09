<?php
require '../auth.php';
checkLogin();
include '../partials/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Quản lý danh mục</h2>
        <!-- <p class="text-sm text-gray-500 mt-0.5">Hỗ trợ 2 cấp: danh mục cha và danh mục con</p> -->
    </div>
    <button onclick="openModal()" class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-colors">
        <i class="fa-solid fa-plus text-xs"></i> Thêm danh mục
    </button>
</div>

<div id="list" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"></div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 id="modalTitle" class="text-lg font-bold text-gray-900 mb-4">Thêm danh mục</h3>

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên danh mục</label>
        <input id="name" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Nhập tên danh mục">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Danh mục cha</label>
        <select id="parent_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            <option value="">— Danh mục cha (cấp 1) —</option>
        </select>

        <div class="flex justify-end gap-2">
            <button onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">Hủy</button>
            <button onclick="save()" class="px-4 py-2.5 rounded-xl text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors">Lưu</button>
        </div>
    </div>
</div>

<script>
let editId = null;
let allCategories = [];

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function buildTree(categories) {
    const parents = {};
    const children = {};

    categories.forEach(c => {
        if (!c.parent_id) {
            parents[c.id] = { ...c, children: [] };
        } else {
            (children[c.parent_id] ||= []).push(c);
        }
    });

    Object.keys(children).forEach(pid => {
        if (parents[pid]) parents[pid].children = children[pid];
    });

    return Object.values(parents);
}

function fillParentSelect(excludeId = null) {
    const select = document.getElementById('parent_id');
    select.innerHTML = '<option value="">— Danh mục cha (cấp 1) —</option>';

    allCategories
        .filter(c => !c.parent_id && c.id != excludeId)
        .forEach(c => {
            select.innerHTML += `<option value="${c.id}">${esc(c.name)}</option>`;
        });
}

function renderTable() {
    const tree = buildTree(allCategories);
    let n = 1;
    let rows = '';

    if (!allCategories.length) {
        document.getElementById('list').innerHTML = `
            <div class="p-12 text-center text-gray-500">
                <i class="fa-solid fa-layer-group text-3xl text-gray-300 mb-3"></i>
                <p>Chưa có danh mục nào</p>
            </div>`;
        return;
    }

    tree.forEach(parent => {
        rows += `
        <tr class="border-t border-gray-100 bg-slate-50/60">
            <td class="p-3 text-center text-gray-500">${n++}</td>
            <td class="p-3">
                <div class="flex items-center gap-2 font-semibold text-gray-900">
                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs"><i class="fa-solid fa-folder"></i></span>
                    ${esc(parent.name)}
                </div>
            </td>
            <td class="p-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700">Cha</span></td>
            <td class="p-3 text-center text-gray-400">—</td>
            <td class="p-3 text-center">
                <button onclick="editItem(${parent.id})" class="text-blue-600 hover:underline text-sm">Sửa</button>
                <span class="text-gray-300 mx-1">|</span>
                <button onclick="remove(${parent.id})" class="text-red-500 hover:underline text-sm">Xóa</button>
            </td>
        </tr>`;

        parent.children.forEach(child => {
            rows += `
            <tr class="border-t border-gray-50 hover:bg-gray-50/80">
                <td class="p-3 text-center text-gray-400">${n++}</td>
                <td class="p-3 pl-10">
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fa-solid fa-turn-up fa-rotate-90 text-gray-300 text-xs"></i>
                        <span class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px]"><i class="fa-solid fa-tag"></i></span>
                        ${esc(child.name)}
                    </div>
                </td>
                <td class="p-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700">Con</span></td>
                <td class="p-3 text-center text-sm text-gray-500">${esc(child.parent_name || parent.name)}</td>
                <td class="p-3 text-center">
                    <button onclick="editItem(${child.id})" class="text-blue-600 hover:underline text-sm">Sửa</button>
                    <span class="text-gray-300 mx-1">|</span>
                    <button onclick="remove(${child.id})" class="text-red-500 hover:underline text-sm">Xóa</button>
                </td>
            </tr>`;
        });
    });

    document.getElementById('list').innerHTML = `
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <th class="p-3 text-center w-14">STT</th>
                <th class="p-3 text-left">Tên danh mục</th>
                <th class="p-3 text-center w-24">Cấp</th>
                <th class="p-3 text-center w-40">Thuộc</th>
                <th class="p-3 text-center w-32">Thao tác</th>
            </tr>
        </thead>
        <tbody>${rows}</tbody>
    </table>`;
}

async function load() {
    const res = await fetch('/api/categories.php');
    allCategories = await res.json();
    renderTable();
}

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Thêm danh mục';
    document.getElementById('name').value = '';
    fillParentSelect();
    document.getElementById('parent_id').value = '';
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

function editItem(id) {
    const item = allCategories.find(c => c.id == id);
    if (!item) return;

    editId = id;
    document.getElementById('modalTitle').textContent = 'Sửa danh mục';
    document.getElementById('name').value = item.name;
    fillParentSelect(id);
    document.getElementById('parent_id').value = item.parent_id || '';
    document.getElementById('modal').classList.remove('hidden');
}

async function save() {
    const name = document.getElementById('name').value.trim();
    const parent_id = document.getElementById('parent_id').value || null;

    if (!name) {
        alert('Vui lòng nhập tên danh mục');
        return;
    }

    const res = await fetch('/api/categories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: editId, name, parent_id })
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
    if (!confirm('Xóa danh mục này?')) return;

    const res = await fetch('/api/categories.php?id=' + id, { method: 'DELETE' });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        alert(data.error || 'Không thể xóa danh mục');
        return;
    }

    load();
}

load();
</script>

<?php include '../partials/footer.php'; ?>
