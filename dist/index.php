<?php
// VEDA_CMS_V1
require __DIR__.'/admin/core.php';
try {
    boot();$preview=isset($_GET['preview']);
    if($preview){sessionboot();if(!cms_current()){http_response_code(403);exit('Sign in to preview drafts.');}}
    header('Content-Type: text/html; charset=utf-8');header('X-Content-Type-Options: nosniff');
    echo rendered($preview);
} catch(Throwable $e) { http_response_code(503);readfile(__DIR__.'/index.html'); }
