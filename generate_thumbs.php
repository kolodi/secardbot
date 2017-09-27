<?php
set_time_limit (600);

function createThumbnail($filepath, $thumbpath, $thumbnail_width, $thumbnail_height, $background=false) {
    list($original_width, $original_height, $original_type) = getimagesize($filepath);
    if ($original_width > $original_height) {
        $new_width = $thumbnail_width;
        $new_height = intval($original_height * $new_width / $original_width);
    } else {
        $new_height = $thumbnail_height;
        $new_width = intval($original_width * $new_height / $original_height);
    }
    $dest_x = intval(($thumbnail_width - $new_width) / 2);
    $dest_y = intval(($thumbnail_height - $new_height) / 2);

    if ($original_type === 1) {
        $imgt = "ImageGIF";
        $imgcreatefrom = "ImageCreateFromGIF";
    } else if ($original_type === 2) {
        $imgt = "ImageJPEG";
        $imgcreatefrom = "ImageCreateFromJPEG";
    } else if ($original_type === 3) {
        $imgt = "ImagePNG";
        $imgcreatefrom = "ImageCreateFromPNG";
    } else {
        return false;
    }

    $old_image = $imgcreatefrom($filepath);
    $new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height); // creates new image, but with a black background

    // figuring out the color for the background
    if(is_array($background) && count($background) === 3) {
      list($red, $green, $blue) = $background;
      $color = imagecolorallocate($new_image, $red, $green, $blue);
      imagefill($new_image, 0, 0, $color);
    // apply transparent background only if is a png image
    } else if($background === 'transparent' && $original_type === 3) {
      imagesavealpha($new_image, TRUE);
      $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
      imagefill($new_image, 0, 0, $color);
    }

    imagecopyresampled($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $original_width, $original_height);
    $imgt($new_image, $thumbpath);
    return file_exists($thumbpath);
}

function downloadOriginalImage($imgName, $imgUrl) {
    file_put_contents($imgName, file_get_contents($imgUrl));
    return $imgName;
}

function GenerateThumb($card) {
    $id=$card["id"];
    $imageUrl = "http://www.shadowera.com/cards/$id.jpg";
    $imageFolder = "Images";
    $imageFileName = "$imageFolder/" . $card["imageUrl"];
    $imageThumbFileName = "$imageFolder/$id.thumb.jpg";
    try {
        if(!file_exists($imageFileName))  {
            echo "Downloading image for " . $id . " " . $card["name"] . "\n";
            file_put_contents($imageFileName, file_get_contents($imageUrl));
            chmod($imageFileName, 0777);
        }

        $imageSize = getimagesize($imageFileName);
        if(!file_exists($imageThumbFileName)) {   
            echo "Creating thumbnail for " . $id . " " . $card["name"] . "\n";
            createThumbnail($imageFileName, $imageThumbFileName, $imageSize[0]/2, $imageSize[1]/2);

        }
    }
    catch (ImagickException $e) {
        echo $e->getMessage();
    }
    catch (Exception $e) {
        echo $e->getMessage();
    }
}

function moveThumb($card) {
    $imageFolder = "Images";
    $t = $card["id"] . ".thumb.jpg";
    $imageThumbFileName = "$imageFolder/$t";
    if(file_exists($imageThumbFileName)) {
        copy($imageThumbFileName, "Thumbs/" . $t);
    }
}


$allCardsJson = file_get_contents("secards.317m.json");
$cards = json_decode($allCardsJson, true);

if(!file_exists("Images")) {
    mkdir("/Images", 0777);
}

foreach($cards as $card) {
    
    //GenerateThumb($card);
    moveThumb($card);
    // if($card["id"] == "ll181") {
    //     GenerateThumb($card);
    //     break;
    // }
}
