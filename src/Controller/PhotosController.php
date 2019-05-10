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
    const maxPhotosPerPage = 100;   // maximum is 500
    const realName = false;         // show your username or realname

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        
        return $this->render('photos/index.html.twig', [
            'active' => 'index',
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }
    
    /**
     * List photos
     * @Route("/photos", name="photos")
     */
    public function listPhotos(SerializerInterface $serial)
    {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        $flickr = new Flickr($serial);
        $photos = $flickr->getPhotosData();

        if (isset($_POST["filter"])){
            list($photos, $activeFilter) = $flickr->filter($_POST["filter"], $photos);  //filter method return 2 variables, an array and a string, all stored in an array, so the list() is dividing them correctly in 2 variables
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
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }
        
    /**
     * List the photos by page (1, 2,...)
     * @Route("/photos/{id}", name="photosPage")
     */
    public function listPhotosPage(SerializerInterface $serial, $id) {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        $flickr = new Flickr($serial);
        // $photos = ($id == 1) ? $flickr->getPhotosData() : '';
        $startValue = 0;
        /**
         * If on page 2, merge the 1st page to the 2nd to a better filtering (TODO : keep the active filter when switching pages)
         */
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
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/photo/{id}", name="photoShow")
     */
    public function showPhoto($id, SerializerInterface $serial)
    {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        $flickr = new Flickr($serial);

        list($photoInfo, $name, $photo, $avatar, $faves, $comments, $plural) = $flickr->getPhotoData($id);

        return $this->render('photos/photo.html.twig', [
            'photo' => $photo,
            'photoInfo' => $photoInfo,
            'name' => $name,
            'avatar' => $avatar,
            'faves' => $faves,
            'comments' => $comments,
            'commentCount' => $plural,
            'active' => '',
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/collection", name="collection")
     */
    public function collection(SerializerInterface $serial)
    {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        $flickr = new Flickr($serial);

        $collections = $flickr->getCollections();

        return $this->render("photos/collection.html.twig", [
            'collections' => $collections,
            'active' => 'collection',
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/collection/{id}", name="collPhotos")
     */
    public function getPhotosFromCollection($id, SerializerInterface $serial)
    {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        $flickr = new Flickr($serial);

        $photos = $flickr->getPhotosFromCollection($id);

        return $this->render('photos/collectionPhotos.html.twig', [
            'photos' => $photos,
            'active' => 'collection',
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        $darkMode = self::darkMode == 1 ? "#212121" : "#fff";
        return $this->render('photos/about.html.twig', [
            'active' => 'about',
            'themeColor' => self::themeColor,
            'darkMode' => $darkMode
        ]);
    }
}
