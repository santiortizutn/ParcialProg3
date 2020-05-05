<?php
namespace Elboletaire\Watimage;

use Exception;
use Elboletaire\Watimage\Exception\ExtensionNotLoadedException;
use Elboletaire\Watimage\Exception\FileNotExistException;
use Elboletaire\Watimage\Exception\InvalidArgumentException;
use Elboletaire\Watimage\Exception\InvalidExtensionException;
use Elboletaire\Watimage\Exception\InvalidMimeException;

/**
 * The Image class. The main code of Watimage.
 *
 * @author Òscar Casajuana <elboletaire at underave dot net>
 * @copyright 2015 Òscar Casajuana <elboletaire at underave dot net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link https://github.com/elboletaire/Watimage
 */
class Image
{
    /**
     * Constant for the (deprecated) transparent color
     */
    const COLOR_TRANSPARENT = -1;

    /**
     * Current image location.
     *
     * @var string
     */
    protected $filename;

    /**
     * Image GD resource.
     *
     * @var resource
     */
    protected $image;

    /**
     * Image metadata.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Current image width
     *
     * @var float
     */
    protected $width;

    /**
     * Current image height
     *
     * @var float
     */
    protected $height;

    /**
     * Image export quality for gif and jpg files.
     *
     * You can set it with setQuality or setImage methods.
     *
     * @var integer
     */
    protected $quality = 80;

    /**
     * Image compression value for png files.
     *
     * You can set it with setCompression method.
     *
     * @var integer
     */
    protected $compression = 9;

    /**
     * Constructor method. You can pass a filename to be loaded by default
     * or load it later with load('filename.ext')
     *
     * @param string $file Filepath of the image to be loaded.
     */
    public function __construct($file = null, $autoOrientate = true)
    {
        if (!extension_loaded('gd')) {
            throw new ExtensionNotLoadedException("GD");
        }

        if (!empty($file)) {
            $this->load($file);

            if ($autoOrientate) {
                $this->autoOrientate();
            }
        }
    }

    /**
     * Ensure everything gets emptied on object destruction.
     *
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * Creates a resource image.
     *
     * This method was using imagecreatefromstring but I decided to switch after
     * reading this: https://thenewphalls.wordpress.com/2012/12/27/imagecreatefromstring-vs-imagecreatefromformat
     *
     * @param  string $filename Image file path/name.
     * @param  string $mime     Image mime or `string` if creating from string
     *                          (no base64 encoded).
     * @return resource
     * @throws InvalidMimeException
     */
    public function createResourceImage($filename, $mime)
    {
        switch ($mime) {
            case 'image/gif':
                $image = imagecreatefromgif($filename);
                break;

            case 'image/png':
                $image = imagecreatefrompng($filename);
                break;

            case 'image/jpeg':
                $image =  imagecreatefromjpeg($filename);
                break;

            case 'string':
                $image = imagecreatefromstring($filename);
                break;

            default:
                throw new InvalidMimeException($mime);
        }

        // Handle transparencies
        imagesavealpha($image, true);
        imagealphablending($image, true);

        return $image;
    }

    /**
     * Cleans up everything to start again.
     *
     * @return Image
     */
    public function destroy()
    {
        if (!empty($this->image)
            && is_resource($this->image)
            && get_resource_type($this->image) == 'gd'
        ) {
            imagedestroy($this->image);
        }
        $this->metadata = [];
        $this->filename = $this->width = $this->height = null;

        return $this;
    }

