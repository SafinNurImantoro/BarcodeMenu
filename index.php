<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TEAZZI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=20260213a">
  <style>
    .menu-page {
      background: #f8f5f0;
      min-height: 100vh;
    }
    .menu-section-title {
      margin-bottom: 1.25rem;
      font-size: clamp(1.2rem, 2.3vw, 1.8rem);
    }
    .menu-card {
      border-radius: 15px;
    }
    .menu-card-image {
      height: 200px;
      object-fit: cover;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
    }
    .card-body h5 { font-size:1.05rem; }
    .card-body p { font-size:0.95rem; }
    .btn-primary { border-radius:8px; }
    .cart-btn {
      min-width: 48px;
      min-height: 40px;
    }
    #cartItems {
      max-height: 45vh;
      overflow-y: auto;
      padding-right: 4px;
    }
    .modal-body {
      max-height: 60vh;
      overflow-y: auto;
    }
    @media (max-width: 991.98px) {
      .menu-card-image { height: 180px; }
    }
    @media (max-width: 767.98px) {
      .container {
        padding-left: 14px;
        padding-right: 14px;
      }
      .menu-card-image { height: 170px; }
      #menuQty { width: 100% !important; }
    }
  </style>
</head>
<body class="menu-page">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg" style="background-color:#080e83;">
  <div class="container">
    <span class="navbar-brand fw-bold text-light">TEAZZI</span>
    <div class="ms-auto">
      <button class="btn btn-outline-light position-relative cart-btn" type="button" data-bs-toggle="modal" data-bs-target="#cartModal" aria-label="Buka keranjang">
        🛒
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">0</span>
      </button>
    </div>
  </div>
</nav>


<!-- Menu -->
<div class="container my-4 my-md-5">

<?php
$categories = ['OUR SIGNATURE', 'PURE TEA', 'MILK TEA', 'HONEY SERIES'];

foreach($categories as $category):
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, price, image FROM menu WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $menuResult = $stmt->get_result();
    
    if($menuResult->num_rows > 0):
?>
    <h2 class="text-center fw-bold menu-section-title"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></h2>
    
    <div class="row g-3 g-md-4 justify-content-center mb-4 mb-md-5">
        <?php while ($menu = $menuResult->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3 d-flex align-items-stretch">
          <div class="card menu-card h-100 shadow-sm border-0 w-100">
            <?php if(!empty($menu['image'])): ?>
            <img src="assets/img/<?= htmlspecialchars($menu['image'], ENT_QUOTES, 'UTF-8') ?>" 
                 class="card-img-top menu-card-image" 
                 alt="<?= htmlspecialchars($menu['name'], ENT_QUOTES, 'UTF-8') ?>" 
                 loading="lazy">
            <?php endif; ?>
            <div class="card-body text-center d-flex flex-column">
              <h5 class="fw-semibold"><?= htmlspecialchars($menu['name'], ENT_QUOTES, 'UTF-8') ?></h5>
              <p class="text-muted mb-3">Rp <?= number_format($menu['price'],0,',','.') ?></p>
              <?php if($category != 'topping'): ?>
              <button type="button" 
                      class="btn btn-primary mt-auto"
                      data-bs-toggle="modal" 
                      data-bs-target="#toppingModal" 
                      data-name="<?= htmlspecialchars($menu['name']) ?>" 
                      data-price="<?= $menu['price'] ?>">
                Pilih Topping
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
    </div>
<?php
    endif;
endforeach;
?>

</div>


<!-- Modal Topping -->
<div class="modal fade" id="toppingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Pilih Topping</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2"><strong id="menuName"></strong> — Rp <span id="menuPrice"></span></p>
        <label class="fw-semibold">Jumlah:</label>
        <input type="number" id="menuQty" value="1" min="1" class="form-control mb-3 w-50">

        <label class="fw-semibold">Topping:</label>
        <div class="d-flex flex-wrap gap-2">
          <?php
          $toppingStmt = $conn->prepare("SELECT id, name, price FROM menu WHERE category = ?");
          $toppingCategory = 'topping';
          $toppingStmt->bind_param("s", $toppingCategory);
          $toppingStmt->execute();
          $toppingResult = $toppingStmt->get_result();
          
          while ($topping = $toppingResult->fetch_assoc()):
          ?>
          <div class="form-check me-3">
            <input class="form-check-input toppingCheck" type="checkbox" value="<?= htmlspecialchars($topping['name'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars($topping['price'], ENT_QUOTES, 'UTF-8') ?>" id="topping_<?= htmlspecialchars($topping['id'], ENT_QUOTES, 'UTF-8') ?>">
            <label class="form-check-label" for="topping_<?= htmlspecialchars($topping['id'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($topping['name'], ENT_QUOTES, 'UTF-8') ?> (Rp <?= number_format($topping['price'],0,',','.') ?>)
            </label>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="addToCart">Tambah ke Keranjang</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Keranjang -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Keranjang Pesanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="cartItems">
          <p class="text-muted text-center">Belum ada pesanan</p>
        </div>
        <hr>
        <div class="d-flex justify-content-between">
          <strong>Total:</strong>
          <span id="cartTotal">Rp 0</span>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success w-100" id="checkoutBtn">Lanjut ke Pembayaran</button>
      </div>
    </div>
  </div>
</div>

<script>
let selectedMenu = {name:'', price:0};
let cart = [];

// Modal topping dibuka
document.getElementById('toppingModal').addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  selectedMenu.name = button.getAttribute('data-name');
  selectedMenu.price = parseInt(button.getAttribute('data-price'));

  document.getElementById('menuName').textContent = selectedMenu.name;
  document.getElementById('menuPrice').textContent = new Intl.NumberFormat('id-ID').format(selectedMenu.price);
  document.getElementById('menuQty').value = 1;
  document.querySelectorAll('.toppingCheck').forEach(cb => cb.checked=false);
});

