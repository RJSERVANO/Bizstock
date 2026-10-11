<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Http\{HttpException,Authorization};
use BizStock\Support\{Config,AtomicWrite};
use BizStock\Repositories\{AuditRepository,ImageRepository};

/** Only generated, re-encoded WebP files enter this private store. */
final readonly class ImageService
{
    public function __construct(private \PDO $pdo, private Config $config) {}
    public function directory(): string { return $this->config->root.'/var/uploads/'.hash('sha256',$this->config->environment.'|'.$this->config->dbName); }
    private function path(string $name): string {
        if(!preg_match('/^[a-f0-9]{32}\.webp$/D',$name)){throw new \RuntimeException('Invalid stored image reference.');}
        return $this->directory().'/'.$name;
    }
    public function read(bool $product,int $id): string {
        $name=(new ImageRepository($this->pdo))->find($product,$id)['filename']??null;
        if(!$name || !is_file($this->path($name)) || is_link($this->path($name))){throw new HttpException(404,'IMAGE_NOT_FOUND','No image is available.');}
        $bytes=@file_get_contents($this->path($name));
        if($bytes===false){throw new HttpException(404,'IMAGE_NOT_FOUND','No image is available.');}return $bytes;
    }
    public function encode(array $file,bool $product): string {
        if(!extension_loaded('gd')||!extension_loaded('fileinfo')||!(gd_info()['WebP Support']??false)){throw new HttpException(503,'IMAGE_SUPPORT_UNAVAILABLE','Image processing is unavailable.');}
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_string($file['tmp_name']??null)){throw new HttpException(422,'INVALID_IMAGE','Choose an image up to 2 MiB.');}
        $source=$file['tmp_name']??'';
        if(!is_uploaded_file($source)){throw new HttpException(422,'INVALID_IMAGE','Choose a valid uploaded image.');}
        $bytes=file_get_contents($source);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if(strlen($bytes)>2097152 || !in_array($mime,['image/jpeg','image/png','image/webp'],true) || preg_match('/<\?(?:php|=)|<script\b/i',$bytes)) {throw new HttpException(422,'INVALID_IMAGE','Use JPEG, PNG or WebP up to 2 MiB.');}
        $size=@getimagesizefromstring($bytes);
        if(!$size || $size[0]<1 || $size[1]<1 || max($size[0],$size[1])>2048 || $size[0]*$size[1]>4194304){throw new HttpException(422,'INVALID_IMAGE','Images must be at most 2048 by 2048 pixels.');}
        try{$image=@imagecreatefromstring($bytes);}catch(\Throwable){$image=false;}
        if(!$image){throw new HttpException(422,'INVALID_IMAGE','The image could not be decoded.');}
        $limit=$product?1024:512;$ratio=min(1,$limit/max($size[0],$size[1]));
        $output=imagecreatetruecolor(max(1,(int)round($size[0]*$ratio)),max(1,(int)round($size[1]*$ratio)));
        imagealphablending($output,false);imagesavealpha($output,true);
        imagecopyresampled($output,$image,0,0,0,0,imagesx($output),imagesy($output),$size[0],$size[1]);
        $directory=$this->directory();if(!is_dir($directory)&&!@mkdir($directory,0700,true)&&!is_dir($directory)){throw new \RuntimeException('Image storage unavailable.');}
        if(is_link($directory)||is_link(dirname($directory))||realpath(dirname($directory))!==realpath($this->config->root.'/var/uploads')){throw new \RuntimeException('Unsafe image storage.');}
        $name=bin2hex(random_bytes(16)).'.webp';
        ob_start();try{if(!imagewebp($output,null,85)){throw new \RuntimeException('Image encoding failed.');}$encoded=ob_get_contents();}finally{ob_end_clean();unset($image,$output);}
        $handle=fopen($this->path($name),'x+b');
        try{chmod($this->path($name),0600);if(fwrite($handle,$encoded)!==strlen($encoded)){throw new \RuntimeException('Image storage failed.');}}catch(\Throwable $e){fclose($handle);$this->removeFile($name);throw $e;}fclose($handle);
        return $name;
    }
    public function change(bool $product,int $id,array $actor,?array $file,string $requestId): array {
        Authorization::requireRole($actor,$product?['Admin']:['Admin','Staff','Viewer']);
        if(!$product && $id!==(int)$actor['user_id']){throw new HttpException(403,'FORBIDDEN','Access denied.');}
        $new=$file===null?null:$this->encode($file,$product);
        $repository=new ImageRepository($this->pdo);
        try{$old=AtomicWrite::run($this->pdo,$actor,$product?['Admin']:['Admin','Staff','Viewer'],function()use($repository,$id,$new,$actor,$product,$requestId){
            $row=$repository->find($product,$id,true);
            if(!$row){throw new HttpException(404,'NOT_FOUND','Record not found.');}
            if(!$row['is_active']){throw new HttpException(409,'INACTIVE_RECORD','Images cannot be changed for an inactive record.');}
            $repository->replace($product,$id,$new);
            (new AuditRepository($this->pdo))->record((int)$actor['user_id'],$product?'product.image_changed':'account.photo_changed',$product?'product':'user',$id,['has_image'=>$row['filename']!==null],['has_image'=>$new!==null],$requestId);
            return $row['filename'];
        });}catch(\Throwable $e){if($new!==null){$this->removeFile($new);}throw $e;}
        if($old!==null){$this->removeFile($old);}
        return ['has_image'=>$new!==null];
    }
    private function removeFile(string $name): void { $path=$this->path($name);if(is_file($path)&&!is_link($path)){unlink($path);} }
}
