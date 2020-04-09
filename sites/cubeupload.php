<?php

namespace datagutten\image_host;

use curlfile;
use datagutten\image_host\exceptions\UploadFailed;
use Requests_Exception;

class cubeupload extends image_host
{
	public $ch;
	public $is_logged_in = false;
	public $config;
	public function __construct()
	{
		parent::__construct();
		curl_setopt($this->ch,CURLOPT_COOKIEFILE,'');
		$this->config = require 'config.php';
	}

    /**
     * Log in to the site
     * @throws Requests_Exception
     * @return string
     */
	function login()
    {
        list($username, $password) = $this->config['imagehost_cubeload_login'];
        $this->is_logged_in = true;
        //TODO: Verify login with 302
        return $this->request('https://cubeupload.com/login','POST',sprintf('cube_username=%s&cube_password=%s&login=Login',$username, $password));
    }

    /**
     * @param $file
     * @throws Requests_Exception
     * @return string
     */
	private function send_upload($file)
	{
	    if(!$this->is_logged_in)
	        $this->login();
		echo "Sending upload\n";
		$pathinfo=pathinfo($file);
		$postdata=array('name'=>$pathinfo['basename'],'userHash'=>'false','userID'=>'false','fileinput[0]'=>new curlfile($file));
		return $this->request('https://cubeupload.com/upload_json.php','POST',$postdata);
		//print_r($postdata);
	}

    /**
     * @param string $file
     * @return string Uploaded file
     * @throws UploadFailed
     */
	public function upload($file)
	{
		$md5=md5_file($file);
		$dupecheck_result=$this->dupecheck($md5);
		if($dupecheck_result!==false)
			$info=$dupecheck_result;
		else
		{
		    try {
                $data = $this->send_upload($file);
            }
            catch (Requests_Exception $e)
            {
                throw new UploadFailed($e->getMessage(), 0, $e);
            }
			if($data!==false)
			{
				$info=json_decode($data,true);
				if(!is_array($info))
				{
                    throw new UploadFailed('cubeupload returned string: '.$data);
				}
				elseif(!isset($info['status']) || $info['status']!=='success')
				{
					throw new UploadFailed($info['error_text']);
				}
				$this->dupecheck_write($info,$md5);		
			}
		}

		if(!empty($info) && $info!==false && is_array($info))
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