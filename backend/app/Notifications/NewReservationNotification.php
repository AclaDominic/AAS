<?php

namespace App\Notifications;

use App\Models\CourtReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReservationNotification extends Notification
{
    use Queueable;

    protected $reservation;

    /**
     * Create a new notification instance.
     */
    public function __construct(CourtReservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Send to database immediately for real-time notifications
        // Email can be queued separately if needed
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation->loadMissing('user');
        $user = $reservation->user;
        
        // Generate reference number (RES-YYYYMMDD-ID)
        $referenceNumber = 'RES-' . $reservation->created_at->format('Ymd') . '-' . str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
        
        // Format category name
        $categoryName = $reservation->category === 'GYM' ? 'Gym' : 'Badminton Court';
        
        // Format date and time
        $reservationDate = $reservation->reservation_date->format('F d, Y');
        $startTime = $reservation->start_time->format('g:i A');
        $endTime = $reservation->end_time->format('g:i A');
        
        // Format duration
        $hours = floor($reservation->duration_minutes / 60);
        $minutes = $reservation->duration_minutes % 60;
        $duration = '';
        if ($hours > 0) {
            $duration = $hours . ' hour' . ($hours > 1 ? 's' : '');
            if ($minutes > 0) {
                $duration .= ' and ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            }
        } else {
            $duration = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        
        // Format status
        $statusBadge = match($reservation->status) {
            'PENDING' => '⏳ Pending Approval',
            'CONFIRMED' => '✅ Confirmed',
            'COMPLETED' => '✓ Completed',
            'CANCELLED' => '❌ Cancelled',
            default => $reservation->status,
        };

        $mail = (new MailMessage)
            ->subject("New {$categoryName} Reservation - Reference: {$referenceNumber}")
            ->greeting('Hello Admin,')
            ->line("A new reservation has been created and requires your attention.")
            ->line('**Reservation Details:**')
            ->line("**Reference Number:** {$referenceNumber}")
            ->line("**Member Name:** {$user->name}")
            ->line("**Member Email:** {$user->email}")
            ->line("**Category:** {$categoryName}")
            ->line("**Date:** {$reservationDate}")
            ->line("**Time:** {$startTime} - {$endTime}")
            ->line("**Duration:** {$duration}");

        // Add court number if it's a badminton court reservation
        if ($reservation->category === 'BADMINTON_COURT' && $reservation->court_number) {
            $mail->line("**Court Number:** Court {$reservation->court_number}");
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $mail->line("**Status:** {$statusBadge}")
            ->action('View Reservation', rtrim($frontendUrl, '/') . '/admin/reservations')
            ->line('Please review and manage this reservation in the admin panel.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $reservation = $this->reservation->loadMissing('user');
        $referenceNumber = 'RES-' . $reservation->created_at->format('Ymd') . '-' . str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
        
        return [
            'reservation_id' => $reservation->id,
            'reference_number' => $referenceNumber,
            'user_id' => $reservation->user_id,
            'user_name' => $reservation->user->name ?? 'Unknown',
            'user_email' => $reservation->user->email ?? '',
            'category' => $reservation->category,
            'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
            'start_time' => $reservation->start_time->format('Y-m-d H:i:s'),
            'end_time' => $reservation->end_time->format('Y-m-d H:i:s'),
            'duration_minutes' => $reservation->duration_minutes,
            'status' => $reservation->status,
            'court_number' => $reservation->court_number,
        ];
    }
}
