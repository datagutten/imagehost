<?Php
class imagehost
{
	protected $ch;
	public $md5_folder;
	public $error;
    function __construct()
    {
		$this->site=static::class;
		$this->ch = curl_init();
        //curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		$this->md5_folder=sprintf('%s/%s/uploads_md5',__DIR__,static::class);
		if(!file_exists($this->md5_folder))
			mkdir($this->md5_folder);
    }

	public function request($url,$type="GET",$postfields=false)
    {
        if ($postfields!==false)
		{
            curl_setopt($this->ch,CURLOPT_POST,true);
			curl_setopt($this->ch,CURLOPT_POSTFIELDS, $postfields);
		}
		else
			curl_setopt($this->ch,CURLOPT_HTTPGET,true);
		
		curl_setopt($this->ch, CURLOPT_URL, $url);
        
		if (($data = curl_exec($this->ch))===false)
            throw new Exception(curl_error($this->ch));
		return $data;
    }
	public function dupecheck($md5)
	{
		$md5_file=$this->md5_folder.'/'.$md5;
		if(file_exists($md5_file)) //Sjekk om filen allerede er lastet opp
			return json_decode(file_get_contents($md5_file),true); //Returner lagrede opplysninger
		else
			return false;
	}
	public function dupecheck_write($data,$md5)
	{	
		$md5_file=$this->md5_folder.'/'.$md5;
		file_put_contents($md5_file,json_encode($data));
	}
}
