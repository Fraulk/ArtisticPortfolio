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
        require_once("..\\public\\apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";

        $photos = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=".apiKey."&user_id=".userId."&per_page=".$this::maxPhotosPerPage."&extras=views&format=json&nojsoncallback=1");
        $photos = $serial->decode($photos, 'json');

        if (isset($_POST["filter"])){
            /**
             * filters, usort to sort with an item in a multidimensional array
             */
            switch ($_POST["filter"]) {
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
                
                case 'fave':

                    $activeFilter = "fave";
                    break;

                case 'comment':
                    
                    $activeFilter = "comment";
                    break;
                
                default:
                    # code...
                    break;
            }
        }
        foreach ($photos["photos"]["photo"] as $photo) {
            
            $photoss["photo"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
            $photoss["id"] = $photo["id"];
            $allPhotos[] = $photoss;
            //randomize
            if (isset($_POST['random'])) {
                if ($_POST['random'] != '' || $_POST['random'] != null) {
                    shuffle($allPhotos);
                }
            }
        }
        
        return $this->render('photos/photos.html.twig', [
            'photos' => $allPhotos,
            'pages' => $photos["photos"]["pages"],
            'actualPage' => 1,
            'active' => 'list',
            'activeFilter' => (isset($activeFilter)) ? $activeFilter : '', //if there is an active filter, it return the name, else nothing
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
            ]);
    }
        
        /**
         * @Route("/photos/{id}", name="photosPage")
         */
        public function listPhotosPage(SerializerInterface $serial, $id) {
            require_once("..\\public\\apiKey.php");
            $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";

            $photos = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=".apiKey."&user_id=".userId."&per_page=".$this::maxPhotosPerPage."&page=".$id."&format=json&nojsoncallback=1");
            $photos = $serial->decode($photos, 'json');

            foreach ($photos["photos"]["photo"] as $photo) {
                $photoss = null;
                $photoss["photo"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
                $photoss["id"] = $photo["id"];
                $allPhotos[] = $photoss;
                if (isset($_POST['random'])) {
                    if ($_POST['random'] != '' || $_POST['random'] != null) {
                        shuffle($allPhotos);
                    }
                }
            }
            
            return $this->render('photos/photos.html.twig', [
                'photos' => $allPhotos,
                'pages' => $photos["photos"]["pages"],
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
        require_once("..\\public\\apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";

        $photoInfo = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $photoInfo = $serial->decode($photoInfo, 'json');

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
        require_once("..\\public\\apiKey.php");
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";

        $collections = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photosets.getList&api_key=".apiKey."&user_id=".userId."&format=json&nojsoncallback=1");
        $collections = $serial->decode($collections, 'json');

        foreach ($collections["photosets"]["photoset"] as $key => $col) {
            $collections["photosets"]["photoset"][$key]["link"] = "https://farm".$col["farm"].".staticflickr.com/".$col["server"]."/".$col["primary"]."_".$col["secret"]."_q.jpg";
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
        require_once("..\\public\\apiKey.php");
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
