<?php use App\Core\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?> | Binary City</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header>
  <h1>Binary City</h1>
  <nav>
    <a href="/clients"  class="<?= $nav === 'clients'  ? 'active' : '' ?>">Clients</a>
    <a href="/contacts" class="<?= $nav === 'contacts' ? 'active' : '' ?>">Contacts</a>
  </nav>
</header>
<main><div class="card"><?= $content ?></div></main>
<div class="toast" id="toast" role="status"></div>
<script src="/assets/app.js"></script>
</body>
</html>
