<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Enum\CourseType as CourseEnum;

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
            ->add("title", TextType::class, [
                "label" => "Название курса",
                "required" => true,
                "constraints" => [
                    new Length(
                        max: 255,
                        maxMessage: "Название курса не должно быть не длиннее {{ limit }} символов"
                    ),
                    new NotBlank(
                        message: "Поле обязательно должно быть заполнено"
                    ),
                ],
            ])
            ->add("description", TextareaType::class, [
                "label" => "Описание",
                "required" => false,
                "constraints" => [
                    new Length(
                        max: 1000,
                        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
                    ),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'label' => 'Тип',
                'choices'  => [
                    'Бесплатный' => CourseEnum::FREE->value,
                    'В аренду' => CourseEnum::RENTAL->value,
                    'Полный' => CourseEnum::FULL_PAYMENT->value,
                ]
            ])
            ->add("price", MoneyType::class, [
                'label' => "Цена",
                'required' => false,
                'currency' => 'RUB',
            ]);

        $builder->get('price')
            ->addModelTransformer(new CallbackTransformer(
                function ($priceAsString): float {
                    return (float)$priceAsString;
                },
                function ($price): string {
                    return (string)$price;
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Course::class,
            "constraints" => [
                new UniqueEntity([
                    "entityClass" => Course::class,
                    "fields" => ["character_code"],
                ]),
            ],
            'price' => 0.0,
            'type' => CourseEnum::FREE->value
        ]);

        $resolver->addAllowedTypes('price', ['int', 'float']);
        $resolver->addAllowedTypes('type', 'int');
    }
}
