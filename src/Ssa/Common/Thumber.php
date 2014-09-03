<?PHP
namespace Ssa\Common;

class Thumber
{
  
 	private $pathToImage;
 	private $pathToThumb;
	private $imageType;

	private $imageWidth, $imageHeight;
	private $thumbArea;
	private $thumbWidth, $thumbHeight;
	private $thumbRoot;
	private $imageRoot;
	private $image;


	function __construct($imageRoot='',$thumbRoot='') 
	{
		//init
		$this->imageRoot=$imageRoot;
		$this->thumbRoot=$thumbRoot;
	}
	
	function setImage($img)
	{   
		$this->pathToImage = $this->imageRoot.$img;
		$this->image=$img;
		if (!file_exists($this->pathToImage))  
		{ throw new Exception('input image not found  at '.$img);
		}
        $this->_gatherInfo();
	}

	function setThumbSize($w=null,$h=null)
	{  
		if (is_null($w) && is_null($h))
		{   throw new Exception('thumb size is null');

		}

		if (is_null($w))
		{
			$sizeRatio = $this->imageHeight / $h;
			$this->thumbWidth = ceil($this->imageWidth / $sizeRatio);
		}
		else
		{  $this->thumbWidth  = $w ;

		}
		
		if (is_null($h))
		{
			$sizeRatio = $this->imageWidth / $w;
			$this->thumbHeight = ceil($this->imageHeight / $sizeRatio);		}
		else
		{  $this->thumbHeight  = $h;

		}
		$sizeRatio = $this->imageWidth / $this->thumbWidth;
		$this->thumbHeight = ceil($this->imageHeight / $sizeRatio);


		


	}




	private function _gatherInfo() {
		
		// --------------------------------------------------------------------------
		// determine the file type and the dimensions of the original image
		// --------------------------------------------------------------------------
		
		// right now, only 'gif', 'jpg' and 'png' files work as input,
		// but future versions of the GD library might understand more formats
		
		$types = array (
		        1 =>  'gif',
		        2 =>  'jpg',
		        3 =>  'png',
		        4 =>  'swf',
		        5 =>  'psd',
		        6 =>  'bmp',
		        7 =>  'tiff(intel byte order)',
		        8 =>  'tiff(motorola byte order)',
		        9 =>  'jpc',
		        10 => 'jp2',
		        11 => 'jpx',
		        12 => 'jb2',
		        13 => 'swc',
		        14 => 'iff',
		        15 => 'wbmp',
		        16 => 'xbm'
		);
		
		$info = getimagesize($this->pathToImage);
		$this->imageWidth  = $info[0];
		$this->imageHeight = $info[1];
		$this->imageType   = $types[$info[2]];
		
	}

		
		

	private function thumbName()
	{
		// --------------------------------------------------------------------------
		// now that we know the definitive dimensions of our thumbnail (as integers),
		// why not use those to label the file properly?
		// --------------------------------------------------------------------------
		$pathPartsImage = pathinfo($this->image);
		$chemin=$this->thumbRoot.$pathPartsImage['dirname'] ;
		//var_dump($pathPartsImage);
		if (!is_dir($chemin) )
	    {	if (!mkdir($chemin, 0755, true)) 
	    	{   throw new Exception('Directoty could not be created');
   				
			}

	    }
		
		
		$this->pathToThumb = $chemin
						   . '/'
						   . $pathPartsImage['filename'] 
						   . '_' . $this->thumbWidth 
						   . 'x' . $this->thumbHeight 
						   . '.' . $pathPartsImage['extension'];

	}

