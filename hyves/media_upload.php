<?php
$filelocation = $_GET['filelocation'];
$consumerKey = 'MTAyMThf39vqLmyf-t2LVswBCYWfhg==';
$consumerSecret = 'MTAyMThfV_7flEzhupNx8JDGX1ZpWQ==';

require_once 'MediaMonks/Service/Hyves.php';
require_once 'MediaMonks/Service/Hyves/Authorization.php';
require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Session.php';
$hyves = new MediaMonks_Service_Hyves($consumerKey, $consumerSecret, $options);
$hyvesAuth = new MediaMonks_Service_Hyves_Authorization($hyves);
$accessToken = $hyvesAuth->requestUserAuthorization(
        $scriptUrl,
        array('users.getLoggedin', 'albums.getByUser', 'media.getByAlbum', 'albums.create','albums.addMedia','media.getUploadToken','auth.revokeSelf'),
        new MediaMonks_Service_Hyves_Authorization_Storage_Session(),
        MediaMonks_Service_Hyves_Authorization::EXPIRATION_TYPE_INFINITE
);
$hyves->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
$uploadToken = $hyves->uploadFile($filelocation, array(
        'title' => 'Your title',
        'description' => 'Your description'
));

// poll upload status
while(true) {
        sleep(2);
        $status = $hyves->getUploadStatus($uploadToken['ip'], $uploadToken['token']);
        if($status->isDone($uploadToken['token'])) {
                $mediaId = $status->getMediaId($uploadToken['token']);
                break;
        }
}

echo $mediaId;
?>