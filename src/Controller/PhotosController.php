<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
require("../public/apiKey.php");

class PhotosController extends AbstractController
{
    

    /**
     * @Route("/", name="index")
     */
    public function index() {
        return $this->render('photos/index.html.twig', [
            'active' => 'index'
        ]);
    }
    
    /**
     * @Route("/photos", name="photos")
     */
    public function listPhotos(SerializerInterface $serial)
    {
        $photosRaw = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=".apiKey."&user_id=164696274@N08&format=json&nojsoncallback=1");
        $photosDecoded = $serial->decode($photosRaw, 'json');
        foreach ($photosDecoded["photos"]["photo"] as $photo) {
            $photos = null;
            $photos["photo"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
            $photos["id"] = $photo["id"];
            $allPhotos[] = $photos;
        }
        // dump($allPhotos);
        // die();

        // flickr.photos.getFavorites

        return $this->render('photos/photos.html.twig', [
            'photos' => $allPhotos,
            'active' => 'list'
        ]);
    }

    /**
     * @Route("/photo/{id}", name="photoShow")
     */
    public function showPhoto($id, SerializerInterface $serial){
        $photosInfoRaw = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $photoInfo = $serial->decode($photosInfoRaw, 'json');
        $photo = "https://farm".$photoInfo["photo"]["farm"].".staticflickr.com/".$photoInfo["photo"]["server"]."/".$photoInfo["photo"]["id"]."_".$photoInfo["photo"]["originalsecret"]."_o.".$photoInfo["photo"]["originalformat"];
        $avatar = "http://farm".$photoInfo["photo"]["owner"]["iconfarm"].".staticflickr.com/".$photoInfo["photo"]["owner"]["iconserver"]."/buddyicons/".$photoInfo["photo"]["owner"]["nsid"].".jpg";
        $faves = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getFavorites&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $faves = $serial->decode($faves, 'json');
        $faves = count($faves["photo"]["total"]);
        $comments = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.comments.getList&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $comments = $serial->decode($comments, 'json');
        if (!isset($comments["comments"]["comment"]))
            $comments["comments"]["comment"] = '';

        return $this->render('photos/photo.html.twig', [
            'photo' => $photo,
            'photoInfo' => $photoInfo,
            'avatar' => $avatar,
            'faves' => $faves,
            'comments' => $comments,
            'active' => ''
        ]);
    }
}
