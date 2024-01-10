<?php

namespace NeihNat\TiktokDownloader;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class TiktokVideo
{
    private $cookie;
    private $client;
    private $useAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';
    public $tiktokUrl;

    public $data;

    /**
     * @param $cookie
     * @return $this
     */
    public function setCookie($cookie)
    {
        $extension = pathinfo($cookie, PATHINFO_EXTENSION);
        $decodeCookie = $cookie;
        if ($extension === 'txt') {
            $decodeCookie = file_get_contents($cookie);
        }

        $cookieJar = new CookieJar();
        $lines = explode("\n", $decodeCookie);
        $cookieNe = null;
        foreach ($lines as $line) {
            $cookieData = explode("\t", str_replace("\r", '', $line));
            if (count($cookieData) >= 7) {
                $cookieNe[] = [
                    'Domain' => $cookieData[0],
                    'Name' => $cookieData[5],
                    'Value' => $cookieData[6],
                    'Path' => $cookieData[2],
                    'Expires' => intval($cookieData[4]),
                    'Secure' => $cookieData[3] === 'TRUE',
                ];
                $cookie = new SetCookie([
                    'Domain' => $cookieData[0],
                    'Name' => $cookieData[5],
                    'Value' => $cookieData[6],
                    'Path' => $cookieData[2],
                    'Expires' => intval($cookieData[4]),
                    'Secure' => $cookieData[3] === 'TRUE',
                ]);
                $cookieJar->setCookie($cookie);
            }
        }

        $client = new Client(['cookies' => $cookieJar]);
        $this->cookie = $cookieNe;
        $this->client = $client;
        return $this;
    }

    /**
     * @param $tiktokUrl
     * @return $this
     */
    public function setUrl($tiktokUrl)
    {
        $this->tiktokUrl = $tiktokUrl;
        return $this;
    }

    /**
     * @return $this|false
     */
    public function getData()
    {
        if (!$this->tiktokUrl || !$this->cookie) {
            throw new \Exception('Link and cookie can not null');
        }
        $data = $this->decodeTiktok($this->tiktokUrl);

        $dataObject = json_decode(json_encode($data));

        $this->data = $dataObject;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginData()
    {
        $html = $this->client->get($this->tiktokUrl);
        $string = $html->getBody()->getContents();
        $this->data = $string;
        return $string;
    }

    /**
     * @param $link
     * @param $fileName
     * @param $type
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($link, $fileName, $type = 'video')
    {
        if (empty($link) || empty($fileName)) {
            throw new \Exception('Link and file name can not null');
        }
        if (!in_array($type, ['video', 'audio', 'image'])) {
            throw new \Exception('Only accepted data types, video, audio and image');
        }
        $response = $this->client->get($link, [
            'headers' => [
                'authority' => 'v16-webapp-prime.tiktok.com',
                'accept' => '*/*',
                'accept-language' => 'en-US,en;q=0.9',
                'dnt' => '1',
                'origin' => 'https://www.tiktok.com',
                'range' => 'bytes=0-',
                'referer' => 'https://www.tiktok.com/',
                'sec-ch-ua' => '"Google Chrome";v="119", "Chromium";v="119", "Not?A_Brand";v="24"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"macOS"',
                'sec-fetch-dest' => 'video',
                'sec-fetch-mode' => 'cors',
                'sec-fetch-site' => 'same-site',
                'user-agent' => $this->useAgent
            ]
        ]);

        $name = $fileName ?? 'tiktok-downloader';
        if ($type === 'video') {
            $fileName = $name.'.mp4';
        }
        if ($type === 'audio') {
            $fileName = $name.'.mp3';
        }
        if ($type === 'image') {
            $fileName = $name.'.jpg';
        }
        $headers = [
            'Content-Type' => $response->getHeader('Content-Type')[0],
            'Content-Disposition' => 'attachment; filename="'.$fileName.'" ',
        ];
        $file = $response->getBody();
        return \Illuminate\Support\Facades\Response::stream(function () use ($file) {
            echo $file;
        }, 200, $headers);
    }

    /**
     * @param $url
     * @return array
     */
    private function decodeTiktok($url)
    {
        $html = $this->client->get($url);
        $string = $html->getBody()->getContents();

        // Get Auth
        $authUnique = explode('"nickname":"', $string);
        $auth = explode("\"", $authUnique[1])[0];
        $auth = $this->escape_sequence_decode($auth);

        // Get avatar
        $avatarThumb = explode('"avatarThumb":"', $string);
        $avatar = explode("\"", $avatarThumb[1])[0];
        $avatar = $this->escape_sequence_decode($avatar);

        // desc
        $desc = explode('"desc":"', $string);
        $description = explode("\"", $desc[1])[0];
        $description = $this->escape_sequence_decode($description);

        // GET Video
        $check = explode('"playAddr":"', $string);
        $video = explode("\"", $check[1])[0];
        $video = $this->escape_sequence_decode($video);

        // Get mp3
        $mp3String = explode('"playUrl":"', $string);
        $mp3 = explode("\"", $mp3String[1])[0];
        $mp3 = $this->escape_sequence_decode($mp3);


        if ($video == "") {
            // Get images
            $pattern = '/\"images\"\:\[(.*?)\]\,/';
            preg_match_all($pattern, $string, $matches);
            $jsonData = '['.$matches[1][0].']';
            $images = json_decode($this->escape_sequence_decode($jsonData));
            array_map(function ($image) {
                if ($image->imageURL->urlList[0]) {
                    return $image->imageURL->urlList[0];
                }
            }, $images);
        }
        $data = [
            'type' => !empty($images) ? 'image' : 'video',
            'video' => !empty($video) ? $video : null,
            'audio' => !empty($mp3) ? $mp3 : null,
            'thumb' => !empty($avatar) ? $avatar : null,
            'title' => !empty($auth) ? $auth : null,
            'description' => !empty($description) ? $description : null,
            'images' => !empty($images) ? $images : null
        ];
        return $data;
    }

    private function escape_sequence_decode($str)
    {
        $regex = '/\\\u([dD][89abAB][\da-fA-F]{2})\\\u([dD][c-fC-F][\da-fA-F]{2})
              |\\\u([\da-fA-F]{4})/sx';

        return preg_replace_callback($regex, function ($matches) {

            if (isset($matches[3])) {
                $cp = hexdec($matches[3]);
            } else {
                $lead = hexdec($matches[1]);
                $trail = hexdec($matches[2]);
                $cp = ($lead << 10) + $trail + 0x10000 - (0xD800 << 10) - 0xDC00;
            }

            if ($cp > 0xD7FF && 0xE000 > $cp) {
                $cp = 0xFFFD;
            }
            if ($cp < 0x80) {
                return chr($cp);
            } else {
                if ($cp < 0xA0) {
                    return chr(0xC0 | $cp >> 6).chr(0x80 | $cp & 0x3F);
                }
            }

            return html_entity_decode('&#'.$cp.';');
        }, $str);
    }


}
