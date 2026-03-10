<?php
require 'auth.php';
//requireAdmin();
?>
<!DOCTYPE html><html><head><meta charset="UTF‑8"><title>Admin</title></head><body>
<h1>Панель Администратора</h1>
<button id="loadUsers">Загрузить клиентов</button>
<div id="users"></div>
<script>
document.getElementById('loadUsers').onclick = () => {
  fetch('ajax/admin_actions.php', {
    method:'POST', body: new URLSearchParams({action: 'list_users'})
  }).then(r=>r.json()).then(users => {
    const c = document.getElementById('users'); c.innerHTML = '';
    users.forEach(u=>{
      const d = document.createElement('div');
      d.innerHTML = `${u.id} – ${u.username} <button onclick="viewOrders(${u.id})">Сметы</button>`;
      c.appendChild(d);
    });
  });
};

function viewOrders(userId) {
  fetch('ajax/admin_actions.php', {
    method:'POST', body: new URLSearchParams({action: 'get_user_orders', user_id: userId})
  }).then(r=>r.json()).then(orders => {
    orders.forEach(o => {
      const div = document.getElementById('users');
      const d = document.createElement('pre');
      d.textContent = JSON.stringify(o, null, 2);
      const btn = document.createElement('button');
      btn.textContent = 'Удалить';
      btn.onclick = () => deleteOrder(o.id, d);
      div.appendChild(d); div.appendChild(btn);
    });
  });
}

function deleteOrder(orderId, node) {
  fetch('ajax/admin_actions.php', {
    method:'POST', body: new URLSearchParams({action: 'delete_order', order_id: orderId})
  }).then(r=>{ if (r.ok) node.remove(); });
}
</script>
</body></html>
