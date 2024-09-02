<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    private $courses = [
        'Основы программирования' => [
            'description' => 'Этот курс предназначен для начинающих, которые хотят освоить основы программирования.',
            'character_code' => 'osnovy_programmirovaniya',
            'lessons' => [
                [
                    'title' => 'Урок 1: Введение в программирование: что это и зачем?',
                    'content' => 'Картельные сговоры не допускают ситуации, при которой сторонники тоталитаризма в науке будут смешаны с не уникальными данными до степени совершенной неузнаваемости, из-за чего возрастает их статус бесполезности.',
                    'order_number' => 1
                ],
                [
                    'title' => 'Урок 2: Переменные и типы данных: основы',
                    'content' => "С другой стороны, экономическая повестка сегодняшнего дня представляет собой интересный эксперимент проверки стандартных подходов.",
                    'order_number' => 2
                ],
                [
                    'title' => 'Урок 3: Условия и циклы: управление потоком',
                    'content' => 'Высокий уровень вовлечения представителей целевой аудитории является четким доказательством простого факта: постоянное информационно-пропагандистское обеспечение нашей деятельности требует анализа распределения внутренних резервов и ресурсов.',
                    'order_number' => 3
                ],
                [
                    'title' => 'Урок 4: Функции: создание и использование',
                    'content' => 'Разнообразный и богатый опыт говорит нам, что базовый вектор развития обеспечивает широкому кругу (специалистов) участие в формировании системы обучения кадров, соответствующей насущным потребностям.',
                    'order_number' => 4
                ],
            ],
        ],
        'Основы личной финансовой грамотности' => [
            'description' => 'Вы научитесь составлять бюджет, планировать сбережения и обязательно разберетесь в тонкостях кредитования.',
            'character_code' => 'osnovy_lichnoj_finansovoj_gramotnosti',
            'lessons' => [
                [
                    'title' => 'Урок 1: Бюджетирование: как составить личный бюджет',
                    'content' => 'Равным образом, понимание сути ресурсосберегающих технологий предоставляет широкие возможности для своевременного выполнения сверхзадачи.',
                    'order_number' => 2
                ],
                [
                    'title' => 'Урок 2: Сбережения и инвестиции: отложи на будущее',
                    'content' => 'Сложно сказать, почему некоторые особенности внутренней политики будут объективно рассмотрены соответствующими инстанциями.',
                    'order_number' => 3
                ],
                [
                    'title' => 'Урок 3: Кредиты и долги: как избежать',
                    'content' => 'Учитывая ключевые сценарии поведения, социально-экономическое развитие в значительной степени обусловливает важность укрепления моральных ценностей.',
                    'order_number' => 1
                ],
                [
                    'title' => 'Урок 4: Основы финансирования: как выбрать правильное решение',
                    'content' => 'Господа, высокотехнологичная концепция общественного уклада однозначно фиксирует необходимость соответствующих условий активизации.',
                    'order_number' => 4
                ]
            ],
        ],
        'Основы фотографии' => [
            'description' => 'Данный курс предлагает вводное обучение основам фотографии. Вы узнаете о композиции, освещении и методах постобработки, которые помогут улучшить качество ваших снимков.',
            'character_code' => 'osnovy_fotografii',
            'lessons' => [
                [
                    'title' => 'Урок 1: Введение в фотографию: теория и практика',
                    'content' => 'Безусловно, высокое качество позиционных исследований влечет за собой процесс внедрения и модернизации укрепления моральных ценностей.',
                    'order_number' => 2
                ],
                [
                    'title' => 'Урок 2: Основы композиции: как сделать кадр привлекательным',
                    'content' => 'С учётом сложившейся международной обстановки, внедрение современных методик говорит о возможностях новых предложений!',
                    'order_number' => 3
                ],
                [
                    'title' => 'Урок 3: Работа с освещением: естественное и искусственное',
                    'content' => 'Но некоторые особенности внутренней политики, которые представляют собой яркий пример континентально-европейского типа политической культуры, будут представлены в исключительно положительном свете.',
                    'order_number' => 1
                ],
                [
                    'title' => 'Урок 4: Постобработка фотографий: использование программ',
                    'content' => 'С другой стороны, граница обучения кадров требует определения и уточнения системы обучения кадров, соответствующей насущным потребностям.',
                    'order_number' => 5
                ],
                [
                    'title' => ' Урок 5: Создание портфолио: как продемонстрировать свои работы',
                    'content' => 'Но действия представителей оппозиции призывают нас к новым свершениям, которые, в свою очередь, должны быть в равной степени предоставлены сами себе.',
                    'order_number' => 4
                ]
            ],
        ]
    ];



    public function load(ObjectManager $manager): void
    {
        foreach($this->courses as $title => $courseData) {
            $course = new Course();
            $course->setTitle($title);
            $course->setDescription($courseData['description']);
            $course->setCharacterCode($courseData['character_code']);
            $manager->persist($course);

            foreach($courseData['lessons'] as $lessonData) {
                $lesson = new Lesson();
                $lesson->setCourse($course);
                $lesson->setTitle($lessonData['title']);
                $lesson->setContent($lessonData['content']);
                $lesson->setOrderNumber($lessonData['order_number']);
                $manager->persist($lesson);
            }


            $manager->flush();
        }

    }
}
