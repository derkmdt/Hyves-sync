<?

require_once 'config.php';
require_once 'model/API.php';
require_once 'picasa/Picasa_Format_Adapter.php';

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_Photos_AlbumQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_PhotoQuery');

class Picasa_Fetcher_Wrapper extends Media_Fetcher_Wrapper
{
    private $google_photos_service = NULL;
    private $client = NULL;

    public function log_in($next_action)
    {
        global $_SESSION, $_GET;

        if (!isset($_SESSION))
        {
            if (!session_start()) throw "Cannot start session";
        }

        if (isset($_SESSION['sessionToken']))
        {
            // We have already logged in, just return a client object
            $this->client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['sessionToken']);
            $this->google_photos_service = 
                    new Zend_Gdata_Photos($this->client, "Google-DevelopersGuide-1.0");
            return $this->client;
        }

        if (isset($_GET['token']))
        {
            // We haven't finished logging in, but we already sent the
            // first auth request
            $_SESSION['sessionToken'] =
                Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);

            return $this->log_in();
        }

        // If we reached this point, this is the first time we are trying to log
        // in, so we need to submit the first request for the loggin token
        $url = $this->get_auth_url($next_action);
        header("Location: $url");
        return NULL;
    }

    private function get_auth_url($next)
    {
        $scope = 'http://picasaweb.google.com/data';
        $secure = false;
        $session = true;
        return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure,
            $session);
    }

    public function get_user_albums()
    {
        assert($this->google_photos_service != NULL);

        $feed = $this->google_photos_service->getUserFeed("default");
        return Picasa_Format_Adapter::album_list_from_external($feed, $this);
    }

    public function get_album($id)
    {
        assert($this->google_photos_service != NULL);

        $query = new Zend_Gdata_Photos_AlbumQuery();
        $query->setUser("default");
        $query->setAlbumId($id);
        $query->setImgMax('d');
         
        try {
            $albumFeed = $this->google_photos_service->getAlbumFeed($query);
            return Picasa_Format_Adapter::get_photos_from_external($albumFeed, $id, $this);
        } catch (Zend_Gdata_App_Exception $e) {
            throw $e;
        }
    }

    public function get_photo_details($photo_id, $album_id)
    {
        $query = new Zend_Gdata_Photos_PhotoQuery();
        $query->setPhotoId($photo_id);
        $query->setAlbumId($album_id);
        $query = $query->getQueryUrl() . "?kind=comment,tag";
        $feed = $this->google_photos_service->getPhotoFeed($query);

        return Picasa_Format_Adapter::get_comments_for_photo($feed, $this);
    }
}

class Picasa_External_Album_Id extends External_Album_Id
{
    private $id;
    private $picasa;

    public function __construct($id, Picasa_Fetcher_Wrapper $picasa) 
    {
        $this->id = $id;
        $this->picasa = $picasa;
    }

    public function get_details()
    {
        return $this->picasa->get_album($this->id);
    }
}

class Picasa_External_Photo_Id extends External_Photo_Id
{
    private $id;
    private $album;
    private $picasa;

    public function __construct($id, $album, Picasa_Fetcher_Wrapper $picasa) 
    {
        $this->id = $id;
        $this->album = $album;
        $this->picasa = $picasa;
    }

    public function get_details()
    {
        return $this->picasa->get_photo_details($this->id, $this->album);
    }
}

?>
