<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

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
                "requred" => true,
            ])
            ->add("order_number", IntegerType::class, [
                "label" => "Порядковый номер",
                "max" => 10000,
            ])
            ->add("course_id", HiddenType::class);

        $builder->get("course_id")->addModelTransformer(
            new CallbackTransformer(
                function ($courseObj) {
                    return $courseObj->getId();
                },
                function ($courseId) {
                    return $this->entityManager
                        ->getRepository(Course::class)
                        ->find($courseId);
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Lesson::class,
        ]);
    }
}
