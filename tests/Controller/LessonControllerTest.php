<?php

namespace App\Test\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get("doctrine")->getManager();
        $this->repository = $this->manager->getRepository(Lesson::class);
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request("GET", $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains("Lesson index");
        self::assertAnySelectorTextNotContains("td", "no records found");
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
        $course = $this->manager->getRepository(Course::class)->findAll()[0];

        $fixture = new Lesson();
        $fixture->setTitle(ByteString::fromRandom(16)->toString());
        $fixture->setContent(ByteString::fromRandom(16)->toString());
        $fixture->setOrderNumber(rand(1, 10));
        $fixture->setCourse($course);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s", $this->path, $fixture->getId())
        );

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains("Lesson");

        // Use assertions to check that the properties are properly displayed.
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
        $course = $this->manager->getRepository(Course::class)->findAll()[0];
        $courseId = $course->getId();
        $lessonsCount = $this->repository->count([]);

        $fixture = new Lesson();
        $fixture->setTitle(ByteString::fromRandom(16)->toString());
        $fixture->setContent(ByteString::fromRandom(16)->toString());
        $fixture->setOrderNumber(rand(1, 10));
        $fixture->setCourse($course);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s", $this->path, $fixture->getId())
        );
        $this->client->submitForm("Удалить");

        self::assertResponseRedirects("/courses/$courseId");
        self::assertSame($lessonsCount, $this->repository->count([]));
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
