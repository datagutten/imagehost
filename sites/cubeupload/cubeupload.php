<?php
//require_once '../imagehost.class.php';
require_once __DIR__.'/../imagehost.class.php';
class cubeupload extends imagehost
{
	public $ch;
	public function __construct()
	{
		parent::__construct('cubeupload');
	}
	private function send_upload($file)
	{
		echo "Sending upload\n";
		$pathinfo=pathinfo($file);
		$postdata=array('name'=>$pathinfo['basename'],'userHash'=>'false','userID'=>'false','fileinput[0]'=>new curlfile($file));
		return $this->request('https://cubeupload.com/upload_json.php','POST',$postdata);
		//print_r($postdata);
	}
	public function upload($file)
	{
		$md5=md5_file($file);
		$dupecheck_result=$this->dupecheck($md5);
		if($dupecheck_result!==false)
			$data=$dupecheck_result;
		else
		{
			$data=$this->send_upload($file);
			if($data!==false)
			{
				$data=json_decode($data,true);
				if($data['status']!=='success')
				{
					$this->error=$data['error'];
					return false;
				}
				$this->dupecheck_write($data,$md5);		
			}
		}

		if($data!==false)
			return sprintf('https://i.cubeupload.com/%s',$data['file_name']);
		else
			return false;
	}
	function thumbnail($link)
	{
		return str_replace('https://i.cubeupload.com','https://i.cubeupload.com/t',$link);
	}
	function page_link($link)
	{
		return str_replace('https://i.cubeupload.com','https://cubeupload.com/im',$link);
	}
	function bbcode($link)
	{
		return sprintf('[url=%s][img]%s[/img][/url]',$this->page_link($link),$this->thumbnail($link));
	}
}