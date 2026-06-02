<?php

namespace Nylo\LaravelFCM\Models;

use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Contracts\FcmNotifiable;
use Nylo\LaravelFCM\Jobs\FcmSendToTokensJob;

/**
 * Class FcmMessage
 */
class FcmMessage
{
    private ?string $title = null;

    private ?string $body = null;

    private ?string $image = null;

    private ?int $badge = null;

    private ?string $sound = null;

    private ?string $priority = null;

    /** @var array<string, mixed> */
    private array $data = [];

    private ?bool $withoutDefaultSound = null;

    /**
     * Create a new FcmMessage instance from an array.
     *
     * @param  array<string, mixed>  $message
     */
    public static function createFromArray(array $message): self
    {
        $fcmMessage = new FcmMessage;

        if (isset($message['title']) && is_string($message['title'])) {
            $fcmMessage->title($message['title']);
        }

        if (isset($message['body']) && is_string($message['body'])) {
            $fcmMessage->body($message['body']);
        }

        if (isset($message['image']) && is_string($message['image'])) {
            $fcmMessage->image($message['image']);
        }

        if (isset($message['badge']) && (is_int($message['badge']) || is_string($message['badge']))) {
            $fcmMessage->badge($message['badge']);
        }

        if (isset($message['sound']) && is_string($message['sound'])) {
            $fcmMessage->sound($message['sound']);
        }

        if (isset($message['priority'])) {
            if ($message['priority'] === 'highest') {
                $fcmMessage->priorityHighest();
            } elseif ($message['priority'] === 'lowest') {
                $fcmMessage->priorityLowest();
            }
        }

        if (isset($message['data']) && is_array($message['data'])) {
            $data = [];
            foreach ($message['data'] as $key => $value) {
                $data[(string) $key] = $value;
            }
            $fcmMessage->data($data);
        }

        if (isset($message['withoutDefaultSound'])) {
            $fcmMessage->withoutDefaultSound();
        }

        return $fcmMessage;
    }

    /**
     * Set the title of the message.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the body of the message.
     */
    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the image of the message.
     */
    public function image(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Set the badge of the message.
     *
     * Accepts an int or a (numeric) string and stores it as an int, so callers
     * may pass values coming straight from request input or JSON payloads.
     */
    public function badge(int|string $badge): self
    {
        $this->badge = (int) $badge;

        return $this;
    }

    /**
     * Set the sound of the message.
     */
    public function sound(string $sound): self
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Set the data of the message.
     *
     * @param  array<string, mixed>  $data
     */
    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the message to be sent without the default sound.
     */
    public function withoutDefaultSound(): self
    {
        $this->withoutDefaultSound = true;

        return $this;
    }

    /**
     * Set the message priority to high.
     */
    public function priorityHighest(): self
    {
        $this->priority = 'highest';

        return $this;
    }

    /**
     * Set the message priority to normal.
     */
    public function priorityLowest(): self
    {
        $this->priority = 'lowest';

        return $this;
    }

    /**
     * Dispatch this message to an arbitrary list of FCM tokens.
     *
     * Tokens may span many notifiables or come from outside the
     * `fcm_devices` table. The queued job chunks into batches of 500.
     *
     * @param  array<int, string>  $tokens
     */
    public function sendToTokens(array $tokens): void
    {
        FcmSendToTokensJob::dispatch($this, $tokens);
    }

    /**
     * Dispatch this message to every active device across a set of notifiables.
     *
     * Pools all active tokens into a single queued multicast job, unlike
     * Laravel's Notification::send() which dispatches per notifiable.
     *
     * @param  iterable<mixed>  $notifiables
     */
    public function sendToNotifiables(iterable $notifiables): void
    {
        $tokens = [];
        $skipped = 0;

        foreach ($notifiables as $notifiable) {
            if (! $notifiable instanceof FcmNotifiable) {
                $skipped++;

                continue;
            }

            $fcmTokens = $notifiable->fcmDevices()
                ->active()
                ->withPushToken()
                ->pluck('fcm_token');

            foreach ($fcmTokens as $token) {
                if (is_string($token) && $token !== '') {
                    $tokens[] = $token;
                }
            }
        }

        if ($skipped > 0) {
            Log::warning('Laravel FCM Channel: skipped '.$skipped.' notifiable(s) that do not implement '.FcmNotifiable::class.'; they received no FCM notifications. Implement the contract on your notifiable model (the HasFcmDevices trait already satisfies it).');
        }

        $this->sendToTokens($tokens);
    }

    /**
     * Get the message as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $message = [];

        if (! empty($this->title)) {
            $message['title'] = $this->title;
        }

        if (! empty($this->body)) {
            $message['body'] = $this->body;
        }

        if (! empty($this->image)) {
            $message['image'] = $this->image;
        }

        if (! empty($this->badge)) {
            $message['badge'] = $this->badge;
        }

        if (! empty($this->sound)) {
            $message['sound'] = $this->sound;
        }

        if (! empty($this->priority)) {
            $message['priority'] = $this->priority;
        }

        if (! empty($this->data)) {
            $message['data'] = $this->data;
        }

        if (! empty($this->withoutDefaultSound)) {
            $message['withoutDefaultSound'] = $this->withoutDefaultSound;
        }

        return $message;
    }
}
