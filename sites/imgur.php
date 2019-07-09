<?Php

namespace datagutten\image_host;

class imgur extends image_host
{
    function __construct()
    {
		parent::__construct();
		require 'imgur/config.php';
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Authorization: Client-Id '.$api_key));
    }
	
	public function upload($file)
	{
		if(empty($file))
			return false;
		$md5=md5_file($file);
		$dupecheck_result=$this->dupecheck($md5);
		if($dupecheck_result!==false)
			$data=$dupecheck_result;
		else
		{
			$data=$this->send_upload($file);
			if($data!==false)
				$this->dupecheck_write($data,$md5);
		}
		return $data['link'];
	}

    /**
     * Send upload to imgur
     * @param $file
     * @return bool
     * @throws \Exception
     */
    private function send_upload($file)
    {
		$json=$this->request("https://api.imgur.com/3/upload","POST",array('image'=>new \curlfile($file)));
		$data=json_decode($json,true);

		if($data['status']!=200)
		{
			$this->error="Feil under opplasting: ".$data['data']['error']."\n";
			return false;
		}

		return $data['data'];
    }
	public function thumbnail($link,$size='t') //http://api.imgur.com/models/image
	{
		$pathinfo=pathinfo($link);
		return str_replace('.'.$pathinfo['extension'],$size.'.'.$pathinfo['extension'],$link); //Lag link til thumbnail
	}
	function bbcode($link)
	{
		return sprintf('[url=%s][img]%s[/img][/url]',$link,$this->thumbnail($link));
	}
}