<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/lessons")]
class LessonController extends AbstractController
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

//    #[Route("/", name: "app_lesson_index", methods: ["GET"])]
//    public function index(LessonRepository $lessonRepository): Response
//    {
//        return $this->render("lesson/index.html.twig", [
//            "lessons" => $lessonRepository->findAll(),
//        ]);
//    }

    #[Route("/new", name: "app_lesson_new", methods: ["GET", "POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseRepository $repository
    ): Response {
        $courseId = $request->get("course_id");
        $course = $repository->find($courseId);
        $lesson = new Lesson();
        $lesson->setCourse($course);

        $form = $this->createForm(LessonType::class, $lesson, [
            "course_id" => (int) $courseId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_course_show",
                ["id" => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }
        return $this->render("lesson/new.html.twig", [
            "lesson" => $lesson,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_lesson_show", methods: ["GET"])]
    #[IsGranted("ROLE_USER")]
    public function show(Lesson $lesson): Response
    {
        $lesson->setCourse($lesson->getCourse());
        $isPaid = $this->billingClient->courseIsPaid($lesson->getCourse()->getCharacterCode(), $this->getUser());


        if (!$isPaid) {
            throw new AccessDeniedException();
        }
        return $this->render("lesson/show.html.twig", [
            "lesson" => $lesson,
        ]);
    }

    #[Route("/{id}/edit", name: "app_lesson_edit", methods: ["GET", "POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function edit(
        Request $request,
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_lesson_show",
                ["id" => $lesson->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render("lesson/edit.html.twig", [
            "lesson" => $lesson,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_lesson_delete", methods: ["POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function delete(
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($lesson);
        $entityManager->flush();

        return $this->redirectToRoute(
            "app_course_show",
            ["id" => $lesson->getCourse()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }
}
