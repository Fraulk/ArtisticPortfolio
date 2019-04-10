<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PhotosController extends AbstractController
{
    const darkMode = 1;
    const themeColor = "#9C27B0"; // don't forget to change the text color in css if it's too luminous
    const maxPhotosPerPage = 100; // maximum is 500

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
        return $this->render('photos/index.html.twig', [
            'active' => 'index',
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
        ]);
    }
    
    /**
     * @Route("/photos", name="photos")
     */
    public function listPhotos(SerializerInterface $serial)
    {
        require_once("../public/apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
        $photosRaw = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=".apiKey."&user_id=".userId."&per_page=".$this::maxPhotosPerPage."&format=json&nojsoncallback=1");
        $photosDecoded = $serial->decode($photosRaw, 'json');
        //need to clean this asap
        foreach ($photosDecoded["photos"]["photo"] as $photo) {
            $photos = null;
            $photos["photo"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
            $photos["id"] = $photo["id"];
            $allPhotos[] = $photos;
            if (isset($_POST['random'])) {
                if ($_POST['random'] != '' || $_POST['random'] != null) {
                    shuffle($allPhotos);
                }
            }
        }
        
        return $this->render('photos/photos.html.twig', [
            'photos' => $allPhotos,
            'pages' => $photosDecoded["photos"]["pages"],
            'actualPage' => 1,
            'active' => 'list',
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
            ]);
        }
        
        /**
         * @Route("/photos/{id}", name="photosPage")
         */
        public function listPhotosPage(SerializerInterface $serial, $id) {
            require_once("../public/apiKey.php");
            $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
            $photosRaw = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=".apiKey."&user_id=".userId."&per_page=".$this::maxPhotosPerPage."&page=".$id."&format=json&nojsoncallback=1");
            $photosDecoded = $serial->decode($photosRaw, 'json');
            foreach ($photosDecoded["photos"]["photo"] as $photo) {
                $photos = null;
                $photos["photo"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
                $photos["id"] = $photo["id"];
                $allPhotos[] = $photos;
                if (isset($_POST['random'])) {
                    if ($_POST['random'] != '' || $_POST['random'] != null) {
                        shuffle($allPhotos);
                    }
                }
            }
            
            return $this->render('photos/photos.html.twig', [
                'photos' => $allPhotos,
                'pages' => $photosDecoded["photos"]["pages"],
                'actualPage' => $id,
                'active' => 'list',
                'themeColor' => PhotosController::themeColor,
                'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/photo/{id}", name="photoShow")
     */
    public function showPhoto($id, SerializerInterface $serial)
    {
        require_once("../public/apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
        $photosInfoRaw = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $photoInfo = $serial->decode($photosInfoRaw, 'json');
        $photo = "https://farm".$photoInfo["photo"]["farm"].".staticflickr.com/".$photoInfo["photo"]["server"]."/".$photoInfo["photo"]["id"]."_".$photoInfo["photo"]["originalsecret"]."_o.".$photoInfo["photo"]["originalformat"];
        $avatar = "http://farm".$photoInfo["photo"]["owner"]["iconfarm"].".staticflickr.com/".$photoInfo["photo"]["owner"]["iconserver"]."/buddyicons/".$photoInfo["photo"]["owner"]["nsid"].".jpg";
        $faves = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getFavorites&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $faves = $serial->decode($faves, 'json');
        $faves = $faves["photo"]["total"];
        $comments = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.comments.getList&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $comments = $serial->decode($comments, 'json');
        if (!isset($comments["comments"]["comment"])) {
            $comments["comments"]["comment"] = '';
        }

        return $this->render('photos/photo.html.twig', [
            'photo' => $photo,
            'photoInfo' => $photoInfo,
            'avatar' => $avatar,
            'faves' => $faves,
            'comments' => $comments,
            'active' => '',
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/collection", name="collection")
     */
    public function collection(SerializerInterface $serial)
    {
        require_once("../public/apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
        $collections = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photosets.getList&api_key=".apiKey."&user_id=".userId."&format=json&nojsoncallback=1");
        $collections = $serial->decode($collections, 'json');
        $pos = 0;
        foreach ($collections["photosets"]["photoset"] as $col) {
            $collections["photosets"]["photoset"][$pos]["link"] = "https://farm".$col["farm"].".staticflickr.com/".$col["server"]."/".$col["primary"]."_".$col["secret"]."_q.jpg";
            $pos++;
        }
        
        return $this->render("photos/collection.html.twig", [
            'collections' => $collections,
            'active' => 'collection',
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/collection/{id}", name="collPhotos")
     */
    public function getPhotosFromCollection($id, SerializerInterface $serial)
    {
        require_once("../public/apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";

        $photos = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=".apiKey."&photoset_id=".$id."&user_id=".userId."&format=json&nojsoncallback=1");
        $photos = $serial->decode($photos, 'json');

        foreach ($photos["photoset"]["photo"] as $key => $col) {
            $photos["photoset"]["photo"][$key]["link"] = "https://farm".$col["farm"].".staticflickr.com/".$col["server"]."/".$col["id"]."_".$col["secret"]."_z.jpg";
        }

        return $this->render('photos/collectionPhotos.html.twig', [
            'photos' => $photos,
            'active' => 'collection',
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
        return $this->render('photos/about.html.twig', [
            'active' => 'about',
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
        ]);
    }
}
