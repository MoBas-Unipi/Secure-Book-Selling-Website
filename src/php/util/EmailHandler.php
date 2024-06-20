<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Provides functionality to send emails using PHPMailer library.
 * It implements a singleton pattern to ensure a single instance is used throughout the application.
 */
class EmailHandler
{
    /**
     * @var EmailHandler|null The singleton instance of the EmailHandler.
     */
    private static ?EmailHandler $instance = null;

    /**
     * @var PHPMailer The PHPMailer instance used for sending emails.
     */
    private $mail;

    /**
     * EmailHandler constructor.
     *
     * This is a private constructor to prevent direct instantiation.
     * It initializes the PHPMailer instance with SMTP settings.
     */
    private function __construct()
    {
        require '/home/bookselling/composer/vendor/autoload.php';

        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'snhbookselling@gmail.com';
        $this->mail->Password = getenv("APACHE_EMAIL_SENDER_PASSWORD");
        $this->mail->SMTPSecure = 'ssl';
        $this->mail->Port = 465;
        $this->mail->setFrom('snhbookselling@gmail.com', 'bookselling');
    }

    /**
     * Returns the singleton instance of EmailHandler.
     *
     * @return EmailHandler|null The singleton instance.
     */
    public static function getInstance(): ?EmailHandler
    {
        if (self::$instance == null) {
            self::$instance = new EmailHandler();
        }

        return self::$instance;
    }

    /**
     * Sends an email.
     *
     * @param string $email The recipient email address.
     * @param string $subject The subject of the email.
     * @param string $title The title of the email (used in the email body as an H1 element).
     * @param string ...$paragraphs The paragraphs to include in the email body.
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public function sendEmail($email, $subject, $title, ...$paragraphs): bool
    {
        $this->mail->addAddress($email);
        $this->mail->isHTML(true);
        $this->mail->Subject = $subject;

        $body = '<h1>' . $title . '</h1>';
        foreach ($paragraphs as $paragraph) {
            $body .= '<p>' . $paragraph . '</p>';
        }

        $this->mail->Body = $body;

        if (!$this->mail->send())
            return false;

        return true;
    }
}
