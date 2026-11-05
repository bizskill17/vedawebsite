<?php
// VEDA_CMS_V1 - Hostinger PHP content management.
declare(strict_types=1);
const CMS_DATA = __DIR__ . '/../../veda-cms-data';
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function schema(): array { static $s; return $s ??= json_decode(file_get_contents(__DIR__.'/schema.json'), true, 512, JSON_THROW_ON_ERROR); }
function boot(): void {
    if (!is_dir(CMS_DATA) && !mkdir(CMS_DATA,0700,true) && !is_dir(CMS_DATA)) throw new RuntimeException('Cannot create private CMS storage.');
    if (!is_file(CMS_DATA.'/state.json')) mutate(function(&$s) { if (!$s) $s=['users'=>[], 'live'=>[], 'draft'=>[], 'hidden'=>[], 'draft_hidden'=>[], 'revision'=>0,'history'=>[],'audit'=>[],'attempts'=>[]]; });
    $lock=fopen(CMS_DATA.'/setup.lock','c'); flock($lock,LOCK_EX);
    if (!is_file(CMS_DATA.'/setup-token.txt') && !readstate()['users']) file_put_contents(CMS_DATA.'/setup-token.txt',bin2hex(random_bytes(24)),LOCK_EX);
    flock($lock,LOCK_UN);fclose($lock);
}
function readstate(): array { return is_file(CMS_DATA.'/state.json') ? json_decode(file_get_contents(CMS_DATA.'/state.json'),true,512,JSON_THROW_ON_ERROR) : []; }
function mutate(callable $fn) {
    $lock=fopen(CMS_DATA.'/write.lock','c');if(!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Storage lock failed.');
    try { $s=readstate();$result=$fn($s);$tmp=CMS_DATA.'/state-'.bin2hex(random_bytes(6)).'.tmp';$data=json_encode($s,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);if(file_put_contents($tmp,$data)===false)throw new RuntimeException('Storage write failed.');chmod($tmp,0600);if(!rename($tmp,CMS_DATA.'/state.json'))throw new RuntimeException('Storage update failed.');return $result; }
    finally { flock($lock,LOCK_UN);fclose($lock); }
}
function sessionboot(): void {
    header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: same-origin');
    ini_set('session.use_strict_mode','1');session_name('veda_admin');session_set_cookie_params(['httponly'=>true,'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'),'samesite'=>'Strict','path'=>'/']);session_start();
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24));
    if(isset($_SESSION['last']) && time()-$_SESSION['last']>3600) { unset($_SESSION['uid']);session_regenerate_id(true); }
    $_SESSION['last']=time();
}
function cms_current(): ?array { $u=readstate()['users'][$_SESSION['uid']??'']??null;return $u && $u['active'] && ($u['version']??0)===($_SESSION['version']??-1) ? $u : null; }
function allowed(array $u,string $g): bool { return $u['role']==='admin' || in_array($g,$u['groups'],true); }
function csrf(): void { if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Session expired. Refresh and try again.'); }
function audit(array &$s,string $action): void { $s['audit'][]=['time'=>gmdate('c'),'user'=>$_SESSION['uid']??'setup','action'=>$action];$s['audit']=array_slice($s['audit'],-200); }
function safevalue(array $field,string $v): string {
    $v=trim($v);if(strlen($v)>12000)throw new RuntimeException('A field is too long.');
    $type=$field['type'];
    if($type==='category' && !in_array($v,['spa','skin','aesthetics','salon','wellness'],true))throw new RuntimeException('Invalid service category.');
    if(in_array($type,['url','image','map'],true) && $v!=='') {
        if(preg_match('/[\x00-\x20<>"\'\\\\]/',$v))throw new RuntimeException('Please enter a valid URL without spaces or quotation marks.');
        $scheme=strtolower((string)parse_url($v,PHP_URL_SCHEME));
        if($scheme && !in_array($scheme,$type==='url'?['https','http','tel','mailto']:['https'],true))throw new RuntimeException('This URL type is not allowed.');
        if(str_starts_with($v,'//') || str_contains($v,'..'))throw new RuntimeException('Invalid relative URL.');
        if($type==='map') { $h=strtolower((string)parse_url($v,PHP_URL_HOST));if($scheme!=='https'|| !in_array($h,['maps.google.com','www.google.com','www.google.co.in','maps.google.co.in'],true))throw new RuntimeException('Use a Google Maps HTTPS embed URL.'); }
    }
    return $v;
}
function rendered(bool $draft=false): string {
    $s=readstate();$values=$draft?$s['draft']:$s['live'];$hidden=$draft?$s['draft_hidden']:$s['hidden'];$html=file_get_contents(__DIR__.'/template.html');
    foreach(schema()['groups'] as $g=>$def) if(!empty($hidden[$g]) && !empty($def['hideable'])) $html=preg_replace('/<!--CMSSTART:'.preg_quote($g,'/').'-->.*?<!--CMSEND:'.preg_quote($g,'/').'-->/s','',$html);
    $html=preg_replace_callback('/\{\{(f\d+)\}\}/',function($m)use($values){$f=schema()['fields'][$m[1]]??null;return $f?esc($values[$m[1]]??$f['default']):'';},$html);
    return preg_replace('/<!--CMS(?:START|END):\w+-->/','',$html);
}
