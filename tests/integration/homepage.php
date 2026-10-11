<?php
declare(strict_types=1);
require __DIR__.'/BusinessFixture.php';
$f=new BusinessFixture('homepage');
try {
    $f->serve();
    $f->product('NAV-LAYOUT');
    $jar=[];
    [$status,,$headers,$html]=$f->http('/',$jar);
    $f->check($status===200 && str_contains($html,'Know your stock. Run your business with confidence.'),'public real HTTP homepage');
    $f->check(!isset($jar['cookie']) && !str_contains($html,'test1') && !str_contains($html,'Admin 1') && !str_contains($html,'NAV-LAYOUT'),'anonymous has no session/private identity or inventory');
    $f->check(str_contains($html,'Sample data') && str_contains($html,'fictional people') && str_contains($html,'Contact your business administrator.'),'honest sample/contact/imagery');
    $f->check(str_contains($html,'href="/bizstock/login"') && str_contains($html,'/bizstock/assets/images/stockroom-480.webp'),'configured base path');
    $f->check(in_array('Cache-Control: private, no-store',$headers,true),'public CTA is never cached across accounts');
    foreach([1,2,5] as $id) {
        $signed=$f->login($id);
        [$status,,,$html]=$f->http('/',$signed);
        $f->check($status===200 && str_contains($html,'Open dashboard') && str_contains($html,'href="/bizstock/dashboard"'),'public page accessible to signed role '.$id);
        $f->check(!str_contains($html,'test'.$id) && !str_contains($html,'/api/v1/account/photo') && !str_contains($html,'/api/v1/products/'),'no private identity/images in public page');
    }
    $f->pdo->exec('UPDATE users SET must_change_password=1 WHERE user_id=5');
    [$status,,,$html]=$f->http('/',$signed);
    $f->check($status===200 && str_contains($html,'Complete password change') && str_contains($html,'href="/bizstock/account"'),'forced role can read public home but must change before dashboard');
    $f->pdo->exec('UPDATE users SET must_change_password=0 WHERE user_id=5');
    $f->pdo->exec('UPDATE users SET session_version=session_version+1 WHERE user_id=5');
    [$status,,,$html]=$f->http('/',$signed);
    $f->check($status===200 && !str_contains($html,'Open dashboard') && str_contains($html,'href="/bizstock/login"'),'revoked session does not get authenticated public CTA');
    $previous=['APP_BUSINESS_NAME'=>getenv('APP_BUSINESS_NAME'),'APP_CONTACT_EMAIL'=>getenv('APP_CONTACT_EMAIL')];
    try {
        putenv('APP_BUSINESS_NAME=<Owner & Team>');putenv('APP_CONTACT_EMAIL=fixture@example.invalid');
        $custom=BizStock\Support\Config::load($f->config->root);
        $rendered=(new BizStock\Http\Controllers\FoundationController($custom))->home()->body;
        $f->check(str_contains($rendered,'&lt;Owner &amp; Team&gt;') && !str_contains($rendered,'<Owner & Team>'),'central business identity escaped');
        $f->check(str_contains($rendered,'mailto:fixture@example.invalid'),'only configured contact is rendered');
        foreach([['APP_CONTACT_EMAIL','invalid-address'],['APP_CONTACT_EMAIL',"mail@example.invalid\r\nInjected: value"],['APP_BUSINESS_NAME',str_repeat('x',101)],['APP_BUSINESS_NAME',"Bad\nname"]] as [$key,$value]) {
            putenv('APP_CONTACT_EMAIL=');putenv('APP_BUSINESS_NAME=BizStock');putenv($key.'='.$value);
            try { BizStock\Support\Config::load($f->config->root);throw new RuntimeException('Invalid public configuration accepted'); }
            catch(InvalidArgumentException) { $f->check(true,'invalid public configuration rejected'); }
        }
    } finally { foreach($previous as $key=>$value) {putenv($value===false?$key:$key.'='.$value);} }
    foreach([480,960,1440] as $width) {
        [$status,, $headers,$bytes]=$f->http('/assets/images/stockroom-'.$width.'.webp',$jar);
        $size=getimagesizefromstring($bytes);
        $f->check($status===200 && $size[0]===$width && strlen($bytes)<100000 && in_array('Content-Type: image/webp',$headers,true),'local optimized image '.$width);
    }
    $f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'before browser reconciles');
    $f->browser('tests/browser/homepage.cjs');
    $f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'after browser reconciles');
    echo 'PASS: '.$f->checks." homepage HTTP/MariaDB assertions.\n";
} finally { $f->close(); }
