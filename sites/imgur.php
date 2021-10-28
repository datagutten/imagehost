<?Php

namespace datagutten\image_host;

use CURLFile;
use datagutten\image_host\exceptions\UploadFailed;
use InvalidArgumentException;
use Requests_Exception;
use Requests_Response;

class imgur extends image_host
{
    public static $config_required = true;
    function __construct($config)
    {
        parent::__construct($config);
        $this->session->headers = ['Authorization'=>'Client-Id '.$this->config['api_key']];
    }
	
	public function upload(string $file)
	{
        if(empty($file) || !file_exists($file))
            throw new InvalidArgumentException(sprintf('File not found: "%s"', $file));
		$md5=md5_file($file);
		$dupecheck_result=$this->dupecheck($md5);
		if($dupecheck_result!==false)
			return $dupecheck_result['link'];
		else
		{
		    try {
                $json = $this->request("https://api.imgur.com/3/upload","POST",array('image'=>new CURLFile($file)));
                $data = json_decode($json, true);
                $this->dupecheck_write($data, $md5);
                return $data['link'];
            }
           catch (Requests_Exception $e)
           {
                $response = $e->getData();
                $data = json_decode($response->body, true);
                throw new UploadFailed($data['data']['error'], 0, $e);
            }
		}
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

    /**
     * Delete an image
     * @param $delete_hash
     * @throws Requests_Exception
     * @return Requests_Response
    */
	function delete($delete_hash)
    {
        $response = $this->session->delete('https://api.imgur.com/3/image/'.$delete_hash);
        $response->throw_for_status();
        return $response;
    }
}