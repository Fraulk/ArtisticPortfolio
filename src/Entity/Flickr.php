<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Controller\PhotosController;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FlickrRepository")
 */
class Flickr
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $apiKey;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxPhotosPerPage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photoId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photosetId;

    private $serial;

    public function __construct($serial = null, $photoId = null, $photosetId = null) {
        require_once("..\\public\\apiKey.php");
        $this->apiKey = apiKey;
        $this->userId = userId;
        $this->maxPhotosPerPage = PhotosController::maxPhotosPerPage;
        $this->photoId = $photoId;
        $this->photosetId = $photosetId;
        $this->serial = $serial;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // public function getApiKey(): ?string
    // {
    //     return $this->apiKey;
    // }

    // public function setApiKey(string $apiKey): self
    // {
    //     $this->apiKey = $apiKey;

    //     return $this;
    // }

    // public function getUserId(): ?string
    // {
    //     return $this->userId;
    // }

    // public function setUserId(string $userId): self
    // {
    //     $this->userId = $userId;

    //     return $this;
    // }

    public function getMaxPhotosPerPage(): ?int
    {
        return $this->maxPhotosPerPage;
    }

    public function setMaxPhotosPerPage(?int $maxPhotosPerPage): self
    {
        $this->maxPhotosPerPage = $maxPhotosPerPage;

        return $this;
    }

    public function getPhotoId(): ?string
    {
        return $this->photoId;
    }

    public function setPhotoId(?string $photoId): self
    {
        $this->photoId = $photoId;

        return $this;
    }

    public function getPhotosetId(): ?string
    {
        return $this->photosetId;
    }

    public function setPhotosetId(?string $photosetId): self
    {
        $this->photosetId = $photosetId;

        return $this;
    }

    public function getPhotoData($id) {
        $photoInfo = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $photoInfo = $this->serial->decode($photoInfo, 'json');

        $name = (PhotosController::realName) ? $photoInfo["photo"]["owner"]["realname"] : $photoInfo["photo"]["owner"]["username"];

        if(isset($photoInfo["photo"]["originalsecret"]))
            $photo = "https://farm".$photoInfo["photo"]["farm"].".staticflickr.com/".$photoInfo["photo"]["server"]."/".$photoInfo["photo"]["id"]."_".$photoInfo["photo"]["originalsecret"]."_o.".$photoInfo["photo"]["originalformat"];
        else
            $photo = "https://farm".$photoInfo["photo"]["farm"].".staticflickr.com/".$photoInfo["photo"]["server"]."/".$photoInfo["photo"]["id"]."_".$photoInfo["photo"]["secret"]."_b.jpg";

        $avatar = "http://farm".$photoInfo["photo"]["owner"]["iconfarm"].".staticflickr.com/".$photoInfo["photo"]["owner"]["iconserver"]."/buddyicons/".$photoInfo["photo"]["owner"]["nsid"].".jpg";

        $faves = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getFavorites&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $faves = $this->serial->decode($faves, 'json');
        $faves = $faves["photo"]["total"];
        
        $comments = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.comments.getList&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $comments = $this->serial->decode($comments, 'json');

        if (!isset($comments["comments"]["comment"])) {
            $comments["comments"]["comment"] = '';
        }

        $commentCount = $photoInfo["photo"]["comments"]["_content"];
        $plural = $commentCount > 1 ? "comments" : "comment";

        return array($photoInfo, $name, $photo, $avatar, $faves, $comments, $plural);
    }

    public function getPhotosData($page = 1) {
        $photos = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=".$this->apiKey."&user_id=".$this->userId."&per_page=".$this->maxPhotosPerPage."&page=".$page."&extras=views,date_upload&format=json&nojsoncallback=1");
        $photos = $this->serial->decode($photos, 'json');

        return $photos;
    }

    public function getCollections() {
        $collections = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photosets.getList&api_key=".apiKey."&user_id=".userId."&format=json&nojsoncallback=1");
        $collections = $this->serial->decode($collections, 'json');

        foreach ($collections["photosets"]["photoset"] as $key => $col) {
            $collections["photosets"]["photoset"][$key]["link"] = "https://farm".$col["farm"].".staticflickr.com/".$col["server"]."/".$col["primary"]."_".$col["secret"]."_q.jpg";
        }

        return $collections;
    }

    public function getPhotosFromCollection($id) {
        $photos = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=".apiKey."&photoset_id=".$id."&user_id=".userId."&format=json&nojsoncallback=1");
        $photos = $this->serial->decode($photos, 'json');

        foreach ($photos["photoset"]["photo"] as $key => $col) {
            $photos["photoset"]["photo"][$key]["link"] = "https://farm".$col["farm"].".staticflickr.com/".$col["server"]."/".$col["id"]."_".$col["secret"]."_z.jpg";
        }

        return $photos;
    }

    public function filter($filter, $photos) {
        /**
         * filters, usort to sort with an item in a multidimensional array
         */
        switch ($filter) {
            case 'view':
                usort($photos["photos"]["photo"], function ($a, $b)
                {
                    if ($a["views"] == $b["views"]) {
                        return 0;
                    }
                    return ($a["views"] > $b["views"]) ? -1 : 1;
                });
                $activeFilter = "view";
                // dump($photos["photos"]["photo"]);
                // die();
                break;
            
            case 'new':
                usort($photos["photos"]["photo"], function ($a, $b)
                {
                    if ($a["dateupload"] == $b["dateupload"]) {
                        return 0;
                    }
                    return ($a["dateupload"] > $b["dateupload"]) ? -1 : 1;
                });
                $activeFilter = "new";
                break;

            case 'old':
                usort($photos["photos"]["photo"], function ($a, $b)
                {
                    if ($a["dateupload"] == $b["dateupload"]) {
                        return 0;
                    }
                    return ($a["dateupload"] < $b["dateupload"]) ? -1 : 1;
                });
                $activeFilter = "old";
                break;
            
            default:
                # code...
                break;
        }

        return array($photos, $activeFilter);
    }
}
