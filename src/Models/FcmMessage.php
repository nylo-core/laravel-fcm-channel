<?php

namespace Nylo\LaravelFCM\Models;

/**
 * Class FcmMessage
 *
 * @package Nylo\LaravelFCM\Models
 */
class FcmMessage {

    private $title;
    private $body;
    private $image;
    private $badge;
    private $sound;
    private $priority;
    private $data = [];
    private $withoutDefaultSound;

    /**
     * Create a new FcmMessage instance from an array.
     *
     * @param $message
     * @return FcmMessage
     */
    static function createFromArray($message)
    {
        $fcmMessage = new FcmMessage();

        if (isset($message['title'])) {
            $fcmMessage->title($message['title']);
        }

        if (isset($message['body'])) {
            $fcmMessage->body($message['body']);
        }

        if (isset($message['image'])) {
            $fcmMessage->image($message['image']);
        }

        if (isset($message['badge'])) {
            $fcmMessage->badge($message['badge']);
        }

        if (isset($message['sound'])) {
            $fcmMessage->sound($message['sound']);
        }

        if (isset($message['priority'])) {
            if ($message['priority'] == 'highest') {
                $fcmMessage->priorityHighest();
            } else if ($message['priority'] == 'lowest') {
                $fcmMessage->priorityLowest();
            }
        }

        if (isset($message['data'])) {
            $fcmMessage->data($message['data']);
        }

        if (isset($message['withoutDefaultSound'])) {
            $fcmMessage->withoutDefaultSound();
        }

        return $fcmMessage;
    }

    /**
     * Set the title of the message.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the body of the message.
     *
     * @param string $body
     *
     * @return $this
     */
    public function body($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the image of the message.
     *
     * @param string $image
     *
     * @return $this
     */
    public function image($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Set the badge of the message.
     *
     * @param int $badge
     *
     * @return $this
     */
    public function badge($badge)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Set the sound of the message.
     *
     * @param string $sound
     *
     * @return $this
     */
    public function sound($sound)
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Set the data of the message.
     *
     * @param array $data
     *
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the message to be sent without the default sound.
     *
     * @return $this
     */
    public function withoutDefaultSound()
    {
        $this->withoutDefaultSound = true;

        return $this;
    }

    /**
     * Set the message priority to high.
     *
     * @return $this
     */
    public function priorityHighest()
    {
        $this->priority = 'highest';

        return $this;
    }

    /**
     * Set the message priority to normal.
     *
     * @return $this
     */
    public function priorityLowest()
    {
        $this->priority = 'lowest';

        return $this;
    }

    /**
     * Get the message as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $message = [];

        if (!empty($this->title)) {
            $message['title'] = $this->title;
        }

        if (!empty($this->body)) {
            $message['body'] = $this->body;
        }

        if (!empty($this->image)) {
            $message['image'] = $this->image;
        }

        if (!empty($this->badge)) {
            $message['badge'] = $this->badge;
        }

        if (!empty($this->sound)) {
            $message['sound'] = $this->sound;
        }

        if (!empty($this->priority)) {
            $message['priority'] = $this->priority;
        }

        if (!empty($this->data)) {
            $message['data'] = $this->data;
        }

        if (!empty($this->withoutDefaultSound)) {
            $message['withoutDefaultSound'] = $this->withoutDefaultSound;
        }

        return $message;
    }
}
