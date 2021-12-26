<?php

namespace datagutten\image_host;

use curlfile;
use datagutten\image_host\exceptions\UploadFailed;
use InvalidArgumentException;
use WpOrg\Requests;

class imma_gr extends image_host
{
	public $ch;
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Send the upload to imma.gr
	 * @param string $file Path to image file
	 * @return string
	 * @throws UploadFailed
	 */
	private function send_upload(string $file): string
	{
		$postdata=array('userfile'=>new curlfile($file));
		try
		{
			return $this->request('https://imma.gr/upload.php', 'POST', $postdata);
		}
		catch (Requests\Exception $e)
		{
			throw new UploadFailed('Upload failed: '.$e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Upload an image to imma.gr
	 * @param string $file Path to image file
	 * @return string
	 * @throws UploadFailed
	 */
	public function upload(string $file): string
	{
        if(empty($file) || !file_exists($file))
            throw new InvalidArgumentException(sprintf('File not found: "%s"', $file));
		$md5=md5_file($file);
		$dupecheck_result=$this->dupecheck($md5);
		if($dupecheck_result!==false)
			$data=$dupecheck_result;
		else
		{
			$data_raw = $this->send_upload($file);
			$data = json_decode($data_raw, true);
			if (!empty($data['error']))
				throw new UploadFailed($data['error']);
			if(!is_array($data))
				throw new UploadFailed('Invalid response from imma.gr');

			$this->dupecheck_write($data, $md5);
		}

		return sprintf('https://imma.gr/%s',$data['msg']);
	}
	function thumbnail($link)
	{
		throw new InvalidArgumentException('imma.gr does not provide thumbnails');
	}
	function bbcode($link)
	{
		return sprintf('[img]%s[/img]',$link);
	}
}