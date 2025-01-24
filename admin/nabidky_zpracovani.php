<?php
  //--------------------------------
  // CREATE WATERMARK FUNCTION
  //--------------------------------

  define( 'WATERMARK_OVERLAY_BACKGROUND', './foto/wattermark.png' );
  define( 'WATERMARK_OVERLAY_OPACITY_BACKGROUND', 90 );
  define( 'WATERMARK_OUTPUT_QUALITY', 90 );

header('Content-Type: image/png');

  function create_watermark( $source_file_path, $output_file_path )
  {
    $sirka = 420;  
    $vyska = 170;
    
    list( $source_width, $source_height, $source_type ) = getimagesize( $source_file_path );
   // echo $source_file_path;
    //print_r(getimagesize( $source_file_path ));
    if ( $source_type === NULL )
    {
      return false;
    }

    switch ( $source_type )
    {
      case IMAGETYPE_GIF:
        $source_gd_image = imagecreatefromgif( $source_file_path );
        break;
      case IMAGETYPE_JPEG:
        $source_gd_image = imagecreatefromjpeg( $source_file_path );
        break;
      case IMAGETYPE_PNG:
        $source_gd_image = imagecreatefrompng( $source_file_path );
        break;
      default:
        return false;
    }

    $nova_fotka=ImageCreateTrueColor($sirka,$vyska); //vytvoøení nového True Color obrázku náhledu
    imagecopyresampled($nova_fotka,$source_gd_image,0,0,0,0,$sirka,$vyska,$source_width,$source_height); //zkopírování a zmenšení pùvodního obrázku do obrázku náhledu
    $source_gd_image=$nova_fotka;
    
    
    //copy podkladu
    $overlay_gd_image = imagecreatefrompng( WATERMARK_OVERLAY_BACKGROUND );
    $overlay_width = imagesx( $overlay_gd_image );
    $overlay_height = imagesy( $overlay_gd_image );
   // echo $overlay_width.$overlay_height;
    $res = imagecopymerge(
      $source_gd_image,
      $overlay_gd_image,
      0,
      0,
      0,
      0,
      420,
      170,
      85
    );
   // echo $res;
$text1=ImageCreateTrueColor(150,100);
$white = imagecolorallocate($text1, 255, 255, 255);
$grey = imagecolorallocate($text1, 128, 128, 128);
$black = imagecolorallocate($text1, 0, 0, 0);
$trans_colour = imagecolorallocatealpha($text1, 0, 0, 0, 127);
imagefill($text1, 0, 0, $trans_colour);

// The text to draw
$text = $_POST["sleva"];
// Replace path by your own font path
$font = 'Arial.ttf';

    
    imagettftext ( $text1 , 25.0 , 13 , 26, 51 , $grey , $font ,$text);
    imagettftext ( $text1 , 25.0 , 13 , 25, 50 , $black , $font ,$text);
    $res = imagecopy(
      $source_gd_image,
      $text1,
      310,
      5,
      0,
      0,
      150,
      100
    );
$text2=ImageCreateTrueColor(420,35);
$white = imagecolorallocate($text2, 255, 255, 255);
$grey = imagecolorallocate($text2, 128, 128, 128);
$black = imagecolorallocate($text2, 0, 0, 0);
$trans_colour = imagecolorallocatealpha($text2, 0, 0, 0, 127);
imagefill($text2, 0, 0, $trans_colour);

// The text to draw
$text = $_POST["text"];
// Replace path by your own font path
$font = 'Arial.ttf';

    
    imagettftext ( $text2 , 12.0 , 0 , 25, 21 , $grey , $font ,$text);
    imagettftext ( $text2 , 12.0 , 0 , 24, 20 , $black , $font ,$text);
    $res = imagecopy(
      $source_gd_image,
      $text2,
      0,
      142,
      0,
      0,
      420,
      35
    );    
    imagepng($source_gd_image);
    //imagejpeg( $source_gd_image, $output_file_path, WATERMARK_OUTPUT_QUALITY );

    imagedestroy( $source_gd_image );
    imagedestroy( $overlay_gd_image );
  }

  //--------------------------------
  // FILE PROCESSING FUNCTION
  //--------------------------------

  define( 'UPLOADED_IMAGE_DESTINATION', './foto/test/' );
  define( 'PROCESSED_IMAGE_DESTINATION', './foto/nabidky/' );

  function process_image_upload( $Field )
  {
     // echo "process_image_upload";
    $temp_file_path = $_FILES[ $Field ][ 'tmp_name' ];
    $temp_file_name = $_FILES[ $Field ][ 'name' ];

    list( , , $temp_type ) = getimagesize( $temp_file_path );

    //echo $temp_type;

    if ( $temp_type === NULL )
    {
      return false;
    }

    switch ( $temp_type )
    {
      case IMAGETYPE_GIF:
        break;
      case IMAGETYPE_JPEG:
        break;
      case IMAGETYPE_PNG:
        break;
      default:
        return false;
    }

    $uploaded_file_path = UPLOADED_IMAGE_DESTINATION . $temp_file_name;
    $processed_file_path = PROCESSED_IMAGE_DESTINATION . preg_replace( '/\\.[^\\.]+$/', '.jpg', $temp_file_name );
    move_uploaded_file( $temp_file_path, $uploaded_file_path );

    $result = create_watermark( $uploaded_file_path, $processed_file_path );

    if ( $result === false )
    {
      return false;
    }
    else
    {
      return array( $uploaded_file_path, $processed_file_path );
    }
  }

  //--------------------------------
  // END OF FUNCTIONS
  //--------------------------------

  $result = process_image_upload( 'podklad' );
/*
  if ( $result === false )
  {

    echo '<br>An error occurred during file processing.';
  }
  else
  {
    echo '<br>Original image saved as <a href="' . $result[ 0 ] . '" target="_blank">' . $result[ 0 ] . '</a>';
    echo '<br>Watermarked image saved as <a href="' . $result[ 1 ] . '" target="_blank">' . $result[ 1 ] . '</a>';
    echo "<img src=\"".$result[ 1 ]."\" />";
  }
*/
?>
