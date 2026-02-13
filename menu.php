<?php
include 'db.php';

if(isset($_POST['simpan'])){
  $name = $_POST['name'];
  $price = $_POST['price'];
  $category = $_POST['category'];

  $conn->query("INSERT INTO menu(name,price,category)
                VALUES('$name','$price','$category')");
}

if(isset($_GET['hapus'])){
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM menu WHERE id=$id");
}
?>

<?php include 'sidebar.php'; ?>

<div class="container p-4">

<h4>CRUD Menu</h4>

<form method="POST" class="row g-2 mb-4">
  <div class="col-md-4">
    <input type="text" name="name" class="form-control" placeholder="Nama Menu" required>
  </div>
  <div class="col-md-3">
    <input type="number" name="price" class="form-control" placeholder="Harga" required>
  </div>
  <div class="col-md-3">
    <input type="text" name="category" class="form-control" placeholder="Kategori">
  </div>
  <div class="col-md-2">
    <button name="simpan" class="btn btn-primary w-100">Simpan</button>
  </div>
</form>

<table class="table table-bordered">
<tr>
<th>Nama</th>
<th>Harga</th>
<th>Kategori</th>
<th>Aksi</th>
</tr>

<?php
$data = $conn->query("SELECT * FROM menu");
while($m = $data->fetch_assoc()){
?>
<tr>
<td><?= $m['name'] ?></td>
<td>Rp <?= number_format($m['price']) ?></td>
<td><?= $m['category'] ?></td>
<td>
  <a href="?hapus=<?= $m['id'] ?>" 
     class="btn btn-danger btn-sm"
     onclick="return confirm('Hapus menu?')">
     Hapus
  </a>
</td>
</tr>
<?php } ?>

</table>
</div>
</div>
