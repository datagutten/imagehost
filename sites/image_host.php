<?Php

namespace datagutten\image_host;

use datagutten\image_host\exceptions\UploadFailed;
use Requests;
use Requests_Exception;
use Requests_Exception_HTTP;
use Requests_Session;

abstract class image_host
{
	public $md5_folder;
	public $error;
    /**
     * @var string Site name
     */
	public $site;

    /**
     * @var Requests_Session
     */
	public $session;
    function __construct()
    {
		$this->site= substr(strrchr(static::class, "\\"), 1);
		$this->md5_folder=sprintf('%s/%s/uploads_md5',__DIR__,$this->site);
		if(!file_exists($this->md5_folder))
			mkdir($this->md5_folder, 0777, true);
        $options = [];
		$this->session = new Requests_Session(null, [], [], $options);
    }

    /**
     * @param $url
     * @param string $method Request HTTP method
     * @param array $post_fields
     * @return string
     * @throws Requests_Exception
     * @throws Requests_Exception_HTTP
     */
	public function request($url, $method='GET', $post_fields=null)
    {
        if(!empty($post_fields) || $method=='POST')
		{
		    if(is_array($post_fields))
		        $response = $this->session->post($url, array('Content-Type'=>'multipart/form-data'), $post_fields, ['transport'=>'Requests_Transport_cURL']);
            else
                $response = $this->session->post($url, [], $post_fields);
		}
		else
            $response = Requests::get($url);

		$response->throw_for_status();

		return $response->body;
    }

    /**
     * Check if the file already is uploaded
     * @param $md5
     * @return bool|array Return false if not found or array with saved information
     */
	public function dupecheck($md5)
	{
		$md5_file=$this->md5_folder.'/'.$md5;
		if(file_exists($md5_file) && is_file($md5_file)) //Sjekk om filen allerede er lastet opp
		{
			$data=file_get_contents($md5_file);
			if(empty($data)) //Empty file
			{
				unlink($md5_file);
				return false;
			}
			$info=json_decode($data,true);
			if(!is_array($info))
			{
				rename($md5_file,$md5_file.'_bad');
				return false;
			}
			return $info;
		}
		else
			return false;
	}

    /**
     * Write duplicate check file with information about the uploaded image
     * @param array $data Array with data to be saved
     * @param string $md5 MD5 hash of the image
     */
	public function dupecheck_write($data,$md5)
	{	
		$md5_file=$this->md5_folder.'/'.$md5;
		file_put_contents($md5_file,json_encode($data));
	}

    /**
     * Upload image
     * @param string $file
     * @return string Link to uploaded file
     * @throws UploadFailed
     */
	abstract function upload($file);

}
