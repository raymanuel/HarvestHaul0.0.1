<?php

namespace App\Notifications;

use App\Models\PoolingJob;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeatherChangeAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PoolingJob $job,
        public string $changeType,
        public string $message,
        public ?string $previousWeather = null,
        public ?string $currentWeather = null,
        public ?float $previousTemp = null,
        public ?float $currentTemp = null,
        public ?float $previousWind = null,
        public ?float $currentWind = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severityEmoji = match ($this->changeType) {
            'severe_weather' => '⚠️',
            'weather_cleared' => '✅',
            'rain_intensified' => '🌧️',
            'wind_increase' => '💨',
            'temperature_extreme' => '🌡️',
            default => '📡',
        };

        $mail = (new MailMessage)
            ->subject("{$severityEmoji} Weather Change Alert — Route #{$this->job->id}")
            ->line("Weather conditions have changed on Route #{$this->job->id}.")
            ->line("**Change:** {$this->changeType}")
            ->line("**Details:** {$this->message}");

        if ($this->previousWeather && $this->currentWeather) {
            $mail->line("**Previous:** {$this->previousWeather}, {$this->previousTemp}°C, {$this->previousWind} km/h wind")
                 ->line("**Current:** {$this->currentWeather}, {$this->currentTemp}°C, {$this->currentWind} km/h wind");
        }

        $mail->line("**Job:** {$this->job->title}")
             ->action('View Route', url("/driver/routes/{$this->job->id}"))
             ->line('Drive safely and check your route for updates.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'change_type' => $this->changeType,
            'message' => $this->message,
            'previous_weather' => $this->previousWeather,
            'current_weather' => $this->currentWeather,
            'previous_temp' => $this->previousTemp,
            'current_temp' => $this->currentTemp,
            'previous_wind' => $this->previousWind,
            'current_wind' => $this->currentWind,
            'driver_id' => $this->job->driver_id,
            'logistics_profile_id' => $this->job->logistics_profile_id,
            'timestamp' => now()->toISOString(),
        ];
    }
}