    /**
     * Outputs or saves the image.
     *
     * @param  string $filename Filename to be saved. Empty to directly print on screen.
     * @param  string $output   Use it to overwrite the output format when no $filename is passed.
     * @param  bool   $header   Wheather or not generate the output header.
     * @return Image
     * @throws InvalidArgumentException If output format is not recognised.
     */
    public function generate($filename = null, $output = null, $header = true)
    {
        $output = $output ?: $this->metadata['mime'];
        if (!empty($filename)) {
            $output = $this->getMimeFromExtension($filename);
        } elseif ($header) {
            header("Content-type: {$output}");
        }

        switch ($output) {
            case 'image/gif':
                imagegif($this->image, $filename, $this->quality);
                break;
            case 'image/png':
                imagesavealpha($this->image, true);
                imagepng($this->image, $filename, $this->compression);
                break;
            case 'image/jpeg':
                imageinterlace($this->image, true);
                imagejpeg($this->image, $filename, $this->quality);
                break;
            default:
                throw new InvalidArgumentException("Invalid output format \"%s\"", $output);
        }

        return $this;
    }

    /**
     * Similar to generate, except that passing an empty $filename here will
     * overwrite the original file.
     *
     * @param  string $filename Filename to be saved. Empty to overwrite original file.
     * @return bool
     */
    public function save($filename = null)
    {
        $filename = $filename ?: $this->filename;

        return $this->generate($filename);
    }

    /**
     * Returns the base64 version for the current Image.
     *
     * @param  bool $prefix Whether or not prefix the string
     *                      with `data:{mime};base64,`.
     * @return string
     */
    public function toString($prefix = false)
    {
        ob_start();
        $this->generate(null, null, false);
        $image = ob_get_contents();
        ob_end_clean();

        $string = base64_encode($image);

        if ($prefix) {
            $prefix = "data:{$this->metadata['mime']};base64,";
            $string = $prefix . $string;
        }

        return $string;
    }

    /**
     *  Loads image and (optionally) its options.
     *
     *  @param mixed $filename Filename string or array containing both filename and quality
     *  @return Watimage
     *  @throws FileNotExistException
     *  @throws InvalidArgumentException
     */
    public function load($filename)
    {
        if (empty($filename)) {
            throw new InvalidArgumentException("Image file has not been set.");
        }

        if (is_array($filename)) {
            if (isset($filename['quality'])) {
                $this->setQuality($filename['quality']);
            }
            $filename = $filename['file'];
        }

        if (!file_exists($filename)) {
            throw new FileNotExistException($filename);
        }

        $this->destroy();

        $this->filename = $filename;
        $this->getMetadataForImage();
        $this->image = $this->createResourceImage($filename, $this->metadata['mime']);

        return $this;
    }

    /**
     * Loads an image from string. Can be either base64 encoded or not.
     *
     * @param  string $string The image string to be loaded.
     * @return Image
     */
    public function fromString($string)
    {
        if (strpos($string, 'data:image') === 0) {
            preg_match('/^data:(image\/[a-z]+);base64,(.+)/', $string, $matches);
            array_shift($matches);
            list($this->metadata['mime'], $string) = $matches;
        }

        if (!$string = base64_decode($string)) {
            throw new InvalidArgumentException(
                'The given value does not seem a valid base64 string'
            );
        }

        $this->image = $this->createResourceImage($string, 'string');
        $this->updateSize();

        if (function_exists('finfo_buffer') && !isset($this->metadata['mime'])) {
            $finfo = finfo_open();
            $this->metadata['mime'] = finfo_buffer($finfo, $string, FILEINFO_MIME_TYPE);
            finfo_close($finfo);
        }

        return $this;
    }

    /**
     * Auto-orients an image based on its exif Orientation information.
     *
     * @return Image
     */
    public function autoOrientate()
    {
        if (empty($this->metadata['exif']['Orientation'])) {
            return $this;
        }

        switch ((int)$this->metadata['exif']['Orientation']) {
            case 2:
                return $this->flip('horizontal');
            case 3:
                return $this->flip('both');
            case 4:
                return $this->flip('vertical');
            case 5:
                $this->flip('horizontal');
                return $this->rotate(-90);
            case 6:
                return $this->rotate(-90);
            case 7:
                $this->flip('horizontal');
                return $this->rotate(90);
            case 8:
                return $this->rotate(90);
            default:
                return $this;
        }
    }

