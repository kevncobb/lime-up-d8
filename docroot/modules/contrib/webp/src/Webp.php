<?php

namespace Drupal\webp;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Imagick;

/**
 * Class Webp.
 *
 * @package Drupal\webp
 */
class Webp {

  use StringTranslationTrait;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Default image processing quality.
   *
   * @var int
   */
  protected $defaultQuality;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Webp constructor.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Image factory to be used.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger channel factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   The file system service.
   */
  public function __construct(ImageFactory $imageFactory, LoggerChannelFactoryInterface $loggerFactory, TranslationInterface $stringTranslation, ConfigFactoryInterface $configFactory, FileSystem $fileSystem) {
    $this->imageFactory = $imageFactory;
    $this->logger = $loggerFactory->get('webp');
    $this->setStringTranslation($stringTranslation);
    $this->defaultQuality = $configFactory->get('webp.settings')->get('quality');
    $this->fileSystem = $fileSystem;
  }

  /**
   * Creates a WebP copy of a source image URI.
   *
   * @param string $uri
   *   Image URI.
   * @param int $quality
   *   Image quality factor (optional).
   *
   * @return bool|string
   *   The location of the WebP image if successful, FALSE if not successful.
   */
  public function createWebpCopy($uri, $quality = NULL) {
    $webp = FALSE;

    $toolkit = \Drupal::config('system.image')->get('toolkit', FALSE);
    // Fall back to GD if the installed imagemagick does not support WEBP.
    if ($toolkit == 'imagemagick' && !in_array('WEBP', Imagick::queryFormats())) {
      $toolkit = 'gd';
    }

    if (is_null($quality)) {
      $quality = $this->defaultQuality;
    }

    if ($toolkit == 'imagemagick') {
      $webp = $this->createImageMagickImage($uri, $quality);
    }
    else {
      // We assume $toolkit == 'gd'.
      // Generate a GD resource from the source image. You can't pass GD resources
      // created by the $imageFactory as a parameter to another function, so we
      // have to do everything in one function.
      $sourceImage = $this->imageFactory->get($uri, 'gd');
      /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $toolkit */
      $toolkit = $sourceImage->getToolkit();
      $mimeType = $sourceImage->getMimeType();
      $sourceImage = $toolkit->getResource();

      // If we can generate a GD resource from the source image, generate the URI
      // of the WebP copy and try to create it.
      if ($sourceImage !== NULL) {
        $pathInfo = pathinfo($uri);
        $destination = strtr('@directory/@filename.webp', [
          '@directory' => $pathInfo['dirname'],
          '@filename' => $pathInfo['filename'],
          '@extension' => $pathInfo['extension'],
        ]);

        imageSaveAlpha($sourceImage, true);
        imageAlphaBlending($sourceImage, true);
        imageSaveAlpha($sourceImage, true);
        if (@imagewebp($sourceImage, $destination, $quality)) {
          // In some cases, libgd generates broken images. See
          // https://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files
          // for more information.
          if (filesize($destination) % 2 == 1) {
            file_put_contents($destination, "\0", FILE_APPEND);
          }

          @imagedestroy($sourceImage);
          $webp = $destination;
        }
        else {
          $error = $this->t('Could not generate WebP image.');
          $this->logger->error($error);
        }
      }

      // If we can't generate a GD resource from the source image, fail safely.
      else {
        $error = $this->t('Could not generate image resource from URI @uri.', [
          '@uri' => $uri,
        ]);
        $this->logger->error($error);
      }
    }
    return $webp;
  }

  /**
   * Deletes all image style derivatives.
   */
  public function deleteImageStyleDerivatives() {
    // Remove the styles directory and generated images.
    try {
      $this->fileSystem->deleteRecursive(file_default_scheme() . '://styles');
    }
    catch (FileException $e){
      $this->logger->error($e->getMessage());
      $error = $this->t('Could not delete image style directory while uninstalling WebP. You have to delete it manually.');
      $this->logger->error($error);
    }
  }

  /**
   * Receives the srcset string of an image and returns the webp equivalent.
   *
   * @param $srcset
   *   Srcset to convert to .webp version
   *
   * @return string
   *   Webp version of srcset
   */
  public function getWebpSrcset($srcset) {
    return preg_replace('/\.(png|jpg|jpeg)(\\?.*?)?(,| |$)/i', '.webp\\2\\3', $srcset);
  }

  /**
   * Creates a WebP copy of a source image URI using imagemagick toolkit.
   *
   * @param string $uri
   *   Image URI.
   * @param int $quality
   *   Image quality factor (optional).
   *
   * @return bool|string
   *   The location of the WebP image if successful, FALSE if not successful.
   */
  private function createImageMagickImage($uri, $quality = NULL) {
    $webp = FALSE;

    $ImageMagickImg = $this->imageFactory->get($uri, 'imagemagick');
    // We'll convert the image into webp.
    $ImageMagickImg->apply('convert', ['extension' => 'webp', 'quality' => $quality]);

    $pathInfo = pathinfo($uri);
    $destination = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    if ($ImageMagickImg->save($pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp')) {
      $webp = $destination;

      $msg = $this->t('Generated WebP image with Image Magick. Quality: ' . $quality . ' Destination:' . $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp');
      $this->logger->info($msg);
    }
    else {
      $error = $this->t('Imagemagick issue: Could not generate WebP image.');
      $this->logger->error($error);
    }

    return $webp;
  }

}
