<?php

namespace datagutten\image_host;

class cubeupload extends image_host
{
	public $ch;
	public function __construct()
	{
		parent::__construct();
		curl_setopt($this->ch,CURLOPT_COOKIEFILE,'');
	}
	function login($username, $password)
    {
        $this->request('https://cubeupload.com/login','POST',sprintf('cube_username=%s&cube_password=%s&login=Login',$username, $password));
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
			$info=$dupecheck_result;
		else
		{
			$data=$this->send_upload($file);
			if($data!==false)
			{
				$info=json_decode($data,true);
				if(!is_array($info))
				{
					$this->error='cubeupload returned string: '.$data;
					return false;
				}
				elseif($info['status']!=='success')
				{
					$this->error='cubeupload returned error: '.$info['error'];
					return false;
				}
				$this->dupecheck_write($info,$md5);		
			}
		}

		if($info!==false && is_array($info))
			return sprintf('https://u.cubeupload.com/%s/%s',$info['user_name'],$info['file_name']);
		else
		{
			$this->error='Unknown error, check cache file '.$md5;
			return false;
		}
			
	}
	function thumbnail($link)
	{
		return preg_replace('#(http.+cubeupload.com/)(.+)/(.+)$#U','$1$2/t/$3',$link);
	}
	function page_link($link)
	{
		return str_replace('https://u.cubeupload.com','https://cubeupload.com/im',$link);
	}
	function bbcode($link)
	{
		return sprintf('[url=%s][img]%s[/img][/url]',$this->page_link($link),$this->thumbnail($link));
	}
}