<?php
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel, Zend\View\Model\JsonModel;

use Zend\Http\Headers;

class AssetController extends AbstractActionController
{


	public function indexAction()
	{
		return new JsonModel();
	}

	public function imageAction()
	{
		$response = $this->getResponse();

		$header = $response->getHeaders();
		$encoding = $this->_encoding();
		$maxage = 60 * 60 * 24 * 30;
		$expire = date("D, d M Y H:i:s", time() + $maxage);

		$requestparams = $this->params()->fromQuery();
		$suffix = $requestparams['suffix'];
		$size   = explode($requestparams['delimiter'], $requestparams['size']);

		if (file_exists(getcwd() . '/data/cache/' . $requestparams['file'])) {

			$_source = file_get_contents(getcwd() . '/data/cache/' . $requestparams['file']);
			$hash = md5($_source);

			if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $hash . '"')) {

				$response
					->getHeaders()
					->addHeaderLine('Content-Transfer-Encoding', 'binary')
					->addHeaderLine('Content-Type', 'image/'.$suffix)
					->addHeaderLine('Content-Length', mb_strlen($_source));

				if ($encoding != 'none')
					$header->addHeaderLine("Content-Encoding", $encoding);


				$header->addHeaderLine("Expires", $expire . " GMT", true);
				$header->addHeaderLine("Cache-Control", "maxage=" . $maxage);

				$header->addHeader("Etag:" . md5($_source));
				$header->addHeader("HTTP/1.0 304 Not Modified");
				$header->addHeader("Content-Length: 0");

				$response->setContent($_source);
				return $response;
			} else {
				$header->addHeaderLine("Etag", '"' . $hash . '"');
				$header->addHeaderLine("Expires", $expire . " GMT", true);
				$header->addHeaderLine("Cache-Control", "maxage=" . $maxage);
				$header->addHeaderLine("Content-Type", "image/" . $suffix);
				$header->addHeaderLine("Content-Length", mb_strlen($_source));

				$response->setContent($_source);
				return $response;
			}
		}
		$image = $this->dm->getRepository("Model\Image")->find($requestparams['id']);
		$_source = $image->getFile()->getBytes();

		$im = new \Imagick();
		$im->readimageblob($_source);
		$im->setImageFormat($requestparams['suffix']);

		switch ($requestparams['type']) {

			case 'image':

				switch ($requestparams['delimiter']) {
					case 'cropFromBottom':
						list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0] + 100, $size[1] + 100);
						$im->thumbnailImage($newX, $newY);
						$im->cropImage($size[0], $size[1], 0, 100);
						break;

						case 'c':
							$im->cropThumbnailImage($size[0], $size[1]);
							break;

						case 'w':
// 							$im->thumbnailImage($size[0], null, true);
							$im->resizeImage($size[0], 0, \Imagick::FILTER_LANCZOS,1);
							break;

						case 'h':
							$im->thumbnailImage($size[0], $size[1], true, false);
							break;

						case 's':
							list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0], $size[1]);
							$im->thumbnailImage($newX, $newY);
							break;

						case 'sc':
							list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0], $size[1]);
							$im->thumbnailImage($newX, $newY);
							$im->cropThumbnailImage($size[0], $size[1]);
							break;
						case 'scsc':
							list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0], $size[1]);
							$im->thumbnailImage($newX, $newY);
							$im->cropThumbnailImage($size[0], $size[1]);

							/*
							 *
							*/
							list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0], $size[1]);
							$im->thumbnailImage($newX, $newY);
							$im->cropThumbnailImage($size[0], $size[1]);
							break;

						case 'cs':
							$im->cropThumbnailImage($size[0], $size[1]);
							list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0], $size[1]);
							$im->thumbnailImage($newX, $newY);
							break;

						case 'ss':
							list ($newX, $newY) = $this->scaleImage($im->getImageWidth(), $im->getImageHeight(), $size[0], $size[1]);
							$im->thumbnailImage($newX, $newY);
							$sh = $im;
							$sh->setImageBackgroundColor(new ImagickPixel('black'));
							$sh->shadowImage(40, 3, 4, 4);
							$sh->compositeImage($im, Imagick::COMPOSITE_OVER, 6, 6);
							$im = $sh;
							break;

				}

				if($requestparams['suffix'] != 'png') {
					$im->setImageCompression(\Imagick::COMPRESSION_JPEG);
					$im->setImageCompressionQuality(75);
					$im->stripImage();
					$suffix = 'jpg';
				}

				$_source = $im->getImageBlob();
				$im->destroy();
