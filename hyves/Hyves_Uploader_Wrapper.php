<?php
require_once 'config.php';
require_once 'MediaMonks/Service/Hyves.php';
require_once 'MediaMonks/Service/Hyves/Authorization.php';
require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Session.php';

class Hyves_Media_Fetcher_Wrapper
{
    public function log_in($next_action) {}
    public function get_user_albums() {}
}

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

    function upload_album(Album $album, $upload_callback=NULL)
    {
        assert($this->hyves != NULL);

        $opts = array(
            'title'=>$album->get_title(),
            'visibility'=>'public',
            'printability'=>'public'
        );

        $x = $this->hyves->call('albums.create', array('params'=>$opts));
        $album_info = $x->parse()->getBody();
        $album_info = $album_info['album'][0];
     
        foreach ($album->get_photos() as $photo)
        {
            $mediaid = $this->upload($photo);

            $opts = array(
                'visibility'=>'public',
                'printability'=>'public',
                'albumid' => $album_info['albumid'],
                'mediaid' => $mediaid,
            );

            $this->hyves->call('albums.addMedia', array('params'=>$opts));

            // Allow the caller to output a confirmation
            if ($upload_callback) $upload_callback($photo);
        }

        return $album_info['url'];
    }

    function upload(Photo $photo)
    {
        assert($this->hyves != NULL);

        $img = file_get_contents($photo->get_default_thumb_url());
        $file = tempnam('', 'msync_temp_photo');
        file_put_contents($file, $img);

        $opts = array(
                'title' => (($photo->get_title() == "")? 'MSync photo' : $photo->get_title()),
                'description' => $photo->get_upload_description()
            );
        $uploadToken = $this->hyves->uploadFile($file, $opts);

        // poll upload status
        while(true) {
                sleep(2);
                $status = $this->hyves->getUploadStatus($uploadToken['ip'], $uploadToken['token']);
                if($status->isDone($uploadToken['token'])) {
                        $mediaId = $status->getMediaId($uploadToken['token']);
                        break;
                }
        }

        return $mediaId;
    }
}
?>
