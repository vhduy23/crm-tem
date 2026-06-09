<?php 
require '../auth.php';
checkLogin(); 
include '../partials/header.php'; ?>

<div class="flex justify-between mb-4">
    <h1 class="text-xl font-bold">Danh mục</h1>
    <button onclick="openModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">+ Thêm</button>
</div>

<div id="list" class="bg-white shadow rounded p-4"></div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-4 rounded w-96">
        <input id="name" class="border p-2 w-full mb-2" placeholder="Tên danh mục">
        <button onclick="save()" class="bg-blue-500 text-white px-4 py-2">Lưu</button>
    </div>
</div>

<script>
let editId = null;

async function load() {
    const res = await fetch('/api/categories.php');
    const data = await res.json();

    let n = 1;

    let html = `
    <table class="w-full border border-gray-200 text-sm">
        <tr class="bg-gray-100">
            <th class="text-center">STT</th>
            <th class="text-center">Tên</th>
            <th class="text-center">Action</th>
        </tr>`;

    data.forEach(c => {

        html += `
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2 text-center">${n}</td>
            <td class="p-2 text-center">${c.name}</td>
            <td class="p-2 text-center">
                <button onclick="edit(${c.id}, '${c.name}')" class="text-blue-500 hover:underline">Sửa</button> |
                <button onclick="remove(${c.id})" class="text-red-500 hover:underline">Xóa</button>
            </td>
        </tr>`;
        n++;
    });

    html += '</table>';

    document.getElementById('list').innerHTML = html;
}

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
}

function edit(id, name) {
    editId = id;
    document.getElementById('name').value = name;
    openModal();
}

async function save() {
    const name = document.getElementById('name').value;

    await fetch('/api/categories.php', {
        method: 'POST',
        body: JSON.stringify({ id: editId, name })
    });

    location.reload();
}

async function remove(id) {
    if (!confirm("Xóa?")) return;

    await fetch('/api/categories.php?id=' + id, { method: 'DELETE' });
    load();
}

load();
</script>

<?php include '../partials/footer.php'; ?>