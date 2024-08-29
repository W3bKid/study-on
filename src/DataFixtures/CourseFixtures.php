<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\MakerBundle\Str;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $course = new Course();
        $course->setTitle('Основы программирования');
        $course->setDescription("Этот курс предназначен для начинающих, которые хотят освоить основы программирования. Мы будем изучать ключевые концепции, такие как переменные, циклы и функции, а также разберемся в процессах отладки и исправления ошибок. В результате вы сможете написать простые программы и развить логическое мышление.");
        $course->setCharacterCode(Str::getRandomTerm());

        $manager->persist($course);

        $manager->flush();
    }
}
