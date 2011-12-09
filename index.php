<?php
require_once 'picasa/Picasa_Wrapper.php';

$media = new Picasa_Fetcher_Wrapper();
if (!$media->log_in('http://localhost/'))
{
    echo "You are being redirected to google...";
}

$user_albums = $media->get_user_albums();
$album = NULL;
$photo = NULL;
if (isset($_GET['album']) && isset($user_albums[$_GET['album']]))
{
    $album = $user_albums[$_GET['album']];

    $photos = $album->get_photos();
    if (isset($_GET['photo']) && isset($photos[$_GET['photo']]))
    {
        $photo = $photos[$_GET['photo']];
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>M Sync</title>
<link rel="stylesheet" href="theme/style.css">
</head>

<body>

<ul class="sandbar">
        <li id="logo"><a href="https://code.google.com/p/godmin/" target="_blank">
                <img src="theme/godmin.png" height="33" width="100" border="0" />
                </a>
        </li>


        <li><a href="index.php">Home</a></li>
        <? if ($album != NULL) { ?>
            <li><a href='?album=<?= $_GET['album'] ?>'><?= $album->get_title() ?></a></li>
        <? } ?>
        <? if ($photo != NULL) { ?>
            <li><a>Photo <?= $_GET['photo'] ?></a></li>
        <? } ?>
</ul>

<div id="content">
<?

if ($photo != NULL) {
    if (isset($_GET['upload'])) {
        uploadPhoto($user_albums, $album, $photo, $_GET['upload']);
    }

    showPhotoDetails($user_albums, $album, $photo);
} else if ($album != NULL) {
    if (isset($_GET['upload'])) {
        uploadAlbum($user_albums, $album, $_GET['upload']);
    }
    showAlbumDetails($user_albums, $album);

} else {
    showAlbums($user_albums);
}
?>
</div>
</body>
</html>


<?
function showAlbums($user_albums)
{
    echo "<ul>";
    foreach ($user_albums as $id=>$album)
    {
        echo "<li><a href='?album=$id'>
                <img src='theme/picasa_logo.jpg' height='10px'>".$album->get_title()."
              </a></li>";
    }
    echo "</ul>";
}


function showAlbumDetails($user_albums, $album)
{
    ?>
    <div align='right'>
    <table>
    <tr><td colspan="1">Share this photo</td></tr>
    <tr>
        <td>
        <a href='?album=<?=$_GET['album']?>&upload=hyves'>
                <img src='/theme/hyves_logo.jpg' height='50px'>
            </a>
        </td>
    </tr>
    </table>
    </div>

    <h1>Viewing album <?= $album->get_title() ?></h1><br/>
    <?
    foreach ($album->get_photos() as $id=>$photo)
    {
        echo "<hr/>";
        echo "<a href='?album=".$_GET['album']."&photo=$id'><img src='".$photo->get_default_thumb_url()."'/></a><br/>";
    }
}


function showPhotoDetails($user_albums, $album, $photo)
{
    ?>
    <div align='right'>
    <table>
    <tr><td colspan="1">Share this album</td></tr>
    <tr>
        <td>
        <a href='?album=<?=$_GET['album']?>&photo=<?=$_GET['photo']?>&upload=hyves'>
                <img src='/theme/hyves_logo.jpg' height='50px'>
            </a>
        </td>
    </tr>
    </table>
    </div>

    <div class="alert"><center>
    <img src='<?= $photo->get_default_thumb_url() ?>'/><br/>
    <?= $photo->get_title() ?>
    </center></div>
    <hr/>
    <ul>
        <li><a href="<?= $photo->get_default_link() ?>">Goto source</a></li>
        <li>The picture has <?= $photo->get_comment_count() ?> comments
            (comments are <?= $photo->get_commenting_enabled()? 'enabled' : 'disabled' ?>)</li>
        <li>The original picture is <?= $photo->get_original_width() ?>x<?= $photo->get_original_height() ?>
            and has a size of <?= $photo->get_original_size_bytes() ?> bytes.</li>
        <li>It was taken at geolocation <?= $photo->get_geo_location() ?>.</li>
    </ul>
    <?
     if ($photo->get_comment_count() > 0) {
        foreach($photo->get_comments() as $comment) {
            ?>
            <hr/>
            <?= $comment ?>
            <?
        }
    }
} 


function uploadPhoto($user_albums, $album, $photo, $service)
{
    require_once 'hyves/Hyves_Uploader_Wrapper.php';
    
    $consumerKey = 'MTAyMThf39vqLmyf-t2LVswBCYWfhg==';
    $consumerSecret = 'MTAyMThfV_7flEzhupNx8JDGX1ZpWQ==';
    $next_url = 'http://localhost/?album='.$_GET['album']."&photo=".$_GET['photo'].'&upload=hyves&confirm';
    $media = new Hyves_Media_Uploader_Wrapper($consumerKey, $consumerSecret);
    $media->log_in($next_url);
    $id = $media->upload($photo);
    echo "Uploaded photo $id";
}

function uploadAlbum($user_albums, $album)
{
    require_once 'hyves/Hyves_Uploader_Wrapper.php';

    $consumerKey = 'MTAyMThf39vqLmyf-t2LVswBCYWfhg==';
    $consumerSecret = 'MTAyMThfV_7flEzhupNx8JDGX1ZpWQ==';
    $next_url = 'http://localhost/?album='.$_GET['album']."&photo=".$_GET['photo'].'&upload=hyves&confirm';
    $media = new Hyves_Media_Uploader_Wrapper($consumerKey, $consumerSecret);
    $media->log_in($next_url);

    echo "<h1>Uploading album...</h1>";
    echo "<ul>";
    $url = $media->upload_album($album);
    echo "</ul>";
    echo "<a href='$url' target='blank'>Done! See it on Hyves</a>";
}

?>


