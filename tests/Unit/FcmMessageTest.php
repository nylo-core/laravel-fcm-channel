<?php

namespace Nylo\LaravelFCM\Test\Unit;

use Nylo\LaravelFCM\Models\FcmMessage;
use PHPUnit\Framework\TestCase;

class FcmMessageTest extends TestCase
{
    public function test_can_set_title()
    {
        $message = new FcmMessage;
        $result = $message->title('Test Title');

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals('Test Title', $message->toArray()['title']);
    }

    public function test_can_set_body()
    {
        $message = new FcmMessage;
        $result = $message->body('Test Body');

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals('Test Body', $message->toArray()['body']);
    }

    public function test_can_set_image()
    {
        $message = new FcmMessage;
        $result = $message->image('https://example.com/image.png');

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals('https://example.com/image.png', $message->toArray()['image']);
    }

    public function test_can_set_badge()
    {
        $message = new FcmMessage;
        $result = $message->badge(5);

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals(5, $message->toArray()['badge']);
    }

    public function test_can_set_sound()
    {
        $message = new FcmMessage;
        $result = $message->sound('notification.wav');

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals('notification.wav', $message->toArray()['sound']);
    }

    public function test_can_set_data()
    {
        $message = new FcmMessage;
        $data = ['key' => 'value', 'foo' => 'bar'];
        $result = $message->data($data);

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals($data, $message->toArray()['data']);
    }

    public function test_can_set_priority_highest()
    {
        $message = new FcmMessage;
        $result = $message->priorityHighest();

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals('highest', $message->toArray()['priority']);
    }

    public function test_can_set_priority_lowest()
    {
        $message = new FcmMessage;
        $result = $message->priorityLowest();

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertEquals('lowest', $message->toArray()['priority']);
    }

    public function test_can_disable_default_sound()
    {
        $message = new FcmMessage;
        $result = $message->withoutDefaultSound();

        $this->assertInstanceOf(FcmMessage::class, $result);
        $this->assertTrue($message->toArray()['withoutDefaultSound']);
    }

    public function test_to_array_returns_all_properties()
    {
        $message = (new FcmMessage)
            ->title('Title')
            ->body('Body')
            ->image('https://example.com/image.png')
            ->badge(3)
            ->sound('custom.wav')
            ->data(['key' => 'value'])
            ->priorityHighest()
            ->withoutDefaultSound();

        $array = $message->toArray();

        $this->assertEquals('Title', $array['title']);
        $this->assertEquals('Body', $array['body']);
        $this->assertEquals('https://example.com/image.png', $array['image']);
        $this->assertEquals(3, $array['badge']);
        $this->assertEquals('custom.wav', $array['sound']);
        $this->assertEquals(['key' => 'value'], $array['data']);
        $this->assertEquals('highest', $array['priority']);
        $this->assertTrue($array['withoutDefaultSound']);
    }

    public function test_to_array_excludes_empty_properties()
    {
        $message = new FcmMessage;
        $array = $message->toArray();

        $this->assertEmpty($array);
    }

    public function test_create_from_array()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Test Body',
            'image' => 'https://example.com/image.png',
            'badge' => 2,
            'sound' => 'alert.wav',
            'priority' => 'highest',
            'data' => ['foo' => 'bar'],
            'withoutDefaultSound' => true,
        ];

        $message = FcmMessage::createFromArray($data);
        $array = $message->toArray();

        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals('Test Body', $array['body']);
        $this->assertEquals('https://example.com/image.png', $array['image']);
        $this->assertEquals(2, $array['badge']);
        $this->assertEquals('alert.wav', $array['sound']);
        $this->assertEquals('highest', $array['priority']);
        $this->assertEquals(['foo' => 'bar'], $array['data']);
        $this->assertTrue($array['withoutDefaultSound']);
    }

    public function test_create_from_array_with_lowest_priority()
    {
        $data = ['priority' => 'lowest'];

        $message = FcmMessage::createFromArray($data);
        $array = $message->toArray();

        $this->assertEquals('lowest', $array['priority']);
    }

    public function test_fluent_chaining()
    {
        $message = (new FcmMessage)
            ->title('Title')
            ->body('Body')
            ->image('image.png')
            ->badge(1)
            ->sound('sound.wav')
            ->data(['a' => 'b'])
            ->priorityHighest()
            ->withoutDefaultSound();

        $this->assertInstanceOf(FcmMessage::class, $message);
    }
}
