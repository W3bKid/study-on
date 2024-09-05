<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add("title")
            ->add("content", TextareaType::class)
            ->add("order_number")
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

// !== ModelTransformer?
// ->add('course_id', EntityType::class, [
//     'class' => Course::class,
//     'choice_label' => 'id',
// ])
