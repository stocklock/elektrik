<?php
header("Content-Type: application/xml; charset=utf-8");

// === НАСТРОЙКИ ===
$site = "https://elektrik-syktyvkar.ru";

// === ПОДКЛЮЧЕНИЕ К БД ===
$mysqli = new mysqli("localhost", "u1851662_default", "5OBw9nvRSA3Al7V0", "u1851662_adminka");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
  die("DB error");
}

// === ЗАПРОС РАЙОНОВ ===
$result = $mysqli->query("
  SELECT slug, priority
  FROM districts
  ORDER BY priority ASC
");

// === XML HEADER ===
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// === ГЛАВНАЯ СТРАНИЦА ===
echo "
<url>
  <loc>{$site}/</loc>
  <lastmod>" . date("Y-m-d") . "</lastmod>
  <changefreq>daily</changefreq>
  <priority>1.0</priority>
</url>
";

// === СТРАНИЦЫ РАЙОНОВ ===
while ($row = $result->fetch_assoc()) {

  $slug = htmlspecialchars($row['slug']);
  $priority = number_format(min(0.9, max(0.3, 1 - ($row['priority'] / 100))), 1);
  $lastmod = date("Y-m-d");

  echo "
<url>
  <loc>{$site}/{$slug}</loc>
  <lastmod>{$lastmod}</lastmod>
  <changefreq>weekly</changefreq>
  <priority>{$priority}</priority>
</url>
";
}

echo '</urlset>';
