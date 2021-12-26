<?php

namespace datagutten\image_host;

use curlfile;
use datagutten\image_host\exceptions\UploadFailed;
use InvalidArgumentException;
use WpOrg\Requests;

class cubeupload extends image_host
{
	public $is_logged_in = false;
	public static $config_required = true;

    /**
     * Log in to the site
     * @throws Requests\Exception
     * @return string
     */
	function login()
    {
        $data = sprintf('cube_username=%s&cube_password=%s&login=Login',
            $this->config['username'], $this->config['password']);
        $this->is_logged_in = true;
        //TODO: Verify login with 302
        return $this->request('https://cubeupload.com/login', 'POST', $data);
    }

    /**
     * @param $file
     * @throws Requests\Exception
     * @return string
     */
	private function send_upload($file): string
	{
	    if(!$this->is_logged_in)
	        $this->login();
		echo "Sending upload\n";
		$pathinfo=pathinfo($file);
		$postdata=array('name'=>$pathinfo['basename'],'userHash'=>'false','userID'=>'false','fileinput[0]'=>new curlfile($file));
		return $this->request('https://cubeupload.com/upload_json.php','POST',$postdata);
	}

    /**
	 * Upload image to cubeupload.com
     * @param string $file Path to image file
     * @return string Uploaded file
     * @throws UploadFailed
     */
	public function upload(string $file): string
	{
        if(empty($file) || !file_exists($file))
            throw new InvalidArgumentException(sprintf('File not found: "%s"', $file));
		$md5=md5_file($file);
		$dupecheck_result=$this->dupecheck($md5);
		if($dupecheck_result!==false)
			$info=$dupecheck_result;
		else
		{
		    try {
                $data = $this->send_upload($file);
            }
            catch (Requests\Exception $e)
            {
                throw new UploadFailed($e->getMessage(), 0, $e);
            }

			$info=json_decode($data,true);

			if(!is_array($info))
				throw new UploadFailed('cubeupload returned string: '.$data);
			elseif(!isset($info['status']) || $info['status']!=='success')
				throw new UploadFailed($info['error_text']);

			$this->dupecheck_write($info,$md5);
		}

		if(!empty($info))
			return sprintf('https://u.cubeupload.com/%s/%s',$info['user_name'],$info['file_name']);
		else
			throw new UploadFailed('Unknown error, check cache file '.$md5);
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