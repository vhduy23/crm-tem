<?php 
include '../partials/header.php';

// chỉ admin
if ($_SESSION['user']['role_id'] != 1) {
    die("Không thể truy cập !!!");
}
?>

<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold">Quản lý User</h1>

    <button onclick="openModal()" 
        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
        + Thêm
    </button>
</div>

<div id="list" class="bg-white p-4 shadow rounded"></div>

<!-- MODAL -->
<div id="modal" 
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">

    <div class="bg-white p-5 rounded w-96 relative">

        <!-- CLOSE -->
        <button onclick="closeModal()" 
            class="absolute top-2 right-3 text-xl text-gray-500 hover:text-red-500">
            ×
        </button>

        <h2 class="text-lg font-semibold mb-3">User</h2>

        <!-- <input id="name" placeholder="Tên người dùng" 
            class="border p-2 w-full mb-2 rounded"> -->

        <input id="username" placeholder="Tên đăng nhập" 
            class="border p-2 w-full mb-2 rounded">

        <input id="password" type="password" 
            placeholder="Mật khẩu (để trống nếu không đổi)" 
            class="border p-2 w-full mb-2 rounded">

        <select id="role" class="border p-2 w-full mb-3 rounded"></select>

        <button onclick="save()" 
            class="bg-blue-500 text-white px-4 py-2 w-full rounded hover:bg-blue-600">
            Lưu
        </button>
    </div>
</div>

<script>
let editId = null;
let roles = [];

// ===== LOAD ROLES =====
async function loadRoles() {
    const res = await fetch('/api/roles.php');
    roles = await res.json();

    document.getElementById('role').innerHTML =
        roles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
}

// ===== LOAD USERS =====
async function loadUsers() {
    const res = await fetch('/api/users.php');
    const data = await res.json();

    let html = `
    <table class="w-full border border-gray-200 text-sm">
        <tr class="bg-gray-100">
            <th class="p-2">ID</th>
            <th class="p-2">Username</th>
            <th class="p-2">Role</th>
            <th class="p-2">Action</th>
        </tr>`;

    data.forEach(u => {
        html += `
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2 text-center">${u.id}</td>
            <td class="p-2 text-center">${u.username}</td>
            <td class="p-2 text-center">${u.role_name}</td>
            <td class="p-2 text-center space-x-2">
                <button onclick='edit(${JSON.stringify(u)})' 
                    class="text-blue-500 hover:underline">
                    Sửa
                </button>
                |
                <button onclick="remove(${u.id})" 
                    class="text-red-500 hover:underline">
                    Xóa
                </button>
            </td>
        </tr>`;
    });

    html += '</table>';

    document.getElementById('list').innerHTML = html;
}

// ===== MODAL =====
function openModal() {
    editId = null;

    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('username').readOnly = false;
    document.getElementById('username').value = '';
    document.getElementById('password').placeholder = 'Mật khẩu...';
    // document.getElementById('role').value = '';
    name.value = '';
    password.value = '';
}

// ===== CLOSE MODAL =====
function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

// click ra ngoài đóng modal
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// ===== EDIT =====
function edit(u) {
    editId = u.id;

    // mở modal
    document.getElementById('modal').classList.remove('hidden');


    // set value đúng cách
    // document.getElementById('name').value = u.name ?? '';
    document.getElementById('username').value = u.username ?? '';
    document.getElementById('username').readOnly = true;
    document.getElementById('role').value = u.role_id ?? '';
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Mật khẩu (để trống nếu không đổi)';

}

// ===== SAVE =====
async function save() {

    const data = {
        id: editId,
        // name: document.getElementById('name').value,
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        role_id: document.getElementById('role').value
    };

    // console.log('Data user:', data);
    // return;

    await fetch('/api/users.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    location.reload();
}

// ===== DELETE =====
async function remove(id) {
    if (!confirm("Xóa user?")) return;

    await fetch('/api/users.php?id=' + id, { method: 'DELETE' });

    loadUsers();
}

// init
loadRoles().then(loadUsers);
</script>

<?php include '../partials/footer.php'; ?>