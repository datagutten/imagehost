<?php
//require_once '../imagehost.class.php';
require_once __DIR__.'/../imagehost.class.php';
class imma_gr extends imagehost
{
	public $ch;
	public function __construct()
	{
		parent::__construct('imma_gr');
	}
	private function send_upload($file)
	{
		echo "Sending upload\n";
		$pathinfo=pathinfo($file);
		$postdata=array('userfile'=>new curlfile($file));
		return $this->request('https://imma.gr/upload.php','POST',$postdata);
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
				if(!empty($data['error']))
				{
					$this->error=$data['error'];
					return false;
				}
				$this->dupecheck_write($data,$md5);				
			}
		}
		
		if($data!==false)
			return sprintf('https://imma.gr/%s',$data['msg']);
		else
			return false;
	}
	function thumbnail($link)
	{
		$this->error='imma.gr does not provide thumbnails';
		return false;
	}
	function bbcode($link)
	{
		return sprintf('[img]%s[/img]',$link);
	}
}