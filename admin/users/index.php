<?php 
include '../partials/header.php';

// Chỉ admin mới được truy cập
if ($_SESSION['user']['role_id'] != 1) {
    die("Không thể truy cập !!!");
}
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Quản lý người dùng</h2>
    </div>
    <button onclick="openModal()" class="size-max inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-colors">
        <i class="fa-solid fa-plus text-xs"></i> Thêm người dùng
    </button>
</div>

<div id="list" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"></div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 id="modalTitle" class="text-lg font-bold text-gray-900 mb-4">Thêm người dùng</h3>

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên đăng nhập</label>
        <input id="username" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Nhập tên đăng nhập">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu</label>
        <input id="password" type="password" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Nhập mật khẩu (để trống nếu không đổi)">

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Vai trò / Phân quyền</label>
        <select id="role" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-4 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"></select>

        <label class="block text-sm font-medium text-gray-700 mb-1.5">Trạng thái tài khoản</label>
        <select id="status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 mb-5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            <option value="0">Chờ phê duyệt</option>
            <option value="1">Đã phê duyệt (Hoạt động)</option>
            <option value="2">Bị khóa</option>
        </select>

        <div class="flex justify-end gap-2">
            <button onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">Hủy</button>
            <button onclick="save()" class="px-4 py-2.5 rounded-xl text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors">Lưu</button>
        </div>
    </div>
</div>

<script>
let editId = null;
let roles = [];
let allUsers = [];

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

// ===== LOAD ROLES =====
async function loadRoles() {
    const res = await fetch('/api/roles.php');
    roles = await res.json();

    document.getElementById('role').innerHTML =
        roles.map(r => `<option value="${r.id}">${esc(r.name)}</option>`).join('');
}

// ===== LOAD USERS =====
async function loadUsers() {
    const res = await fetch('/api/users.php');
    allUsers = await res.json();

    if (!allUsers.length) {
        document.getElementById('list').innerHTML = `
            <div class="p-12 text-center text-gray-500">
                <i class="fa-solid fa-users text-3xl text-gray-300 mb-3"></i>
                <p>Chưa có người dùng nào</p>
            </div>`;
        return;
    }

    let rows = '';

    allUsers.forEach(u => {
        let statusBadge = '';
        if (u.status == 0) {
            statusBadge = `
                <div class="flex items-center justify-center gap-2">
                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/50">Chờ duyệt</span>
                    <button onclick="approveUser(${u.id})" class="text-emerald-600 hover:text-emerald-700 hover:underline text-xs font-semibold">Duyệt</button>
                </div>
            `;
        } else if (u.status == 1) {
            statusBadge = `<span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/50">Hoạt động</span>`;
        } else {
            statusBadge = `<span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-700 border border-red-200/50">Bị khóa</span>`;
        }

        rows += `
        <tr class="border-t border-gray-100 hover:bg-gray-50/80">
            <td class="p-3 text-center text-gray-500">${u.id}</td>
            <td class="p-3">
                <div class="flex items-center gap-2.5 font-semibold text-gray-900">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    ${esc(u.username)}
                </div>
            </td>
            <td class="p-3">
                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">
                    ${esc(u.role_name || 'Khác')}
                </span>
            </td>
            <td class="p-3 text-center">${statusBadge}</td>
            <td class="p-3 text-center">
                <button onclick="edit(${u.id})" class="text-blue-600 hover:underline text-sm font-medium">Sửa</button>
                <span class="text-gray-300 mx-1.5">|</span>
                <button onclick="remove(${u.id})" class="text-red-500 hover:underline text-sm font-medium">Xóa</button>
            </td>
        </tr>`;
    });

    document.getElementById('list').innerHTML = `
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="p-3 text-center w-16">ID</th>
                    <th class="p-3 text-left">Tên đăng nhập</th>
                    <th class="p-3 text-left">Vai trò</th>
                    <th class="p-3 text-center w-40">Trạng thái</th>
                    <th class="p-3 text-center w-44">Thao tác</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

// ===== DUYỆT TÀI KHOẢN =====
async function approveUser(id) {
    const u = allUsers.find(user => user.id == id);
    if (!u) return;

    const res = await fetch('/api/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: u.id,
            username: u.username,
            role_id: u.role_id,
            status: 1
        })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        alert(data.error || 'Có lỗi xảy ra');
        return;
    }
    loadUsers();
}

// ===== MODAL =====
function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Thêm người dùng';
    document.getElementById('username').value = '';
    document.getElementById('username').readOnly = false;
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Nhập mật khẩu...';
    document.getElementById('role').value = roles[0] ? roles[0].id : '';
    document.getElementById('status').value = '1';
    document.getElementById('modal').classList.remove('hidden');
}

// ===== CLOSE MODAL =====
function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

// ===== EDIT =====
function edit(id) {
    const u = allUsers.find(user => user.id == id);
    if (!u) return;

    editId = u.id;
    document.getElementById('modalTitle').textContent = 'Sửa người dùng';
    document.getElementById('username').value = u.username ?? '';
    document.getElementById('username').readOnly = true;
    document.getElementById('role').value = u.role_id ?? '';
    document.getElementById('status').value = u.status ?? '1';
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Mật khẩu (để trống nếu không đổi)';
    document.getElementById('modal').classList.remove('hidden');
}

// ===== SAVE =====
async function save() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const role_id = document.getElementById('role').value;
    const status = document.getElementById('status').value;

    if (!username) {
        alert('Vui lòng nhập tên đăng nhập');
        return;
    }

    const data = {
        id: editId,
        username,
        password,
        role_id,
        status
    };

    const res = await fetch('/api/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const resData = await res.json().catch(() => ({}));
    if (!res.ok) {
        alert(resData.error || 'Có lỗi xảy ra');
        return;
    }

    closeModal();
    loadUsers();
}

// ===== DELETE =====
async function remove(id) {
    if (!confirm("Xóa người dùng này?")) return;

    const res = await fetch('/api/users.php?id=' + id, { method: 'DELETE' });
    const resData = await res.json().catch(() => ({}));
    
    if (!res.ok) {
        alert(resData.error || 'Có lỗi xảy ra');
        return;
    }

    loadUsers();
}

// init
loadRoles().then(loadUsers);
</script>

<?php include '../partials/footer.php'; ?>