<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/courses")]
class CourseController extends AbstractController
{
    #[Route("/", name: "app_course_index", methods: ["GET"])]
    public function index(CourseRepository $courseRepository): Response
    {
        return $this->render("course/index.html.twig", [
            "courses" => $courseRepository->findAll(),
        ]);
    }

    #[Route("/new", name: "app_course_new", methods: ["GET", "POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_course_index",
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render("course/new.html.twig", [
            "course" => $course,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_course_show", methods: ["GET"])]
    public function show(Course $course): Response
    {
        $lessons = $course->getLessons()->getValues();

        return $this->render("course/show.html.twig", [
            "course" => $course,
            "lessons" => $lessons,
        ]);
    }

    #[Route("/{id}/edit", name: "app_course_edit", methods: ["GET", "POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function edit(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_course_index",
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render("course/edit.html.twig", [
            "course" => $course,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_course_delete", methods: ["POST"])]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function delete(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        if (
            $this->isCsrfTokenValid(
                "delete" . $course->getId(),
                $request->getPayload()->getString("_token")
            )
        ) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            "app_course_index",
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
