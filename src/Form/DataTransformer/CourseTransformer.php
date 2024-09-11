<?php
namespace App\Form\DataTransformer;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  Issue|null $course
     */
    public function reverseTransform($course): string
    {
        if (null === $course) {
            return "";
        }

        $course = $this->entityManager
            ->getRepository(Course::class)
            ->find($course);

        return $course->getId();
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $issueNumber
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function transform($courseId): ?Course
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->entityManager
            ->getRepository(Course::class)
            ->find($courseId);

        if (null === $course) {
            throw new TransformationFailedException(
                sprintf('An course with id "%s" does not exist!', $courseId)
            );
        }

        return $course;
    }
}
