<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Http\{Response,Authorization,HttpException};
use BizStock\Support\{Config,PdoFactory,SessionManager,ApiInput};
use BizStock\Services\{AuthService,ImageService};
final readonly class ImageController
{
    public function __construct(private Config $config,private PdoFactory $factory,private AuthService $auth,private SessionManager $sessions){}
    public function handle(string $method,?int $id,string $uri,string $requestId): Response {
        $actor=$this->auth->current();$product=$id!==null;$id??=(int)$actor['user_id'];
        $query=ApiInput::query(explode('?',$uri,2)[1]??'',$method==='GET'?['size']:[]);
        $size=isset($query['size'])?ApiInput::choice($query['size'],'size',['full','thumbnail','card']):'full';
        $service=new ImageService($this->factory->connection(),$this->config);
        if($method==='GET'){return new Response(200,$service->read($product,$id,$size),['Content-Type'=>'image/webp','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff','Content-Disposition'=>'inline']);}
        Authorization::requireRole($actor,$product?['Admin']:['Admin','Staff','Viewer']);$this->sessions->verifyCsrf();
        if($method==='POST' && (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE']??''),'multipart/form-data;') || $_POST!==[] || array_diff(array_keys($_FILES),['image']))){throw new HttpException(422,'INVALID_IMAGE','Upload one image using the image field.');}
        if($method==='DELETE' && (int)($_SERVER['CONTENT_LENGTH']??0)>0){ApiInput::body([]);}
        return Response::success($service->change($product,$id,$actor,$method==='DELETE'?null:($_FILES['image']??[]),$requestId));
    }
}
