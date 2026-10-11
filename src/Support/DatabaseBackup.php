<?php
declare(strict_types=1);namespace BizStock\Support;
final readonly class DatabaseBackup{
 public function __construct(private Config $config){}
 private function execute(string $executable,array $args,?string $input=null):void{
  if(!is_file($executable))throw new \RuntimeException('Database client executable missing.');
  $dir=$this->config->root.'/var/backups';if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new \RuntimeException('Private backup directory unavailable.');
  $secret=tempnam($dir,'client-');chmod($secret,0600);$quote=static fn($v)=>'"'.strtr((string)$v,['\\'=>'\\\\','"'=>'\\"',"\n"=>'\\n',"\r"=>'\\r']).'"';
  try{file_put_contents($secret,"[client]\nhost=".$quote($this->config->dbHost)."\nport=".$this->config->dbPort."\nuser=".$quote($this->config->dbUser)."\npassword=".$quote($this->config->dbPassword)."\nprotocol=tcp\n");
   $process=proc_open([$executable,'--defaults-extra-file='.$secret,...$args],[0=>$input===null?['pipe','r']:['file',$input,'r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$this->config->root,null,['bypass_shell'=>true]);if(!is_resource($process))throw new \RuntimeException('Cannot run database client.');if(isset($pipes[0]))fclose($pipes[0]);stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);if($exit!==0)throw new \RuntimeException('Database client failed (exit '.$exit.'); no restore/backup success claimed.');
  }finally{if(is_file($secret))unlink($secret);}
 }
 public function backup(string $executable,string $output):string{
  $parent=realpath(dirname($output));$public=realpath($this->config->root.'/public');if($parent===false||str_starts_with(strtolower(str_replace('\\','/',$parent).'/'),strtolower(str_replace('\\','/',$public).'/')))throw new \InvalidArgumentException('Use an existing private directory outside public/.');
  $output=$parent.'/'.basename($output);if(file_exists($output)||is_link($output))throw new \RuntimeException('Refusing to overwrite a backup.');(new PdoFactory($this->config))->connection();$handle=fopen($output,'x');if($handle===false)throw new \RuntimeException('Cannot exclusively create backup.');chmod($output,0600);fclose($handle);
  try{$this->execute($executable,['--single-transaction','--quick','--skip-lock-tables','--no-tablespaces','--hex-blob','--default-character-set=utf8mb4','--result-file='.$output,$this->config->dbName]);if(!is_file($output)||filesize($output)===0)throw new \RuntimeException('Backup empty.');chmod($output,0600);return hash_file('sha256',$output);}catch(\Throwable $e){if(is_file($output))unlink($output);throw $e;}
 }
 public function restoreEmpty(string $executable,string $input):void{
  $pdo=(new PdoFactory($this->config))->connection();$q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');$q->execute([$this->config->dbName]);if((int)$q->fetchColumn()!==0)throw new \RuntimeException('Restore requires a separate empty target; existing data is preserved.');if(!is_file($input)||!is_readable($input))throw new \RuntimeException('Backup input missing.');
  $this->execute($executable,['--default-character-set=utf8mb4',$this->config->dbName],$input);
 }
}
