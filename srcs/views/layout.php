<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>📸 Camagru</title>
	<link rel="icon" href="data:;base64,iVBORw0KGgo=" />
	<link rel="stylesheet" href="/css/styles.css">
</head>
<body>

	<header class="main-header">
		<a href="/" class="logo">CAM<span>★</span>GRU</a>
		<nav>
			<?php
			$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			if (isset($_SESSION['user_id'])): ?>
				<a href="/studio" class="btn btn-ghost">Studio</a>
				<a href="/logout" class="btn btn-primary">Log out</a>
			<?php elseif ($currentPath === '/login'): ?>
				<a href="/register" class="btn btn-primary">Register</a>
			<?php elseif ($currentPath === '/register'): ?>
				<a href="/login" class="btn btn-ghost">Login</a>
			<?php else: ?>
				<a href="/login" class="btn btn-ghost">Login</a>
				<a href="/register" class="btn btn-primary">Register</a>
			<?php endif; ?>
		</nav>
	</header>

	<div class="ticker-wrap">
		<div class="ticker-track">
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
			<span>★ CREATE</span><span>★ SHARE</span><span>★ LIKE</span><span>★ COMMENT</span>
		</div>
	</div>

	<?php echo $content ?? ''; ?>

	<footer>
		<span>© <?= date('Y') ?> Camagru</span>
		<span>Made with ★</span>
	</footer>

</body>
</html>