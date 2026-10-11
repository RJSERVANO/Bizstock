<?php
declare(strict_types=1);
namespace BizStock\Http;

final class ApiRouter
{
    private array $routes=[];
    public function add(string $path,array $methods,callable $handler): void
    {
        if(!str_starts_with($path,'/api/v1/') || isset($this->routes[$path])){throw new \InvalidArgumentException('Invalid or duplicate API route.');}
        $parts=[];$names=[];
        foreach(explode('/',$path) as $part){
            if(preg_match('/^\{([a-z][a-z0-9_]*)\}$/D',$part,$match)){
                if(in_array($match[1],$names,true)){throw new \InvalidArgumentException('Repeated route parameter.');}
                $names[]=$match[1];$parts[]='(?P<'.$match[1].'>[1-9][0-9]*)';
            }else{$parts[]=preg_quote($part,'~');}
        }
        $this->routes[$path]=[$methods,$handler,'~^'.implode('/',$parts).'$~D',$names];
    }
    public function dispatch(string $method,string $path): Response
    {
        foreach($this->routes as [$methods,$handler,$pattern,$names]){
            if(!preg_match($pattern,$path,$matches)){continue;}
            if(!in_array($method,$methods,true)){throw new HttpException(405,'METHOD_NOT_ALLOWED','Method not allowed.',[],['Allow'=>implode(', ',$methods)]);}
            $parameters=[];foreach($names as $name){$parameters[$name]=\BizStock\Support\ApiInput::integer($matches[$name],$name,1);}
            return $handler($parameters);
        }
        throw new HttpException(404,'NOT_FOUND','Route not found.');
    }
}
