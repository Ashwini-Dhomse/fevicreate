<?php

namespace App\Controller;

use App\Service\OtpService;
use Pimcore\Model\DataObject\Customer; // change to your class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;

class AuthController extends AbstractController
{
    public function loginPage(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    public function requestOtp(Request $request, OtpService $otpService): Response
    {
        $email = $request->request->get('email');

        $user = Customer::getByEmail($email, 1); // change if needed
        if (!$user) {
            return new Response('User not found');
        }

        $otp = $otpService->generateOtp($email);

        // TODO: Send OTP via email/SMS here

        return $this->render('auth/verify.html.twig', [
            'email' => $email
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otpService, Security $security): Response
    {
        $email = $request->request->get('email');
        $otp = $request->request->get('otp');

        if (!$otpService->verifyOtp($email, $otp)) {
            return new Response('Invalid OTP');
        }

        $user = Customer::getByEmail($email, 1);

        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $security->setToken($token);

        return $this->redirect('/dashboard');
    }
}
