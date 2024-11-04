<?php

namespace App\Tests\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Mock\BillingClientMock;

class SecurityControllerTest extends WebTestCase
{
    private const USER_CREDENTIALS = [
        'username' => 'admin@billing.ru',
        'password' => '12345678',
        'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
        'refresh_token' => 'ahpoim7Ohyiepi3e',
        'balance' => 0,
    ];



    private function billingClient()
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        return $client;
    }

    public function testLoginAndLogoutAndProfile(): void
    {
        $client = self::billingClient();
        $client->request('GET', '/');
        self::assertResponseStatusCodeSame(301);
        $crawler = $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        $authFormLink = $crawler->selectLink('Вход')->link();
        $crawler = $client->click($authFormLink);
        self::assertEquals('/login', $client->getRequest()->getPathInfo());
        self::assertResponseStatusCodeSame(200);
        $submitBtn = $crawler->selectButton('Sign in');
        $login = $submitBtn->form([
            'email' => self::USER_CREDENTIALS['username'],
            'password' => self::USER_CREDENTIALS['password'],
        ]);
        $client->submit($login);
        self::assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());

        $link = $crawler->selectLink('Профиль')->link();
        $crawler = $client->click($link);

        self::assertSelectorTextContains('.username', self::USER_CREDENTIALS['username']);
        self::assertSelectorTextContains('.balance', self::USER_CREDENTIALS['balance']);
        self::assertSelectorTextContains('.list-group-item', 'Администратор');

        $crawler = $client->request('GET', '/');
        $crawler = $client->followRedirect();

        $link = $crawler->selectLink('Выход')->link();
        $client->click($link);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseRedirects();
        $crawler = $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
    }

    public function testFailedLogin(): void
    {
        $client = self::billingClient();
        $crawler = $client->request('GET', '/login');
        $submitBtn = $crawler->selectButton('Sign in');
        $login = $submitBtn->form([
            'email' => 'afsdf',
            'password' => 'asdfasdf',
        ]);
        $client->submit($login);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'Неправильно введены логин или пароль'
        );
    }

    public function testFailedLoginEmpty(): void
    {
        $client = self::billingClient();
        $crawler = $client->request('GET', '/login');
        $submitBtn = $crawler->selectButton('Sign in');
        $login = $submitBtn->form([
            'password' => 'asdfasdf',
        ]);
        $client->submit($login);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'Неправильно введены логин или пароль'
        );
        $login = $submitBtn->form([
            'email' => 'asdfasdf',
        ]);
        $client->submit($login);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'Неправильно введены логин или пароль'
        );
    }

    public function testRegister()
    {
        $client = $this->billingClient();
        $client->request('GET', '/');
        $crawler = $client->followRedirect();

        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        self::assertResponseIsSuccessful();
        $reg = $crawler->selectButton('Сохранить')->form([
            'registration_form[email]' => "pishisyda@mail.ru",
            'registration_form[password][first]' => '12345678',
            'registration_form[password][second]' => '12345678',
        ]);
        $client->submit($reg);

        //TODO запрос попадает в метод currentUser, в котором захардкожены 2 пользователя.
        // Как лушче сделать, не создавая таблицу с пользователями
//        self::assertResponseRedirects();
//        $crawler = $client->followRedirect();
//        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
    }
}
