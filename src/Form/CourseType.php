<?php

namespace App\Form;

use App\Entity\Course;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class CourseType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add(
                "character_code",
                StringType::class,
                options: [
                    "label" => "Название курса",
                    "required" => true,
                    "constaints" => [new Length(max: 255)],
                ]
            )
            ->add("title", TextType::class)
            ->add("description");
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Course::class,
        ]);
    }
}
