<?php

namespace FastImage;

/**
 * FastImage - Because sometimes you just want the size!
 * Based on the Ruby Implementation by Steven Sykes (https://github.com/sdsykes/fastimage)
 *
 * Copyright (c) 2012
 * @version 0.1
 */
class FastImage
{
    /**
     * @var integer
     */
    private $strpos = 0;

    /**
     * @var string
     */
    private $str;

    /**
     * @var string
     */
    private $type;

    /**
     * @var #resource
     */
    private $handle;

    /**
     * Initialize object.
     *
     * @param string $uri
     */
    public function __construct($uri = null)
    {
        if ($uri) {
            $this->load($uri);
        }
    }

    /**
     * Load resource.
     *
     * @param  string $uri
     * @return void
     */
    public function load($uri)
    {
        if ($this->handle) {
            $this->close();
        }

        $this->handle = fopen($uri, 'r');
    }

    /**
     * Close resource.
     *
     * @return void
     */
    public function close()
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
            $this->type   = null;
            $this->str    = null;
        }
    }

    /**
     * Get image size.
     *
     * @return mixed
     */
    public function getSize()
    {
        $this->strpos = 0;
        if ($this->getType()) {
            return array_values($this->parseSize());
        }

        return false;
    }

    /**
     * Get image type.
     *
     * @return mixed
     */
    public function getType()
    {
        $this->strpos = 0;

        if (!$this->type) {
            switch ($this->getChars(2)) {
                case "BM":
                    return $this->type = 'bmp';
                case "GI":
                    return $this->type = 'gif';
                case chr(0xFF).chr(0xd8):
                    return $this->type = 'jpeg';
                case chr(0x89).'P':
                    return $this->type = 'png';
                default:
                    return false;
            }
        }

        return $this->type;
    }

    /**
     * Parse image size.
     *
     * @return mixed
     */
    private function parseSize()
    {
        $this->strpos = 0;

        $methodName = 'parseSizeFor'.strtoupper($this->type);

        if (function_exists($methodName)) {
            return null;
        }

        return $this->{$methodName}();
    }

    /**
     * Parse PNG images size.
     *
     * @return array
     */
    private function parseSizeForPNG()
    {
        $chars = $this->getChars(25);

        return unpack("N*", substr($chars, 16, 8));
    }

    /**
     * Parse GIF images size.
     *
     * @return array
     */
    private function parseSizeForGIF()
    {
        $chars = $this->getChars(11);

        return unpack("S*", substr($chars, 6, 4));
    }

    /**
     * Parse BPM images size.
     *
     * @return array
     */
    private function parseSizeForBMP()
    {
        $chars = $this->getChars(29);
        $chars = substr($chars, 14, 14);
        $type  = unpack('C', $chars);

        return (reset($type) == 40) ?
            unpack('L*', substr($chars, 4)) :
            unpack('L*', substr($chars, 4, 8));
    }

    /**
     * Parse JPG images size.
     *
     * @return array
     */
    private function parseSizeForJPEG()
    {
        $state = null;
        $i     = 0;

        while (true) {
            switch ($state) {
                default:
                    $this->getChars(2);
                    $state = 'started';
                    break;

                case 'started':
                    $b = $this->getByte();
                    if ($b === false) {
                        return false;
                    }

                    $state = $b == 0xFF ? 'sof' : 'started';
                    break;

                case 'sof':
                    $b = $this->getByte();
                    if (in_array($b, range(0xe0, 0xef))) {
                        $state = 'skipframe';
                    } elseif (in_array($b, array_merge(range(0xC0, 0xC3), range(0xC5, 0xC7), range(0xC9, 0xCB), range(0xCD, 0xCF)))) {
                        $state = 'readsize';
                    } elseif ($b == 0xFF) {
                        $state = 'sof';
                    } else {
                        $state = 'skipframe';
                    }
                    break;

                case 'skipframe':
                    $skip  = $this->readInt($this->getChars(2)) - 2;
                    $state = 'doskip';
                    break;

                case 'doskip':
                    $this->getChars($skip);
                    $state = 'started';
                    break;

                case 'readsize':
                    $c = $this->getChars(7);

                    return [$this->readInt(substr($c, 5, 2)), $this->readInt(substr($c, 3, 2))];
            }
        }
    }

    /**
     * Get chars from image data.
     *
     * @param  integer $n
     * @return mixed
     */
    private function getChars($n)
    {
        $response = null;

        // do we need more data?
        if ($this->strpos + $n - 1 >= strlen($this->str)) {
            $end = ($this->strpos + $n);

            while (strlen($this->str) < $end && $response !== false) {
                // read more from the file handle
                $need = $end - ftell($this->handle);

                if ($response = fread($this->handle, $need)) {
                    $this->str .= $response;
                } else {
                    return false;
                }
            }
        }

        $result = substr($this->str, $this->strpos, $n);
        $this->strpos += $n;

        return $result;
    }

    /**
     * Get image byte.
     *
     * @return mixed
     */
    private function getByte()
    {
        $c = $this->getChars(1);
        $b = unpack("C", $c);

        return reset($b);
    }

    /**
     * Return char integer.
     *
     * @param  string $str
     * @return integer
     */
    private function readInt($str)
    {
        $size = unpack("C*", $str);

        return ($size[1] << 8) + $size[2];
    }

    /**
     * Destruct object.
     */
    public function __destruct()
    {
        $this->close();
    }
}
