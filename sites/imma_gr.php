<?php

namespace datagutten\image_host;

use curlfile;
use InvalidArgumentException;

class imma_gr extends image_host
{
	public $ch;
	public function __construct()
	{
		parent::__construct();
	}
	private function send_upload($file)
	{
		$postdata=array('userfile'=>new curlfile($file));
		return $this->request('https://imma.gr/upload.php','POST',$postdata);
	}

	public function upload($file)
	{
        if(empty($file) || !file_exists($file))
            throw new InvalidArgumentException(sprintf('File not found: "%s"', $file));
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