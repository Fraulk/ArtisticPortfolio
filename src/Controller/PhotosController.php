<?php

namespace App\Controller;

use App\Entity\Flickr;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PhotosController extends AbstractController
{
    const darkMode = 1;
    const themeColor = "#9C27B0";   // don't forget to change the text color in css if it's too luminous
    const maxPhotosPerPage = 50;   // maximum is 500
    const realName = false;         // show your username or realname

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
     * List photos
     * @Route("/photos", name="photos")
     */
    public function listPhotos(SerializerInterface $serial)
    {
        $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
        $flickr = new Flickr($serial);
        $photos = $flickr->getPhotosData();

        if (isset($_POST["filter"])){
            list($photos, $activeFilter) = $flickr->filter($_POST["filter"], $photos);
        }

        foreach ($photos["photos"]["photo"] as $key => $photo) {
            $photos["photos"]["photo"][$key]["link"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
        }

        if (isset($_POST['random'])) {
            if ($_POST['random'] != '' || $_POST['random'] != null) {
                shuffle($photos["photos"]["photo"]);
            }
        }
        
        return $this->render('photos/photos.html.twig', [
            'photos' => $photos["photos"],
            'pages' => $photos["photos"]["pages"],
            'actualPage' => 1,
            'startValue' => 0,
            'endValue' => self::maxPhotosPerPage,
            'active' => 'list',
            'activeFilter' => (isset($activeFilter)) ? $activeFilter : '', //if there is an active filter, it return the name, else nothing
            'themeColor' => PhotosController::themeColor,
            'darkMode' => $darkMode
            ]);
    }
        
        /**
         * List the photos by page (1, 2,...)
         * @Route("/photos/{id}", name="photosPage")
         */
        public function listPhotosPage(SerializerInterface $serial, $id) {
            $darkMode = PhotosController::darkMode == 1 ? "#212121" : "#fff";
            $flickr = new Flickr($serial);
            // $photos = ($id == 1) ? $flickr->getPhotosData() : '';
            $startValue = 0;
            for ($i=1; $i <= $id; $i++) { 
                if ($i == 1)
                    $photos = $flickr->getPhotosData();$i++;
                $pageIdPhotos = $flickr->getPhotosData($i);
                $photos["photos"]["photo"] = array_merge($photos["photos"]["photo"], $pageIdPhotos["photos"]["photo"]);
                $startValue = ($id-1) * self::maxPhotosPerPage;
                $endValue = $startValue + self::maxPhotosPerPage;
            }

            if (isset($_POST["filter"])){
                list($photos, $activeFilter) = $flickr->filter($_POST["filter"], $photos);
            }

            foreach ($photos["photos"]["photo"] as $key => $photo) {
                $photos["photos"]["photo"][$key]["link"] = "https://farm".$photo["farm"].".staticflickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_z.jpg";
            }
    
            if (isset($_POST['random'])) {
                if ($_POST['random'] != '' || $_POST['random'] != null) {
                    shuffle($photos["photos"]["photo"]);
                }
            }
            
        return $this->render('photos/photos.html.twig', [
            'photos' => $photos["photos"],
            'pages' => $photos["photos"]["pages"],
            'actualPage' => $id,
            'startValue' => (isset($startValue)) ? $startValue : 0,
            'endValue' => (isset($endValue)) ? $endValue : 50,
            'active' => 'list',
            'activeFilter' => (isset($activeFilter)) ? $activeFilter : '', //if there is an active filter, it return the name, else nothing
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

        $name = (PhotosController::realName) ? $photoInfo["photo"]["owner"]["realname"] : $photoInfo["photo"]["owner"]["username"];

        // dump($photoInfo);
        // die();
        if(isset($photoInfo["photo"]["originalsecret"]))
            $photo = "https://farm".$photoInfo["photo"]["farm"].".staticflickr.com/".$photoInfo["photo"]["server"]."/".$photoInfo["photo"]["id"]."_".$photoInfo["photo"]["originalsecret"]."_o.".$photoInfo["photo"]["originalformat"];
        else
            $photo = "https://farm".$photoInfo["photo"]["farm"].".staticflickr.com/".$photoInfo["photo"]["server"]."/".$photoInfo["photo"]["id"]."_".$photoInfo["photo"]["secret"]."_b.jpg";

        $avatar = "http://farm".$photoInfo["photo"]["owner"]["iconfarm"].".staticflickr.com/".$photoInfo["photo"]["owner"]["iconserver"]."/buddyicons/".$photoInfo["photo"]["owner"]["nsid"].".jpg";

        $faves = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.getFavorites&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $faves = $serial->decode($faves, 'json');
        $faves = $faves["photo"]["total"];
        
        $comments = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.photos.comments.getList&api_key=".apiKey."&photo_id=".$id."&format=json&nojsoncallback=1");
        $comments = $serial->decode($comments, 'json');

        if (!isset($comments["comments"]["comment"])) {
            $comments["comments"]["comment"] = '';
        }

        $commentCount = $photoInfo["photo"]["comments"]["_content"];
        $plural = $commentCount > 1 ? "comments" : "comment";

        return $this->render('photos/photo.html.twig', [
            'photo' => $photo,
            'photoInfo' => $photoInfo,
            'name' => $name,
            'avatar' => $avatar,
            'faves' => $faves,
            'comments' => $comments,
            'commentCount' => $plural,
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
