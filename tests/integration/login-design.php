<?php
declare(strict_types=1);require __DIR__.'/BusinessFixture.php';
$f=new BusinessFixture('logindesign');try{$f->serve();$f->browser('tests/browser/login-design.cjs');$f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'login-only fixture reconciles');echo 'PASS: '.$f->checks.' login-design harness assertions.'.PHP_EOL;}finally{$f->close();}
