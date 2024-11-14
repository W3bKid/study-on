<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Form\RegistrationFormType;
use App\Repository\CourseRepository;
use App\Security\BillingAuthenticator;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/profile', name: 'app_user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $transactions = $this->billingClient->getTransactions($user->getApiToken());
        $userInformation = $this->billingClient->currentUser($user->getApiToken());

        return $this->render('security/profile.html.twig', [
            'user' => $userInformation,
            'transactions' => $transactions,
        ]);
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        BillingAuthenticator $billingAuthenticator,
    ): Response {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('email')->getData();
            $password = $form->get('password')->getData();
            try {
                $user = $this->billingClient->register($username, $password);
                $userInformation = $this->billingClient->currentUser($user->getApiToken());
                $user->setEmail($username)
                    ->setBalance($userInformation->getBalance())
                    ->setRoles($userInformation->getRoles());
            } catch (\Exception $e) {
                if ($e instanceof  BillingUnavailableException) {
                    $error = 'Сервис временно недоступен. Попробуйте зайти позже';
                } else {
                    $error = $e->getMessage();
                }
                return $this->render('security/register.html.twig', [
                    'registerForm' => $form->createView(),
                    'error' => $error,
                    'form' => $form,
                ]);
            }

            return $userAuthenticator->authenticateUser($user, $billingAuthenticator, $request);

            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
            'error' => null
        ]);
    }

    #[Route(path: '/profile/transactions', name: 'app_user_transactions', methods: ['GET'])]
    public function userTransactions(CourseRepository $courseRepository)
    {
        $transactions = $this->billingClient->getTransactions($this->getUser()->getApiToken());

        $transactions = json_decode($transactions, true);

        return $this->render('security/transactions.html.twig', [
            'transactions' => $transactions,
        ]);
    }
}
