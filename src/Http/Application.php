<?php
declare(strict_types=1);

namespace BizStock\Http;

use BizStock\Http\Controllers\FoundationController;
use BizStock\Http\Controllers\PreviewController;
use BizStock\Support\Config;
use BizStock\Support\ApiInput;

final readonly class Application
{
    public function __construct(private Config $config) {}

    public function handle(string $method, string $uri, string $requestId): Response
    {
        $path = explode('?', $uri, 2)[0];
        if ($path === '' || $path[0] !== '/' || preg_match('/[\x00-\x20\x7f]/', $path)) {
            throw new HttpException(400, 'BAD_REQUEST', 'Invalid request path.');
        }
        if ($this->config->basePath !== '') {
            if ($path === $this->config->basePath) {
                return new Response(308, '', ['Location' => $this->config->url('/')]);
            }
            if (!str_starts_with($path, $this->config->basePath . '/')) {
                throw new HttpException(404, 'NOT_FOUND', 'Route not found.');
            }
            $path = substr($path, strlen($this->config->basePath));
        }
        $preview = str_starts_with($path, '/preview/') && $this->config->environment !== 'production';
        $sessions=new \BizStock\Support\SessionManager($this->config);
        $factory=new \BizStock\Support\PdoFactory($this->config);
        $auth=new \BizStock\Services\AuthService($factory,$sessions);
        $authController=new \BizStock\Http\Controllers\AuthController($auth,$sessions,$this->config);
        $lookups=new \BizStock\Http\Controllers\LookupController($auth,$sessions,new \BizStock\Services\LookupService($factory),$this->config);
        if($path==='/api/v1' || str_starts_with($path,'/api/v1/')){
            $router=new ApiRouter();
            $images=new Controllers\ImageController($this->config,$factory,$auth,$sessions);
            $profile=new Controllers\ProfileController($factory,$auth,$sessions);
            $router->add('/api/v1/account/profile',['PATCH'],fn():Response=>$profile->handle($uri,$requestId));
            $router->add('/api/v1/account/photo',['GET','POST','DELETE'],fn():Response=>$images->handle($method,null,$uri,$requestId));
            $router->add('/api/v1/products/{id}/image',['GET','POST','DELETE'],fn(array $params):Response=>$images->handle($method,$params['id'],$uri,$requestId));
            $router->add('/api/v1/health',['GET','HEAD'],function()use($uri):Response{
                ApiInput::query(explode('?',$uri,2)[1]??'',[]);
                return (new FoundationController($this->config))->health();
            });
            foreach (['/api/v1/auth/csrf'=>'GET','/api/v1/auth/login'=>'POST','/api/v1/auth/logout'=>'POST',
                '/api/v1/auth/me'=>'GET','/api/v1/account/password'=>'PATCH'] as $route=>$verb) {
                $router->add($route,[$verb],function()use($authController,$route,$requestId,$uri):Response{
                    ApiInput::query(explode('?',$uri,2)[1]??'',[]);
                    return $authController->handle($route,$requestId);
                });
            }
            foreach (Authorization::plannedRoutes() as $route=>$permissions) {
                if(str_starts_with($route,'/api/v1/users')||$route==='/api/v1/audit-events'){$users=new Controllers\UserController($factory,$auth,$sessions);$router->add($route,array_keys($permissions),fn(array $params):Response=>$users->handle($method,$params['id']??null,$uri,$requestId,$route==='/api/v1/audit-events',str_ends_with($route,'/password-reset')));continue;}
                if(preg_match('~^/api/v1/reports/(inventory|transactions)$~D',$route,$reportRoute)){$reports=new Controllers\ReportController($factory,$auth);$report=$reportRoute[1];$router->add($route,['GET'],fn():Response=>$reports->handle($report,$uri));continue;}
                if($route==='/api/v1/dashboard'){$dashboard=new Controllers\DashboardController($factory,$auth);$router->add($route,['GET'],fn():Response=>$dashboard->handle($uri));continue;}
                if(in_array($route,['/api/v1/products','/api/v1/products/{id}'],true)) {
                    $products=new \BizStock\Http\Controllers\ProductController($factory,$auth,$sessions);
                    $router->add($route,array_keys($permissions),fn(array $params):Response=>$products->handle($method,$params['id']??null,$uri,$requestId));continue;
                }
                if(in_array($route,['/api/v1/transactions','/api/v1/transactions/{id}'],true)) {
                    $stock=new \BizStock\Http\Controllers\StockController($factory,$auth,$sessions);
                    $router->add($route,array_keys($permissions),fn(array $params):Response=>$stock->handle($method,$params['id']??null,$uri));continue;
                }
                if(preg_match('~^/api/v1/(categories|suppliers)(?:/\{id\})?$~D',$route,$lookup)) {
                    $resource=$lookup[1];
                    $router->add($route,array_keys($permissions),fn(array $params):Response=>$lookups->handle($resource,$method,$params['id']??null,$uri,$requestId));
                    continue;
                }
                $router->add($route,array_keys($permissions),function()use($auth,$sessions,$permissions,$method,$route,$uri):Response{
                    $user=$auth->current(); Authorization::requireRole($user,$permissions[$method]);
                    if (!in_array($method,['GET','HEAD'],true)) { $sessions->verifyCsrf(); }
                    if ($user['role']==='Viewer' && $route==='/api/v1/products') {
                        $query=ApiInput::query(explode('?',$uri,2)[1]??'',['q','category_id','supplier_id','active','stock_status','page','per_page','sort','direction']);
                        if (isset($query['supplier_id']) || in_array($query['sort']??'',['price','supplier_id','supplier_name'],true)) {
                            throw new HttpException(403,'FORBIDDEN','Access denied.');
                        }
                    }
                    if ($user['role']==='Staff' && $route==='/api/v1/transactions' && $method==='POST') {
                        $body=ApiInput::body(['product_id','type','quantity','notes','correction_of_transaction_id']);
                        if (array_key_exists('correction_of_transaction_id',$body)) { throw new HttpException(403,'FORBIDDEN','Access denied.'); }
                    }
                    throw new HttpException(404,'NOT_FOUND','Route not found.');
                });
            }
            return $router->dispatch($method,$path);
        }
        if(in_array($path,['/dashboard','/movements','/history','/reports/inventory','/reports/transactions','/users','/audit-events'],true)){
            if(!in_array($method,['GET','HEAD'],true)){throw new HttpException(405,'METHOD_NOT_ALLOWED','Method not allowed.',[],['Allow'=>'GET, HEAD']);}
            return (new Controllers\BusinessPageController($this->config,$auth))->page(str_starts_with($path,'/reports/')?'reports':substr($path,1),$path==='/reports/transactions'?1:null);
        }
        if($path==='/products'||$path==='/products/new'||preg_match('~^/products/([1-9][0-9]*)/edit$~D',$path,$productPage)){
            if(!in_array($method,['GET','HEAD'],true)){throw new HttpException(405,'METHOD_NOT_ALLOWED','Method not allowed.',[],['Allow'=>'GET, HEAD']);}
            return (new Controllers\BusinessPageController($this->config,$auth))->page($path==='/products'?'products':'product-form',isset($productPage[1])?ApiInput::integer($productPage[1],'id',1):null);
        }
        if(in_array($path,['/categories','/suppliers'],true)) {
            if(!in_array($method,['GET','HEAD'],true)){throw new HttpException(405,'METHOD_NOT_ALLOWED','Method not allowed.',[],['Allow'=>'GET, HEAD']);}
            ApiInput::query(explode('?',$uri,2)[1]??'',[]); return $lookups->page(substr($path,1));
        }
        if (in_array($path,['/login','/account'],true)) {
            if (!in_array($method,['GET','HEAD'],true)) { throw new HttpException(405,'METHOD_NOT_ALLOWED','Method not allowed.',[],['Allow'=>'GET, HEAD']); }
            $query=ApiInput::query(explode('?',$uri,2)[1]??'',$path==='/login'?['role']:[]);
            $hint=isset($query['role'])?ApiInput::choice($query['role'],'role',['Admin','Staff','Viewer']):'';
            return $authController->page(substr($path,1),$hint);
        }
        if (!$preview && $path!=='/') {
            throw new HttpException(404, 'NOT_FOUND', 'Route not found.');
        }
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            throw new HttpException(405,'METHOD_NOT_ALLOWED','Method not allowed.',[],['Allow'=>'GET, HEAD']);
        }
        if ($preview) {
            return (new PreviewController($this->config))->show($path, $uri);
        }
        $controller = new FoundationController($this->config,$auth);
        return $controller->home();
    }
}
