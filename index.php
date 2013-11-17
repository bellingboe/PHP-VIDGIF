<?php
include "ImageTools.class.php";
include "GIFEncoder.class.php";

if (!$_POST) {
?>

<!doctype>
<html>
    <head>
        <title>
            VIDGIF
        </title>
    </head>
    <body>
    
        <form method='POST' name='uploadform' enctype='multipart/form-data' action=''>
            <input type='file' name='file'><br>
            <input type='submit' name='cmdSubmit' value='Upload'>
        </form>
    
    </body>
</html>

<?php
} else {

    $allowedExts = array("flv", "mp4", "m3u8", "ts", "3gp", "mov", "avi", "wmv");
    $extension = end(explode(".", $_FILES["file"]["name"]));
    if (
        /*(
            ($_FILES["file"]["type"] == "video/x-flv")
            || ($_FILES["file"]["type"] == "video/mp4")
            || ($_FILES["file"]["type"] == "application/x-mpegURL")
            || ($_FILES["file"]["type"] == "video/MP2T")
            || ($_FILES["file"]["type"] == "video/3gpp")
            || ($_FILES["file"]["type"] == "video/quicktime")
            || ($_FILES["file"]["type"] == "video/x-msvideo")
            || ($_FILES["file"]["type"] == "video/x-ms-wmv")
        )
    && */in_array($extension, $allowedExts)
    ) {
        
        $err = false;
        
        switch( $_FILES['file']['error'] ) {
            case UPLOAD_ERR_OK:
                $message = false;
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message .= ' - file too large (limit of '.get_max_upload().' bytes).';
                $err = true;
                break;
            case UPLOAD_ERR_PARTIAL:
                $message .= ' - file upload was not completed.';
                $err = true;
                break;
            case UPLOAD_ERR_NO_FILE:
                $message .= ' - zero-length file uploaded.';
                $err = true;
                break;
            default:
                $message .= ' - internal error #'.$_FILES['file']['error'];
                $err = true;
                break;
        }
        
        if ($err) {
            echo "<pre>";
            var_dump($_FILES["file"]);
            echo "</pre>";
            
            echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
        } else {
            echo "Upload: " . $_FILES["file"]["name"] . "<br />";
            echo "Type: " . $_FILES["file"]["type"] . "<br />";
            echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
            echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
            
            $file = time()."_".$_FILES["file"]["name"];
            
            $session_id = sha1($file);
            $session_path = "upload/" . $session_id;
            $stored_name = $session_path . "/" . $file;
            $gif_name = $session_id.".gif";
            
            $dir = getcwd();
            
            mkdir($session_path);

            move_uploaded_file($_FILES["file"]["tmp_name"], $stored_name);
            echo "Stored in: " . $stored_name;
                        
            $vid_to_frames = system('ffmpeg -i '.$dir.'/'.$stored_name.' -f image2 -vf fps=fps=1 '.$dir.'/'.$session_path.'/%d.png', $ret);
            
            unlink($stored_name);
            
            $sd = scandir ("$session_path/");
            natsort($sd);
            
            foreach ($sd as $s) {
                if ( $s != "." && $s != ".." ) {
                        $fn = ImageTools::toGif("$session_path/$s");
                        $frames [ ] = $fn;
                        $framed [ ] = 10;
                }
            }
            
            $gif = new GIFEncoder (
                $frames,
                $framed,
                0,
                2,
                0, 0, 0,
                "url"
            );
            
            fwrite(fopen($gif_name, "wb"), $gif->GetAnimation());
            
            foreach ($sd as $s) {
                if ( $s != "." && $s != ".." ) {
                    unlink("$session_path/$s");
                }
            }
            
            rmdir($session_path);
            
            echo "<img src='$gif_name'>";

        }
    } else {
        var_dump($_FILES);
        echo "Invalid file";
    }
  
}