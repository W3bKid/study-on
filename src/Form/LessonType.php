<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Repository\CourseRepository;
use COM;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;

class LessonType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add("title", TextType::class, [
                "label" => "Название урока",
                "required" => true,
            ])
            ->add("content", TextareaType::class, [
                "label" => "Наполение урока",
                "required" => true,
            ])
            ->add("order_number", IntegerType::class, [
                "label" => "Порядковый номер",
                "constraints" => [new LessThan(255)],
            ])
            ->add("course", HiddenType::class, [
                "data" => $options["course_id"],
                "mapped" => false,
            ]);

        $builder->get("course")->addModelTransformer(
            new CallbackTransformer(
                function ($courseInt) {
                    $course = $this->entityManager
                        ->getRepository(Course::class)
                        ->find($courseInt);
                    return $course;
                },
                function ($courseObj) {
                    $course = new $courseObj();
                    return $course->getId();
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Lesson::class,
            "course_id" => 0,
        ]);
        $resolver->setAllowedTypes("course_id", "int");
    }
}
