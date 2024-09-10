<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Unique;

class CourseType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add("character_code", TextType::class, [
                "label" => "Символьный код",
                "required" => true,
                "constraints" => [new Length(max: 255)],
            ])
            ->add("title", TextType::class, [
                "label" => "Название курса",
                "required" => true,
                "constraints" => [new Length(max: 255)],
            ])
            ->add("description", TextareaType::class, [
                "label" => "Описание",
                "required" => false,
                "constraints" => [new Length(max: 1000)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Course::class,
            "constraints" => [
                new UniqueEntity([
                    "entityClass" => Course::class,
                    "fields" => "title",
                ]),
            ],
        ]);
    }
}
