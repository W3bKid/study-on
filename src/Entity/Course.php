<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    #[Assert\NotBlank(
        message: "Поле обязательно должно быть заполнено"
    )]
    private string $character_code;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    #[Assert\NotBlank(
        message: "Поле обязательно должно быть заполнено"
    )]
    private string $title;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    private ?string $description = null;

    /**
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: "course")]
    #[ORM\JoinColumn(referencedColumnName: "id", onDelete: "CASCADE")]
    #[ORM\OrderBy(["order_number" => "ASC"])]
    private Collection $lessons;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacterCode(): string
    {
        return $this->character_code;
    }

    public function setCharacterCode(string $character_code): static
    {
        $this->character_code = $character_code;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setCourse($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getCourse() === $this) {
                $lesson->setCourse(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }
}
