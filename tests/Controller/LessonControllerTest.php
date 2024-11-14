<?php

namespace App\Test\Controller;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Kernel;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\String\ByteString;

class LessonControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = "/lessons/";

    protected $databaseTool;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $this->auth();
        $this->manager = static::getContainer()->get("doctrine")->getManager();
        $this->repository = $this->manager->getRepository(Lesson::class);

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();
    }

    public function auth()
    {
        $crawler = $this->client->request('GET', '/login');
        $submitBtn = $crawler->selectButton('Sign in');

        $login = $submitBtn->form([
            'email' => 'admin@billing.ru',
            'password' => 12345678,
        ]);
        $this->client->submit($login);
        $this->client->followRedirect();
    }

    public function testNew(): void
    {
        $lessons = $this->repository->findAll();
        $course = $lessons[0]->getCourse();
        $courseId = $course->getId();

        $this->client->request(
            "GET",
            sprintf("%snew%s", $this->path, "?course_id=$courseId")
        );

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm("Сохранить", [
            "lesson[title]" => ByteString::fromRandom(16)->toString(),
            "lesson[content]" => ByteString::fromRandom(16)->toString(),
            "lesson[order_number]" => rand(1, 10),
        ]);

        self::assertResponseRedirects(sprintf("/courses/%s", $courseId));
        self::assertSame(count($lessons) + 1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $course = $this->manager->getRepository(Course::class)
            ->findOneBy(['character_code' => 'osnovy_lichnoj_finansovoj_gramotnosti']);
        $lesson = new Lesson();
        $lesson->setTitle(ByteString::fromRandom(16)->toString());
        $lesson->setContent(ByteString::fromRandom(16)->toString());
        $lesson->setOrderNumber(3);
        $lesson->setCourse($course);
        $this->manager->persist($lesson);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s", $this->path, $lesson->getId())
        );
        self::assertResponseStatusCodeSame(200);
    }

    public function testEdit(): void
    {
        $lesson = $this->repository->findAll([])[0];
        $lessonId = $lesson->getId();

        $this->client->request(
            "GET",
            sprintf("%s%s/edit", $this->path, $lessonId)
        );

        $orderNumber = rand(1, 100);

        $this->client->submitForm("Обновить", [
            "lesson[title]" => "Something New",
            "lesson[content]" => "Something New",
            "lesson[order_number]" => $orderNumber,
        ]);

        $updatedLesson = $this->repository->find($lessonId);

        self::assertResponseRedirects("/lessons/$lessonId");
        self::assertSame("Something New", $updatedLesson->getTitle());
        self::assertSame("Something New", $updatedLesson->getContent());
        self::assertSame($orderNumber, $updatedLesson->getOrderNumber());
    }

    public function testRemove(): void
    {
        $lessonsCount = $this->repository->count([]);
        $this->databaseTool->loadFixtures([CourseFixtures::class]);
        $course = $this->repository->find(1);
        $courseId = $course->getId();
        $this->client->request(
            "POST",
            sprintf("%s%s", $this->path, $course->getId())
        );
        self::assertResponseRedirects("/courses/$courseId");
    }

    public function testEmptyTitle(): void
    {
        $course = $this->manager->getRepository(Course::class)->findAll()[0];
        $courseId = $course->getId();

        $this->client->request(
            "GET",
            sprintf("%s%s%s", $this->path, "new", "?course_id=$courseId")
        );

        $this->client->submitForm("Сохранить", [
            "lesson[title]" => "",
            "lesson[content]" => "Something New",
            "lesson[order_number]" => rand(1, 10),
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testToLongTitle(): void
    {
        $course = $this->manager->getRepository(Course::class)->findAll()[0];
        $courseId = $course->getId();

        $this->client->request(
            "GET",
            sprintf("%s%s%s", $this->path, "new", "?course_id=$courseId")
        );

        $this->client->submitForm("Сохранить", [
            "lesson[title]" => "",
            "lesson[content]" => ByteString::fromRandom(300)->toString(),
            "lesson[order_number]" => rand(1, 10),
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testEmptyContent(): void
    {
        $course = $this->manager->getRepository(Course::class)->findAll()[0];
        $courseId = $course->getId();

        $this->client->request(
            "GET",
            sprintf("%s%s%s", $this->path, "new", "?course_id=$courseId")
        );

        $this->client->submitForm("Сохранить", [
            "lesson[title]" => ByteString::fromRandom(300)->toString(),
            "lesson[content]" => "",
            "lesson[order_number]" => rand(1, 10),
        ]);

        self::assertResponseIsUnprocessable();
    }
}
