<?php
require_once __DIR__.'/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

$robots = getSetting('seo_robots_txt', "User-agent: *\nAllow: /\nSitemap: ".BASE_URL."/sitemap.xml");
echo $robots;
