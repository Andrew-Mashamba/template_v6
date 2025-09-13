<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $password;
    protected $department;
    protected $role;

    public function __construct($user, $password, $department = null, $role = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->department = $department;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appName = config('app.name', 'SACCOS Management System');
        
        $message = (new MailMessage)
            ->subject('Your Account Has Been Created - ' . $appName)
            ->greeting('Dear ' . $this->user->name . ',')
            ->line('Your account has been successfully created in the ' . $appName . '.')
            ->line('Below are your login credentials:')
            ->line('**Email:** ' . $this->user->email)
            ->line('**Password:** ' . $this->password);
            
        // Add department and role information if available
        if ($this->department) {
            $message->line('**Department:** ' . $this->department);
        }
        
        if ($this->role) {
            $message->line('**Role:** ' . $this->role);
        }
        
        $message->line('For security reasons, please change your password immediately after your first login.')
            ->line('To access the system, please use the following link:')
            ->action('Login to System', url('/login'))
            ->line('If you have any questions or need assistance, please contact our support team.')
            ->line('Thank you for joining our team!');

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'message' => 'Account credentials sent to ' . $this->user->email,
        ];
    }
}