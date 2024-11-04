<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "lessons")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    #[Assert\NotBlank(
        message: "Поле обязательно должно быть заполнено"
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    #[Assert\NotBlank(
        message: "Поле обязательно должно быть заполнено"
    )]
    private ?string $content = null;

    #[ORM\Column]
    #[Assert\NotBlank()]
    #[ASsert\Type(type: Types::INTEGER)]
    #[Assert\Range(
        notInRangeMessage: "Порядковый номер не должен быть меньше 0 и больше 10 000",
        min: 0,
        max: 10000
    )]

    private ?int $order_number = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getOrderNumber(): ?int
    {
        return $this->order_number;
    }

    public function setOrderNumber(int $order_number): static
    {
        $this->order_number = $order_number;

        return $this;
    }
}