    /**
     * Rotates an image.
     *
     * Will rotate clockwise when using positive degrees.
     *
     * @param  int    $degrees Rotation angle in degrees.
     * @param  mixed  $bgcolor Background to be used for the background, transparent by default.
     * @return Image
     */
    public function rotate($degrees, $bgcolor = self::COLOR_TRANSPARENT)
    {
        $bgcolor = $this->color($bgcolor);

        $this->image = imagerotate($this->image, $degrees, $bgcolor);

        $this->updateSize();

        return $this;
    }

    /**
     * All in one method for all resize methods.
     *
     * @param  string $type   Type of resize: resize, resizemin, reduce, crop & resizecrop.
     * @param  mixed  $width  Can be just max width or an array containing both params.
     * @param  int    $height Max height.
     * @return Image
     */
    public function resize($type, $width, $height = null)
    {
        $types = [
            'classic'    => 'classicResize',
            'resize'     => 'classicResize',
            'reduce'     => 'reduce',
            'resizemin'  => 'reduce',
            'min'        => 'reduce',
            'crop'       => 'classicCrop',
            'resizecrop' => 'resizeCrop'
        ];

        $lowertype = strtolower($type);

        if (!array_key_exists($lowertype, $types)) {
            throw new InvalidArgumentException("Invalid resize type %s.", $type);
        }

        return $this->{$types[$lowertype]}($width, $height);
    }

    /**
     * Resizes maintaining aspect ratio.
     *
     * Maintains the aspect ratio of the image and makes sure that it fits
     * within the max width and max height (thus some side will be smaller).
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function classicResize($width, $height = null)
    {
        list($width, $height) = Normalize::size($width, $height);

        if ($this->width == $width && $this->height == $height) {
            return $this;
        }

        if ($this->width > $this->height) {
            $height = ($this->height * $width) / $this->width;
        } elseif ($this->width < $this->height) {
            $width = ($this->width * $height) / $this->height;
        } elseif ($this->width == $this->height) {
            $width = $height;
        }

        $this->image = $this->imagecopy($width, $height);

        $this->updateSize();

        return $this;
    }

    /**
     * Backwards compatibility alias for reduce (which has the same logic).
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     * @deprecated
     * @codeCoverageIgnore
     */
    public function resizeMin($width, $height = null)
    {
        return $this->reduce($width, $height);
    }

