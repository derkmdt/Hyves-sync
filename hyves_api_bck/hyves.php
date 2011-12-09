<?php
$action = $_GET['action'];

$consumerKey = 'MTAyMThf39vqLmyf-t2LVswBCYWfhg==';
$consumerSecret = 'MTAyMThfV_7flEzhupNx8JDGX1ZpWQ==';
$scriptUrl = 'http://localhost/';

set_include_path('library/' . PATH_SEPARATOR . get_include_path());

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once 'classes/common.php';
require_once 'MediaMonks/Service/Hyves.php';
require_once 'MediaMonks/Service/Hyves/Authorization.php';
require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Session.php';

$options = array();
$options['secureConnection'] = true;
$options['signatureMethod'] = 'PLAINTEXT';

$hyves = new MediaMonks_Service_Hyves($consumerKey, $consumerSecret, $options);
$hyvesAuth = new MediaMonks_Service_Hyves_Authorization($hyves);
$accessToken = $hyvesAuth->requestUserAuthorization(
        $scriptUrl,
        array('users.getLoggedin', 'albums.getByUser', 'media.getByAlbum', 'albums.create','albums.addMedia','media.getUploadToken','auth.revokeSelf'),
        new MediaMonks_Service_Hyves_Authorization_Storage_Session(),
        MediaMonks_Service_Hyves_Authorization::EXPIRATION_TYPE_INFINITE
);
$hyves->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);

if($action == 'deauthorize') {
    $response = $hyves->call('auth.revokeSelf');
}

// call
$response = $hyves->call('users.getLoggedin', array(
        'responsefields' => array('profilepicture'),
        'returnfields' => array(
                '/user/firstname',
                '/user/profilepicture/square_large/*'
        )
));
echo '<html>
<head>
<style>
body {
    font: 11px/18px "lucida grande",tahoma,verdana,arial,sans-serif;
}
#div_images {
    width:80%px;
    font-size: 18px;
    color:#FFFFFF;
}
#div_images .imageh {
    margin:5px;
    padding:5px;
    float:left;
    border:1px solid #000000;
    width:150px;
    height:150px;
    word-wrap: break-word;
}
#div_images .imageh a:hover {
    background-color:#999999;
}
</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
</head>
<body>';

$color = array('263138','406155','7C9C71','DBC297','FF5755');

try {
$albums = array();
$albums = $hyves->call('albums.getByUser', array(
            'params' => array('userid' => $accessToken['userid']),
            'returnfields' => array(
                    '/album/albumid',
                    '/album/title'
            )         
    ));

$iamount = count($albums->album);
$album_image = array();
for($i=0;$i<$iamount;$i++) {
    $album_image[$i] = $hyves->call('media.getByAlbum', array(
            'params' => array('albumid' => $albums->album[$i]['albumid']),
            'returnfields' => array(
                    '/media/square_large/src'
            ),            
            'pagination' => array('resultsperpage' => '1')
    ));
}

    echo '<div id="div_images">';
    for($i=0;$i<$iamount;$i++) {
        echo '<div ';
        if($i%5 == 0) echo 'class="imageh"'; else echo 'class="imageh"';
        echo 'style="background-color:#'.$color[rand(0,4)].'"><div style="background-image:url(\''.$album_image[$i]->media[0]['square_large']['src'].'\');background-size:100% 100%;width:100%; height:100%;">';
        echo $albums->album[$i]['title'].'</div></div>';
    }
    echo '</div>';
    echo "<div style=\"clear:both;\"></div>";    
    $albums->parse();
}
catch(MediaMonks_Service_Hyves_Request_Exception $e) {
        // some error in curl
        echo $e;
}
catch(MediaMonks_Service_Hyves_Response_Exception $e) {
        // some error from api
        echo $e;
}
catch(Exception $e) {
        // some general error
        echo $e;        
}
echo "<input type=\"button\" onClick=\"location.href='".$scriptUrl."index.php?action=deauthorize'\" value=\"deauthorize\">";
echo '</body></html>';