// 				file_put_contents(getcwd().'/data/cache/'.$requestparams['file'], $_source);

				$response
					->getHeaders()
					->addHeaderLine('Content-Transfer-Encoding', 'binary')
					->addHeaderLine('Content-Type', 'image/'.$suffix)
					->addHeaderLine('Content-Length', mb_strlen($_source))
					->addHeaderLine("Etag: " . md5($_source))
					->addHeaderLine("Expires: " . $expire . ' GMT')
					->addHeaderLine("Pragma: cache")
					->addHeaderLine("Cache-Control: max-age=" . $maxage);

				$response->setContent("data:image/{$suffix};base64,".base64_encode($_source));
				return $response;
				break;

			case 'file':
				break;

		}

	}

	protected function _encoding()
	{
		// Determine supported compression method
		$http_accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
		$http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		$gzip = strstr($http_accept_encoding, 'gzip');
		$deflate = strstr($http_accept_encoding, 'deflate');

		$this->gzip = $gzip;
		$this->deflate = $deflate;

		$encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : 'none');

		// Check for buggy versions of Internet Explorer
		$matches = false;
		if (! strstr($http_user_agent, 'Opera') && preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $http_user_agent, $matches)) {
			$version = floatval($matches[1]);
			if ($version < 6 || ($version == 6 && ! strstr($http_user_agent, 'EV1'))) {
				$encoding = 'none';
			}
		}
		return $encoding; // $encoding;
	}

	public function scaleImage($x, $y, $cx, $cy)
	{
		// Set the default NEW values to be the old, in case it doesn't even
		// need scaling
		list ($nx, $ny) = array(
			$x,
			$y
		);

		// If image is generally smaller, don't even bother
		if ($x >= $cx || $y >= $cx) {

			// Work out ratios
			if ($x > 0)
				$rx = $cx / $x;
			if ($y > 0)
				$ry = $cy / $y;

			// Use the lowest ratio, to ensure we don't go over the wanted image
			// size
			if ($rx > $ry) {
				$r = $ry;
			} else {
				$r = $rx;
			}

			// Calculate the new size based on the chosen ratio
			$nx = intval($x * $r);
			$ny = intval($y * $r);
		}

		// Return the results
		return array(
			$nx,
			$ny
		);
	}

	private function drawWatermark(&$image, &$watermark, $padding = 10)
	{

		// Check if the watermark is bigger than the image
		$image_width = $image->getImageWidth();
		$image_height = $image->getImageHeight();
		$watermark_width = $watermark->getImageWidth();
		$watermark_height = $watermark->getImageHeight();

		if ($image_width < $watermark_width + $padding || $image_height < $watermark_height + $padding) {
			return false;
		}

		// Calculate each position
		$positions = array();
		$positions[] = array(
			0 + $padding,
			0 + $padding
		);
		$positions[] = array(
			$image_width - $watermark_width - $padding,
			0 + $padding
		);
		$positions[] = array(
			$image_width - $watermark_width - $padding,
			$image_height - $watermark_height - $padding
		);
		$positions[] = array(
			0 + $padding,
			$image_height - $watermark_height - $padding
		);

		// Initialization
		$min = null;
		$min_colors = 0;

		// Calculate the number of colors inside each region
		// and retrieve the minimum
		foreach ($positions as $position) {
			$colors = $image->getImageRegion($watermark_width, $watermark_height, $position[0], $position[1])->getImageColors();

			if ($min === null || $colors <= $min_colors) {
				$min = $position;
				$min_colors = $colors;
			}
		}

		// Draw the watermark
		$image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $min[0], $min[1]);

		return true;
	}
}