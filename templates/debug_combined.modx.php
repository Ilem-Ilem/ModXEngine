<html>
<head>
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    
<header>
    <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
    <p>User: <?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?></p>
</header>
    <?php echo htmlspecialchars($food, ENT_QUOTES, 'UTF-8'); ?>
    <div>
        here now
    </div>
<ul>
    <?php foreach ($array_test as $index => $test): ?>
        <li><?php echo htmlspecialchars($index, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>

</ul>
</body>
</html>