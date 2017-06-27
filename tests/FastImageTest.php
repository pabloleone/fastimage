<?php

namespace FastImage;

use PHPUnit\Framework\TestCase;

class FastImageTest extends TestCase
{
    private $resource;

    public function setUp()
    {
        $this->resource = 'https://www.warbble.com/assets/front/img/logo.png';
    }

    /** @test */
    public function canCreateInstance()
    {
        $instance = new FastImage();

        $this->assertTrue($instance instanceof FastImage);

        $instance = new FastImage($this->resource);

        $this->assertTrue($instance instanceof FastImage);
    }

    /** @test */
    public function canLoadValidResource()
    {
        $instance = new FastImage();

        $this->assertEquals(null, $instance->load($this->resource));

        $instance = new FastImage($this->resource);

        $this->assertEquals(null, $instance->load($this->resource));
    }

    /** @test */
    public function cantLoadInvalidResource()
    {
        $instance = new FastImage();

        $this->assertEquals(null, @$instance->load('fakeUri'));

        $instance = new FastImage($this->resource);

        $this->assertEquals(null, @$instance->load('fakeUri'));
    }

    /** @test */
    public function canCloseResource($value='')
    {
        $instance = new FastImage($this->resource);

        $this->assertEquals(null, $instance->close());
    }
}
