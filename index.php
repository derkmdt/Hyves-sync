<?php
require_once 'picasa/Picasa_Wrapper.php';

$media = new Picasa_Wrapper();
if (!$media->log_in())
{
    echo "You are being redirected to google...";
}

$user_albums = $media->get_user_albums();
if (!isset($_GET['album']) || !isset($user_albums[$_GET['album']]))
{
    echo "<ul>";
    foreach ($user_albums as $id=>$album)
    {
        echo "<li><a href='?album=$id'>".$album->get_title()."</a></li>";
    }
    echo "</ul>";
    exit;
}

$album = $user_albums[$_GET['album']];
$photos = $album->get_photos();
if (!isset($_GET['photo']) || !isset($photos[$_GET['photo']]))
{
    echo "<a href='?'>Viewing ".$album->get_title()."; go back to index.</a><br/><br/>";
    foreach ($photos as $id=>$photo)
    {
        echo "<hr/>";
        echo "<a href='?album=".$_GET['album']."&photo=$id'><img src='".$photo->get_default_thumb_url()."'/></a><br/>";
    }
    exit;
}

$photo = $photos[$_GET['photo']];

?>
<a href='?'>Index</a> &gt; 
<a href='?album=<?= $_GET['album'] ?>'><?= $album->get_title() ?></a> &gt;
Viewing photo <?= $_GET['photo'] ?><br/>
<hr/>
<table>
<tr>
<td><img src='<?= $photo->get_default_thumb_url() ?>'/></td>
<td>
<?= $photo->get_title() ?>
<hr/>
<a href="<?= $photo->get_default_link() ?>">Goto source</a><br/>
The picture has <?= $photo->get_comment_count() ?> comments
    (comments are <?= $photo->get_commenting_enabled()? 'enabled' : 'disabled' ?>)<br/>
The original picture is <?= $photo->get_original_width() ?>x<?= $photo->get_original_height() ?>
    and has a size of <?= $photo->get_original_size_bytes() ?> bytes.
    It was taken at geolocation <?= $photo->get_geo_location() ?>.
</td>
</tr>
<? if ($photo->get_comment_count() > 0) { ?>
    <tr><td colspan="2">
    <? foreach($photo->get_comments() as $comment) { ?>
        <hr/>
        <?= $comment ?>
    <? } ?>
    </td></tr>
<? } ?>
</table>