    /**
     * A straight centered crop.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function classicCrop($width, $height = null)
    {
        list($width, $height) = Normalize::size($width, $height);

        $startY = ($this->height - $height) / 2;
        $startX = ($this->width - $width) / 2;

        $this->image = $this->imagecopy($width, $height, $startX, $startY, $width, $height);

        $this->updateSize();

        return $this;
    }

    /**
     * Resizes to max, then crops to center.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function resizeCrop($width, $height = null)
    {
        list($width, $height) = Normalize::size($width, $height);

        $ratioX = $width / $this->width;
        $ratioY = $height / $this->height;
        $srcW = $this->width;
        $srcH = $this->height;

        if ($ratioX < $ratioY) {
            $startX = round(($this->width - ($width / $ratioY)) / 2);
            $startY = 0;
            $srcW = round($width / $ratioY);
        } else {
            $startX = 0;
            $startY = round(($this->height - ($height / $ratioX)) / 2);
            $srcH = round($height / $ratioX);
        }

        $this->image = $this->imagecopy($width, $height, $startX, $startY, $srcW, $srcH);

        $this->updateSize();

        return $this;
    }

    /**
     * Resizes maintaining aspect ratio but not exceeding width / height.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function reduce($width, $height = null)
    {
        list($width, $height) = Normalize::size($width, $height);

        if ($this->width < $width && $this->height < $height) {
            return $this;
        }

        $ratioX = $this->width / $width;
        $ratioY = $this->height / $height;

        $ratio = $ratioX > $ratioY ? $ratioX : $ratioY;

        if ($ratio === 1) {
            return $this;
        }

        // Getting the new image size
        $width = (int)($this->width / $ratio);
        $height = (int)($this->height / $ratio);

        $this->image = $this->imagecopy($width, $height);

        $this->updateSize();

        return $this;
    }

    /**
     * Flips an image. If PHP version is 5.5.0 or greater will use
     * proper php gd imageflip method. Otherwise will fallback to
     * convenienceflip.
     *
     * @param  string $type Type of flip, can be any of: horizontal, vertical, both
     * @return Image
     */
    public function flip($type = 'horizontal')
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return $this->convenienceFlip($type);
        }

        imageflip($this->image, Normalize::flip($type));

        return $this;
    }

    /**
     * Flip method for PHP versions < 5.5.0
     *
     * @param  string $type Type of flip, can be any of: horizontal, vertical, both
     * @return Image
     */
    public function convenienceFlip($type = 'horizontal')
    {
        $type = Normalize::flip($type);

        $resampled = $this->imagecreate($this->width, $this->height);

        // @codingStandardsIgnoreStart
        switch ($type) {
            case IMG_FLIP_VERTICAL:
                imagecopyresampled(
                    $resampled, $this->image,
                    0, 0, 0, ($this->height - 1),
                    $this->width, $this->height, $this->width, 0 - $this->height
                );
                break;
            case IMG_FLIP_HORIZONTAL:
                imagecopyresampled(
                    $resampled, $this->image,
                    0, 0, ($this->width - 1), 0,
                    $this->width, $this->height, 0 - $this->width, $this->height
                );
                break;
            // same as $this->rotate(180)
            case IMG_FLIP_BOTH:
                imagecopyresampled(
                    $resampled, $this->image,
                    0, 0, ($this->width - 1), ($this->height - 1),
                    $this->width, $this->height, 0 - $this->width, 0 - $this->height
                );
                break;
        }
        // @codingStandardsIgnoreEnd

        $this->image = $resampled;

        return $this;
    }

    /**
     * Creates an empty canvas.
     *
     * If no arguments are passed and we have previously created an
     * image it will create a new canvas with the previous canvas size.
     * Due to this, you can use this method to "empty" the current canvas.
     *
     * @param  int $width  Canvas width.
     * @param  int $height Canvas height.
     * @return Image
     */
    public function create($width = null, $height = null)
    {
        if (!isset($width)) {
            if (!isset($this->width, $this->height)) {
                throw new InvalidArgumentException("You must set the canvas size.");
            }
            $width = $this->width;
            $height = $this->height;
        }

        if (!isset($height)) {
            $height = $width;
        }

        $this->image = $this->imagecreate($width, $height);
        $exif = null;
        $this->metadata = compact('width', 'height', 'exif');

        $this->updateSize();

        return $this;
    }

    /**
     * Creates an empty canvas.
     *
     * @param  int  $width         Canvas width.
     * @param  int  $height        Canvas height.
     * @param  bool $transparency  Whether or not to set transparency values.
     * @return resource            Image resource with the canvas.
     */
    protected function imagecreate($width, $height, $transparency = true)
    {
        $image = imagecreatetruecolor($width, $height);

        if ($transparency) {
            // Required for transparencies
            $bgcolor = imagecolortransparent(
                $image,
                imagecolorallocatealpha($image, 255, 255, 255, 127)
            );
            imagefill($image, 0, 0, $bgcolor);
            imagesavealpha($image, true);
            imagealphablending($image, true);
        }

        return $image;
    }

    /**
     * Helper method for all resize methods and others that require
     * imagecopyresampled method.
     *
     * @param  int  $dstW New width.
     * @param  int  $dstH New height.
     * @param  int  $srcX Starting source point X.
     * @param  int  $srcY Starting source point Y.
     * @return resource    GD image resource containing the resized image.
     */
    protected function imagecopy($dstW, $dstH, $srcX = 0, $srcY = 0, $srcW = false, $srcH = false)
    {
        $destImage = $this->imagecreate($dstW, $dstH);

        if ($srcW === false) {
            $srcW = $this->width;
        }

        if ($srcH === false) {
            $srcH = $this->height;
        }

        // @codingStandardsIgnoreStart
        imagecopyresampled(
            $destImage, $this->image,
            0, 0, $srcX, $srcY,
            $dstW, $dstH, $srcW, $srcH
        );
        // @codingStandardsIgnoreEnd

        return $destImage;
    }

    /**
     * Fills current canvas with specified color.
     *
     * It works with newly created canvas. If you want to overwrite the current
     * canvas you must first call `create` method to empty current canvas.
     *
     * @param  mixed $color The color. Check out getColorArray for allowed formats.
     * @return Image
     */
    public function fill($color = '#fff')
    {
        imagefill($this->image, 0, 0, $this->color($color));

        return $this;
    }

    /**
     * Allocates a color for the current image resource and returns it.
     *
     * Useful for directly treating images.
     *
     * @param  mixed $color The color. Check out getColorArray for allowed formats.
     * @return int
     * @codeCoverageIgnore
     */
    public function color($color)
    {
        $color = Normalize::color($color);

        if ($color['a'] !== 0) {
            return imagecolorallocatealpha($this->image, $color['r'], $color['g'], $color['b'], $color['a']);
        }

        return imagecolorallocate($this->image, $color['r'], $color['g'], $color['b']);
    }

    /**
     * Crops an image based on specified coords and size.
     *
     * You can pass arguments one by one or an array passing arguments
     * however you like.
     *
     * @param  int $x      X position where start to crop.
     * @param  int $y      Y position where start to crop.
     * @param  int $width  New width of the image.
     * @param  int $height New height of the image.
     * @return Image
     */
    public function crop($x, $y = null, $width = null, $height = null)
    {
        list($x, $y, $width, $height) = Normalize::crop($x, $y, $width, $height);

        $crop = $this->imagecreate($width, $height);

        // @codingStandardsIgnoreStart
        imagecopyresampled(
            $crop, $this->image,
            0, 0, $x, $y,
            $width, $height, $width, $height
        );
        // @codingStandardsIgnoreEnd

        $this->image = $crop;

        $this->updateSize();

        return $this;
    }

    /**
     * Blurs the image.
     *
     * @param  mixed   $type   Type of blur to be used between: gaussian, selective.
     * @param  integer $passes Number of times to apply the filter.
     * @return Image
     * @throws InvalidArgumentException
     */
    public function blur($type = null, $passes = 1)
    {
        switch (strtolower($type)) {
            case IMG_FILTER_GAUSSIAN_BLUR:
            case 'selective':
                $type = IMG_FILTER_GAUSSIAN_BLUR;
                break;

            // gaussian by default (just because I like it more)
            case null:
            case 'gaussian':
            case IMG_FILTER_SELECTIVE_BLUR:
                $type = IMG_FILTER_SELECTIVE_BLUR;
                break;

            default:
                throw new InvalidArgumentException("Incorrect blur type \"%s\"", $type);
        }

        for ($i = 0; $i < Normalize::fitInRange($passes, 1); $i++) {
            imagefilter($this->image, $type);
        }

        return $this;
    }

    /**
     * Changes the brightness of the image.
     *
     * @param  integer $level Brightness value; range between -255 & 255.
     * @return Image
     */
    public function brightness($level)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_BRIGHTNESS,
            Normalize::fitInRange($level, -255, 255)
        );

        return $this;
    }

    /**
     * Like grayscale, except you can specify the color.
     *
     * @param  mixed  $color Color in any format accepted by Normalize::color
     * @return Image
     */
    public function colorize($color)
    {
        $color = Normalize::color($color);

        imagefilter(
            $this->image,
            IMG_FILTER_COLORIZE,
            $color['r'],
            $color['g'],
            $color['b'],
            $color['a']
        );

        return $this;
    }

    /**
     * Changes the contrast of the image.
     *
     * @param  integer $level Use for adjunting level of contrast (-100 to 100)
     * @return Image
     */
    public function contrast($level)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_CONTRAST,
            Normalize::fitInRange($level, -100, 100)
        );

        return $this;
    }

    /**
     * Uses edge detection to highlight the edges in the image.
     *
     * @return Image
     */
    public function edgeDetection()
    {
        imagefilter($this->image, IMG_FILTER_EDGEDETECT);

        return $this;
    }

    /**
     * Embosses the image.
     *
     * @return Image
     */
    public function emboss()
    {
        imagefilter($this->image, IMG_FILTER_EMBOSS);

        return $this;
    }

    /**
     * Applies grayscale filter.
     *
     * @return Image
     */
    public function grayscale()
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);

        return $this;
    }

    /**
     * Uses mean removal to achieve a "sketchy" effect.
     *
     * @return Image
     */
    public function meanRemove()
    {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);

        return $this;
    }

    /**
     * Reverses all colors of the image.
     *
     * @return Image
     */
    public function negate()
    {
        imagefilter($this->image, IMG_FILTER_NEGATE);

        return $this;
    }

    /**
     * Pixelates the image.
     *
     * @param  int  $blockSize Block size in pixels.
     * @param  bool $advanced  Set to true to enable advanced pixelation.
     * @return Image
     */
    public function pixelate($blockSize = 3, $advanced = false)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_PIXELATE,
            Normalize::fitInRange($blockSize, 1),
            $advanced
        );

        return $this;
    }

    /**
     * A combination of various effects to achieve a sepia like effect.
     *
     * TODO: Create an additional class with instagram-like effects and move it there.
     *
     * @param  int   $alpha Defines the transparency of the effect: from 0 to 100
     * @return Image
     */
    public function sepia($alpha = 0)
    {
        return $this
            ->grayscale()
            ->contrast(-3)
            ->brightness(-15)
            ->colorize([
                'r' => 100,
                'g' => 70,
                'b' => 50,
                'a' => Normalize::fitInRange($alpha, 0, 100)
            ])
        ;
    }

    /**
     * Makes the image smoother.
     *
     * @param  int   $level Level of smoothness, between -15 and 15.
     * @return Image
     */
    public function smooth($level)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_SMOOTH,
            Normalize::fitInRange($level, -15, 15)
        );

        return $this;
    }

    /**
     * Adds a vignette to image.
     *
     * @param  float  $size  Size of the vignette, between 0 and 10. Low is sharper.
     * @param  float  $level Vignete transparency, between 0 and 1
     * @return Image
     * @link   http://php.net/manual/en/function.imagefilter.php#109809
     */
    public function vignette($size = 0.7, $level = 0.8)
    {
        for ($x = 0; $x < $this->width; ++$x) {
            for ($y = 0; $y < $this->height; ++$y) {
                $index = imagecolorat($this->image, $x, $y);
                $rgb = imagecolorsforindex($this->image, $index);

                $this->vignetteEffect($size, $level, $x, $y, $rgb);
                $color = imagecolorallocate($this->image, $rgb['red'], $rgb['green'], $rgb['blue']);

                imagesetpixel($this->image, $x, $y, $color);
            }
        }

        return $this;
    }

    /**
     * Sets quality for gif and jpg files.
     *
     * @param int $quality A value from 0 (zero quality) to 100 (max quality).
     * @return Image
     * @codeCoverageIgnore
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Sets compression for png files.
     *
     * @param int $compression A value from 0 (no compression, not recommended) to 9.
     * @return Image
     * @codeCoverageIgnore
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;

        return $this;
    }

    /**
     * Allows you to set the current image resource.
     *
     * This is intented for use it in conjuntion with getImage.
     *
     * @param resource $image Image resource to be set.
     * @throws Exception      If given image is not a GD resource.
     * @return Image
     */
    public function setImage($image)
    {
        if (!is_resource($image) || !get_resource_type($image) == 'gd') {
            throw new Exception("Given image is not a GD image resource");
        }

        $this->image = $image;
        $this->updateSize();

        return $this;
    }

    /**
     * Useful method to calculate real crop measures. Used when you crop an image
     * which is smaller than the original one. In those cases you can call
     * calculateCropMeasures to retrieve the real $ox, $oy, $dx & $dy of the
     * image to be cropped.
     *
     * Note that you need to set the destiny image and pass the smaller (cropped)
     * image to this function.
     *
     * @param  string|Image $croppedFile The cropped image.
     * @param  mixed        $ox          Origin X.
     * @param  int          $oy          Origin Y.
     * @param  int          $dx          Destiny X.
     * @param  int          $dy          Destiny Y.
     * @return array
     */
    public function calculateCropMeasures($croppedFile, $ox, $oy = null, $dx = null, $dy = null)
    {
        list($ox, $oy, $dx, $dy) = Normalize::cropMeasures($ox, $oy, $dx, $dy);

        if (!($croppedFile instanceof self)) {
            $croppedFile = new self($croppedFile);
        }

        $meta = $croppedFile->getMetadata();

        $rateWidth = $this->width / $meta['width'];
        $rateHeight = $this->height / $meta['height'];

        $ox = round($ox * $rateWidth);
        $oy = round($oy * $rateHeight);
        $dx = round($dx * $rateHeight);
        $dy = round($dy * $rateHeight);

        $width = $dx - $ox;
        $height = $dy - $oy;

        return [$ox, $oy, $dx, $dy, $width, $height];
    }

    /**
     * Returns image resource, so you can use it however you wan.
     *
     * @return resource
     * @codeCoverageIgnore
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Returns metadata for current image.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets metadata information from given $filename.
     *
     * @param  string $filename File path
     * @return array
     */
    public static function getMetadataFromFile($filename)
    {
        $info = getimagesize($filename);

        $metadata = [
            'width'  => $info[0],
            'height' => $info[1],
            'mime'   => $info['mime'],
            'exif'   => null // set later, if necessary
        ];

        if (function_exists('exif_read_data') && $metadata['mime'] == 'image/jpeg') {
            $metadata['exif'] = @exif_read_data($filename);
        }

        return $metadata;
    }

    /**
     * Loads metadata to internal variables.
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function getMetadataForImage()
    {
        $this->metadata = $this->getMetadataFromFile($this->filename);

        $this->width = $this->metadata['width'];
        $this->height = $this->metadata['height'];
    }

    /**
     * Gets mime for an image from its extension.
     *
     * @param  string $filename Filename to be checked.
     * @return string           Mime for the filename given.
     * @throws InvalidExtensionException
     */
    protected function getMimeFromExtension($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                throw new InvalidExtensionException($extension);
        }
    }

    /**
     * Updates current image metadata.
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function updateMetadata()
    {
        $this->metadata['width'] = $this->width;
        $this->metadata['height'] = $this->height;
    }

    /**
     * Resets width and height of the current image.
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function updateSize()
    {
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);

        $this->updateMetadata();
    }

    /**
     * Required by vignette to generate the propper colors.
     *
     * @param  float  $size  Size of the vignette, between 0 and 10. Low is sharper.
     * @param  float  $level Vignete transparency, between 0 and 1
     * @param  int    $x     X position of the pixel.
     * @param  int    $y     Y position of the pixel.
     * @param  array  &$rgb  Current pixel olor information.
     * @return void
     * @codeCoverageIgnore
     */
    protected function vignetteEffect($size, $level, $x, $y, &$rgb)
    {
        $l = sin(M_PI / $this->width * $x) * sin(M_PI / $this->height * $y);
        $l = pow($l, Normalize::fitInRange($size, 0, 10));

        $l = 1 - Normalize::fitInRange($level, 0, 1) * (1 - $l);

        $rgb['red'] *= $l;
        $rgb['green'] *= $l;
        $rgb['blue'] *= $l;
    }
}
