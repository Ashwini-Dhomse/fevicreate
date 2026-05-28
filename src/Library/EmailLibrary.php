<?php

namespace App\Library;

use Pimcore\Mail;
use Twig\Environment;

class EmailLibrary
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function sendEmail($subject, $htmlContent, $to, $cc = '')
    {
        if (trim($to) == '') {
            return false;
        }

        try {
            $mail = new Mail();
            $mail->to($to);

            if (!empty($cc)) {
                $ccEmails = array_map('trim', explode(',', $cc));

                foreach ($ccEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        if (trim($email) != trim($to)) {
                            $mail->addBcc($email);
                        }
                    }
                }
            }

            $mail->subject($subject);
            $mail->html($htmlContent);

            return $mail->send();

        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendRegistrationEmail($data)
    {
        $html = $this->twig->render('emails/registration.html.twig', [
            'name' => $data['name'] ?? '',
            'email' => $data['to'] ?? '',
        ]);

        return $this->sendEmail(
            'Registration Complete',
            $html,
            $data['to']
        );
    }
}
