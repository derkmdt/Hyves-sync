<?php

require_once 'MediaMonks/Service/Hyves.php';
require_once 'MediaMonks/Service/Hyves/Authorization.php';
require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Session.php';


class Hyves_Media_Uploader_Wrapper extends Media_Uploader_Wrapper
{
    private $api_methods = array('users.getLoggedin', 'albums.getByUser', 
                                'media.getByAlbum', 'albums.create','albums.addMedia',
                                'media.getUploadToken','auth.revokeSelf');

    private $hyves = NULL;
    private $consumerKey;
    private $consumerSecret;

    public function __construct($consumerKey, $consumerSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
    }

    public function log_in($next_action)
    {
        $this->hyves = new MediaMonks_Service_Hyves($this->consumerKey, $this->consumerSecret);
        $hyvesAuth = new MediaMonks_Service_Hyves_Authorization($this->hyves);

        $accessToken = $hyvesAuth->requestUserAuthorization(
                            $next_action,
                            $this->api_methods,
                            new MediaMonks_Service_Hyves_Authorization_Storage_Session(),
                            MediaMonks_Service_Hyves_Authorization::EXPIRATION_TYPE_INFINITE
        );

        $this->hyves->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
        return true;
    }

    function upload(Photo $photo)
    {
        assert($this->hyves != NULL);

        $file = '';
        $uploadToken = $this->hyves->uploadFile($file, array(
                'title' => 'Your title',
                'description' => 'Your description'
        ));

        // poll upload status
        while(true) {
                sleep(2);
                $status = $this->hyves->getUploadStatus($uploadToken['ip'], $uploadToken['token']);
                if($status->isDone($uploadToken['token'])) {
                        $mediaId = $status->getMediaId($uploadToken['token']);
                        break;
                }
        }

        echo $mediaId;
    }
}


$consumerKey = 'MTAyMThf39vqLmyf-t2LVswBCYWfhg==';
$consumerSecret = 'MTAyMThfV_7flEzhupNx8JDGX1ZpWQ==';
$file = '/home/nico/pics/vincent_early.jpg';

$media = new Hyves_Uploader($consumerKey, $consumerSecret);
$media->log_in('localhost/hyves/media_upload.php');
$media->upload($file);

?>
