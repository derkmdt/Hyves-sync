<?php
require_once 'picasa/Picasa_Wrapper.php';

$media = new Picasa_Wrapper();
if (!$media->log_in())
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
<link rel="stylesheet" href="style.css">
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
    showPhotoDetails($user_albums, $album, $photo);
} else if ($album != NULL) {
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
        echo "<li><a href='?album=$id'>".$album->get_title()."</a></li>";
    }
    echo "</ul>";
}


function showAlbumDetails($user_albums, $album)
{
    echo "<h1>Viewing album ".$album->get_title()."</h1><br/>";
    foreach ($album->get_photos() as $id=>$photo)
    {
        echo "<hr/>";
        echo "<a href='?album=".$_GET['album']."&photo=$id'><img src='".$photo->get_default_thumb_url()."'/></a><br/>";
    }
}


function showPhotoDetails($user_albums, $album, $photo)
{
    ?>
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
?>


