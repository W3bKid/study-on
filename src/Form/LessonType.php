<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Form\DataTransformer\CourseTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class LessonType extends AbstractType
{
    private $entityManager;
    private $courseTransformer;

    public function __construct(
        EntityManagerInterface $entityManager,
        CourseTransformer $courseTransformer
    ) {
        $this->entityManager = $entityManager;
        $this->courseTransformer = $courseTransformer;
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add("title", TextType::class, [
                "label" => "Название урока",
                "required" => true,
                "constraints" => [
                    new Length(
                        max: 255,
                        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
                    ),
                    new NotBlank(
                        message: "Поле обязательно должно быть заполнено"
                    ),
                ],
            ])
            ->add("content", TextareaType::class, [
                "label" => "Наполение урока",
                "required" => true,
                "constraints" => [
                    new Length(
                        max: 255,
                        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
                    ),
                    new NotBlank(
                        message: "Поле обязательно должно быть заполнено"
                    ),
                ],
            ])
            ->add("order_number", IntegerType::class, [
                "label" => "Порядковый номер",
                "constraints" => [
                    new NotBlank(
                        message: "Поле обязательно должно быть заполнено"
                    ),
                    new Type(['type' => 'integer', 'message' => 'This value should be of type integer.']),
                    new Range(
                        notInRangeMessage: "Порядковый номер не должен быть меньше 0 и больше 10 000",
                        min: 0,
                        max: 10000
                    )
                ],
            ])
            ->add("course", HiddenType::class, [
                "data" => $options["course_id"],
                "mapped" => false,
            ]);

        $builder->get("course")->addModelTransformer($this->courseTransformer);
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
