<?php

namespace datagutten\image_host\sites;

use curlfile;
use WpOrg\Requests;
use WpOrg\Requests\Response;

class cubeupload extends image_host
{
	public static bool $config_required = true;
    public static string $response_format = 'json';

    /**
     * Log in to the site
     * @throws exceptions\LoginFailed
     */
    function login(): void
    {
        $data = sprintf('cube_username=%s&cube_password=%s&login=Login',
            $this->config['username'], $this->config['password']);
        $response = $this->session->post('https://cubeupload.com/login', data: $data);
        if ($this->is_logged_in($response) === false)
            throw new exceptions\LoginFailed();
    }

    function is_logged_in(Response $response = null): string|bool
    {
        if ($response === null)
            $response = $this->session->get('https://cubeupload.com');

        $body = $response->body;
        if (preg_match('#href="/account".+?title="(.+?)"#', $body, $matches_username))
            return $matches_username[1];
        else
            return false;
    }

    public static function image_url(array $data): string
    {
        return sprintf('https://u.cubeupload.com/%s/%s', $data['user_name'], $data['file_name']);
    }

    protected function send_upload($file): array
    {
        echo "Sending upload\n";
        $pathinfo = pathinfo($file);
        $postdata = array('name' => $pathinfo['basename'], 'userHash' => 'false', 'userID' => 'false', 'fileinput[0]' => new curlfile($file));
        try
        {
            $response = $this->post_multipart('https://cubeupload.com/upload_json.php', $postdata);
            $info = $response->decode_body();
            if (!isset($info['status']) || $info['status'] !== 'success')
                throw new exceptions\UploadFailed($info['error_text']);
            elseif (!$response->success)
                throw new exceptions\UploadFailed('Unknown error');
            return $info;
        }
        catch (Requests\Exception $e)
        {
            throw new exceptions\UploadFailed($e->getMessage(), $e->getCode(), $e);
        }
    }

    function thumbnail($link): string
	{
		return preg_replace('#(http.+cubeupload.com/)(.+)/(.+)$#U','$1$2/t/$3',$link);
	}

    function page_link($link): string
	{
		return str_replace('https://u.cubeupload.com','https://cubeupload.com/im',$link);
	}

    function bbcode($link): string
	{
		return sprintf('[url=%s][img]%s[/img][/url]',$this->page_link($link),$this->thumbnail($link));
	}
}