	public function serveThumb() {
		
        
		//$this->_calculateThumbDimensions();
		$this->thumbName();

		// --------------------------------------------------------------------------
		// if the thumbnail image already exists, serve it; 
		// otherwise generate one
		// --------------------------------------------------------------------------
		
		#$this->_generateThumb(); return; // force the generation of a new thumbnail (for testing)
		
		if (file_exists($this->pathToThumb)) {
			
			#self::error(filemtime($this->pathToImage) . '->' . filemtime($this->pathToThumb));
			
			// force the creation of a new thumbnail if the modification date of the cached one is older than the orginal’s
			
			if (filemtime($this->pathToImage) > filemtime($this->pathToThumb)) {
				$this->_generateThumb(); return;
			}
			
			// --------------------------------------------------------------------------
			/* old, slow
			
			$uri = 'http://' . $_SERVER['SERVER_NAME'] . rtrim(dirname($_SERVER['PHP_SELF']), '/') . ltrim($this->pathToThumb, '.');
			header('Content-Type: image/' . ($this->imageType == 'jpg' ? 'jpeg' : $this->imageType)); 
			header('Content-Length: ' . filesize($this->pathToThumb));
			header('Location: ' .  $uri);
			exit;
			*/
			
			
			// --------------------------------------------------------------------------
			// new, much faster
			
			#$pathToThumb = ltrim($this->pathToThumb);
			
			// open the file in binary mode
			$fp = fopen($this->pathToThumb, 'rb');
			
			// send the right headers
			header('Content-Type: image/' . ($this->imageType == 'jpg' ? 'jpeg' : $this->imageType));
			header('Content-Disposition: inline; filename='. urlencode(basename($this->pathToThumb)) . '');
			header('Content-Length: ' . filesize($this->pathToThumb));
			
			// stream it through
			fpassthru($fp);
			fclose($fp);
			exit;	
				
		} else {
			if (file_exists($this->pathToImage)) {
				$this->_generateThumb();
			}
		}
	}

	private function _generateThumb() {
		
		// --------------------------------------------------------------------------
		// create an image from the input image file
		// --------------------------------------------------------------------------

		switch($this->imageType) {
			case 'jpg':
				$image = @imagecreatefromjpeg($this->pathToImage);
			break;
			case 'gif':
				$image = @imagecreatefromgif($this->pathToImage);
			break;
			case 'png':
				$image = @imagecreatefrompng($this->pathToImage);
			break;
		}
			
		if ($image === false) 
		{	throw new Exception('image could not be created');
			
		}
			
		// --------------------------------------------------------------------------
		// create the thumbnail image and paste the original into it in its new
		// dimensions
		// --------------------------------------------------------------------------
		
		$thumbImage = @ImageCreateTrueColor($this->thumbWidth, $this->thumbHeight);
		
		if ($this->imageType == 'png' || $this->imageType == 'gif') {
			imagealphablending($thumbImage, false);
		}

		// copy image and paste it into the thumb image
		ImageCopyResampled($thumbImage, $image, 0, 0, 0, 0, $this->thumbWidth, $this->thumbHeight, $this->imageWidth, $this->imageHeight);

		if ($this->imageType == 'png' || $this->imageType == 'gif') {
			
			ImageSaveAlpha($thumbImage, true);
			
			// we don’t sharpen thumbs that might contain alpha channels, because it produces nasty borders
			// to do: detect alpha channel in the original image
			
		} else {

			// --------------------------------------------------------------------------
			// sharpen it a little
			// --------------------------------------------------------------------------

			if (function_exists('imageconvolution')) {
				$sharpen = array(array( -1, -1, -1 ),
					             array( -1, 34, -1 ),
					             array( -1, -1, -1 )
				);
				$divisor = array_sum(array_map('array_sum', $sharpen));
				imageconvolution($thumbImage, $sharpen, $divisor, 0);
			}

		}

		// --------------------------------------------------------------------------
		// spit it out
		// --------------------------------------------------------------------------
			
		switch($this->imageType) {
			case 'jpg':
				imagejpeg($thumbImage, $this->pathToThumb, 80);
				header('Content-type: image/jpeg'); 
				imagejpeg($thumbImage, NULL, 80);	
			break;
			case 'gif':
				imagegif($thumbImage, $this->pathToThumb);
				header('Content-type: image/gif'); 
				imagegif($thumbImage, NULL);
			break;
			case 'png':
				imagepng($thumbImage, $this->pathToThumb);
				header('Content-type: image/png');
				imagepng($thumbImage, NULL);
			break;
		}
		
		imagedestroy($image);
		imagedestroy($thumbImage);
		
		exit;
	}

	

} // class 

