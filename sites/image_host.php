<?Php

namespace datagutten\image_host\sites;

use datagutten\image_host\exceptions\UploadFailed;
use datagutten\tools\files\files;
use InvalidArgumentException;
use RuntimeException;
use WpOrg\Requests;
use WpOrg\Requests\Transport\Curl;

abstract class image_host
{
    public static bool $config_required = false;
	public string $md5_folder;
    public static string $base_url;

    /**
     * @var string Site name
     */
	public $site;

    /**
     * @var Requests\Session
     */
	public $session;

    /**
     * @var array Configuration parameters
     */
    protected $config = [];
    /**
     * @var array Post data to be used in HTTP request
     */
    private $post_fields;
    public static string $response_format = 'json';

    function __construct(array $config = [])
    {
		$this->site= substr(strrchr(static::class, "\\"), 1);
        if (static::$config_required)
        {
            if (empty($config[$this->site]))
                throw new RuntimeException(sprintf('Missing configuration for %s', $this->site));
            $this->config = $config[$this->site];
        }

        $this->md5_folder = files::path_join($config['dedupe_path'] ?? __DIR__, $this->site, 'uploads_md5');

		if(!file_exists($this->md5_folder))
			mkdir($this->md5_folder, 0777, true);
        $options = [];
		$this->session = new Requests\Session(static::$base_url ?? null, [], [], $options);
    }

    public function md5_file($md5, $extension=null): string
    {
        $file = files::path_join($this->md5_folder, $md5);
        $extension = $extension ?? static::$response_format ?? null;
        if (file_exists($file) || empty($extension))
            return $file;
        else
            return files::path_join($this->md5_folder, $md5 . '.' . $extension);
    }

    public function load_dedup($md5): array
    {
        $file = $this->md5_file($md5, 'json');
        if (!file_exists($file))
            return [];
        if (static::$response_format == 'json')
            return json_decode(file_get_contents($file));
        else
            throw new RuntimeException(sprintf('Loading of %s not implemented', static::$response_format));
    }

    public function multipart_hook($ch)
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_fields);
    }

    /**
     * Send HTTP request
     * @param string $url
     * @param string $method Request HTTP method
     * @param array|string|null $post_fields
     * @param bool $return_body Return the response body instead of the response object
     * @return string|Requests\Response
     * @throws Requests\Exception
     * @throws Requests\Exception\Http
     * @deprecated
     */
    public function request(string $url, string $method = 'GET', $post_fields = null, bool $return_body = True)
    {
        if (!empty($post_fields) || $method == 'POST')
            $response = $this->post_multipart($url, $post_fields);
        else
            $response = Requests\Requests::get($url);
        if ($return_body)
        {
            $response->throw_for_status();
            return $response->body;
        }
        else
            return $response;
    }

    /**
     * Send an HTTP POST request with multipart/form-data
     * @param string $url
     * @param array|string|null $post_fields
     * @return string|Requests\Response
     * @throws Requests\Exception
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function post_multipart(string $url, $post_fields): Requests\Response
    {
        $hooks = new Requests\Hooks();
        $hooks->register('curl.before_send', [$this, 'multipart_hook']);
        $this->post_fields = $post_fields;
        return $this->session->post($url, array('Content-Type' => 'multipart/form-data'), $post_fields, [
            'transport' => Curl::class,
            'hooks' => $hooks
        ]);
    }

    /**
     * Check if the file already is uploaded
     * @param string $md5 MD5 hash
     * @return bool|array Return false if not found or array with saved information
     * @deprecated
     */
	public function dupecheck(string $md5): array
	{
		$md5_file=$this->md5_file($md5);
        return $this->load_dedup($md5);

/*		if(file_exists($md5_file) && is_file($md5_file)) //Sjekk om filen allerede er lastet opp
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
			return false;*/
	}

    /**
     * Write duplicate check file with information about the uploaded image
     * @param array $data Array with data to be saved
     * @param string $md5 MD5 hash of the image
     */
	public function dupecheck_write(array $data, string $md5)
	{
        $md5_file = $this->md5_file($md5);
		file_put_contents($md5_file,json_encode($data));
	}

    /**
     * Internal method to send the image to the host
     * @param string $file Path to the file to upload
     * @return array
     */
    abstract protected function send_upload(string $file): array;

    /**
     * Get URL to full size image
     * @param array $data Data from dedupe JSON file
     * @return string
     */
    abstract public static function image_url(array $data): string;

    /**
     * Upload image with deduplication check
     * @param string $file File to upload
     * @return string Link to uploaded file
     * @throws UploadFailed
     */
    public function upload(string $file): string
    {
        if(empty($file) || !file_exists($file))
            throw new InvalidArgumentException(sprintf('File not found or empty: "%s"', $file));
        $md5=md5_file($file);
        $dupecheck_result = $this->load_dedup($md5);
        if (!empty($dupecheck_result))
            return static::image_url($dupecheck_result);
        else
        {
            $data = $this->send_upload($file);
            $this->dupecheck_write($data, $md5);
            return static::image_url($data);
        }
    }

    function bbcode($link)
    {
        return sprintf('[url=%s][img]%s[/img][/url]',$link,$this->thumbnail($link));
    }
}
