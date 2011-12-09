<?
require_once 'model/API.php';
require_once 'model/Album.php';
require_once 'model/Photo.php';

set_include_path('/home/nico/hackathon/ZendGdata-1.11.11/library');
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_Photos_AlbumQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_PhotoQuery');

class Picasa_Format_Adapter
{
    public static function get_comments_for_photo(
                            Zend_Gdata_Photos_PhotoFeed $feed,
                            Picasa_Wrapper $source)
    {
        $comments = array();
        foreach ($feed as $entry)
        {
            if (! $entry instanceof Zend_Gdata_Photos_CommentEntry) continue;
            $content = $entry->getContent()->getText();
            $id = $entry->getGphotoId()->getText();
            $comments[$id] = $content;
        }

        return $comments;
    }

    public static function get_photos_from_external(
                            Zend_Gdata_Photos_AlbumFeed $albumFeed,
                            $album_id,
                            Picasa_Wrapper $source)
    {
        $photos = array();
        foreach ($albumFeed as $entry) {
            // This shouldn't make sense, but the samples have it...
            if (! $entry instanceof Zend_Gdata_Photos_PhotoEntry) continue;

            $p = Picasa_Format_Adapter::get_photo_overview_from_external($entry, $album_id, $source);
            array_push($photos, $p);
        }

        return $photos;
    }

    public static function get_photo_overview_from_external(
                                Zend_Gdata_Photos_PhotoEntry $photo,
                                $album_id,
                                Picasa_Wrapper $source)
    {
        $external_photo_id = $photo->getGphotoId();
        $original_filename = $photo->getTitle();
        $title = $photo->getSummary();

        $thumbs = array();
        foreach($photo->getMediaGroup()->getThumbnail() as $t) array_push($thumbs, $t->getUrl());
        $default_thumb = (count($thumbs) > 1? $thumbs[count($thumbs)-1] : NULL);

        $links = array();
        foreach($photo->getLink() as $link) array_push($links, $link->getHref());
        $link_to_source = (count($links) > 1? $links[1] : NULL);
 
        // Calling $photo->getExifTags() yields a lot of interesting
        // info on the picture. TODO: What can we get from there?

        $geo = NULL;
        if ($photo->getGeoRssWhere() && $photo->getGeoRssWhere()->getPoint()
            && $photo->getGeoRssWhere()->getPoint()->getPos())
        {
            $geo = $photo->getGeoRssWhere()->getPoint()->getPos()->getText();
        }

        return new Photo(
                new Picasa_External_Photo_Id($external_photo_id, $album_id, $source),
                $default_thumb,
                $link_to_source,
                $links,
                $title,
                $photo->getUpdated(),
                $photo->getGphotoCommentCount()->getText(),
                $photo->getGphotoCommentingEnabled(),
                $original_filename,
                $photo->getGphotoHeight(),
                $photo->getGphotoWidth(),
                $photo->getGphotoSize(),
                $geo
            );
    }


    public static function album_list_from_external(
                                Zend_Gdata_Photos_UserFeed $feed,
                                Picasa_Wrapper $source)
    {
        $albums = array();
        foreach($feed as $f)
        {
            $a = Picasa_Format_Adapter::get_album_from_external($f, $source);
            array_push($albums, $a);
        }
        return $albums;
    }

    public static function get_album_from_external(
                                Zend_Gdata_Photos_AlbumEntry $album,
                                Picasa_Wrapper $source)
    {
        $external_album_id = $album->getGphotoId()->getText();

        // We assume the first name is the author
        $author = $album->getAuthor();
        $author = $author[0]->getName()->getText()."\n";

        // TODO
        $categories = array();

        $links = array();
        foreach($album->getLink() as $l) array_push($links, $l->getHref());
        $default_link = (count($links) > 1? $links[1] : NULL);

        return new Album(
                    new Picasa_External_Album_Id($external_album_id, $source),
                    $album->getTitle()->getText(),
                    $album->getPublished(),
                    $album->getSummary(),
                    $album->getGphotoCommentCount(),
                    $album->getGphotoCommentingEnabled(),
                    $author,
                    $categories,
                    $default_link,
                    $links);
    }
}

?>