// Tambah ke keranjang
document.getElementById('addToCart').addEventListener('click', () => {
  const qty = parseInt(document.getElementById('menuQty').value);
  const toppings = [];
  let toppingTotal = 0;

  document.querySelectorAll('.toppingCheck:checked').forEach(cb => {
    toppings.push({name: cb.value, price: parseInt(cb.dataset.price)});
    toppingTotal += parseInt(cb.dataset.price);
  });

  const subtotal = (selectedMenu.price + toppingTotal) * qty;
  cart.push({menu: selectedMenu.name, qty, toppings, subtotal});
  updateCartModal();

  bootstrap.Modal.getInstance(document.getElementById('toppingModal')).hide();
});

// Update modal keranjang
function updateCartModal(){
  const cartCount = document.getElementById('cartCount');
  const cartItems = document.getElementById('cartItems');
  const cartTotal = document.getElementById('cartTotal');

  cartCount.textContent = cart.length;

  if(cart.length===0){
    cartItems.innerHTML = '<p class="text-muted text-center">Belum ada pesanan</p>';
    cartTotal.textContent='Rp 0';
    return;
  }

  cartItems.innerHTML='';
  let total=0;
  cart.forEach((item,index)=>{
    total+=item.subtotal;
    const toppingText = item.toppings.map(t=>`${t.name} (Rp ${new Intl.NumberFormat('id-ID').format(t.price)})`).join('<br>')||'-';
    const div=document.createElement('div');
    div.classList.add('mb-3','border-bottom','pb-2');
    div.innerHTML=`
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <strong>${item.menu}</strong> x${item.qty}<br>
          <small>${toppingText}</small><br>
          <small>Subtotal: Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}</small>
        </div>
        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">Hapus</button>
      </div>
    `;
    cartItems.appendChild(div);
  });
  cartTotal.textContent='Rp '+new Intl.NumberFormat('id-ID').format(total);
}

// Hapus item
function removeFromCart(index){
  cart.splice(index,1);
  updateCartModal();
}

// Checkout
document.getElementById('checkoutBtn').addEventListener('click',()=>{
  const form=document.createElement('form');
  form.method='POST';
  form.action='checkout.php';

  cart.forEach((item,i)=>{
    form.insertAdjacentHTML('beforeend',`
      <input type="hidden" name="menu[${i}][name]" value="${item.menu}">
      <input type="hidden" name="menu[${i}][qty]" value="${item.qty}">
      <input type="hidden" name="menu[${i}][subtotal]" value="${item.subtotal}">
      <input type="hidden" name="menu[${i}][toppings]" value='${JSON.stringify(item.toppings)}'>
    `);
  });

  document.body.appendChild(form);
  form.submit();
});
</script>

</body>
</html>
