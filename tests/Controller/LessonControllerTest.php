<?php

namespace App\Test\Controller;

use App\Entity\Lesson;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

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

        // foreach ($this->repository->findAll() as $object) {
        //     $this->manager->remove($object);
        // }

        // $input = new ArrayInput([
        //     "command" => "d:f:l --env=test",
        // ]);

        // $kernel = new KernelInterface();
        // $application = new Application($kernel);
        // $application->run($input);

        // $this->manager->flush();
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
        $this->markTestIncomplete();
        $this->client->request("GET", sprintf("%snew", $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm("Save", [
            "lesson[title]" => "Testing",
            "lesson[content]" => "Testing",
            "lesson[order_number]" => "Testing",
            "lesson[course_id]" => "Testing",
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Lesson();
        $fixture->setTitle("My Title");
        $fixture->setContent("My Title");
        $fixture->setOrder_number("My Title");
        $fixture->setCourse_id("My Title");

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
        $this->markTestIncomplete();
        $fixture = new Lesson();
        $fixture->setTitle("Value");
        $fixture->setContent("Value");
        $fixture->setOrder_number("Value");
        $fixture->setCourse_id("Value");

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s/edit", $this->path, $fixture->getId())
        );

        $this->client->submitForm("Update", [
            "lesson[title]" => "Something New",
            "lesson[content]" => "Something New",
            "lesson[order_number]" => "Something New",
            "lesson[course_id]" => "Something New",
        ]);

        self::assertResponseRedirects("/lesson/");

        $fixture = $this->repository->findAll();

        self::assertSame("Something New", $fixture[0]->getTitle());
        self::assertSame("Something New", $fixture[0]->getContent());
        self::assertSame("Something New", $fixture[0]->getOrder_number());
        self::assertSame("Something New", $fixture[0]->getCourse_id());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Lesson();
        $fixture->setTitle("Value");
        $fixture->setContent("Value");
        $fixture->setOrder_number("Value");
        $fixture->setCourse_id("Value");

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s", $this->path, $fixture->getId())
        );
        $this->client->submitForm("Delete");

        self::assertResponseRedirects("/lesson/");
        self::assertSame(0, $this->repository->count([]));
    }
}
