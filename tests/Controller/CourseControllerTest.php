<?php

namespace App\Test\Controller;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\ByteString;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Faker\Factory;

class CourseControllerTest extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = "/courses/";

    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->manager = static::getContainer()->get("doctrine")->getManager();
        $this->repository = $this->manager->getRepository(Course::class);
        $this->faker = Factory::create();
//        $this->databaseTool = static::getContainer()
//            ->get(DatabaseToolCollection::class)
//            ->get();
//
//        $this->databaseTool->loadFixtures([CourseFixtures::class]);

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $this->databaseTool->loadFixtures([CourseFixtures::class]);

    }

    public function testIndex(): void
    {
        $crawler = $this->client->request("GET", $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains("Course index");

        $courses = $this->repository->findAll();

        for ($i = 0, $count = count($courses); $count > $i; $i++) {
            self::assertSame(
                $courses[$i]->getTitle(),
                trim($crawler->filter(".card-title")->getNode($i)->nodeValue)
            );
            self::assertSame(
                $courses[$i]->getDescription(),
                trim($crawler->filter(".card-text")->getNode($i)->nodeValue)
            );
        }
    }

    public function testNew(): void
    {
        $crawler = $this->client->request("GET", $this->path);

        $startCount = $this->repository->count([]);

        $this->client->request("GET", sprintf("%snew", $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => ByteString::fromRandom(16)->toString(),
            "course[title]" => ByteString::fromRandom(16)->toString(),
            "course[description]" => ByteString::fromRandom(32)->toString(),
        ]);

        self::assertResponseRedirects($this->path);

        $endCount = $this->repository->count([]);

        self::assertSame($startCount + 1, $endCount);
    }

    public function testShow(): void
    {
        $fixture = new Course();
        $fixture->setCharacterCode(ByteString::fromRandom(16)->toString());
        $fixture->setTitle(ByteString::fromRandom(16)->toString());
        $fixture->setDescription(ByteString::fromRandom(16)->toString());

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s", $this->path, $fixture->getId())
        );

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains("Course");

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $newCode = ByteString::fromRandom(16)->toString();
        $newTitle = ByteString::fromRandom(16)->toString();
        $newDescription = ByteString::fromRandom(255)->toString();

        $fixture = new Course();
        $fixture->setCharacterCode(ByteString::fromRandom(16)->toString());
        $fixture->setTitle(ByteString::fromRandom(16)->toString());
        $fixture->setDescription(ByteString::fromRandom(255)->toString());

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s/edit", $this->path, $fixture->getId())
        );

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => $newCode,
            "course[title]" => $newTitle,
            "course[description]" => $newDescription,
        ]);

        $updatedCourse = $this->repository->find($fixture->getId());

        self::assertSame($newCode, $updatedCourse->getCharacterCode());
        self::assertSame($newTitle, $updatedCourse->getTitle());
        self::assertSame($newDescription, $updatedCourse->getDescription());

        self::assertResponseRedirects("/courses/");
    }

    public function testRemove(): void
    {
        $count = $this->repository->count([]);

        $fixture = new Course();
        $fixture->setCharacterCode(ByteString::fromRandom(16)->toString());
        $fixture->setTitle(ByteString::fromRandom(16)->toString());
        $fixture->setDescription(ByteString::fromRandom(255)->toString());

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request(
            "GET",
            sprintf("%s%s", $this->path, $fixture->getId())
        );
        $this->client->submitForm("Удалить");

        self::assertResponseRedirects("/courses/");
        self::assertSame($count, $this->repository->count([]));
    }

    public function testEmptyTitle(): void
    {
        $this->client->request("GET", sprintf("%s%s", $this->path, "new"));

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => "noviy_course",
            "course[title]" => "",
            "course[description]" => "Самый новый курс",
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testToLongTitle(): void
    {
        $this->client->request("GET", sprintf("%s%s", $this->path, "new"));

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => "noviy_course",
            "course[title]" => ByteString::fromRandom(300)->toString(),
            "course[description]" => "Самый новый курс",
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testEmptyCode(): void
    {
        $this->client->request("GET", sprintf("%s%s", $this->path, "new"));

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => "",
            "course[title]" => ByteString::fromRandom(16)->toString(),
            "course[description]" => "Самый новый курс",
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testToLongCode(): void
    {
        $this->client->request("GET", sprintf("%s%s", $this->path, "new"));

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => ByteString::fromRandom(300)->toString(),
            "course[title]" => ByteString::fromRandom(16)->toString(),
            "course[description]" => "Самый новый курс",
        ]);

        self::assertResponseIsUnprocessable();
        self::assertLessThan(300, 255);
    }

    public function testSameCodeExists(): void
    {
        $course = $this->repository->findAll()[0];

        $this->client->request("GET", sprintf("%s%s", $this->path, "new"));

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => $course->getCharacterCode(),
            "course[title]" => ByteString::fromRandom(16)->toString(),
            "course[description]" => "Самый новый курс",
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testToLongDescription(): void
    {
        $this->client->request("GET", sprintf("%s%s", $this->path, "new"));

        $this->client->submitForm("Сохранить", [
            "course[character_code]" => ByteString::fromRandom(
                1000
            )->toString(),
            "course[title]" => ByteString::fromRandom(16)->toString(),
            "course[description]" => "Самый новый курс",
        ]);

        self::assertResponseIsUnprocessable();
    }
}
